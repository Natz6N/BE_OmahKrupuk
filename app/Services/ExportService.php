<?php
namespace App\Services;

use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\CurrentStock;
use Carbon\Carbon;

class ExportService
{
    public function exportSalesReport($startDate, $endDate, $format = 'csv')
    {
        $sales = Sale::with(['items.productVariant.product', 'user'])
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->orderBy('created_at', 'desc')
                    ->get();

        $data = [];
        foreach ($sales as $sale) {
            foreach ($sale->items as $item) {
                $data[] = [
                    'Tanggal' => $sale->created_at->format('Y-m-d H:i:s'),
                    'No Invoice' => $sale->invoice_number,
                    'Kasir' => $sale->user->name,
                    'Produk' => $item->productVariant->product->name,
                    'Varian' => $item->productVariant->variant_name,
                    'Qty' => $item->quantity,
                    'Harga Satuan' => $item->unit_price,
                    'Total Harga' => $item->total_price,
                    'Harga Beli' => $item->purchase_price ?? 0,
                    'Profit' => $item->profit,
                    'Total Transaksi' => $sale->total_amount,
                    'Pembayaran' => $sale->payment_amount,
                    'Kembalian' => $sale->change_amount,
                    'Catatan' => $sale->notes
                ];
            }
        }

        return $this->generateExport($data, 'laporan_penjualan_' . $startDate . '_' . $endDate, $format);
    }

    public function exportStockReport($format = 'csv')
    {
        $stocks = CurrentStock::with('productVariant.product.category')
                             ->get();

        $data = [];
        foreach ($stocks as $stock) {
            $data[] = [
                'Kategori' => $stock->productVariant->product->category->name ?? '',
                'Produk' => $stock->productVariant->product->name,
                'Varian' => $stock->productVariant->variant_name,
                'Barcode' => $stock->productVariant->barcode,
                'Stok Saat Ini' => $stock->quantity,
                'Minimum Stok' => $stock->min_stock,
                'Status' => $stock->is_low_stock ? 'Stok Menipis' : ($stock->is_out_of_stock ? 'Habis' : 'Normal'),
                'Harga Jual' => $stock->productVariant->selling_price,
                'Rata-rata Harga Beli' => $stock->avg_purchase_price,
                'Nilai Stok' => $stock->stock_value,
                'Unit' => $stock->productVariant->unit
            ];
        }

        return $this->generateExport($data, 'laporan_stok_' . date('Y-m-d'), $format);
    }

    public function exportStockMovements($startDate, $endDate, $format = 'csv')
    {
        $movements = StockMovement::with(['productVariant.product', 'supplier', 'user'])
                                 ->whereBetween('created_at', [$startDate, $endDate])
                                 ->orderBy('created_at', 'desc')
                                 ->get();

        $data = [];
        foreach ($movements as $movement) {
            $data[] = [
                'Tanggal' => $movement->created_at->format('Y-m-d H:i:s'),
                'Produk' => $movement->productVariant->product->name,
                'Varian' => $movement->productVariant->variant_name,
                'Barcode' => $movement->productVariant->barcode,
                'Tipe' => ucfirst($movement->type),
                'Qty' => $movement->quantity,
                'Harga Beli' => $movement->purchase_price ?? '',
                'Supplier' => $movement->supplier->name ?? '',
                'Batch' => $movement->batch_number ?? '',
                'Expired Date' => $movement->expired_date ? $movement->expired_date->format('Y-m-d') : '',
                'User' => $movement->user->name,
                'Reference Type' => $movement->reference_type ?? '',
                'Reference ID' => $movement->reference_id ?? '',
                'Catatan' => $movement->notes
            ];
        }

        return $this->generateExport($data, 'pergerakan_stok_' . $startDate . '_' . $endDate, $format);
    }

    private function generateExport($data, $filename, $format)
    {
        switch ($format) {
            case 'csv':
                return $this->generateCSV($data, $filename);
            case 'excel':
                return $this->generateExcel($data, $filename);
            default:
                throw new \Exception('Format export tidak didukung');
        }
    }

    private function generateCSV($data, $filename)
    {
        $output = fopen('php://temp', 'w');

        if (!empty($data)) {
            // Header
            fputcsv($output, array_keys($data[0]));

            // Data
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return [
            'success' => true,
            'data' => $csv,
            'filename' => $filename . '.csv',
            'mime_type' => 'text/csv'
        ];
    }

    private function generateExcel($data, $filename)
    {
        // Implementasi Excel export bisa menggunakan PhpSpreadsheet
        // Untuk sementara return CSV format
        return $this->generateCSV($data, $filename);
    }
}
