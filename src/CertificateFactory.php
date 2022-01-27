<?php
declare(strict_types=1);

namespace Nubium\DCCValidator;

use CBOR\ByteStringObject;
use CBOR\Decoder;
use CBOR\ListObject;
use CBOR\OtherObject;
use CBOR\StringStream;
use CBOR\Tag;
use CBOR\TextStringObject;
use Composer\Semver\Semver;
use Cose\Algorithm\Signature\ECDSA\ES256;
use Cose\Algorithm\Signature\RSA\PS256;
use Cose\Algorithm\Signature\Signature;
use Cose\Key\Ec2Key;
use Cose\Key\Key;
use Cose\Key\RsaKey;
use Exception;
use InvalidArgumentException;
use Nubium\DCCValidator\Exceptions\InvalidSignatureException;
use Nubium\DCCValidator\Exceptions\MissingHC1HeaderException;
use Nubium\DCCValidator\Trust\ITrustStore;
use Mhauri\Base45;

class CertificateFactory
{
	// Allowed algorithms as per https://github.com/ehn-dcc-development/hcert-spec/blob/main/hcert_spec.md#332-signature-algorithm
	private const ALLOWED_ALGORITHMS = [
		ES256::ID => ES256::class,
		PS256::ID => PS256::class,
	];

	private ITrustStore $trustStore;

	public function __construct(ITrustStore $trustStore)
	{
		$this->trustStore = $trustStore;
	}

	/**
	 * @throws MissingHC1HeaderException
	 * @throws InvalidSignatureException
	 * @throws InvalidArgumentException
	 * @throws Exception
	 */
	public function create(string $base45Data): Certificate
	{
		$decodedData = $this->extractCborContentFromCertificate($base45Data);

		$decoder = $this->createCborDecoder();
		$coseObject = $decoder->decode(new StringStream($decodedData));

		if (!$coseObject instanceof CoseSign1Tag) {
			throw new Exception('No COSE Sign1 Tag found');
		}

		$coseMessages = $coseObject->getValue();
		if (!$coseMessages instanceof ListObject) {
			throw new InvalidArgumentException('Invalid COSE data');
		}

		$coseHeaderByteArray = $coseMessages->get(0);
		if (!$coseHeaderByteArray instanceof ByteStringObject) {
			throw new InvalidArgumentException('Invalid COSE header');
		}

		$coseHeaderByteStream = new StringStream($coseHeaderByteArray->getValue());
		$coseHeader = $decoder->decode($coseHeaderByteStream)->getNormalizedData();
		if (!is_array($coseHeader)) {
			throw new InvalidArgumentException('Invalid COSE header');
		}

		$unprotectedHeader = $coseMessages->get(1)->getNormalizedData();
		if (!is_array($unprotectedHeader)) {
			throw new InvalidArgumentException('Invalid COSE header');
		}

		$decodedHeaders = $coseHeader + $unprotectedHeader;
		if (!array_key_exists(4, $decodedHeaders)) {
			throw new InvalidArgumentException('Invalid COSE header. KID not found');
		}
		$coseKid = base64_encode($decodedHeaders[4]);

		$cosePayloadByteArray = $coseMessages->get(2);
		if (!$cosePayloadByteArray instanceof ByteStringObject) {
			throw new InvalidArgumentException('Invalid COSE payload');
		}

		$cosePayloadByteStream = new StringStream($cosePayloadByteArray->getValue());
		$cosePayload = $decoder->decode($cosePayloadByteStream)->getNormalizedData();
		if (!is_array($cosePayload)) {
			throw new InvalidArgumentException('Invalid COSE payload');
		}

		$this->validateSignature(
			$coseMessages,
			$coseKid,
			$coseHeader,
			$coseHeaderByteArray,
			$cosePayloadByteArray
		);

		return $this->parseCertificate($cosePayload, $coseKid);
	}

	/**
	 * @throws InvalidArgumentException
	 */
	public static function parseCertificate(array  $data, string $kid): Certificate
	{
		// CWT hcert = -260, claim key 1, see https://github.com/ehn-dcc-development/hcert-spec/blob/main/hcert_spec.md
		$certificateData = $data['-260']['1'];

		if (!Semver::satisfies($certificateData['ver'], '^1.0.0')) {
			throw new InvalidArgumentException('Invalid hcert version: ' . $certificateData['ver']);
		}

		$vaccinationEntry = null;
		$testEntry = null;
		$recoveryEntry = null;

		if (array_key_exists('v', $certificateData)) {
			$vaccinationEntry = new VaccinationEntry(
				$certificateData['v'][0]['tg'],
				$certificateData['v'][0]['vp'],
				$certificateData['v'][0]['mp'],
				$certificateData['v'][0]['ma'],
				(int)$certificateData['v'][0]['dn'],
				(int)$certificateData['v'][0]['sd'],
				$certificateData['v'][0]['dt'],
				$certificateData['v'][0]['co'],
				$certificateData['v'][0]['is'],
				$certificateData['v'][0]['ci'],
			);
		} else {
			if (array_key_exists('t', $certificateData)) {
				$testEntry = new TestEntry(
					$certificateData['t'][0]['tg'],
					$certificateData['t'][0]['tt'],
					$certificateData['t'][0]['nm'] ?? null,
					$certificateData['t'][0]['ma'] ?? null,
					$certificateData['t'][0]['sc'],
					$certificateData['t'][0]['tr'],
					$certificateData['t'][0]['tc'] ?? null,
					$certificateData['t'][0]['co'],
					$certificateData['t'][0]['is'],
					$certificateData['t'][0]['ci'],
				);
			} else {
				if (array_key_exists('r', $certificateData)) {
					$recoveryEntry = new RecoveryEntry(
						$certificateData['r'][0]['tg'],
						$certificateData['r'][0]['fr'],
						$certificateData['r'][0]['co'],
						$certificateData['r'][0]['df'],
						$certificateData['r'][0]['du'],
						$certificateData['r'][0]['is'],
						$certificateData['r'][0]['ci'],
					);
				}
			}
		}

		return new Certificate(
			$data['1'],
			array_key_exists('6', $data) ? (int)$data['6'] : null,
			array_key_exists('4', $data) ? (int)$data['4'] : null,
			new Subject(
				$certificateData['nam']['gn'],
				$certificateData['nam']['fn'],

				// This is off-spec, but unfortunately there are certificates that have a time parameter
				// in the dob field and they are also accepted by CovPassCheck etc. (whyyy?)
				// https://github.com/Digitaler-Impfnachweis/certification-apis/blob/master/Implementation.md#information-for-all-types-of-certificates
				explode('T', $certificateData['dob'], 2)[0],
			),
			$vaccinationEntry,
			$testEntry,
			$recoveryEntry,
			$kid
		);
	}

	/**
	 * @throws InvalidSignatureException
	 */
	private function validateSignature(
		ListObject       $coseMessages,
		string           $coseKid,
		array            $coseHeader,
		ByteStringObject $coseHeaderByteArray,
		ByteStringObject $cosePayloadByteArray
	): void {

		$coseSignature = $coseMessages->get(3)->getNormalizedData();
		if (!is_string($coseSignature)) {
			throw new InvalidArgumentException('Invalid COSE signature');
		}

		$trustAnchor = $this->trustStore->getTrustAnchorByKid($coseKid);
		if ($trustAnchor === null) {
			throw new InvalidSignatureException('KID not found');
		}

		$cert = openssl_x509_read($trustAnchor->getCertificate());
		if ($cert === false) {
			throw new InvalidSignatureException('Cert not found');
		}

		$publicKey = openssl_pkey_get_public($cert);
		if ($publicKey === false) {
			throw new InvalidSignatureException('Public key found');
		}

		$publicKeyData = openssl_pkey_get_details($publicKey);
		if ($publicKeyData === false) {
			throw new InvalidSignatureException('Public key data');
		}

		$signatureAlgorithmClass = self::ALLOWED_ALGORITHMS[(int)$coseHeader[1]] ?? null;
		if (!$signatureAlgorithmClass) {
			throw new InvalidArgumentException('Invalid signature algorithm requested: ' . $coseHeader[1]);
		}

		// see https://github.com/ehn-dcc-development/hcert-spec/blob/main/hcert_spec.md#332-signature-algorithm
		$key = $signatureAlgorithmClass === ES256::class
			? Key::createFromData([ // ECDSA (ES256), primary algorithm
				Key::TYPE => Key::TYPE_EC2,
				Key::KID => $trustAnchor->getKid(),
				Ec2Key::DATA_CURVE => Ec2Key::CURVE_P256,
				Ec2Key::DATA_X => $publicKeyData['ec']['x'],
				Ec2Key::DATA_Y => $publicKeyData['ec']['y'],
			])
			: Key::createFromData([ // RSASSA-PSS (PS256), secondary algorithm
				Key::TYPE => Key::TYPE_RSA,
				Key::KID => $trustAnchor->getKid(),
				RsaKey::DATA_E => $publicKeyData['rsa']['e'],
				RsaKey::DATA_N => $publicKeyData['rsa']['n'],
			]);


		$structure = new ListObject();
		$structure->add(new TextStringObject('Signature1'));
		$structure->add($coseHeaderByteArray);
		$structure->add(new ByteStringObject(''));
		$structure->add($cosePayloadByteArray);

		/** @var Signature $signature */
		$signature = new $signatureAlgorithmClass();

		if (!$signature->verify((string)$structure, $key, $coseSignature)) {
			throw new InvalidSignatureException('Certificate signature is invalid');
		}
	}

	private function createCborDecoder(): Decoder
	{
		$otherObjectManager = new OtherObject\OtherObjectManager();

		$tagManager = new Tag\TagManager();
		$tagManager->add(CoseSign1Tag::class);

		return new Decoder($tagManager, $otherObjectManager);
	}

	/**
	 * @throws MissingHC1HeaderException
	 * @throws InvalidArgumentException
	 * @throws Exception
	 */
	private function extractCborContentFromCertificate(string $base45Data): string
	{
		if (mb_substr($base45Data, 0, 4) != 'HC1:') {
			throw new MissingHC1HeaderException();
		}

		$base45 = new Base45();
		$decodedData = $base45->decode(substr($base45Data, 4));

		// Content is deflated, inflate...
		if (ord($decodedData) === 0x78) {
			error_clear_last();
			$decodedData = zlib_decode($decodedData);
			if ($decodedData === false) {
				$error = error_get_last();
				throw new InvalidArgumentException('Invalid ZLib encoded data' . ($error ? ': ' . $error['message'] : ''));
			}
		}

		return $decodedData;
	}
}
