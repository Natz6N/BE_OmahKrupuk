<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete(); // kasir yang melayani
            $table->string('invoice_number', 50)->unique();
            $table->decimal('total_amount', 10, 2);
            $table->integer('total_items');
            $table->enum('payment_method', ['cash'])->default('cash');
            $table->decimal('payment_amount', 10, 2);
            $table->decimal('change_amount', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('invoice_number');
            $table->index('created_at');
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('sales');
    }
};
