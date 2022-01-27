<?php
declare(strict_types=1);

namespace Nubium\DCCValidator\Trust;

class TrustAnchor implements TrustAnchorContract
{
	private string $certificateType;
	private string $country;
	private string $kid;
	private string $certificate;
	private bool $active;
	private int $changeId;

	public function __construct(
		string $certificateType,
		string $country,
		string $kid,
		string $certificate,
		bool   $active,
		int    $changeId
	) {
		$this->certificateType = $certificateType;
		$this->country = $country;
		$this->kid = $kid;
		$this->certificate = $certificate;
		$this->active = $active;
		$this->changeId = $changeId;
	}

	public function getCertificateType(): string
	{
		return $this->certificateType;
	}

	public function getCountry(): string
	{
		return $this->country;
	}

	public function getKid(): string
	{
		return $this->kid;
	}

	public function getCertificate(): string
	{
		return $this->certificate;
	}

	public function getActive(): bool
	{
		return $this->active;
	}

	public function getChangeId(): int
	{
		return $this->changeId;
	}
}
