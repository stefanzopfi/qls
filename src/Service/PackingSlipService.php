<?php declare(strict_types=1);

namespace App\Service;

use setasign\Fpdi\Fpdi;

class PackingSlipService
{
    /**
     * Generates a packing slip PDF using the shipping label as a background template.
     *
     * @param array $order Order details including addresses and order lines.
     * @param string $shippingLabelFilePath Path to the shipping label PDF file.
     * @return string PDF output as a string.
     * @throws \RuntimeException If the shipping label cannot be read or written.
     */
    public function generatePackingSlip(array $order, string $shippingLabelFilePath): string
    {
        // Read the shipping label file contents
        $fileContent = @file_get_contents($shippingLabelFilePath);
        if ($fileContent === false) {
            throw new \RuntimeException('Shipment label not found.');
        }

        // Create a temporary file for the shipping label
        $tempFilePath = tempnam(sys_get_temp_dir(), 'shipping_label_') . '.pdf';
        if (file_put_contents($tempFilePath, $fileContent) === false) {
            throw new \RuntimeException('Could not write shipping label to temporary file.');
        }

        // Use the shipping label as the background for the packing slip
        try {
            $pdf = new Fpdi();
            $pdf->AddPage();
            $pdf->setSourceFile($tempFilePath);
            $tplId = $pdf->importPage(1);
            $pdf->useTemplate($tplId);

            $offset = 10;
            $yPosition = 5;

            // Add the title "Pakbon"
            $this->addTitleToPdf($pdf, 'Pakbon', $offset, $yPosition);

            // Add order number
            $this->addLabeledTextToPdf($pdf, 'Bestelnummer:', $order['number'], $offset, $yPosition);

            // Add invoice address
            $yPosition += 5;
            $this->addAddressToPdf($pdf, 'Factuuradres:', $order['billing_address'], $offset, $yPosition);

            // Add delivery address
            $yPosition += 5;
            $this->addAddressToPdf($pdf, 'Bezorgadres:', $order['delivery_address'], $offset, $yPosition);

            // Add order lines table below the shipping label
            $this->addOrderLinesToPdf($pdf, $order['order_lines'], $offset, 150);

            return $pdf->Output();
        } finally {
            unlink($tempFilePath);
        }
    }

    private function addTitleToPdf(Fpdi $pdf, string $title, int $x, int &$y)
    {
        $pdf->SetFont('Arial', 'B', 24);
        $lineHeight = 20;
        $pdf->SetXY($x, $y);
        $pdf->Cell(0, $lineHeight, $title, 0, 1, 'L');
        $y += $lineHeight;
    }

    private function addLabeledTextToPdf(Fpdi $pdf, string $label, string $text, int $x, int &$y): void
    {
        $pdf->SetFont('Arial', 'B', 12);
        $lineHeight = 8;
        $pdf->SetXY($x, $y);
        $pdf->Cell(0, $lineHeight, $label, 0, 1, 'L');
        $y += $lineHeight;

        $pdf->SetFont('Arial', '', 10);
        $lineHeight = 6;
        $pdf->SetXY($x, $y);
        $pdf->Cell(0, $lineHeight, $text, 0, 1, 'L');
        $y += $lineHeight;
    }

    private function addAddressToPdf(Fpdi $pdf, string $title, array $address, int $x, int &$y): void
    {
        $this->addLabeledTextToPdf($pdf, $title, $address['name'], $x, $y);

        $pdf->SetFont('Arial', '', 10);
        $lineHeight = 6;
        $pdf->SetXY($x, $y);
        $pdf->Cell(0, $lineHeight, $address['street'] . ' ' . $address['housenumber'], 0, 1, 'L');
        $y += $lineHeight;

        if (!empty($address['address_line_2'])) {
            $pdf->SetXY($x, $y);
            $pdf->Cell(0, $lineHeight, $address['address_line_2'], 0, 1, 'L');
            $y += $lineHeight;
        }

        $pdf->SetXY($x, $y);
        $pdf->Cell(0, $lineHeight, $address['zipcode'] . ' ' . $address['city'], 0, 1, 'L');
        $y += $lineHeight;

        $pdf->SetXY($x, $y);
        $pdf->Cell(0, $lineHeight, $address['country'], 0, 1, 'L');
        $y += $lineHeight;
    }

    private function addOrderLinesToPdf(Fpdi $pdf, array $orderLines, int $x, int $y): void
    {
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetXY($x, $y);
        $pdf->Cell(60, 10, 'Product', 1);
        $pdf->Cell(30, 10, 'Aantal', 1);
        $pdf->Cell(40, 10, 'SKU', 1);
        $pdf->Cell(60, 10, 'EAN', 1, 1);

        $pdf->SetFont('Arial', '', 12);
        foreach ($orderLines as $line) {
            $pdf->Cell(60, 10, $line['name'], 1);
            $pdf->Cell(30, 10, (string) $line['amount_ordered'], 1, 0, 'R');
            $pdf->Cell(40, 10, (string) $line['sku'], 1, 0, 'R');
            $pdf->Cell(60, 10, (string) $line['ean'], 1, 1, 'R');
        }
    }
}