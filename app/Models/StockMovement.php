<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_variant_id',
        'supplier_id',
        'user_id',
        'type',
        'quantity',
        'purchase_price',
        'batch_number',
        'expired_date',
        'notes',
        'reference_type',
        'reference_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'purchase_price' => 'decimal:2',
        'expired_date' => 'date',
    ];

    // Relationships
    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'reference_id')
                    ->where('reference_type', 'sale');
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeIn($query)
    {
        return $query->where('type', 'in');
    }

    public function scopeOut($query)
    {
        return $query->where('type', 'out');
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('expired_date', '<=', now()->addDays($days))
                     ->where('expired_date', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('expired_date', '<', now());
    }

    // Accessors
    public function getIsExpiredAttribute()
    {
        return $this->expired_date && $this->expired_date < now();
    }

    public function getIsExpiringSoonAttribute()
    {
        return $this->expired_date && $this->expired_date <= now()->addDays(30) && $this->expired_date > now();
    }
}
