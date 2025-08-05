<?php
namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\ReportService;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\ProductVariant;
use App\Models\CurrentStock;
use Carbon\Carbon;

class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $reportService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reportService = app(ReportService::class);
    }

    public function test_dashboard_summary_returns_correct_data()
    {
        // Create some test sales for today
        $todaySales = Sale::factory()->count(3)->create([
            'created_at' => Carbon::today(),
            'total_amount' => 50000
        ]);

        $result = $this->reportService->getDashboardSummary();

        $this->assertTrue($result['success']);
        $this->assertEquals(150000, $result['data']['today_sales']['total_amount']);
        $this->assertEquals(3, $result['data']['today_sales']['total_transactions']);
    }

    public function test_profit_loss_report_calculates_correctly()
    {
        $variant = ProductVariant::factory()->create(['selling_price' => 10000]);

        $sale = Sale::factory()->create([
            'total_amount' => 20000,
            'created_at' => Carbon::today()
        ]);

        SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
            'unit_price' => 10000,
            'total_price' => 20000,
            'purchase_price' => 7000
        ]);

        $result = $this->reportService->getProfitLossReport(
            Carbon::today()->toDateString(),
            Carbon::today()->toDateString()
        );

        $this->assertTrue($result['success']);
        $this->assertEquals(20000, $result['data']['revenue']);
        $this->assertEquals(14000, $result['data']['cost']); // 2 * 7000
        $this->assertEquals(6000, $result['data']['profit']); // 20000 - 14000
        $this->assertEquals(30, $result['data']['profit_margin']); // (6000/20000) * 100
    }
}
