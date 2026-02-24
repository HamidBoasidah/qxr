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

class CompanyDashboardStatsService
{
    protected $companyUserId;

    public function __construct($companyUserId)
    {
        $this->companyUserId = $companyUserId;
    }

    /**
     * Get all dashboard statistics with optional filters
     * 
     * @param array $filters ['date_from', 'date_to', 'status']
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
            'chat' => $this->getChatStats($filters),
        ];
    }

    /**
     * Get order statistics
     */
    protected function getOrderStats(array $filters): array
    {
        $query = Order::query()->where('company_user_id', $this->companyUserId);
        $this->applyDateFilter($query, $filters);

        $total = $query->count();
        
        $byStatus = Order::where('company_user_id', $this->companyUserId)
            ->when(isset($filters['date_from']), fn($q) => $q->where('created_at', '>=', $filters['date_from']))
            ->when(isset($filters['date_to']), fn($q) => $q->where('created_at', '<=', $filters['date_to']))
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Get previous period for trend
        $previousQuery = $this->getPreviousPeriodQuery(
            Order::query()->where('company_user_id', $this->companyUserId), 
            $filters
        );
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
        $query = Invoice::query()
            ->whereHas('order', function ($q) {
                $q->where('company_user_id', $this->companyUserId);
            });
        $this->applyDateFilter($query, $filters, 'issued_at');

        $total = $query->count();
        $paid = $query->clone()->where('status', 'paid')->count();
        $unpaid = $query->clone()->where('status', 'unpaid')->count();

        // Get previous period for trend
        $previousQuery = Invoice::query()
            ->whereHas('order', function ($q) {
                $q->where('company_user_id', $this->companyUserId);
            });
        $previousQuery = $this->getPreviousPeriodQuery($previousQuery, $filters, 'issued_at');
        $previousTotal = $previousQuery->count();
        $trend = $this->calculateTrend($total, $previousTotal);

        return [
            'total' => $total,
            'paid' => $paid,
            'unpaid' => $unpaid,
            'trend' => $trend,
        ];
    }

    /**
     * Get revenue statistics
     */
    protected function getRevenueStats(array $filters): array
    {
        $query = Invoice::query()
            ->where('status', 'paid')
            ->whereHas('order', function ($q) {
                $q->where('company_user_id', $this->companyUserId);
            });
        $this->applyDateFilter($query, $filters, 'issued_at');

        $totalRevenue = $query->sum('total_snapshot');
        $totalDiscount = $query->sum('discount_total_snapshot');

        // Get previous period for trend
        $previousQuery = Invoice::query()
            ->where('status', 'paid')
            ->whereHas('order', function ($q) {
                $q->where('company_user_id', $this->companyUserId);
            });
        $previousQuery = $this->getPreviousPeriodQuery($previousQuery, $filters, 'issued_at');
        $previousRevenue = $previousQuery->sum('total_snapshot');
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
        $query = Offer::query()->where('company_user_id', $this->companyUserId);
        $this->applyDateFilter($query, $filters);

        $total = $query->count();
        $active = $query->clone()->where('status', 'active')->count();
        $expired = $query->clone()->where('status', 'expired')->count();
        $inactive = $query->clone()->where('status', 'inactive')->count();

        // Get previous period for trend
        $previousQuery = $this->getPreviousPeriodQuery(
            Offer::query()->where('company_user_id', $this->companyUserId), 
            $filters
        );
        $previousTotal = $previousQuery->count();
        $trend = $this->calculateTrend($total, $previousTotal);

        return [
            'total' => $total,
            'active' => $active,
            'expired' => $expired,
            'inactive' => $inactive,
            'trend' => $trend,
        ];
    }

    /**
     * Get product statistics
     */
    protected function getProductStats(array $filters): array
    {
        $query = Product::query()->where('company_user_id', $this->companyUserId);
        $this->applyDateFilter($query, $filters);

        $total = $query->count();

        // Get previous period for trend
        $previousQuery = $this->getPreviousPeriodQuery(
            Product::query()->where('company_user_id', $this->companyUserId), 
            $filters
        );
        $previousTotal = $previousQuery->count();
        $trend = $this->calculateTrend($total, $previousTotal);

        return [
            'total' => $total,
            'trend' => $trend,
        ];
    }

    /**
     * Get chat statistics
     */
    protected function getChatStats(array $filters): array
    {
        $conversationQuery = Conversation::query()
            ->whereHas('participants', function ($q) {
                $q->where('user_id', $this->companyUserId);
            });
        $messageQuery = Message::query()
            ->whereHas('conversation.participants', function ($q) {
                $q->where('user_id', $this->companyUserId);
            });
        
        $this->applyDateFilter($conversationQuery, $filters);
        $this->applyDateFilter($messageQuery, $filters);

        $totalConversations = $conversationQuery->count();
        $totalMessages = $messageQuery->count();

        return [
            'total_conversations' => $totalConversations,
            'total_messages' => $totalMessages,
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
            'offers_activity' => $this->getOffersActivity($filters),
            'top_products' => $this->getTopProducts($filters),
        ];
    }

    /**
     * Get orders over time
     */
    protected function getOrdersOverTime(array $filters): array
    {
        $period = $this->determinePeriod($filters);
        $query = Order::query()->where('company_user_id', $this->companyUserId);
        $this->applyDateFilter($query, $filters);

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
        $query = Invoice::query()
            ->where('status', 'paid')
            ->whereHas('order', function ($q) {
                $q->where('company_user_id', $this->companyUserId);
            });
        $this->applyDateFilter($query, $filters, 'issued_at');

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
        $query = Order::query()->where('company_user_id', $this->companyUserId);
        $this->applyDateFilter($query, $filters);

        $data = $query->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        return [
            'labels' => $data->pluck('status')->toArray(),
            'data' => $data->pluck('count')->toArray(),
        ];
    }

    /**
     * Get offers activity
     */
    protected function getOffersActivity(array $filters): array
    {
        $query = Offer::query()->where('company_user_id', $this->companyUserId);
        $this->applyDateFilter($query, $filters);

        $data = $query->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        return [
            'labels' => $data->pluck('status')->toArray(),
            'data' => $data->pluck('count')->toArray(),
        ];
    }

    /**
     * Get top products
     */
    protected function getTopProducts(array $filters): array
    {
        $query = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.company_user_id', $this->companyUserId);
        $this->applyDateFilter($query, $filters, 'orders.created_at');

        $data = $query->select(
                'products.name as product_name',
                DB::raw('SUM(order_items.qty) as total_quantity')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->get();

        return [
            'labels' => $data->pluck('product_name')->toArray(),
            'data' => $data->pluck('total_quantity')->toArray(),
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
        $query = Order::query()
            ->where('company_user_id', $this->companyUserId)
            ->with(['customer'])
            ->orderBy('created_at', 'desc')
            ->limit(5);

        return $query->get()->map(function ($order) {
            return [
                'id' => $order->id,
                'order_no' => $order->order_no,
                'company' => '', // Empty for company dashboard
                'customer' => $order->customer->first_name . ' ' . $order->customer->last_name,
                'status' => $order->status,
                'created_at' => $order->created_at->format('Y-m-d H:i'),
            ];
        })->toArray();
    }

    /**
     * Get latest invoices
     */
    protected function getLatestInvoices(array $filters): array
    {
        $query = Invoice::query()
            ->whereHas('order', function ($q) {
                $q->where('company_user_id', $this->companyUserId);
            })
            ->with(['order'])
            ->orderBy('issued_at', 'desc')
            ->limit(5);

        return $query->get()->map(function ($invoice) {
            return [
                'id' => $invoice->id,
                'invoice_no' => $invoice->invoice_no,
                'order_no' => $invoice->order->order_no,
                'status' => $invoice->status,
                'total' => $invoice->total_snapshot,
                'issued_at' => $invoice->issued_at->format('Y-m-d'),
            ];
        })->toArray();
    }

    /**
     * Get latest offers
     */
    protected function getLatestOffers(array $filters): array
    {
        $query = Offer::query()
            ->where('company_user_id', $this->companyUserId)
            ->orderBy('created_at', 'desc')
            ->limit(5);

        return $query->get()->map(function ($offer) {
            return [
                'id' => $offer->id,
                'title' => $offer->title,
                'company' => '', // Empty for company dashboard
                'scope' => $offer->scope,
                'status' => $offer->status,
                'start_at' => $offer->start_at?->format('Y-m-d'),
                'end_at' => $offer->end_at?->format('Y-m-d'),
            ];
        })->toArray();
    }

    /**
     * Get latest products
     */
    protected function getLatestProducts(array $filters): array
    {
        $query = Product::query()
            ->where('company_user_id', $this->companyUserId)
            ->orderBy('created_at', 'desc')
            ->limit(5);

        return $query->get()->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'company' => '', // Empty for company dashboard
                'base_price' => round($product->base_price, 2),
                'is_active' => $product->is_active,
            ];
        })->toArray();
    }

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
            'weekly' => '%Y-W%v',
            'monthly' => '%Y-%m',
            default => '%Y-%m-%d',
        };
    }

    protected function getCacheKey(array $filters): string
    {
        return 'company_dashboard_stats_' . $this->companyUserId . '_' . md5(json_encode($filters));
    }

    /**
     * Clear dashboard cache
     */
    public function clearCache(): void
    {
        Cache::flush();
    }
}
