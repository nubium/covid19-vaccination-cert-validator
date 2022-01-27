<?php
declare(strict_types=1);

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Nubium\DCCValidator\Trust\ITrustStore;
use Nubium\DCCValidator\Trust\TrustAnchor;
use Nubium\DCCValidator\Trust\TrustAnchorContract;
use Psr\Http\Message\StreamInterface;

class TrustStore implements ITrustStore
{
    private string $source = 'https://dgcverify.mzcr.cz/api/v1/verify/NactiPodpisoveCertifikaty';

    public function getTrustAnchorByKid(string $kid): ?TrustAnchorContract
    {
        foreach ($this->fetchTrustAnchors() as $trustAnchor) {
            if ($trustAnchor->getKid() === $kid) {
                return $trustAnchor;
            }
        }

        return null;
    }

    private function fetchTrustAnchors(): array
    {
        $downloadData = $this->downloadTrustData();
        $data = json_decode((string)$downloadData, true, 512, JSON_THROW_ON_ERROR);

        $anchors = [];
        foreach ($data['podpisoveCertifikaty'] as $certificate) {
            $anchors[] = new TrustAnchor(
                $certificate['certificateType'],
                $certificate['country'],
                $certificate['kid'],
                "-----BEGIN CERTIFICATE-----\n" . $certificate['rawData'] . "\n-----END CERTIFICATE-----",
                $certificate['aktivni'],
                $certificate['changeId']
            );
        }

        return $anchors;
    }

    private function downloadTrustData(): StreamInterface
    {
        try {
            $client = new Client();
            $response = $client->get($this->source, ['timeout' => 20]);
        } catch (GuzzleException $e) {
            throw new \Exception('Failed to load covid certificate blacklist');
        }

        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Failed to load covid trust data');
        }

        return $response->getBody();
    }
}
