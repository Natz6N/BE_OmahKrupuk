<?php
namespace App\Traits;

trait HasProfitCalculation
{
    /**
     * Calculate profit for sale item
     */
    public function calculateProfit()
    {
        if (!$this->purchase_price) return 0;
        return $this->total_price - ($this->quantity * $this->purchase_price);
    }

    /**
     * Calculate profit margin percentage
     */
    public function calculateProfitMargin()
    {
        if (!$this->purchase_price || $this->total_price == 0) return 0;
        $profit = $this->calculateProfit();
        return ($profit / $this->total_price) * 100;
    }

    /**
     * Get profit attribute
     */
    public function getProfitAttribute()
    {
        return $this->calculateProfit();
    }

    /**
     * Get profit margin attribute
     */
    public function getProfitMarginAttribute()
    {
        return round($this->calculateProfitMargin(), 2);
    }

    /**
     * Scope untuk filter berdasarkan profit
     */
    public function scopeWithProfit($query)
    {
        return $query->selectRaw('*, (total_price - (quantity * COALESCE(purchase_price, 0))) as profit')
                    ->selectRaw('CASE
                        WHEN total_price > 0 AND purchase_price IS NOT NULL
                        THEN ((total_price - (quantity * purchase_price)) / total_price) * 100
                        ELSE 0
                    END as profit_margin');
    }

    /**
     * Scope untuk filter profit tinggi
     */
    public function scopeHighProfit($query, $minMargin = 30)
    {
        return $query->whereRaw('
            CASE
                WHEN total_price > 0 AND purchase_price IS NOT NULL
                THEN ((total_price - (quantity * purchase_price)) / total_price) * 100
                ELSE 0
            END >= ?', [$minMargin]);
    }
}
