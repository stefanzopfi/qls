<?php declare(strict_types=1);

namespace App\Controller;

use App\Service\PackingSlipService;
use App\Service\ShipmentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PakbonController extends AbstractController
{
    public function __construct(
        private readonly ShipmentService $shipmentService,
        private readonly PackingSlipService $packingSlipService,
    ) {
    }

    #[Route('/pakbon', name: 'pakbon')]
    public function pdf(): Response
    {
        $order = $this->getMockedOrder();
        $companyId = '9e606e6b-44a4-4a4e-a309-cc70ddd3a103';
        $brandId = 'e41c8d26-bdfd-4999-9086-e5939d67ae28';

        $shipment = $this->shipmentService->createShipment($order, $companyId, $brandId);

        // There are 4 different offsets for the shipping label
        // 'offset_1' is aligned to the top right of the page and will be used as a template for the packing slip

        if (empty($shipment['data']['labels']['a4']['offset_1'])) {
            throw $this->createNotFoundException('Shipment label not found.');
        }

        $packingSlip = $this->packingSlipService->generatePackingSlip($order, $shipment['data']['labels']['a4']['offset_1']);

        return new Response(
            $packingSlip,
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
            ]
        );
    }

    private function getMockedOrder(): array
    {
        return [
            'number' => '#958201',
            'billing_address' => [
                'companyname' => null,
                'name' => 'John Doe',
                'street' => 'Daltonstraat',
                'housenumber' => '65',
                'address_line_2' => '',
                'zipcode' => '3316GD',
                'city' => 'Dordrecht',
                'country' => 'NL',
                'email' => 'email@example.com',
                'phone' => '0101234567',
            ],
            'delivery_address' => [
                'companyname' => '',
                'name' => 'John Doe',
                'street' => 'Daltonstraat',
                'housenumber' => '65',
                'address_line_2' => '',
                'zipcode' => '3316GD',
                'city' => 'Dordrecht',
                'country' => 'NL',
            ],
            'order_lines' => [
                [
                    'amount_ordered' => 2,
                    'name' => 'Jeans - Black - 36',
                    'sku' => 69205,
                    'ean' => '8710552295268',
                ],
                [
                    'amount_ordered' => 1,
                    'name' => 'Sjaal - Rood Oranje',
                    'sku' => 25920,
                    'ean' => '3059943009097',
                ]
            ]
        ];
    }
}
