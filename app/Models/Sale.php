<?php
// app/Models/Sale.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'invoice_number',
        'total_amount',
        'total_items',
        'payment_method',
        'payment_amount',
        'change_amount',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'total_items' => 'integer',
        'payment_amount' => 'decimal:2',
        'change_amount' => 'decimal:2',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class, 'reference_id')
                    ->where('reference_type', 'sale');
    }

    // Scopes
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', now()->toDateString());
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                     ->whereYear('created_at', now()->year);
    }

    // Accessors
    public function getTotalProfitAttribute()
    {
        return $this->items->sum(function ($item) {
            return $item->profit;
        });
    }

    public function getProfitMarginAttribute()
    {
        $totalCost = $this->items->sum(function ($item) {
            return $item->quantity * ($item->purchase_price ?? 0);
        });

        if ($totalCost == 0) return 0;

        return (($this->total_amount - $totalCost) / $this->total_amount) * 100;
    }

    // Static Methods
    public static function generateInvoiceNumber()
    {
        $date = now()->format('Ymd');
        $lastInvoice = static::whereDate('created_at', now())
                           ->orderBy('id', 'desc')
                           ->first();

        $sequence = $lastInvoice ?
                   intval(substr($lastInvoice->invoice_number, -3)) + 1 : 1;

        return 'INV-' . $date . '-' . str_pad($sequence, 3, '0', STR_PAD_LEFT);
    }
}
