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
          Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('variant_name'); // contoh: "Kemasan 100gr", "Kemasan 250gr"
            $table->string('barcode')->unique();
            $table->enum('barcode_type', ['EAN', 'Code128'])->default('EAN');
            $table->decimal('selling_price', 10, 2);
            $table->string('unit', 50)->default('pcs'); // pcs, kg, liter, dll
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('barcode');
            $table->index(['product_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
