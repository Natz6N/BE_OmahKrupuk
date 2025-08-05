<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\StockMovement;
use App\Events\ProductExpiringAlert;
use Carbon\Carbon;

class CheckExpiringProductsCommand extends Command
{
    protected $signature = 'stock:check-expiring {--days=30}';
    protected $description = 'Check for products that will expire soon';

    public function handle()
    {
        $days = $this->option('days');
        $this->info("Checking for products expiring in {$days} days...");

        $expiringProducts = StockMovement::with('productVariant.product')
                                       ->where('type', 'in')
                                       ->where('expired_date', '<=', Carbon::now()->addDays($days))
                                       ->where('expired_date', '>', Carbon::now())
                                       ->orderBy('expired_date', 'asc')
                                       ->get();

        $count = 0;
        foreach ($expiringProducts as $movement) {
            $daysUntilExpiry = Carbon::now()->diffInDays($movement->expired_date);

            // Event untuk produk yang akan expired
            // event(new ProductExpiringAlert($movement));

            $this->line("Expiring product: {$movement->productVariant->full_name} - Expires in {$daysUntilExpiry} days ({$movement->expired_date->format('Y-m-d')})");
            $count++;
        }

        $this->info("Total expiring products found: {$count}");
        return Command::SUCCESS;
    }
}
