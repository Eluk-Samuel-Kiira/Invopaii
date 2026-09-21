<?php

namespace App\Services\Payment;

use App\Models\Payment\InvoiceSequence;
use Illuminate\Support\Facades\DB;

class InvoiceNumberGenerator
{
    /**
     * Atomically reserve the next invoice number for a company.
     * Must be called inside a DB transaction.
     */
    public static function next(
        int $companyId,
        string $mode,
        string $prefix = 'INV',
        ?int $year = null
    ): string {
        $year = $year ?? (int) now()->year;

        // Ensure a sequence row exists
        $sequence = InvoiceSequence::firstOrCreate(
            ['company_id' => $companyId, 'mode' => $mode, 'prefix' => $prefix, 'year' => $year],
            ['next_number' => 1, 'padding' => 4]
        );

        // Lock the row for the duration of the transaction
        $sequence = InvoiceSequence::where('id', $sequence->id)
            ->lockForUpdate()
            ->first();

        $number = $sequence->next_number;
        $sequence->increment('next_number');

        return sprintf(
            '%s-%d-%s',
            $prefix,
            $year,
            str_pad((string) $number, $sequence->padding, '0', STR_PAD_LEFT)
        );
    }
}