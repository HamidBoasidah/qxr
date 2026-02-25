<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class ReportFilters
{
    /**
     * Apply all common report filters to a query
     */
    public static function apply(Builder $query, array $filters): Builder
    {
        // Date range filter
        if (!empty($filters['date_from'])) {
            $query->whereDate(static::getDateColumn($query), '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate(static::getDateColumn($query), '<=', $filters['date_to']);
        }

        // Quick date presets
        if (!empty($filters['date_preset'])) {
            static::applyDatePreset($query, $filters['date_preset']);
        }

        // Company filter (supports both company_id and company_user_id)
        $companyId = $filters['company_id'] ?? $filters['company_user_id'] ?? null;
        if (!empty($companyId)) {
            static::applyCompanyFilter($query, $companyId);
        }

        // Customer filter
        if (!empty($filters['customer_id'])) {
            static::applyCustomerFilter($query, $filters['customer_id']);
        }

        // Status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Category filter (when applicable)
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Search filter
        if (!empty($filters['search'])) {
            static::applySearch($query, $filters['search']);
        }

        return $query;
    }

    /**
     * Apply date preset (today, last_7, last_30, etc.)
     */
    protected static function applyDatePreset(Builder $query, string $preset): void
    {
        $dateColumn = static::getDateColumn($query);

        switch ($preset) {
            case 'today':
                $query->whereDate($dateColumn, Carbon::today());
                break;
            case 'yesterday':
                $query->whereDate($dateColumn, Carbon::yesterday());
                break;
            case 'last_7':
                $query->whereDate($dateColumn, '>=', Carbon::now()->subDays(7));
                break;
            case 'last_30':
                $query->whereDate($dateColumn, '>=', Carbon::now()->subDays(30));
                break;
            case 'this_month':
                $query->whereMonth($dateColumn, Carbon::now()->month)
                      ->whereYear($dateColumn, Carbon::now()->year);
                break;
            case 'last_month':
                $query->whereMonth($dateColumn, Carbon::now()->subMonth()->month)
                      ->whereYear($dateColumn, Carbon::now()->subMonth()->year);
                break;
        }
    }

    /**
     * Get the appropriate date column based on the model
     */
    protected static function getDateColumn(Builder $query): string
    {
        $model = $query->getModel();
        $table = $model->getTable();

        // Map table names to their primary date column
        $dateColumns = [
            'invoices' => 'issued_at',
            'orders' => 'submitted_at',
            'offers' => 'start_at',
            'products' => 'created_at',
        ];

        return $dateColumns[$table] ?? 'created_at';
    }

    /**
     * Apply company filter (handles both direct and relation-based)
     */
    protected static function applyCompanyFilter(Builder $query, $companyId): void
    {
        $model = $query->getModel();
        $table = $model->getTable();

        if ($table === 'invoices') {
            // Invoices: filter via order relationship
            $query->whereHas('order', function($q) use ($companyId) {
                $q->where('company_user_id', $companyId);
            });
        } elseif ($table === 'orders') {
            $query->where('company_user_id', $companyId);
        } elseif (in_array($table, ['offers', 'products'])) {
            $query->where('company_user_id', $companyId);
        }
    }

    /**
     * Apply customer filter
     */
    protected static function applyCustomerFilter(Builder $query, $customerId): void
    {
        $model = $query->getModel();
        $table = $model->getTable();

        if ($table === 'invoices') {
            // Invoices: filter via order relationship
            $query->whereHas('order', function($q) use ($customerId) {
                $q->where('customer_user_id', $customerId);
            });
        } elseif ($table === 'orders') {
            $query->where('customer_user_id', $customerId);
        }
    }

    /**
     * Apply search filter (model-specific)
     */
    protected static function applySearch(Builder $query, string $search): void
    {
        $model = $query->getModel();
        $table = $model->getTable();

        switch ($table) {
            case 'invoices':
                $query->where(function($q) use ($search) {
                    $q->where('invoice_no', 'like', "%{$search}%")
                      ->orWhereHas('order', function($q2) use ($search) {
                          $q2->where('order_no', 'like', "%{$search}%");
                      });
                });
                break;

            case 'orders':
                $query->where(function($q) use ($search) {
                    $q->where('order_no', 'like', "%{$search}%");
                });
                break;

            case 'offers':
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
                break;

            case 'products':
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                });
                break;
        }
    }

    /**
     * Get clean filter array (remove nulls, empty strings)
     */
    public static function clean(array $filters): array
    {
        return array_filter($filters, function($value) {
            return !is_null($value) && $value !== '';
        });
    }
}
