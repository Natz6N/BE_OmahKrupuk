<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $kasir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->kasir = User::factory()->kasir()->create();
    }

    public function test_admin_can_create_product()
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->admin, 'api')
                        ->postJson('/api/products', [
                            'category_id' => $category->id,
                            'name' => 'Test Product',
                            'description' => 'Test Description',
                            'brand' => 'Test Brand',
                            'has_expiry' => true
                        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Produk berhasil dibuat'
                ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Test Product',
            'category_id' => $category->id
        ]);
    }

    public function test_kasir_cannot_create_product()
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->kasir, 'api')
                        ->postJson('/api/products', [
                            'category_id' => $category->id,
                            'name' => 'Test Product'
                        ]);

        $response->assertStatus(403);
    }

    public function test_can_search_product_by_barcode()
    {
        $variant = ProductVariant::factory()->create([
            'barcode' => '1234567890123'
        ]);

        $response = $this->actingAs($this->kasir, 'api')
                        ->getJson('/api/barcode/1234567890123');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ])
                ->assertJsonPath('data.id', $variant->id);
    }
}
