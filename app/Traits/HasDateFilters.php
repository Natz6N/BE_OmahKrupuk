<?php
// app/Traits/HasDateFilters.php
namespace App\Traits;

use Carbon\Carbon;

trait HasDateFilters
{
    /**
     * Scope untuk filter berdasarkan range tanggal
     */
    public function scopeDateRange($query, $startDate, $endDate, $column = 'created_at')
    {
        return $query->whereBetween($column, [
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay()
        ]);
    }

    /**
     * Scope untuk filter hari ini
     */
    public function scopeToday($query, $column = 'created_at')
    {
        return $query->whereDate($column, Carbon::today());
    }

    /**
     * Scope untuk filter kemarin
     */
    public function scopeYesterday($query, $column = 'created_at')
    {
        return $query->whereDate($column, Carbon::yesterday());
    }

    /**
     * Scope untuk filter minggu ini
     */
    public function scopeThisWeek($query, $column = 'created_at')
    {
        return $query->whereBetween($column, [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek()
        ]);
    }

    /**
     * Scope untuk filter bulan ini
     */
    public function scopeThisMonth($query, $column = 'created_at')
    {
        return $query->whereBetween($column, [
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth()
        ]);
    }

    /**
     * Scope untuk filter tahun ini
     */
    public function scopeThisYear($query, $column = 'created_at')
    {
        return $query->whereBetween($column, [
            Carbon::now()->startOfYear(),
            Carbon::now()->endOfYear()
        ]);
    }

    /**
     * Scope untuk filter berdasarkan periode
     */
    public function scopePeriod($query, $period, $column = 'created_at')
    {
        switch ($period) {
            case 'today':
                return $query->today($column);
            case 'yesterday':
                return $query->yesterday($column);
            case 'this_week':
                return $query->thisWeek($column);
            case 'this_month':
                return $query->thisMonth($column);
            case 'this_year':
                return $query->thisYear($column);
            default:
                return $query;
        }
    }
}
