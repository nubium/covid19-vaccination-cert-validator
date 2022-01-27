<?php
declare(strict_types=1);

namespace Nubium\DCCValidator;

use InvalidArgumentException;

class Subject
{
	private string $firstName;
	private string $lastName;
	private string $dateOfBirth;

	/**
	 * @throw InvalidArgumentException
	 */
	public function __construct(string $firstName, string $lastName, string $dateOfBirth)
	{
		$this->dateOfBirth = $dateOfBirth;

		if (!preg_match('/^((19|20)\\d\\d(-\\d\\d){0,2})?$/', $this->dateOfBirth)) {
			throw new InvalidArgumentException('Invalid date of birth: ' . $this->dateOfBirth);
		}
		$this->firstName = $firstName;
		$this->lastName = $lastName;

	}

	public function getFirstName(): string
	{
		return $this->firstName;
	}

	public function getLastName(): string
	{
		return $this->lastName;
	}

	public function getDateOfBirth(): string
	{
		return $this->dateOfBirth;
	}
}
