<?php
namespace App\Observers;

use App\Models\Sale;

class SaleObserver
{
    /**
     * Handle the Sale "creating" event.
     */
    public function creating(Sale $sale): void
    {
        // Generate invoice number jika belum ada
        if (empty($sale->invoice_number)) {
            $sale->invoice_number = Sale::generateInvoiceNumber();
        }
    }

    /**
     * Handle the Sale "created" event.
     */
    public function created(Sale $sale): void
    {
        \Log::info('Sale created', [
            'sale_id' => $sale->id,
            'invoice_number' => $sale->invoice_number,
            'total_amount' => $sale->total_amount,
            'user_id' => $sale->user_id,
            'created_at' => $sale->created_at
        ]);
    }

    /**
     * Handle the Sale "updated" event.
     */
    public function updated(Sale $sale): void
    {
        if ($sale->wasChanged()) {
            \Log::info('Sale updated', [
                'sale_id' => $sale->id,
                'changes' => $sale->getChanges(),
                'updated_by' => auth()->id()
            ]);
        }
    }

    /**
     * Handle the Sale "deleted" event.
     */
    public function deleted(Sale $sale): void
    {
        \Log::warning('Sale deleted', [
            'sale_id' => $sale->id,
            'invoice_number' => $sale->invoice_number,
            'total_amount' => $sale->total_amount,
            'deleted_by' => auth()->id()
        ]);
    }
}
