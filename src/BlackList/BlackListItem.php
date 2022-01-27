<?php
declare(strict_types=1);

namespace Nubium\DCCValidator\BlackList;

class BlackListItem
{
	private string $certId;
	private int $changeId;

	public function __construct(string $certId, int $changeId)
	{
		$this->certId = $certId;
		$this->changeId = $changeId;
	}

	public function getCertId(): string
	{
		return $this->certId;
	}

	public function getChangeId(): int
	{
		return $this->changeId;
	}
}
