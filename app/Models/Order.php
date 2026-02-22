<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_no',
        'company_user_id',
        'customer_user_id',
        'status',
        'submitted_at',
        'approved_at',
        'approved_by_user_id',
        'delivered_at',
        'delivery_address_id',
        'notes_customer',
        'notes_company',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(User::class, 'company_user_id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_user_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusLogs()
    {
        return $this->hasMany(OrderStatusLog::class);
    }

    public function deliveryAddress()
    {
        return $this->belongsTo(Address::class, 'delivery_address_id');
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }
}
