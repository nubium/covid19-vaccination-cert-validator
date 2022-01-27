<?php
declare(strict_types=1);

// read certificate
$hash = 'HC1:.....'; // HC1 code
$trustStore = new \App\TrustStore();
$certificateFactory = new \Nubium\DCCValidator\CertificateFactory($trustStore);

$certificate = $certificateFactory->create($hash);
$vaccinationEntry = $certificate->getVaccinationEntry();


// validation process
$blackListStore = new \App\BlackListStore();
$certificateValidator = new \App\CertificateValidator($blackListStore);

if ($certificateValidator->isValid($certificate) && $vaccinationEntry->isFullyVaccinated()) {
	// Certificate is valid and proves full vaccination
}
