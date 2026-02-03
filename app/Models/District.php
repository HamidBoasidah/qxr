<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;

class District extends BaseModel
{
    use HasFactory;
    protected $fillable = [
        'name_ar',
        'name_en',
        'is_active',
        'governorate_id',
    ];

    // لا توجد خصائص تحويل إضافية مطلوبة هنا

    public function governorate()
    {
        return $this->belongsTo(Governorate::class);
    }

    public function areas()
    {
        return $this->hasMany(Area::class);
    }

}
