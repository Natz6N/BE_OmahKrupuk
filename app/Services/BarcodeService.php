<?php
// app/Services/BarcodeService.php
namespace App\Services;

use Picqer\Barcode\BarcodeGeneratorPNG;
use Picqer\Barcode\BarcodeGeneratorHTML;
use App\Models\ProductVariant;

class BarcodeService
{
    protected $generator;
    protected $htmlGenerator;

    public function __construct()
    {
        $this->generator = new BarcodeGeneratorPNG();
        $this->htmlGenerator = new BarcodeGeneratorHTML();
    }

    public function generateBarcode($code, $type = 'CODE128', $format = 'png')
    {
        try {
            $barcodeType = $this->getBarcodeType($type);

            if ($format === 'html') {
                return $this->htmlGenerator->getBarcode($code, $barcodeType);
            }

            return $this->generator->getBarcode($code, $barcodeType);
        } catch (\Exception $e) {
            throw new \Exception('Gagal generate barcode: ' . $e->getMessage());
        }
    }

    public function generateBarcodeForVariant($variantId, $format = 'png')
    {
        $variant = ProductVariant::with('product')->findOrFail($variantId);

        $barcode = $this->generateBarcode(
            $variant->barcode,
            $variant->barcode_type,
            $format
        );

        return [
            'success' => true,
            'data' => [
                'barcode' => $format === 'png' ? base64_encode($barcode) : $barcode,
                'code' => $variant->barcode,
                'type' => $variant->barcode_type,
                'product_name' => $variant->product->name,
                'variant_name' => $variant->variant_name,
                'price' => $variant->selling_price
            ]
        ];
    }

    public function generateBulkBarcodes(array $variantIds, $format = 'png')
    {
        $results = [];

        foreach ($variantIds as $variantId) {
            try {
                $results[] = $this->generateBarcodeForVariant($variantId, $format);
            } catch (\Exception $e) {
                $results[] = [
                    'success' => false,
                    'variant_id' => $variantId,
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'success' => true,
            'data' => $results
        ];
    }

    public function validateBarcodeFormat($code, $type = 'CODE128')
    {
        try {
            $this->generateBarcode($code, $type);
            return [
                'success' => true,
                'message' => 'Format barcode valid'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Format barcode tidak valid: ' . $e->getMessage()
            ];
        }
    }

    private function getBarcodeType($type)
    {
        $types = [
            'CODE128' => BarcodeGeneratorPNG::TYPE_CODE_128,
            'EAN' => BarcodeGeneratorPNG::TYPE_EAN_13,
            'EAN13' => BarcodeGeneratorPNG::TYPE_EAN_13,
            'CODE39' => BarcodeGeneratorPNG::TYPE_CODE_39,
        ];

        return $types[$type] ?? BarcodeGeneratorPNG::TYPE_CODE_128;
    }
}
