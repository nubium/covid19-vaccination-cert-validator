<?php
declare(strict_types=1);

namespace Nubium\DCCValidator\BlackList;

interface IBlackListStore
{
	/**
	 * @return array<BlackListItem>
	 */
	public function getItemsByCertId(string $certId): array;
}
