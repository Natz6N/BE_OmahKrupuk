<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CurrentStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_variant_id',
        'quantity',
        'min_stock',
        'avg_purchase_price',
        'last_updated',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'min_stock' => 'integer',
        'avg_purchase_price' => 'decimal:2',
        'last_updated' => 'datetime',
    ];

    // Relationships
    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    // Scopes
    public function scopeLowStock($query)
    {
        return $query->whereRaw('quantity <= min_stock');
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('quantity', '<=', 0);
    }

    // Accessors
    public function getIsLowStockAttribute()
    {
        return $this->quantity <= $this->min_stock;
    }

    public function getIsOutOfStockAttribute()
    {
        return $this->quantity <= 0;
    }

    public function getStockValueAttribute()
    {
        return $this->quantity * $this->avg_purchase_price;
    }
}
