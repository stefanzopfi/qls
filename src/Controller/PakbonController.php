<?php

namespace App\Controller;

use setasign\Fpdi\Fpdi;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PakbonController extends AbstractController
{
    #[Route('/pakbon', name: 'pakbon')]
    public function pdf(): Response
    {
        $order = [
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
                    'ean' =>  '8710552295268',
                ],
                [
                    'amount_ordered' => 1,
                    'name' => 'Sjaal - Rood Oranje',
                    'sku' => 25920,
                    'ean' =>  '3059943009097',
                ]
            ]
        ];

        $companyId = '9e606e6b-44a4-4a4e-a309-cc70ddd3a103';
        $brandId = 'e41c8d26-bdfd-4999-9086-e5939d67ae28';

        $productId = 2;
        $productCombinationId = 3;

        $ch = curl_init("https://api.pakketdienstqls.nl/company/{$companyId}/shipment/create");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'brand_id' => $brandId,
            'reference' => $order['number'],
            'weight' => 1000,
            'product_id' => $productId,
            'product_combination_id' => $productCombinationId,
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
        ]));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERPWD, $_ENV['PAKKETDIENST_SQL_USER'] . ':' . $_ENV['PAKKETDIENST_SQL_PASSWORD']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        $shipment = json_decode($response, true);
        $shippingLabelFilePath = $shipment['data']['labels']['a4']['offset_1'];
        $fileContent = file_get_contents($shippingLabelFilePath);

        if ($fileContent === false) {
            throw $this->createNotFoundException('Could not download the PDF file.');
        }

        $tempFilePath = tempnam(sys_get_temp_dir(), 'shipping_label_') . '.pdf';
        file_put_contents($tempFilePath, $fileContent);

        $pdf = new Fpdi();
        $pdf->AddPage();

        $pdf->setSourceFile($tempFilePath);
        $tplId = $pdf->importPage(1);
        $pdf->useTemplate($tplId);

        $offset = 10;
        $yPosition = 10;

        // Title
        $pdf->SetFont('Arial', 'B', 24); // Set font size and style
        $lineHeight = 20;
        $pdf->SetXY($offset, $yPosition); // Position the title at the top left
        $pdf->Cell(0, $lineHeight, 'Pakbon', 0, 1, 'L'); // 'L' aligns the text to the left
        $yPosition += $lineHeight;

        // Order number
        $pdf->SetFont('Arial', 'B', 12);
        $lineHeight = 8;
        $pdf->SetXY($offset, $yPosition);
        $pdf->Cell(0, $lineHeight, 'Bestelnummer:', 0, 1, 'L');
        $yPosition += $lineHeight;

        $pdf->SetFont('Arial', '', 10);
        $lineHeight = 6;
        $pdf->SetXY($offset, $yPosition);
        $pdf->Cell(0, $lineHeight, $order['number'], 0, 1, 'L');
        $yPosition += $lineHeight;

        // Invoice address
        $yPosition += 5;
        $pdf->SetFont('Arial', 'B', 12);
        $lineHeight = 8;
        $pdf->SetXY($offset, $yPosition);
        $pdf->Cell(0, $lineHeight, 'Factuuradres:', 0, 1, 'L');
        $yPosition += $lineHeight;

        $pdf->SetFont('Arial', '', 10);
        $lineHeight = 6;
        $pdf->SetXY($offset, $yPosition);
        $pdf->Cell(0, $lineHeight, $order['billing_address']['name'], 0, 1, 'L');
        $yPosition += $lineHeight;
        $pdf->SetXY($offset, $yPosition);
        $pdf->Cell(0, $lineHeight, $order['billing_address']['street'] . ' ' . $order['billing_address']['housenumber'], 0, 1, 'L');
        $yPosition += $lineHeight;
        if (!empty($order['billing_address']['address_line_2'])) {
            $pdf->SetXY($offset, $yPosition);
            $pdf->Cell(0, $lineHeight, $order['billing_address']['address_line_2'], 0, 1, 'L');
            $yPosition += $lineHeight;
        }
        $pdf->SetXY($offset, $yPosition);
        $pdf->Cell(0, $lineHeight, $order['billing_address']['zipcode'] . ' ' . $order['billing_address']['city'], 0, 1, 'L');
        $yPosition += $lineHeight;
        $pdf->SetXY($offset, $yPosition);
        $pdf->Cell(0, $lineHeight, $order['billing_address']['country'], 0, 1, 'L');
        $yPosition += $lineHeight;

        // Delivery address
        $yPosition += 5;
        $pdf->SetFont('Arial', 'B', 12);
        $lineHeight = 8;
        $pdf->SetXY($offset, $yPosition);
        $pdf->Cell(0, $lineHeight, 'Bezorgadres:', 0, 1, 'L');
        $yPosition += $lineHeight;

        $pdf->SetFont('Arial', '', 10);
        $lineHeight = 6;
        $pdf->SetXY($offset, $yPosition);
        $pdf->Cell(0, $lineHeight, $order['delivery_address']['name'], 0, 1, 'L');
        $yPosition += $lineHeight;
        $pdf->SetXY($offset, $yPosition);
        $pdf->Cell(0, $lineHeight, $order['delivery_address']['street'] . ' ' . $order['delivery_address']['housenumber'], 0, 1, 'L');
        $yPosition += $lineHeight;
        if (!empty($order['delivery_address']['address_line_2'])) {
            $pdf->SetXY($offset, $yPosition);
            $pdf->Cell(0, $lineHeight, $order['delivery_address']['address_line_2'], 0, 1, 'L');
            $yPosition += $lineHeight;
        }
        $pdf->SetXY($offset, $yPosition);
        $pdf->Cell(0, $lineHeight, $order['delivery_address']['zipcode'] . ' ' . $order['delivery_address']['city'], 0, 1, 'L');
        $yPosition += $lineHeight;
        $pdf->SetXY($offset, $yPosition);
        $pdf->Cell(0, $lineHeight, $order['delivery_address']['country'], 0, 1, 'L');


        // Order lines are placed statically below the shipping label
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetXY($offset, 150);
        $pdf->Cell(60, 10, 'Product', 1);
        $pdf->Cell(30, 10, 'Aantal', 1);
        $pdf->Cell(40, 10, 'SKU', 1);
        $pdf->Cell(60, 10, 'EAN', 1, 1);

        $pdf->SetFont('Arial', '', 12);

        foreach ($order['order_lines'] as $line) {
            $pdf->Cell(60, 10, $line['name'], 1);
            $pdf->Cell(30, 10, $line['amount_ordered'], 1, 0, 'R');
            $pdf->Cell(40, 10, $line['sku'], 1, 0, 'R');
            $pdf->Cell(60, 10, $line['ean'], 1, 1, 'R');
        }


        // Return the PDF as a response
        return new Response(
            $pdf->Output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
            ]
        );
    }
}
