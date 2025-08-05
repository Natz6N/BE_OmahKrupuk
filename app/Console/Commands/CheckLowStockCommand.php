<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NotificationService;
use App\Events\LowStockAlert;
use App\Models\CurrentStock;

class CheckLowStockCommand extends Command
{
    protected $signature = 'stock:check-low';
    protected $description = 'Check for low stock products and send notifications';

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    public function handle()
    {
        $this->info('Checking for low stock products...');

        $lowStocks = CurrentStock::with('productVariant.product')
                                ->whereRaw('quantity <= min_stock')
                                ->where('quantity', '>', 0)
                                ->get();

        $count = 0;
        foreach ($lowStocks as $stock) {
            event(new LowStockAlert($stock->productVariant, $stock->quantity));
            $count++;

            $this->line("Low stock alert sent for: {$stock->productVariant->full_name} (Stock: {$stock->quantity})");
        }

        $this->info("Total low stock alerts sent: {$count}");
        return Command::SUCCESS;
    }
}
