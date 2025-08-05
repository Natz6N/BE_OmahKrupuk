<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\AuthService;
use App\Services\ProductService;
use App\Services\StockService;
use App\Services\SaleService;
use App\Services\ReportService;
use App\Services\BarcodeService;
use App\Services\NotificationService;
use App\Services\ExportService;
use Illuminate\Cache\RateLimiting\Limit; // <-- Tambahkan use statement ini
use Illuminate\Http\Request;              // <-- Tambahkan use statement ini
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {

        // Bind services ke container
        $this->app->bind(AuthService::class, function ($app) {
            return new AuthService();
        });

        $this->app->bind(ProductService::class, function ($app) {
            return new ProductService();
        });

        $this->app->bind(StockService::class, function ($app) {
            return new StockService();
        });

        $this->app->bind(SaleService::class, function ($app) {
            return new SaleService($app->make(StockService::class));
        });

        $this->app->bind(ReportService::class, function ($app) {
            return new ReportService();
        });

        $this->app->bind(BarcodeService::class, function ($app) {
            return new BarcodeService();
        });

        $this->app->bind(NotificationService::class, function ($app) {
            return new NotificationService();
        });

        $this->app->bind(ExportService::class, function ($app) {
            return new ExportService();
        });
    }


    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
          RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
