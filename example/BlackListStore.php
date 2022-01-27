<?php
declare(strict_types=1);

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Nubium\DCCValidator\BlackList\BlackListItem;
use Nubium\DCCValidator\BlackList\IBlackListStore;
use Psr\Http\Message\StreamInterface;

class BlackListStore implements IBlackListStore
{
    private string $source = 'https://dgcverify.mzcr.cz/api/v1/verify/NactiRevokovaneCertifikaty';

    public function getItemsByCertId(string $certId): array
    {
        return array_values(
            array_filter(
                $this->fetchBlackListItems(),
                static fn(BlackListItem $item) => $item->getCertId() === $certId,
                ARRAY_FILTER_USE_BOTH
            )
        );
    }

    protected function fetchBlackListItems(): array
    {
        $downloadData = $this->downloadBlackListData();
        $data = json_decode((string)$downloadData, true, 512, JSON_THROW_ON_ERROR);

        $items = [];
        foreach ($data['revokovaneCertifikaty'] as $item) {

            $items[] = new BlackListItem(
                $item['idCertifikatu'],
                $item['changeId']
            );
        }

        return $items;
    }

    private function downloadBlackListData(): StreamInterface
    {
        try {
            $client = new Client();
            $response = $client->get($this->source, ['timeout' => 20]);
        } catch (GuzzleException $e) {
            throw new \Exception('Failed to load covid certificate blacklist');
        }

        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Failed to load covid certificate blacklist');
        }

        return $response->getBody();
    }
}
