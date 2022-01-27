# Digital Covid Certificate Validator
(by Nubium Development SE www.nubium.jobs)

<p>
  <a href="https://github.com/nubium/covid19-vaccination-cert-validator"><img src="https://badgen.net/badge/php/%3E%3D7.4/green"></a>
  <a href="https://github.com/nubium/covid19-vaccination-cert-validator/blob/master/LICENSE"><img src="https://badgen.net/badge/license/GPL-3.0-or-later/blue"></a>
</p>

## Installation

To install latest version of `nubium/covid19-vaccination-cert-validator` use [Composer](https://getcomposer.com).


```
composer require nubium/covid19-vaccination-cert-validator
```

## Example
https://github.com/nubium/covid19-vaccination-cert-validator/tree/master/example
```php
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
```
