<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\DebtEntry;
use Carbon\Carbon;

class DebtReportService
{
    public function statement(Customer $customer, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $from = $from?->copy()->startOfDay();
        $to   = $to?->copy()->endOfDay();

        // Saldo awal = saldo tepat sebelum periode dimulai
        $opening = $from
            ? (int) $customer->debtEntries()->where('created_at', '<', $from)
                ->selectRaw("COALESCE(SUM(CASE WHEN type = 'hutang' THEN amount ELSE -amount END), 0) as bal")
                ->value('bal')
            : 0;

        $entries = $customer->debtEntries()->with(['order.items'])
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to))
            ->orderBy('id')
            ->get();

        $running = $opening;
        $totals  = ['hutang' => 0, 'bayar' => 0, 'batal' => 0];

        $rows = $entries->map(function (DebtEntry $e) use (&$running, &$totals) {
            $isDebt   = $e->type === DebtEntry::TYPE_HUTANG;
            $running += $isDebt ? $e->amount : -$e->amount;
            $totals[$e->type] += $e->amount;

            $label = match ($e->type) {
                DebtEntry::TYPE_HUTANG => 'Hutang'.($e->order ? ' - '.$e->order->order_no : ''),
                DebtEntry::TYPE_BAYAR  => 'Pembayaran'.($e->method ? ' ('.strtoupper($e->method).')' : ''),
                default                => 'Pembatalan'.($e->order ? ' '.$e->order->order_no : ''),
            };

            return [
                'date'    => $e->created_at,
                'label'   => $label,
                'items'   => $isDebt && $e->order
                    ? $e->order->items->map(fn ($i) => $i->qty.'x '.$i->product_name)->implode(', ')
                    : ($e->note && ! $e->order ? $e->note : ''),
                'debit'   => $isDebt ? $e->amount : null,
                'credit'  => $isDebt ? null : $e->amount,
                'balance' => $running,
            ];
        });

        return [
            'customer' => $customer,
            'from'     => $from,
            'to'       => $to,
            'opening'  => $opening,
            'rows'     => $rows,
            'totals'   => $totals,
            'closing'  => $running,
            'current'  => $customer->balance(),
        ];
    }
}