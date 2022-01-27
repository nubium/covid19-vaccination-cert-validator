<?php
declare(strict_types=1);

namespace Nubium\DCCValidator;

class VaccinationEntry
{
	private string $target;
	private string $vaccineType;
	private string $vaccineProduct;
	private string $vaccineCompany;
	private int $dosesReceived;
	private int $dosesRequired;
	private string $vaccinationDate;
	private string $locationCountryCode;
	private string $certificateIssuer;
	private string $certificateId;

	public function __construct(
		string $target,
		string $vaccineType,
		string $vaccineProduct,
		string $vaccineCompany,
		int    $dosesReceived,
		int    $dosesRequired,
		string $vaccinationDate,
		string $locationCountryCode,
		string $certificateIssuer,
		string $certificateId
	) {
		$this->vaccinationDate = $vaccinationDate;
		$this->target = $target;
		$this->vaccineType = $vaccineType;
		$this->vaccineProduct = $vaccineProduct;
		$this->vaccineCompany = $vaccineCompany;
		$this->dosesReceived = $dosesReceived;
		$this->dosesRequired = $dosesRequired;
		$this->locationCountryCode = $locationCountryCode;
		$this->certificateIssuer = $certificateIssuer;
		$this->certificateId = $certificateId;
	}

	public function getTarget(): string
	{
		return $this->target;
	}

	public function getVaccineType(): string
	{
		return $this->vaccineType;
	}

	public function getVaccineProduct(): string
	{
		return $this->vaccineProduct;
	}

	public function getVaccineCompany(): string
	{
		return $this->vaccineCompany;
	}

	public function getDosesReceived(): int
	{
		return $this->dosesReceived;
	}

	public function getDosesRequired(): int
	{
		return $this->dosesRequired;
	}

	public function getVaccinationDate(): string
	{
		return $this->vaccinationDate;
	}

	public function getLocationCountryCode(): string
	{
		return $this->locationCountryCode;
	}

	public function getCertificateIssuer(): string
	{
		return $this->certificateIssuer;
	}

	public function getCertificateId(): string
	{
		return $this->certificateId;
	}

	public function isFullyVaccinated(): bool
	{
		return $this->dosesReceived >= $this->dosesRequired;
	}
}
