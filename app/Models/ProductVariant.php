<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'variant_name',
        'barcode',
        'barcode_type',
        'selling_price',
        'unit',
        'is_active',
    ];

    protected $casts = [
        'selling_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function currentStock()
    {
        return $this->hasOne(CurrentStock::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByBarcode($query, $barcode)
    {
        return $query->where('barcode', $barcode);
    }

    public function scopeWithStock($query)
    {
        return $query->with('currentStock');
    }

    public function scopeLowStock($query)
    {
        return $query->whereHas('currentStock', function ($q) {
            $q->whereRaw('quantity <= min_stock');
        });
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return $this->product->name . ' - ' . $this->variant_name;
    }

    public function getIsLowStockAttribute()
    {
        if (!$this->currentStock) return false;
        return $this->currentStock->quantity <= $this->currentStock->min_stock;
    }

    public function getCurrentQuantityAttribute()
    {
        return $this->currentStock ? $this->currentStock->quantity : 0;
    }
}
