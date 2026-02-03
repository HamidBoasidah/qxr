<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;

class Governorate extends BaseModel
{
    use HasFactory;
    protected $fillable = [
        'name_ar',
        'name_en',
        'is_active',
    ];

    // لا توجد خصائص تحويل إضافية مطلوبة هنا

    public function districts()
    {
        return $this->hasMany(District::class);
    }

    public function areas()
    {
        return $this->hasManyThrough(Area::class, District::class);
    }

}
