<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('current_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(0);
            $table->integer('min_stock')->default(5); // batas minimum stok
            $table->decimal('avg_purchase_price', 10, 2)->default(0); // rata-rata harga beli
            $table->timestamp('last_updated')->useCurrent()->useCurrentOnUpdate();
            $table->unique('product_variant_id');
            $table->index(['quantity', 'min_stock']); // untuk cari stok menipis
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("current_stocks");
    }
};
