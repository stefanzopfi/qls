<?php declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class ShipmentService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        #[Autowire(env: 'PAKKETDIENST_QLS_BASE_URL')]
        private string $baseUrl,
        #[Autowire(env: 'PAKKETDIENST_QLS_USERNAME')]
        private string $username,
        #[Autowire(env: 'PAKKETDIENST_QLS_PASSWORD')]
        private string $password,
    ) {
    }

    public function createShipment(array $order, string $companyId, string $brandId): array
    {
        try {
            $url = sprintf('%s/company/%s/shipment/create', $this->baseUrl, $companyId);

            $response = $this->httpClient->request('POST', $url, [
                'auth_basic' => [$this->username, $this->password],
                'json' => $this->buildShipmentPayload($order, $brandId),
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new \RuntimeException(sprintf('Failed to create shipment: %s', $response->getContent(false)));
            }

            return $response->toArray();
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf('Failed to create shipment: %s', $e->getMessage()));
        }
    }

    private function buildShipmentPayload(array $order, string $brandId)
    {
        return [
            'brand_id' => $brandId,
            'reference' => $order['number'],
            'weight' => 1000,
            'product_id' => 2,             // TODO: Make dynamic
            'product_combination_id' => 3, // TODO: Make dynamic
            'cod_amount' => 0,
            'piece_total' => 1,
            'receiver_contact' => [
                'companyname' => $order['delivery_address']['companyname'],
                'name' => $order['delivery_address']['name'],
                'street' => $order['delivery_address']['street'],
                'housenumber' => $order['delivery_address']['housenumber'],
                'postalcode' => $order['delivery_address']['zipcode'],
                'locality' => $order['delivery_address']['city'],
                'country' => $order['delivery_address']['country'],
                'email' => $order['billing_address']['email'],
            ]
        ];
    }
}