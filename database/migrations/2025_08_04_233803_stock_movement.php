<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
         Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->enum('type', ['in', 'out', 'adjustment', 'expired', 'damaged']);
            $table->integer('quantity'); // bisa negatif untuk keluar
            $table->decimal('purchase_price', 10, 2)->nullable(); // harga beli (untuk stok masuk)
            $table->string('batch_number')->nullable();
            $table->date('expired_date')->nullable();
            $table->text('notes')->nullable();
            $table->string('reference_type', 50)->nullable(); // 'sale', 'purchase', 'adjustment'
            $table->unsignedBigInteger('reference_id')->nullable(); // ID dari transaksi terkait
            $table->timestamps();

            $table->index(['product_variant_id', 'type']);
            $table->index('expired_date');
            $table->index(['reference_type', 'reference_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("stock_movements");
    }
};
