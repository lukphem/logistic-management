<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * "Account for all payment-related activity on the system" spans
 * five genuinely different tables with different schemas — a
 * shipment's own payment/refund fields, wallet fundings, wallet
 * transfers, and cash settlements. Rather than pulling each into PHP
 * and merging/sorting/paginating there (which gets slower as the
 * data grows, and would mean loading far more into memory than any
 * one page or export chunk actually needs), each source is
 * normalized into the same column set at the SQL level and combined
 * with UNION ALL — so sorting, filtering, pagination, and chunked
 * export all happen inside the database engine itself, the same way
 * they would for a single table.
 */
class PaymentActivityReportService
{
    /**
     * @param array{date_from?: string, date_to?: string, outlet_ids?: array<int>, type?: string} $filters
     */
    public function query(array $filters): Builder
    {
        $sources = [
            $this->shipmentPayments($filters),
            $this->shipmentRefunds($filters),
            $this->walletFundings($filters),
            $this->walletTransfers($filters),
            $this->cashSettlements($filters),
        ];

        if (! empty($filters['type'])) {
            $sources = array_filter($sources, fn ($q, $i) => $this->typeKeys()[$i] === $filters['type'], ARRAY_FILTER_USE_BOTH);
        }

        $sources = array_values($sources);
        $query = array_shift($sources);

        foreach ($sources as $source) {
            $query->unionAll($source);
        }

        return DB::query()->fromSub($query, 'payment_activity')->orderByDesc('occurred_at');
    }

    private function typeKeys(): array
    {
        return ['shipment_payment', 'shipment_refund', 'wallet_funding', 'wallet_transfer', 'cash_settlement'];
    }

    private function dateRange(Builder $query, array $filters, string $column): Builder
    {
        if (! empty($filters['date_from'])) {
            $query->whereDate($column, '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate($column, '<=', $filters['date_to']);
        }

        return $query;
    }

    private function shipmentPayments(array $filters): Builder
    {
        $query = DB::table('shipments')
            ->select([
                DB::raw("'Shipment Payment' as type"),
                DB::raw("COALESCE(paid_at, cash_collected_at, created_at) as occurred_at"),
                DB::raw('total_amount as amount'),
                DB::raw('collection_method as method'),
                DB::raw('tracking_number as reference'),
                DB::raw("CONCAT('Payment for shipment ', tracking_number) as description"),
                'current_outlet_id as outlet_id',
                'id as shipment_id',
            ])
            ->whereNotNull('collection_method')
            ->where(function ($q) {
                $q->whereNotNull('cash_collected_at')
                    ->orWhere('collection_method', 'wallet')
                    ->orWhere(function ($q2) {
                        $q2->where('collection_method', 'paystack')->where('payment_status', 'paid');
                    });
            });

        if (! empty($filters['outlet_ids'])) {
            $query->whereIn('current_outlet_id', $filters['outlet_ids']);
        }

        return $this->dateRange($query, $filters, 'created_at');
    }

    private function shipmentRefunds(array $filters): Builder
    {
        $query = DB::table('shipments')
            ->select([
                DB::raw("'Shipment Refund' as type"),
                DB::raw('refunded_at as occurred_at'),
                DB::raw('total_amount as amount'),
                DB::raw('refund_destination as method'),
                DB::raw("CONCAT('REFUND-', tracking_number) as reference"),
                DB::raw("CONCAT('Refund for shipment ', tracking_number) as description"),
                'current_outlet_id as outlet_id',
                'id as shipment_id',
            ])
            ->whereNotNull('refunded_at');

        if (! empty($filters['outlet_ids'])) {
            $query->whereIn('current_outlet_id', $filters['outlet_ids']);
        }

        return $this->dateRange($query, $filters, 'refunded_at');
    }

    private function walletFundings(array $filters): Builder
    {
        $query = DB::table('account_wallet_fundings')
            ->select([
                DB::raw("'Wallet Funding' as type"),
                DB::raw('paid_at as occurred_at'),
                'amount',
                'funding_method as method',
                'payment_reference as reference',
                DB::raw("'Wallet top-up' as description"),
                DB::raw('NULL as outlet_id'),
                DB::raw('NULL as shipment_id'),
            ])
            ->where('status', 'paid');

        return $this->dateRange($query, $filters, 'paid_at');
    }

    private function walletTransfers(array $filters): Builder
    {
        $query = DB::table('account_wallet_transfers')
            ->select([
                DB::raw("'Wallet Transfer' as type"),
                DB::raw('created_at as occurred_at'),
                'amount',
                DB::raw("'transfer' as method"),
                'reference',
                DB::raw("COALESCE(note, 'Wallet-to-wallet transfer') as description"),
                DB::raw('NULL as outlet_id'),
                DB::raw('NULL as shipment_id'),
            ]);

        return $this->dateRange($query, $filters, 'created_at');
    }

    private function cashSettlements(array $filters): Builder
    {
        $query = DB::table('cash_settlements')
            ->select([
                DB::raw("'Cash Settlement' as type"),
                DB::raw('paid_at as occurred_at'),
                DB::raw('total_amount as amount'),
                DB::raw("'paystack' as method"),
                'payment_reference as reference',
                DB::raw("'Cash settlement payout' as description"),
                DB::raw('NULL as outlet_id'),
                DB::raw('NULL as shipment_id'),
            ])
            ->where('status', 'paid');

        return $this->dateRange($query, $filters, 'paid_at');
    }
}
