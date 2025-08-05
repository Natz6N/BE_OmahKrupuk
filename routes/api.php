<?php

// routes/api.php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\v1\AuthController;
use App\Http\Controllers\v1\UserController;
use App\Http\Controllers\v1\CategoryController;
use App\Http\Controllers\v1\SupplierController;
use App\Http\Controllers\v1\ProductController;
use App\Http\Controllers\v1\ProductVariantController;
use App\Http\Controllers\v1\StockController;
use App\Http\Controllers\v1\SaleController;
use App\Http\Controllers\v1\ReportController;
use App\Http\Controllers\v1\BarcodeController;
use App\Http\Controllers\v1\NotificationController;
use App\Http\Controllers\v1\DashboardController;
use App\Http\Controllers\v1\ExportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/
// Public routes (no authentication required)
Route::prefix('v1')->group(function () {
    // Authentication routes
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('register', [AuthController::class, 'register']); // Optional, for admin to create accounts
    });

    // Health check
    Route::get('health', function () {
        return response()->json([
            'success' => true,
            'message' => 'API is running',
            'timestamp' => now(),
            'version' => '1.0.0'
        ]);
    });
});

// Protected routes (authentication required)
Route::prefix('v1')->middleware(['jwt.auth'])->group(function () {

    // Authentication management
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
    });

    // Dashboard routes
    Route::prefix('dashboard')->group(function () {
        Route::get('summary', [DashboardController::class, 'summary']);
        Route::get('sales-chart', [DashboardController::class, 'salesChart']);
        Route::get('top-products', [DashboardController::class, 'topProducts']);
        Route::get('recent-activities', [DashboardController::class, 'recentActivities']);
    });

    // User Management (Admin only)
    Route::middleware(['role:admin'])->group(function () {
        Route::apiResource('users', UserController::class);
        Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus']);
        Route::patch('users/{user}/reset-password', [UserController::class, 'resetPassword']);
    });

    // Category Management (Admin only)
    Route::middleware(['role:admin'])->group(function () {
        Route::apiResource('categories', CategoryController::class);
        Route::get('categories/{category}/products', [CategoryController::class, 'products']);
    });

    // Supplier Management (Admin only)
    Route::middleware(['role:admin'])->group(function () {
        Route::apiResource('suppliers', SupplierController::class);
        Route::patch('suppliers/{supplier}/toggle-status', [SupplierController::class, 'toggleStatus']);
        Route::get('suppliers/{supplier}/stock-movements', [SupplierController::class, 'stockMovements']);
    });

    // Product Management
    Route::prefix('products')->group(function () {
        // Read operations (both admin and kasir)
        Route::get('/', [ProductController::class, 'index']);
        Route::get('{product}', [ProductController::class, 'show']);
        Route::get('{product}/variants', [ProductController::class, 'variants']);
        Route::get('search/{query}', [ProductController::class, 'search']);

        // Write operations (Admin only)
        Route::middleware(['role:admin'])->group(function () {
            Route::post('/', [ProductController::class, 'store']);
            Route::put('{product}', [ProductController::class, 'update']);
            Route::delete('{product}', [ProductController::class, 'destroy']);
            Route::patch('{product}/toggle-status', [ProductController::class, 'toggleStatus']);
            Route::post('{product}/duplicate', [ProductController::class, 'duplicate']);
        });
    });

    // Product Variant Management
    Route::prefix('product-variants')->group(function () {
        // Read operations (both roles)
        Route::get('/', [ProductVariantController::class, 'index']);
        Route::get('{variant}', [ProductVariantController::class, 'show']);
        Route::get('{variant}/stock-movements', [ProductVariantController::class, 'stockMovements']);
        Route::get('low-stock', [ProductVariantController::class, 'lowStock']);
        Route::get('out-of-stock', [ProductVariantController::class, 'outOfStock']);

        // Write operations (Admin only)
        Route::middleware(['role:admin'])->group(function () {
            Route::post('/', [ProductVariantController::class, 'store']);
            Route::put('{variant}', [ProductVariantController::class, 'update']);
            Route::delete('{variant}', [ProductVariantController::class, 'destroy']);
            Route::patch('{variant}/toggle-status', [ProductVariantController::class, 'toggleStatus']);
        });
    });

    // Stock Management
    Route::prefix('stock')->group(function () {
        // Read operations (both roles)
        Route::get('current', [StockController::class, 'currentStock']);
        Route::get('movements', [StockController::class, 'movements']);
        Route::get('movements/{movement}', [StockController::class, 'showMovement']);
        Route::get('expiring', [StockController::class, 'expiringProducts']);
        Route::get('expired', [StockController::class, 'expiredProducts']);
        Route::get('alerts', [StockController::class, 'alerts']);

        // Stock operations (Admin only)
        Route::middleware(['role:admin'])->group(function () {
            Route::post('in', [StockController::class, 'stockIn']);
            Route::post('adjustment', [StockController::class, 'adjustment']);
            Route::post('bulk-adjustment', [StockController::class, 'bulkAdjustment']);
            Route::delete('movements/{movement}', [StockController::class, 'deleteMovement']);
        });
    });

    // Sales Management
    Route::prefix('sales')->group(function () {
        // Read operations
        Route::get('/', [SaleController::class, 'index']);
        Route::get('{sale}', [SaleController::class, 'show']);
        Route::get('{sale}/receipt', [SaleController::class, 'receipt']);
        Route::get('invoice/{invoice}', [SaleController::class, 'findByInvoice']);

        // Write operations
        Route::post('/', [SaleController::class, 'store']);
        Route::post('{sale}/cancel', [SaleController::class, 'cancel']);
        Route::put('{sale}', [SaleController::class, 'update']); // Limited update

        // Admin only operations
        Route::middleware(['role:admin'])->group(function () {
            Route::delete('{sale}', [SaleController::class, 'destroy']);
            Route::get('daily-summary/{date?}', [SaleController::class, 'dailySummary']);
        });
    });

    // Barcode Operations
    Route::prefix('barcode')->group(function () {
        Route::get('{code}', [BarcodeController::class, 'findByBarcode']);
        Route::post('generate', [BarcodeController::class, 'generate']);
        Route::post('validate', [BarcodeController::class, 'validate']);

        // Admin only
        Route::middleware(['role:admin'])->group(function () {
            Route::post('bulk-generate', [BarcodeController::class, 'bulkGenerate']);
            Route::get('print/{variant}', [BarcodeController::class, 'printLabel']);
        });
    });

    // Reports (Admin only)
    Route::prefix('reports')->middleware(['role:admin'])->group(function () {
        Route::get('sales', [ReportController::class, 'sales']);
        Route::get('profit-loss', [ReportController::class, 'profitLoss']);
        Route::get('stock-valuation', [ReportController::class, 'stockValuation']);
        Route::get('best-selling', [ReportController::class, 'bestSelling']);
        Route::get('cashier-performance', [ReportController::class, 'cashierPerformance']);
        Route::get('inventory-movements', [ReportController::class, 'inventoryMovements']);
        Route::get('low-stock-history', [ReportController::class, 'lowStockHistory']);
        Route::get('expiry-report', [ReportController::class, 'expiryReport']);
    });

    // Export functionality (Admin only)
    Route::prefix('export')->middleware(['role:admin'])->group(function () {
        Route::post('sales', [ExportController::class, 'sales']);
        Route::post('stock', [ExportController::class, 'stock']);
        Route::post('movements', [ExportController::class, 'movements']);
        Route::post('products', [ExportController::class, 'products']);
        Route::get('template/{type}', [ExportController::class, 'template']);
    });

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('count', [NotificationController::class, 'count']);
        Route::patch('{id}/read', [NotificationController::class, 'markAsRead']);
        Route::patch('mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::delete('{id}', [NotificationController::class, 'destroy']);
    });

    // Utility routes
    Route::prefix('utils')->group(function () {
        Route::get('app-info', function () {
            return response()->json([
                'success' => true,
                'data' => [
                    'app_name' => config('app.name'),
                    'version' => '1.0.0',
                    'environment' => config('app.env'),
                    'timezone' => config('app.timezone'),
                    'current_time' => now(),
                   'user' => auth()->user()?->only(['id', 'name', 'email', 'role']),
                ]
            ]);
        });

        Route::get('permissions', function () {
            $user = auth()->user();
            return response()->json([
                'success' => true,
                'data' => [
                    'role' => $user->role,
                    'permissions' => [
                        'can_manage_users' => $user->is_admin,
                        'can_manage_products' => $user->is_admin,
                        'can_manage_stock' => $user->is_admin,
                        'can_view_reports' => $user->is_admin,
                        'can_process_sales' => true,
                        'can_view_dashboard' => true,
                    ]
                ]
            ]);
        });
    });

    // Quick actions for POS interface
    Route::prefix('pos')->group(function () {
        Route::get('search-product/{query}', [ProductController::class, 'quickSearch']);
        Route::get('recent-sales', [SaleController::class, 'recent']);
        Route::get('popular-products', [ProductController::class, 'popular']);
        Route::post('quick-sale', [SaleController::class, 'quickSale']);
        Route::get('session-info', function () {
            return response()->json([
                'success' => true,
                'data' => [
                    'cashier' => auth()->user()->only(['id', 'name', 'role']),
                    'session_start' => now(),
                    'today_sales_count' => \App\Models\Sale::whereDate('created_at', today())
                        ->where('user_id', auth()->id())
                        ->count(),
                    'shift_total' => \App\Models\Sale::whereDate('created_at', today())
                        ->where('user_id', auth()->id())
                        ->sum('total_amount')
                ]
            ]);
        });
    });
});

// Catch-all route for undefined API endpoints
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'API endpoint not found',
        'available_endpoints' => [
            'auth' => '/api/v1/auth/*',
            'dashboard' => '/api/v1/dashboard/*',
            'products' => '/api/v1/products/*',
            'sales' => '/api/v1/sales/*',
            'stock' => '/api/v1/stock/*',
            'reports' => '/api/v1/reports/*',
            'barcode' => '/api/v1/barcode/*'
        ]
    ], 404);
});
