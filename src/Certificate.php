<?php
declare(strict_types=1);

namespace Nubium\DCCValidator;

class Certificate
{
	public const TYPE_NONE = 0b000;
	public const TYPE_VACCINATION = 0b001;
	public const TYPE_TEST = 0b010;
	public const TYPE_RECOVERY = 0b100;

	private string $issuer;
	private ?int $issuedAt;
	private ?int $expiresAt;
	private string $kid;
	private Subject $subject;
	private ?VaccinationEntry $vaccinationEntry;
	private ?TestEntry $testEntry;
	private ?RecoveryEntry $recoveryEntry;

	public function __construct(
		string            $issuer,
		?int              $issuedAt,
		?int              $expiresAt,
		Subject           $subject,
		?VaccinationEntry $vaccinationEntry,
		?TestEntry        $testEntry,
		?RecoveryEntry    $recoveryEntry,
		string            $kid
	) {
		$this->issuer = $issuer;
		$this->issuedAt = $issuedAt;
		$this->expiresAt = $expiresAt;
		$this->subject = $subject;
		$this->vaccinationEntry = $vaccinationEntry;
		$this->testEntry = $testEntry;
		$this->recoveryEntry = $recoveryEntry;
		$this->kid = $kid;
	}

	public function getIssuer(): string
	{
		return $this->issuer;
	}

	public function getIssuedAt(): ?int
	{
		return $this->issuedAt;
	}

	public function getExpiresAt(): ?int
	{
		return $this->expiresAt;
	}

	public function getSubject(): Subject
	{
		return $this->subject;
	}

	public function getVaccinationEntry(): ?VaccinationEntry
	{
		return $this->vaccinationEntry;
	}

	public function getTestEntry(): ?TestEntry
	{
		return $this->testEntry;
	}

	public function getRecoveryEntry(): ?RecoveryEntry
	{
		return $this->recoveryEntry;
	}

	public function isExpired(): bool
	{
		$now = time();
		return $this->expiresAt !== null && $this->expiresAt < $now;
	}

	public function getKid(): string
	{
		return $this->kid;
	}

	public function getType(): int
	{
		if ($this->vaccinationEntry) {
			return self::TYPE_VACCINATION;
		}

		if ($this->testEntry) {
			return self::TYPE_TEST;
		}

		if ($this->recoveryEntry) {
			return self::TYPE_RECOVERY;
		}

		return self::TYPE_NONE;
	}

	public function getCertificateHash(): string
	{
		$name = strtolower($this->getSubject()->getFirstName());
		$lastname = strtolower($this->getSubject()->getLastName());
		$getDateOfBirth = $this->getSubject()->getDateOfBirth();
		$country = strtolower($this->getIssuer());

		$hash = $name . $getDateOfBirth . $lastname . $country;
		return md5($hash);
	}

	public function getBatchStatus(): string
	{
		$batchStatus = '';
		$vaccinationEntry = $this->getVaccinationEntry();
		if ($vaccinationEntry && $vaccinationEntry->getTarget() === Target::COVID19) {
			$batchStatus = $vaccinationEntry->getDosesReceived() . '/' . $vaccinationEntry->getDosesRequired();
		}

		return $batchStatus;
	}
}
