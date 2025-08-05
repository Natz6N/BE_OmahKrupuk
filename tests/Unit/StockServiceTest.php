<?php
namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\StockService;
use App\Models\User;
use App\Models\ProductVariant;
use App\Models\CurrentStock;
use App\Models\Supplier;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $stockService;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stockService = app(StockService::class);
        $this->user = User::factory()->admin()->create();
        $this->actingAs($this->user);
    }

    public function test_stock_in_increases_current_stock()
    {
        $variant = ProductVariant::factory()->create();
        $supplier = Supplier::factory()->create();

        CurrentStock::factory()->create([
            'product_variant_id' => $variant->id,
            'quantity' => 10
        ]);

        $result = $this->stockService->stockIn([
            'product_variant_id' => $variant->id,
            'supplier_id' => $supplier->id,
            'quantity' => 20,
            'purchase_price' => 5000
        ]);

        $this->assertTrue($result['success']);

        $currentStock = CurrentStock::where('product_variant_id', $variant->id)->first();
        $this->assertEquals(30, $currentStock->quantity);
    }

    public function test_stock_adjustment_sets_exact_quantity()
    {
        $variant = ProductVariant::factory()->create();

        CurrentStock::factory()->create([
            'product_variant_id' => $variant->id,
            'quantity' => 25
        ]);

        $result = $this->stockService->adjustStock([
            'product_variant_id' => $variant->id,
            'new_quantity' => 15,
            'notes' => 'Physical count adjustment'
        ]);

        $this->assertTrue($result['success']);

        $currentStock = CurrentStock::where('product_variant_id', $variant->id)->first();
        $this->assertEquals(15, $currentStock->quantity);
    }

    public function test_stock_out_throws_exception_for_insufficient_stock()
    {
        $variant = ProductVariant::factory()->create();

        CurrentStock::factory()->create([
            'product_variant_id' => $variant->id,
            'quantity' => 5
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Stok tidak mencukupi');

        $this->stockService->stockOut($variant->id, 10);
    }
}
