<?php
declare(strict_types=1);

namespace Nubium\DCCValidator\Trust;

interface TrustAnchorContract
{
	public function getCertificateType(): string;

	public function getCountry(): string;

	public function getKid(): string;

	public function getCertificate(): string;

	public function getActive(): bool;

	public function getChangeId(): int;
}
