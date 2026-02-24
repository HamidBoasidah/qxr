<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير الفواتير - Invoices Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #333;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #4A5568;
        }
        
        .header h1 {
            font-size: 24px;
            color: #2D3748;
            margin-bottom: 8px;
        }
        
        .header .subtitle {
            font-size: 14px;
            color: #718096;
        }
        
        .summary {
            background: #F7FAFC;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 25px;
        }
        
        .summary h2 {
            font-size: 16px;
            margin-bottom: 10px;
            color: #2D3748;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        
        .summary-item {
            padding: 8px;
            background: white;
            border-radius: 3px;
            border: 1px solid #E2E8F0;
        }
        
        .summary-label {
            font-size: 11px;
            color: #718096;
            margin-bottom: 3px;
        }
        
        .summary-value {
            font-size: 14px;
            font-weight: bold;
            color: #2D3748;
        }
        
        .table-container {
            margin-top: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        
        thead {
            background: #4A5568;
            color: white;
        }
        
        th, td {
            padding: 10px 8px;
            text-align: right;
            border: 1px solid #E2E8F0;
        }
        
        th {
            font-weight: bold;
            font-size: 11px;
        }
        
        tbody tr:nth-child(even) {
            background: #F7FAFC;
        }
        
        .status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
        }
        
        .status-paid {
            background: #C6F6D5;
            color: #22543D;
        }
        
        .status-unpaid {
            background: #FED7D7;
            color: #742A2A;
        }
        
        .status-void {
            background: #E2E8F0;
            color: #4A5568;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #E2E8F0;
            text-align: center;
            font-size: 10px;
            color: #A0AEC0;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #A0AEC0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>تقرير الفواتير / Invoices Report</h1>
        <div class="subtitle">{{ $generated_at }}</div>
    </div>

    <div class="summary">
        <h2>ملخص التقرير / Summary</h2>
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-label">إجمالي الفواتير / Total Invoices</div>
                <div class="summary-value">{{ number_format($summary['total_count']) }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">مدفوعة / Paid</div>
                <div class="summary-value">{{ number_format($summary['paid_count']) }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">غير مدفوعة / Unpaid</div>
                <div class="summary-value">{{ number_format($summary['unpaid_count']) }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">ملغية / Void</div>
                <div class="summary-value">{{ number_format($summary['void_count']) }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">إجمالي الإيرادات المدفوعة / Paid Revenue</div>
                <div class="summary-value">{{ number_format($summary['paid_revenue'], 2) }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">إجمالي المبالغ غير المدفوعة / Unpaid Amount</div>
                <div class="summary-value">{{ number_format($summary['unpaid_amount'], 2) }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">إجمالي الخصومات / Total Discounts</div>
                <div class="summary-value">{{ number_format($summary['total_discounts'], 2) }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">المبلغ الإجمالي / Total Amount</div>
                <div class="summary-value">{{ number_format($summary['total_amount'], 2) }}</div>
            </div>
        </div>
    </div>

    <div class="table-container">
        @if(count($invoices) > 0)
            <table>
                <thead>
                    <tr>
                        <th>رقم الفاتورة<br>Invoice No</th>
                        <th>رقم الطلب<br>Order No</th>
                        <th>الشركة<br>Company</th>
                        <th>العميل<br>Customer</th>
                        <th>المجموع الفرعي<br>Subtotal</th>
                        <th>الخصم<br>Discount</th>
                        <th>الإجمالي<br>Total</th>
                        <th>الحالة<br>Status</th>
                        <th>تاريخ الإصدار<br>Issued At</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoices as $invoice)
                        <tr>
                            <td>{{ $invoice['invoice_no'] }}</td>
                            <td>{{ $invoice['order_no'] ?? '—' }}</td>
                            <td>{{ $invoice['company_name'] ?? '—' }}</td>
                            <td>{{ $invoice['customer_name'] ?? '—' }}</td>
                            <td>{{ number_format($invoice['subtotal'], 2) }}</td>
                            <td>{{ number_format($invoice['discount'], 2) }}</td>
                            <td>{{ number_format($invoice['total'], 2) }}</td>
                            <td>
                                <span class="status status-{{ $invoice['status'] }}">
                                    @if($invoice['status'] === 'paid')
                                        مدفوع / Paid
                                    @elseif($invoice['status'] === 'unpaid')
                                        غير مدفوع / Unpaid
                                    @else
                                        ملغي / Void
                                    @endif
                                </span>
                            </td>
                            <td>{{ $invoice['issued_at'] ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="no-data">
                <p>لا توجد بيانات متاحة / No data available</p>
            </div>
        @endif
    </div>

    <div class="footer">
        <p>Powered by QXR System</p>
    </div>
</body>
</html>
