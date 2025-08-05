<?php
// app/Models/SaleItem.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'product_variant_id',
        'quantity',
        'unit_price',
        'total_price',
        'purchase_price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'purchase_price' => 'decimal:2',
    ];

    // Relationships
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    // Accessors
    public function getProfitAttribute()
    {
        if (!$this->purchase_price) return 0;
        return $this->total_price - ($this->quantity * $this->purchase_price);
    }

    public function getProfitMarginAttribute()
    {
        if (!$this->purchase_price || $this->total_price == 0) return 0;
        return ($this->profit / $this->total_price) * 100;
    }

    // Scopes
    public function scopeByProduct($query, $productVariantId)
    {
        return $query->where('product_variant_id', $productVariantId);
    }
}
