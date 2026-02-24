<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Invoice;
use App\Models\Message;
use App\Models\Offer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminDashboardStatsService
{
    /**
     * Get all dashboard statistics with optional filters
     * 
     * @param array $filters ['date_from', 'date_to', 'company_id', 'status']
     * @return array
     */
    public function getStats(array $filters = []): array
    {
        $cacheKey = $this->getCacheKey($filters);
        $cacheDuration = 300; // 5 minutes

        return Cache::remember($cacheKey, $cacheDuration, function () use ($filters) {
            return [
                'kpis' => $this->getKPIs($filters),
                'charts' => $this->getChartData($filters),
                'tables' => $this->getLatestData($filters),
                'filters' => $filters,
            ];
        });
    }

    /**
     * Get KPI cards data
     */
    protected function getKPIs(array $filters): array
    {
        return [
            'orders' => $this->getOrderStats($filters),
            'invoices' => $this->getInvoiceStats($filters),
            'revenue' => $this->getRevenueStats($filters),
            'offers' => $this->getOfferStats($filters),
            'products' => $this->getProductStats($filters),
            'users' => $this->getUserStats($filters),
            'chat' => $this->getChatStats($filters),
        ];
    }

    /**
     * Get order statistics
     */
    protected function getOrderStats(array $filters): array
    {
        $query = Order::query();
        $this->applyDateFilter($query, $filters);
        $this->applyCompanyFilter($query, $filters);

        $total = $query->count();
        
        $byStatus = $query->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Get previous period for trend
        $previousQuery = $this->getPreviousPeriodQuery(Order::query(), $filters);
        $previousTotal = $previousQuery->count();
        $trend = $this->calculateTrend($total, $previousTotal);

        return [
            'total' => $total,
            'trend' => $trend,
            'by_status' => [
                'pending' => $byStatus['pending'] ?? 0,
                'approved' => $byStatus['approved'] ?? 0,
                'preparing' => $byStatus['preparing'] ?? 0,
                'shipped' => $byStatus['shipped'] ?? 0,
                'delivered' => $byStatus['delivered'] ?? 0,
                'rejected' => $byStatus['rejected'] ?? 0,
                'cancelled' => $byStatus['cancelled'] ?? 0,
            ],
        ];
    }

    /**
     * Get invoice statistics
     */
    protected function getInvoiceStats(array $filters): array
    {
        $query = Invoice::query();
        $this->applyDateFilter($query, $filters, 'issued_at');
        
        if (isset($filters['company_id'])) {
            $query->whereHas('order', function ($q) use ($filters) {
                $q->where('company_user_id', $filters['company_id']);
            });
        }

        $total = $query->count();
        
        $byStatus = $query->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $previousQuery = $this->getPreviousPeriodQuery(Invoice::query(), $filters, 'issued_at');
        $previousTotal = $previousQuery->count();
        $trend = $this->calculateTrend($total, $previousTotal);

        return [
            'total' => $total,
            'trend' => $trend,
            'paid' => $byStatus['paid'] ?? 0,
            'unpaid' => $byStatus['unpaid'] ?? 0,
            'void' => $byStatus['void'] ?? 0,
        ];
    }

    /**
     * Get revenue and discount statistics
     */
    protected function getRevenueStats(array $filters): array
    {
        $invoiceQuery = Invoice::query()->where('status', 'paid');
        $this->applyDateFilter($invoiceQuery, $filters, 'issued_at');
        
        if (isset($filters['company_id'])) {
            $invoiceQuery->whereHas('order', function ($q) use ($filters) {
                $q->where('company_user_id', $filters['company_id']);
            });
        }

        $totalRevenue = $invoiceQuery->sum('total_snapshot');
        $totalDiscount = Invoice::query()
            ->when(isset($filters['date_from']), function ($q) use ($filters) {
                $q->where('issued_at', '>=', $filters['date_from']);
            })
            ->when(isset($filters['date_to']), function ($q) use ($filters) {
                $q->where('issued_at', '<=', $filters['date_to']);
            })
            ->when(isset($filters['company_id']), function ($q) use ($filters) {
                $q->whereHas('order', function ($q) use ($filters) {
                    $q->where('company_user_id', $filters['company_id']);
                });
            })
            ->sum('discount_total_snapshot');

        // Previous period revenue for trend
        $previousInvoiceQuery = $this->getPreviousPeriodQuery(
            Invoice::query()->where('status', 'paid'), 
            $filters, 
            'issued_at'
        );
        $previousRevenue = $previousInvoiceQuery->sum('total_snapshot');
        $trend = $this->calculateTrend($totalRevenue, $previousRevenue);

        return [
            'total_revenue' => round($totalRevenue, 2),
            'total_discount' => round($totalDiscount, 2),
            'trend' => $trend,
        ];
    }

    /**
     * Get offer statistics
     */
    protected function getOfferStats(array $filters): array
    {
        $query = Offer::query();
        $this->applyDateFilter($query, $filters);
        $this->applyCompanyFilter($query, $filters, 'company_user_id');

        $total = $query->count();
        $active = (clone $query)->where('status', 'active')->count();
        $public = (clone $query)->where('scope', 'public')->count();
        $private = (clone $query)->where('scope', 'private')->count();

        $byStatus = $query->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'total' => $total,
            'active' => $active,
            'public' => $public,
            'private' => $private,
            'by_status' => $byStatus,
        ];
    }

    /**
     * Get product statistics
     */
    protected function getProductStats(array $filters): array
    {
        $query = Product::query();
        $this->applyCompanyFilter($query, $filters, 'company_user_id');

        $total = $query->count();
        $active = (clone $query)->where('is_active', true)->count();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $total - $active,
        ];
    }

    /**
     * Get user statistics
     */
    protected function getUserStats(array $filters): array
    {
        $customers = User::where('user_type', 'customer')->count();
        $companies = User::where('user_type', 'company')->count();

        return [
            'total_customers' => $customers,
            'total_companies' => $companies,
            'total_users' => $customers + $companies,
        ];
    }

    /**
     * Get chat statistics
     */
    protected function getChatStats(array $filters): array
    {
        $conversationQuery = Conversation::query();
        $messageQuery = Message::query();

        $this->applyDateFilter($conversationQuery, $filters);
        $this->applyDateFilter($messageQuery, $filters);

        return [
            'total_conversations' => $conversationQuery->count(),
            'total_messages' => $messageQuery->count(),
        ];
    }

    /**
     * Get chart data
     */
    protected function getChartData(array $filters): array
    {
        return [
            'orders_over_time' => $this->getOrdersOverTime($filters),
            'revenue_over_time' => $this->getRevenueOverTime($filters),
            'orders_by_status' => $this->getOrdersByStatus($filters),
            'top_companies' => $this->getTopCompanies($filters),
            'top_products' => $this->getTopProducts($filters),
            'offers_activity' => $this->getOffersActivity($filters),
        ];
    }

    /**
     * Get orders over time (daily/weekly/monthly)
     */
    protected function getOrdersOverTime(array $filters): array
    {
        $period = $this->determinePeriod($filters);
        $query = Order::query();
        $this->applyDateFilter($query, $filters);
        $this->applyCompanyFilter($query, $filters);

        $dateColumn = 'created_at';
        $groupFormat = $this->getDateGroupFormat($period);

        $data = $query->select(
                DB::raw("DATE_FORMAT({$dateColumn}, '{$groupFormat}') as period"),
                DB::raw('count(*) as count')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        // Fill missing dates with zero values
        $filledData = $this->fillMissingDates(
            $data->pluck('period')->toArray(),
            $data->pluck('count')->toArray(),
            $filters,
            $period
        );

        return [
            'labels' => $filledData['labels'],
            'data' => $filledData['data'],
            'period' => $period,
        ];
    }

    /**
     * Get revenue over time
     */
    protected function getRevenueOverTime(array $filters): array
    {
        $period = $this->determinePeriod($filters);
        $query = Invoice::query()->where('status', 'paid');
        $this->applyDateFilter($query, $filters, 'issued_at');
        
        if (isset($filters['company_id'])) {
            $query->whereHas('order', function ($q) use ($filters) {
                $q->where('company_user_id', $filters['company_id']);
            });
        }

        $dateColumn = 'issued_at';
        $groupFormat = $this->getDateGroupFormat($period);

        $data = $query->select(
                DB::raw("DATE_FORMAT({$dateColumn}, '{$groupFormat}') as period"),
                DB::raw('SUM(total_snapshot) as total')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        // Fill missing dates with zero values
        $filledData = $this->fillMissingDates(
            $data->pluck('period')->toArray(),
            $data->pluck('total')->map(fn($v) => round($v, 2))->toArray(),
            $filters,
            $period
        );

        return [
            'labels' => $filledData['labels'],
            'data' => $filledData['data'],
            'period' => $period,
        ];
    }

    /**
     * Get orders by status for pie chart
     */
    protected function getOrdersByStatus(array $filters): array
    {
        $query = Order::query();
        $this->applyDateFilter($query, $filters);
        $this->applyCompanyFilter($query, $filters);

        $data = $query->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        return [
            'labels' => $data->pluck('status')->toArray(),
            'data' => $data->pluck('count')->toArray(),
        ];
    }

    /**
     * Get top companies by revenue
     */
    protected function getTopCompanies(array $filters): array
    {
        $query = Invoice::query()
            ->where('invoices.status', 'paid')
            ->join('orders', 'invoices.order_id', '=', 'orders.id')
            ->join('users', 'orders.company_user_id', '=', 'users.id')
            ->select(
                'users.id',
                DB::raw("CONCAT(users.first_name, ' ', users.last_name) as name"),
                DB::raw('SUM(invoices.total_snapshot) as revenue')
            );

        $this->applyDateFilter($query, $filters, 'invoices.issued_at');

        $data = $query->groupBy('users.id', 'users.first_name', 'users.last_name')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        return [
            'labels' => $data->pluck('name')->toArray(),
            'data' => $data->pluck('revenue')->map(fn($v) => round($v, 2))->toArray(),
        ];
    }

    /**
     * Get top products by quantity ordered
     */
    protected function getTopProducts(array $filters): array
    {
        $query = OrderItem::query()
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->select(
                'products.id',
                'products.name',
                DB::raw('SUM(order_items.qty) as total_qty')
            );

        $this->applyDateFilter($query, $filters, 'orders.created_at');
        
        if (isset($filters['company_id'])) {
            $query->where('orders.company_user_id', $filters['company_id']);
        }

        $data = $query->groupBy('products.id', 'products.name')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get();

        return [
            'labels' => $data->pluck('name')->toArray(),
            'data' => $data->pluck('total_qty')->toArray(),
        ];
    }

    /**
     * Get offers activity (active vs expired vs paused)
     */
    protected function getOffersActivity(array $filters): array
    {
        $query = Offer::query();
        $this->applyDateFilter($query, $filters);
        $this->applyCompanyFilter($query, $filters, 'company_user_id');

        $data = $query->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        return [
            'labels' => $data->pluck('status')->toArray(),
            'data' => $data->pluck('count')->toArray(),
        ];
    }

    /**
     * Get latest data for tables
     */
    protected function getLatestData(array $filters): array
    {
        return [
            'latest_orders' => $this->getLatestOrders($filters),
            'latest_invoices' => $this->getLatestInvoices($filters),
            'latest_offers' => $this->getLatestOffers($filters),
            'latest_products' => $this->getLatestProducts($filters),
        ];
    }

    /**
     * Get latest orders
     */
    protected function getLatestOrders(array $filters): array
    {
        $query = Order::with(['company:id,first_name,last_name', 'customer:id,first_name,last_name'])
            ->select('id', 'order_no', 'company_user_id', 'customer_user_id', 'status', 'created_at');

        $this->applyCompanyFilter($query, $filters);

        return $query->latest()
            ->limit(10)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_no' => $order->order_no,
                    'company' => $order->company ? $order->company->first_name . ' ' . $order->company->last_name : 'N/A',
                    'customer' => $order->customer ? $order->customer->first_name . ' ' . $order->customer->last_name : 'N/A',
                    'status' => $order->status,
                    'created_at' => $order->created_at->format('Y-m-d H:i'),
                ];
            })
            ->toArray();
    }

    /**
     * Get latest invoices
     */
    protected function getLatestInvoices(array $filters): array
    {
        $query = Invoice::with('order:id,order_no')
            ->select('id', 'invoice_no', 'order_id', 'total_snapshot', 'status', 'issued_at');

        if (isset($filters['company_id'])) {
            $query->whereHas('order', function ($q) use ($filters) {
                $q->where('company_user_id', $filters['company_id']);
            });
        }

        return $query->latest('issued_at')
            ->limit(10)
            ->get()
            ->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_no' => $invoice->invoice_no,
                    'order_no' => $invoice->order?->order_no ?? 'N/A',
                    'total' => round($invoice->total_snapshot, 2),
                    'status' => $invoice->status,
                    'issued_at' => $invoice->issued_at?->format('Y-m-d H:i') ?? 'N/A',
                ];
            })
            ->toArray();
    }

    /**
     * Get latest offers
     */
    protected function getLatestOffers(array $filters): array
    {
        $query = Offer::with('company:id,first_name,last_name')
            ->select('id', 'title', 'company_user_id', 'scope', 'status', 'start_at', 'end_at', 'created_at');

        $this->applyCompanyFilter($query, $filters, 'company_user_id');

        return $query->latest()
            ->limit(10)
            ->get()
            ->map(function ($offer) {
                return [
                    'id' => $offer->id,
                    'title' => $offer->title,
                    'company' => $offer->company ? $offer->company->first_name . ' ' . $offer->company->last_name : 'N/A',
                    'scope' => $offer->scope,
                    'status' => $offer->status,
                    'start_at' => $offer->start_at?->format('Y-m-d'),
                    'end_at' => $offer->end_at?->format('Y-m-d'),
                ];
            })
            ->toArray();
    }

    /**
     * Get latest products
     */
    protected function getLatestProducts(array $filters): array
    {
        $query = Product::with('company:id,first_name,last_name')
            ->select('id', 'name', 'company_user_id', 'base_price', 'is_active', 'created_at');

        $this->applyCompanyFilter($query, $filters, 'company_user_id');

        return $query->latest()
            ->limit(10)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'company' => $product->company ? $product->company->first_name . ' ' . $product->company->last_name : 'N/A',
                    'base_price' => round($product->base_price, 2),
                    'is_active' => $product->is_active,
                ];
            })
            ->toArray();
    }

    /**
     * Helper methods
     */

    /**
     * Fill missing dates with zero values
     */
    protected function fillMissingDates(array $labels, array $data, array $filters, string $period): array
    {
        if (!isset($filters['date_from']) || !isset($filters['date_to'])) {
            return ['labels' => $labels, 'data' => $data];
        }

        $from = Carbon::parse($filters['date_from']);
        $to = Carbon::parse($filters['date_to']);
        
        // Create a mapping of existing data
        $dataMap = array_combine($labels, $data);
        
        $filledLabels = [];
        $filledData = [];
        
        if ($period === 'daily') {
            $current = $from->copy();
            while ($current->lte($to)) {
                $label = $current->format('Y-m-d');
                $filledLabels[] = $label;
                $filledData[] = $dataMap[$label] ?? 0;
                $current->addDay();
            }
        } elseif ($period === 'weekly') {
            $current = $from->copy()->startOfWeek();
            while ($current->lte($to)) {
                // Use ISO week number format to match MySQL %v
                $isoWeek = str_pad($current->isoWeek(), 2, '0', STR_PAD_LEFT);
                $label = $current->isoWeekYear() . '-W' . $isoWeek;
                $filledLabels[] = $label;
                $filledData[] = $dataMap[$label] ?? 0;
                $current->addWeek();
            }
        } elseif ($period === 'monthly') {
            $current = $from->copy()->startOfMonth();
            while ($current->lte($to)) {
                $label = $current->format('Y-m');
                $filledLabels[] = $label;
                $filledData[] = $dataMap[$label] ?? 0;
                $current->addMonth();
            }
        }
        
        return [
            'labels' => $filledLabels,
            'data' => $filledData,
        ];
    }

    protected function applyDateFilter($query, array $filters, string $column = 'created_at')
    {
        if (isset($filters['date_from'])) {
            $query->where($column, '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->where($column, '<=', $filters['date_to']);
        }
    }

    protected function applyCompanyFilter($query, array $filters, string $column = 'company_user_id')
    {
        if (isset($filters['company_id'])) {
            $query->where($column, $filters['company_id']);
        }
    }

    protected function getPreviousPeriodQuery($query, array $filters, string $column = 'created_at')
    {
        if (isset($filters['date_from']) && isset($filters['date_to'])) {
            $from = Carbon::parse($filters['date_from']);
            $to = Carbon::parse($filters['date_to']);
            $diff = $from->diffInDays($to);
            
            $previousFrom = $from->copy()->subDays($diff);
            $previousTo = $to->copy()->subDays($diff);
            
            $query->whereBetween($column, [$previousFrom, $previousTo]);
        }
        
        return $query;
    }

    protected function calculateTrend($current, $previous): array
    {
        if ($previous == 0) {
            return [
                'percentage' => $current > 0 ? 100 : 0,
                'direction' => $current > 0 ? 'up' : 'neutral',
            ];
        }

        $percentage = (($current - $previous) / $previous) * 100;
        
        return [
            'percentage' => round(abs($percentage), 1),
            'direction' => $percentage > 0 ? 'up' : ($percentage < 0 ? 'down' : 'neutral'),
        ];
    }

    protected function determinePeriod(array $filters): string
    {
        if (!isset($filters['date_from']) || !isset($filters['date_to'])) {
            return 'daily';
        }

        $from = Carbon::parse($filters['date_from']);
        $to = Carbon::parse($filters['date_to']);
        $diff = $from->diffInDays($to);

        if ($diff <= 31) {
            return 'daily';
        } elseif ($diff <= 90) {
            return 'weekly';
        } else {
            return 'monthly';
        }
    }

    protected function getDateGroupFormat(string $period): string
    {
        return match($period) {
            'daily' => '%Y-%m-%d',
            'weekly' => '%Y-W%v',  // Changed from %u to %v for ISO week number
            'monthly' => '%Y-%m',
            default => '%Y-%m-%d',
        };
    }

    protected function getCacheKey(array $filters): string
    {
        return 'admin_dashboard_stats_' . md5(json_encode($filters));
    }

    /**
     * Clear dashboard cache
     */
    public function clearCache(): void
    {
        Cache::flush();
    }
}
