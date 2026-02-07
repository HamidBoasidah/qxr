<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CompanyProfile extends Model
{
    use HasFactory;

    protected $table = 'company_profiles';

    protected $fillable = [
        'user_id',
        'company_name',
        'category_id',
        'logo_path',
        'is_active',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // حساب المستخدم (الشركة)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // تصنيف الشركة
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

}