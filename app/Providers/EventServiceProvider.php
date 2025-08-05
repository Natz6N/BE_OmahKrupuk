<?php
namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

// Import Models
use App\Models\StockMovement;
use App\Models\Sale;
use App\Models\User;

// Import Observers
use App\Observers\StockMovementObserver;
use App\Observers\SaleObserver;
// use App\Observers\UserObserver; // Uncomment when created

// Import Events
use App\Events\StockUpdated;
use App\Events\LowStockAlert;
use App\Events\SaleCompleted;
use App\Events\ProductExpiringAlert;
use App\Events\UserActivity;
use App\Events\DailySalesReport;
use App\Events\SystemAlert;

// Import Listeners
use App\Listeners\StockUpdatedListener;
use App\Listeners\LowStockAlertListener;
use App\Listeners\SaleCompletedListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        // Stock Events
        StockUpdated::class => [
            StockUpdatedListener::class,
        ],

        LowStockAlert::class => [
            LowStockAlertListener::class,
        ],

        // Sale Events
        SaleCompleted::class => [
            SaleCompletedListener::class,
        ],

        // Product Expiry Events
        ProductExpiringAlert::class => [
            // Add listeners as needed
            ProductExpiryListener::class,
        ],

        // User Activity Events
        UserActivity::class => [
            // Add listeners as needed
            UserActivityListener::class,
        ],

        // Daily Sales Report Events
        DailySalesReport::class => [
            // Add listeners as needed
            DailySalesReportListener::class,
        ],

        // System Alert Events
        SystemAlert::class => [
            // Add listeners as needed
            SystemAlertListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        // Register observers
        StockMovement::observe(StockMovementObserver::class);
        Sale::observe(SaleObserver::class);

        // Uncomment when UserObserver is created
        // User::observe(UserObserver::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
