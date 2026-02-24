# Admin Reports Module - Complete Implementation

## Overview
A comprehensive reporting system for admin users with multi-format export capabilities (Excel, PDF, Word).

## Features Implemented

### 1. Report Types
- **Invoices Report**: Financial overview with revenue tracking
- **Orders Report**: Order management and fulfillment tracking  
- **Offers Report**: Marketing campaign analysis
- **Products Report**: Inventory and pricing overview

### 2. Filtering Capabilities
All reports support:
- **Date Presets**: Today, Yesterday, Last 7 Days, Last 30 Days, This Month, Last Month
- **Status Filtering**: Context-specific status options per report type
- **Company/Customer Filtering**: Where applicable
- **Search**: Context-aware search across relevant fields

### 3. Export Formats
- **Excel (.xlsx)**: Includes summary header and formatted data tables
- **PDF (.pdf)**: Bilingual (Arabic/English) with professional styling
- **Word (.docx)**: Structured document with tables and summaries

## Files Created/Modified

### Backend (Laravel)

#### Routes
- `routes/admin.php` - Added reports routes group:
  ```php
  GET  /admin/reports                  → index (reports overview)
  GET  /admin/reports/invoices         → invoices report
  GET  /admin/reports/invoices/export  → export invoices
  GET  /admin/reports/orders           → orders report
  GET  /admin/reports/orders/export    → export orders
  GET  /admin/reports/offers           → offers report
  GET  /admin/reports/offers/export    → export offers
  GET  /admin/reports/products         → products report
  GET  /admin/reports/products/export  → export products
  ```

#### Controllers
- `app/Http/Controllers/Admin/ReportController.php`
  - `index()`: Reports overview page
  - `invoices()`, `orders()`, `offers()`, `products()`: Report pages
  - `exportInvoices()`, `exportOrders()`, etc.: Export handlers

#### Services
- `app/Services/Reports/InvoicesReportService.php`
- `app/Services/Reports/OrdersReportService.php`
- `app/Services/Reports/OffersReportService.php`
- `app/Services/Reports/ProductsReportService.php`

Each service provides:
- `generate($filters, $perPage)`: Paginated data + summary
- `export($filters, $format)`: Multi-format export

#### Utilities
- `app/Support/ReportFilters.php`: Unified filter application
  - `apply($query, $filters)`: Main orchestrator
  - `applyDatePreset($query, $preset, $model)`: Date range handling
  - `applyCompanyFilter($query, $companyId, $model)`: Company filtering
  - `applyCustomerFilter($query, $customerId, $model)`: Customer filtering
  - `applySearch($query, $search, $model)`: Model-aware search
  - `clean($filters)`: Remove empty filter values

#### Export Classes
- `app/Exports/InvoicesReportExport.php`
- `app/Exports/OrdersReportExport.php`
- `app/Exports/OffersReportExport.php`
- `app/Exports/ProductsReportExport.php`

#### PDF Templates (Blade)
- `resources/views/reports/invoices-pdf.blade.php` (created as template)
- Similar templates needed for orders, offers, products (follow same pattern)

### Frontend (Vue 3 + Inertia)

#### Pages
- `resources/js/pages/Admin/Reports/Index.vue`: Reports dashboard
- `resources/js/pages/Admin/Reports/Invoices.vue`: Invoices report (complete)
- NOTE: Orders.vue, Offers.vue, Products.vue need to be created (replicate Invoices.vue pattern)

#### Translations
- `resources/js/locales/ar.json`: Added `reports` section
- `resources/js/locales/en.json`: Added `reports` section

## Usage

### Accessing Reports
1. Navigate to `/admin/reports` (requires admin authentication)
2. Select desired report type
3. Apply filters as needed
4. View paginated results with summary cards
5. Export to Excel/PDF/Word using header buttons

### Filter Examples
- Date: `?date_preset=last_30_days`
- Status: `?status=paid`
- Search: `?search=INV-2024`
- Combined: `?date_preset=this_month&status=paid&search=company`

### Export Examples
- Excel: `GET /admin/reports/invoices/export?format=excel&status=paid`
- PDF: `GET /admin/reports/invoices/export?format=pdf&date_preset=this_month`
- Word: `GET /admin/reports/invoices/export?format=word`

## Data Structure

### Invoices Report Summary
```json
{
  "total_count": 150,
  "paid_count": 120,
  "unpaid_count": 25,
  "void_count": 5,
  "paid_revenue": 125000.50,
  "unpaid_amount": 15000.75,
  "total_discounts": 5000.00,
  "total_amount": 140000.50
}
```

### Orders Report Summary
```json
{
  "total_count": 200,
  "pending_count": 30,
  "approved_count": 50,
  "delivered_count": 100,
  "cancelled_count": 20,
  "total_revenue": 250000.00
}
```

### Offers Report Summary
```json
{
  "total_count": 50,
  "active_count": 15,
  "inactive_count": 20,
  "expired_count": 15,
  "public_count": 30,
  "private_count": 20
}
```

### Products Report Summary
```json
{
  "total_count": 500,
  "active_count": 450,
  "inactive_count": 50,
  "avg_price": 125.50
}
```

## Dependencies Installed
```bash
composer require maatwebsite/excel      # v3.1.67
composer require barryvdh/laravel-dompdf # v3.1.1
composer require phpoffice/phpword       # v1.4.0
```

## TODO: Remaining Tasks

### 1. Create Missing Vue Pages
Clone `Invoices.vue` structure for:
- `resources/js/pages/Admin/Reports/Orders.vue`
- `resources/js/pages/Admin/Reports/Offers.vue`
- `resources/js/pages/Admin/Reports/Products.vue`

**Adjustments per page:**
- Update props/computed (orders, offers, products)
- Update summary cards (match service summary structure)
- Update table columns (match service data transformation)
- Update status badges (order statuses: pending/approved/delivered/cancelled, offer: active/inactive/expired)
- Update translation keys

### 2. Create PDF Blade Templates
Clone `invoices-pdf.blade.php` for:
- `resources/views/reports/orders-pdf.blade.php`
- `resources/views/reports/offers-pdf.blade.php`
- `resources/views/reports/products-pdf.blade.php`

**Adjustments per template:**
- Update summary cards (match service summary)
- Update table columns
- Update title and headers

### 3. Add Menu Links
Add reports link to admin navigation:
```vue
// In resources/js/components/layout/AdminLayout.vue or similar
{
  name: 'reports.title',
  route: 'admin.reports.index',
  icon: ChartBarIcon // or similar
}
```

### 4. Testing Checklist
- [ ] Access reports index page
- [ ] Each report loads with data
- [ ] Filters apply correctly
- [ ] Pagination works
- [ ] Summary cards calculate accurately
- [ ] Excel export downloads with formatted data
- [ ] PDF export generates bilingual document
- [ ] Word export creates structured document
- [ ] Filtering persists across pagination
- [ ] Search works for all relevant fields

## Architecture Patterns

### Service Layer
- All business logic in services (not controllers)
- Each service handles: data generation, summary calculation, exports
- Consistent method signatures across all report services

### Filter Utility
- Centralized filter logic in `ReportFilters`
- Model-aware (different date columns per model)
- Relationship-aware (invoices filter via order relationship)
- Clean separation of concerns

### Export Strategy
- Excel: maatwebsite/excel with custom classes
- PDF: Blade templates + dompdf (bilingual support)
- Word: phpoffice/phpword programmatic generation
- All exports include summary + filtered data

### Frontend Pattern
- Summary cards at top
- Collapsible filter section
- Export buttons in card header
- Paginated table with proper styling
- Locale-formatted numbers (en-US)

## Security Considerations
- All routes protected with `auth:admin` middleware
- Filter inputs cleaned via `ReportFilters::clean()`
- SQL injection prevented by Eloquent query builder
- Export files temporarily stored and auto-deleted after download
- No sensitive data in URLs (POST could be used for complex filters)

## Performance Notes
- Pagination limits memory usage (default 15 per page)
- Exports fetch all records but stream to file
- Summary calculations use separate queries (cloned)
- Eager loading prevents N+1 queries
- Consider caching for frequently accessed reports

## Localization
All text is fully bilingual:
- UI: Arabic (default) + English
- PDF exports: Both languages side-by-side
- Number formatting: en-US locale (as per project standards)
- Date formatting: Y-m-d H:i (consistent across reports)

## Extension Points

### Adding New Report Type
1. Create service: `app/Services/Reports/NewReportService.php`
2. Create export: `app/Exports/NewReportExport.php`
3. Create PDF template: `resources/views/reports/new-pdf.blade.php`
4. Add routes in `routes/admin.php`
5. Add methods in `ReportController.php`
6. Create Vue page: `resources/js/pages/Admin/Reports/New.vue`
7. Add card to Index.vue
8. Add translations

### Adding New Filter Type
Add method to `ReportFilters.php`:
```php
public static function applyNewFilter($query, $value, $model)
{
    // Implementation
    return $query;
}
```

Call in `apply()` method:
```php
if (isset($filters['new_filter'])) {
    $query = static::applyNewFilter($query, $filters['new_filter'], $model);
}
```

## Support
For issues or questions:
- Check Laravel logs: `storage/logs/laravel.log`
- Check browser console for frontend errors
- Verify export packages installed: `composer show | grep -E "maatwebsite|dompdf|phpword"`
- Validate routes: `php artisan route:list --name=admin.reports`
