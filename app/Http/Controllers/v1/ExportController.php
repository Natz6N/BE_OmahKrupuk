<?php
// app/Http/Controllers/API/ExportController.php
namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Services\ExportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ExportController extends Controller
{
    protected $exportService;

    public function __construct(ExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    /**
     * Export sales data
     */
    public function sales(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'format' => 'sometimes|in:csv,excel'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->exportService->exportSalesReport(
                $request->start_date,
                $request->end_date,
                $request->get('format', 'csv')
            );

            if ($result['success']) {
                return response($result['data'])
                    ->header('Content-Type', $result['mime_type'])
                    ->header('Content-Disposition', 'attachment; filename="' . $result['filename'] . '"');
            }

            return response()->json($result, 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export sales: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export stock data
     */
    public function stock(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'format' => 'sometimes|in:csv,excel'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->exportService->exportStockReport(
                $request->get('format', 'csv')
            );

            if ($result['success']) {
                return response($result['data'])
                    ->header('Content-Type', $result['mime_type'])
                    ->header('Content-Disposition', 'attachment; filename="' . $result['filename'] . '"');
            }

            return response()->json($result, 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export stock: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export stock movements
     */
    public function movements(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'format' => 'sometimes|in:csv,excel'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->exportService->exportStockMovements(
                $request->start_date,
                $request->end_date,
                $request->get('format', 'csv')
            );

            if ($result['success']) {
                return response($result['data'])
                    ->header('Content-Type', $result['mime_type'])
                    ->header('Content-Disposition', 'attachment; filename="' . $result['filename'] . '"');
            }

            return response()->json($result, 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export movements: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export products data
     */
    public function products(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'format' => 'sometimes|in:csv,excel'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Generate products export data
            $products = \App\Models\ProductVariant::with(['product.category', 'currentStock'])
                                                 ->get();

            $data = [];
            foreach ($products as $variant) {
                $data[] = [
                    'Kategori' => $variant->product->category->name ?? '',
                    'Produk' => $variant->product->name,
                    'Brand' => $variant->product->brand ?? '',
                    'Varian' => $variant->variant_name,
                    'Barcode' => $variant->barcode,
                    'Harga Jual' => $variant->selling_price,
                    'Unit' => $variant->unit,
                    'Stok Saat Ini' => $variant->current_quantity,
                    'Status' => $variant->is_active ? 'Aktif' : 'Tidak Aktif',
                    'Tanggal Dibuat' => $variant->created_at->format('Y-m-d H:i:s')
                ];
            }

            $format = $request->get('format', 'csv');
            $filename = 'data_produk_' . date('Y-m-d');

            if ($format === 'csv') {
                $output = fopen('php://temp', 'w');

                if (!empty($data)) {
                    fputcsv($output, array_keys($data[0]));
                    foreach ($data as $row) {
                        fputcsv($output, $row);
                    }
                }

                rewind($output);
                $csv = stream_get_contents($output);
                fclose($output);

                return response($csv)
                    ->header('Content-Type', 'text/csv')
                    ->header('Content-Disposition', 'attachment; filename="' . $filename . '.csv"');
            }

            return response()->json([
                'success' => false,
                'message' => 'Format not supported yet'
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export products: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get export template
     */
    public function template(string $type): JsonResponse
    {
        try {
            $templates = [
                'products' => [
                    'filename' => 'template_produk.csv',
                    'headers' => ['Kategori', 'Nama Produk', 'Brand', 'Varian', 'Barcode', 'Harga Jual', 'Unit', 'Min Stock'],
                    'example' => ['Makanan', 'Kerupuk Udang', 'Brand A', 'Kemasan 100gr', '1234567890123', '15000', 'pcs', '10']
                ],
                'stock' => [
                    'filename' => 'template_stok.csv',
                    'headers' => ['Barcode', 'Quantity', 'Batch Number', 'Expired Date'],
                    'example' => ['1234567890123', '50', 'BATCH001', '2024-12-31']
                ]
            ];

            if (!isset($templates[$type])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Template type not found'
                ], 404);
            }

            $template = $templates[$type];
            $output = fopen('php://temp', 'w');

            fputcsv($output, $template['headers']);
            fputcsv($output, $template['example']);

            rewind($output);
            $csv = stream_get_contents($output);
            fclose($output);

            return response($csv)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="' . $template['filename'] . '"');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate template: ' . $e->getMessage()
            ], 500);
        }
    }
}
