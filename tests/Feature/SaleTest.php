<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\ProductVariant;
use App\Models\CurrentStock;

class SaleTest extends TestCase
{
    use RefreshDatabase;

    protected $kasir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kasir = User::factory()->kasir()->create();
    }

    public function test_can_create_sale()
    {
        $variant = ProductVariant::factory()->create(['selling_price' => 10000]);
        CurrentStock::factory()->create([
            'product_variant_id' => $variant->id,
            'quantity' => 50
        ]);

        $response = $this->actingAs($this->kasir, 'api')
                        ->postJson('/api/sales', [
                            'items' => [
                                [
                                    'product_variant_id' => $variant->id,
                                    'quantity' => 2,
                                    'unit_price' => 10000
                                ]
                            ],
                            'payment_method' => 'cash',
                            'payment_amount' => 25000
                        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Transaksi berhasil'
                ]);

        $this->assertDatabaseHas('sales', [
            'user_id' => $this->kasir->id,
            'total_amount' => 20000,
            'total_items' => 2
        ]);

        // Check stock reduction
        $this->assertDatabaseHas('current_stocks', [
            'product_variant_id' => $variant->id,
            'quantity' => 48 // 50 - 2
        ]);
    }

    public function test_cannot_sell_more_than_available_stock()
    {
        $variant = ProductVariant::factory()->create();
        CurrentStock::factory()->create([
            'product_variant_id' => $variant->id,
            'quantity' => 5
        ]);

        $response = $this->actingAs($this->kasir, 'api')
                        ->postJson('/api/sales', [
                            'items' => [
                                [
                                    'product_variant_id' => $variant->id,
                                    'quantity' => 10
                                ]
                            ],
                            'payment_method' => 'cash',
                            'payment_amount' => 50000
                        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => false
                ]);
    }
}
