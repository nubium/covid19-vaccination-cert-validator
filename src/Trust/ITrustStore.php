<?php
declare(strict_types=1);

namespace Nubium\DCCValidator\Trust;

interface ITrustStore
{
	public function getTrustAnchorByKid(string $kid): ?TrustAnchorContract;
}
