<?php
// app/Http/Controllers/API/BarcodeController.php
namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Services\BarcodeService;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class BarcodeController extends Controller
{
    protected $barcodeService;
    protected $productService;

    public function __construct(BarcodeService $barcodeService, ProductService $productService)
    {
        $this->barcodeService = $barcodeService;
        $this->productService = $productService;
    }

    /**
     * Find product by barcode
     */
    public function findByBarcode(string $code): JsonResponse
    {
        $result = $this->productService->findByBarcode($code);
        return response()->json($result, $result['success'] ? 200 : 404);
    }

    /**
     * Generate barcode
     */
    public function generate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_variant_id' => 'required|exists:product_variants,id',
            'format' => 'sometimes|in:png,html'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $format = $request->get('format', 'png');
            $result = $this->barcodeService->generateBarcodeForVariant(
                $request->product_variant_id,
                $format
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate barcode: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate barcode format
     */
    public function validate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
            'type' => 'sometimes|in:CODE128,EAN,EAN13,CODE39'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->barcodeService->validateBarcodeFormat(
            $request->code,
            $request->get('type', 'CODE128')
        );

        return response()->json($result);
    }

    /**
     * Bulk generate barcodes
     */
    public function bulkGenerate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'variant_ids' => 'required|array|min:1',
            'variant_ids.*' => 'exists:product_variants,id',
            'format' => 'sometimes|in:png,html'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $format = $request->get('format', 'png');
            $result = $this->barcodeService->generateBulkBarcodes(
                $request->variant_ids,
                $format
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate bulk barcodes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Print barcode label
     */
    public function printLabel(\App\Models\ProductVariant $variant): JsonResponse
    {
        try {
            $result = $this->barcodeService->generateBarcodeForVariant($variant->id, 'html');

            if (!$result['success']) {
                return response()->json($result, 400);
            }

            $labelData = [
                'barcode_html' => $result['data']['barcode'],
                'product_name' => $result['data']['product_name'],
                'variant_name' => $result['data']['variant_name'],
                'price' => $result['data']['price'],
                'code' => $result['data']['code'],
                'formatted_price' => 'Rp ' . number_format($result['data']['price'], 0, ',', '.')
            ];

            return response()->json([
                'success' => true,
                'data' => $labelData,
                'message' => 'Label data generated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate label: ' . $e->getMessage()
            ], 500);
        }
    }
}
