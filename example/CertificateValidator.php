<?php
declare(strict_types=1);

namespace App;

use Nubium\DCCValidator\BlackList\IBlackListStore;
use Nubium\DCCValidator\Certificate;
use Nubium\DCCValidator\Target;

class CertificateValidator
{
    private IBlackListStore $blackListStore;

    public function __construct(IBlackListStore $blackListStore)
    {
        $this->blackListStore = $blackListStore;
    }

    public function isValid(Certificate $certificate): bool
    {
        $vaccinationEntry = $certificate->getVaccinationEntry();
        $allowedIssuers = ['sk', 'cz'];

        if (
            $vaccinationEntry &&
            $vaccinationEntry->getTarget() === Target::COVID19 &&
            $vaccinationEntry->isFullyVaccinated() &&
            !$certificate->isExpired() &&
            in_array(strtolower($certificate->getIssuer()), $allowedIssuers) &&
            !$this->isOnBlackList($certificate)
        ) {
            return true;
        }

        return false;
    }

    public function isOnBlackList(Certificate $certificate): bool
    {
        $vaccinationEntry = $certificate->getVaccinationEntry();
        if ($vaccinationEntry && !empty($this->blackListStore->getItemsByCertId($vaccinationEntry->getCertificateId()))) {
            return true;
        }

        return false;
    }
}
