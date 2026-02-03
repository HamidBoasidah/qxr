<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomerProfile extends Model
{
    use HasFactory;

    protected $table = 'customer_profiles';

    protected $fillable = [
        'user_id',
        'business_name',
        'category_id',
        'main_address_id',
        'is_active',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // حساب المستخدم (العميل)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // تصنيف العميل
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // العنوان الرئيسي
    public function mainAddress()
    {
        return $this->belongsTo(Address::class, 'main_address_id');
    }
}