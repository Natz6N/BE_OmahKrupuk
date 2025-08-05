<?php

namespace App\Traits;

use App\Models\CurrentStock;

trait HasCurrentStock
{
    /**
     * Get current stock relationship
     */
    public function currentStock()
    {
        return $this->hasOne(CurrentStock::class, 'product_variant_id');
    }

    /**
     * Get current quantity
     */
    public function getCurrentQuantity()
    {
        return $this->currentStock ? $this->currentStock->quantity : 0;
    }

    /**
     * Check if stock is low
     */
    public function isLowStock()
    {
        if (!$this->currentStock)
            return false;
        return $this->currentStock->quantity <= $this->currentStock->min_stock;
    }

    /**
     * Check if out of stock
     */
    public function isOutOfStock()
    {
        if (!$this->currentStock)
            return true;
        return $this->currentStock->quantity <= 0;
    }

    /**
     * Get stock status
     */
    public function getStockStatus()
    {
        if ($this->isOutOfStock())
            return 'out_of_stock';
        if ($this->isLowStock())
            return 'low_stock';
        return 'normal';
    }

    /**
     * Scope untuk filter berdasarkan stok
     */
    public function scopeWithStockStatus($query, $status = null)
    {
        return $query->with('currentStock')
            ->when($status === 'low_stock', function ($q) {
                $q->whereHas('currentStock', function ($sq) {
                    $sq->whereRaw('quantity <= min_stock AND quantity > 0');
                });
            })
            ->when($status === 'out_of_stock', function ($q) {
                $q->whereHas('currentStock', function ($sq) {
                    $sq->where('quantity', '<=', 0);
                });
            })
            ->when($status === 'normal', function ($q) {
                $q->whereHas('currentStock', function ($sq) {
                    $sq->whereRaw('quantity > min_stock');
                });
            });
    }
}
