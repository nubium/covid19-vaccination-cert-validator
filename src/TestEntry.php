<?php
declare(strict_types=1);

namespace Nubium\DCCValidator;

class TestEntry
{
	public const TEST_RESULT_DETECTED = "260373001";
	public const TEST_RESULT_NOT_DETECTED = "260415000";

	private string $target;
	private string $testType;
	private ?string $testName;
	private ?string $testDeviceIdentifier;
	private string $testResult;
	private ?string $testingFacility;
	private string $locationCountryCode;
	private string $certificateIssuer;
	private string $certificateId;
	private string $testDate;

	public function __construct(
		string  $target,
		string  $testType,
		?string $testName,
		?string $testDeviceIdentifier,
		string  $testDate,
		string  $testResult,
		?string $testingFacility,
		string  $locationCountryCode,
		string  $certificateIssuer,
		string  $certificateId
	) {
		$this->testDate = $testDate;
		$this->target = $target;
		$this->testType = $testType;
		$this->testName = $testName;
		$this->testDeviceIdentifier = $testDeviceIdentifier;
		$this->testResult = $testResult;
		$this->testingFacility = $testingFacility;
		$this->locationCountryCode = $locationCountryCode;
		$this->certificateIssuer = $certificateIssuer;
		$this->certificateId = $certificateId;
	}

	public function getTarget(): string
	{
		return $this->target;
	}

	public function getTestType(): string
	{
		return $this->testType;
	}

	public function getTestName(): ?string
	{
		return $this->testName;
	}

	public function getTestDeviceIdentifier(): ?string
	{
		return $this->testDeviceIdentifier;
	}

	public function getTestDate(): string
	{
		return $this->testDate;
	}

	public function getTestResult(): string
	{
		return $this->testResult;
	}

	public function getTestingFacility(): ?string
	{
		return $this->testingFacility;
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

	public function isPositive(): bool
	{
		return $this->testResult === self::TEST_RESULT_DETECTED;
	}

	public function isNegative(): bool
	{
		return $this->testResult === self::TEST_RESULT_NOT_DETECTED;
	}
}
