<?php
declare(strict_types=1);

namespace Nubium\DCCValidator;

use Exception;

class RecoveryEntry
{
	private string $target;
	private string $locationCountryCode;
	private string $certificateIssuer;
	private string $certificateId;
	private string $testDate;
	private string $certificateValidFrom;
	private string $certificateValidUntil;

	public function __construct(
		string $target,
		string $testDate,
		string $locationCountryCode,
		string $certificateValidFrom,
		string $certificateValidUntil,
		string $certificateIssuer,
		string $certificateId
	) {
		$this->testDate = $testDate;
		$this->certificateValidFrom = $certificateValidFrom;
		$this->certificateValidUntil = $certificateValidUntil;
		$this->target = $target;
		$this->locationCountryCode = $locationCountryCode;
		$this->certificateIssuer = $certificateIssuer;
		$this->certificateId = $certificateId;
	}

	public function getTarget(): string
	{
		return $this->target;
	}

	public function getTestDate(): string
	{
		return $this->testDate;
	}

	public function getLocationCountryCode(): string
	{
		return $this->locationCountryCode;
	}

	public function getCertificateValidFrom(): string
	{
		return $this->certificateValidFrom;
	}

	public function getCertificateValidUntil(): string
	{
		return $this->certificateValidUntil;
	}

	public function getCertificateIssuer(): string
	{
		return $this->certificateIssuer;
	}

	public function getCertificateId(): string
	{
		return $this->certificateId;
	}

	/**
	 * @throws Exception
	 */
	public function isExpired(): bool
	{
		$certificateValidUntil = new \DateTime($this->certificateValidUntil);
		$now = time();
		return $now > $certificateValidUntil->getTimestamp();
	}
}
