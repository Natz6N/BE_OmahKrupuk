<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            // Index untuk laporan pergerakan stok berdasarkan tanggal
            $table->index(['created_at', 'type']);
            // Index untuk mencari stok movement berdasarkan produk dan tanggal
            $table->index(['product_variant_id', 'created_at']);
        });

        Schema::table('sales', function (Blueprint $table) {
            // Index untuk laporan penjualan berdasarkan tanggal dan kasir
            $table->index(['created_at', 'user_id']);
            // Index untuk pencarian berdasarkan total amount (untuk laporan)
            $table->index('total_amount');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            // Index untuk laporan produk terlaris
            $table->index(['product_variant_id', 'quantity']);
            // Index untuk perhitungan profit margin
            $table->index(['created_at', 'purchase_price']);
        });
    }

    public function down()
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex(['created_at', 'type']);
            $table->dropIndex(['product_variant_id', 'created_at']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['created_at', 'user_id']);
            $table->dropIndex(['total_amount']);
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropIndex(['product_variant_id', 'quantity']);
            $table->dropIndex(['created_at', 'purchase_price']);
        });
    }
};
