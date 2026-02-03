<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


abstract class BaseModel extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'created_by',
        'updated_by',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * إعدادات الـ Activity Log لهذا الـ Model
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            // اسم الدفتر (log) — يمكنك تغييره لاحقًا لكل موديل إذا رغبت
            ->useLogName(class_basename(static::class))
            // سجّل فقط الحقول التي تتغير (Dirty)
            ->logOnlyDirty()
            // لا تُنشئ لوج إن لم تتغير القيم فعلاً
            ->dontSubmitEmptyLogs()
            // حدّد ما الذي يُسجل: نجمع fillable الخاصة بالموديل الابن + الحقول المشتركة هنا
            ->logOnly($this->getLoggableAttributes());
    }

    /**
     * تحديد الحقول التي سنسجلها (يقرأ fillable من الموديل الابن ويضيف المشترك)
     */
    protected function getLoggableAttributes(): array
    {
        // الحقول المشتركة التي لا نريد تسجيلها
        $ignore = ['created_by', 'updated_by'];

        // لو الموديل الابن عرف خاصية dontLog ندمجها مع قائمة التجاهل
        if (property_exists($this, 'dontLog')) {
            $ignore = array_merge($ignore, $this->dontLog);
        }

        // جميع الحقول القابلة للتعبئة + is_active
        $attributes = array_unique(array_merge($this->fillable, ['is_active']));

        // نستبعد الأعمدة التي لا نريد تسجيلها
        return array_values(array_diff($attributes, $ignore));
    }


    /**
     * نص الوصف في خانة description لكل حدث (created/updated/deleted)
     */
    public function getDescriptionForEvent(string $eventName): string
    {
        $user = optional(request()->user())->name ?? __('activitylog.system');
        $model = __('models.' . class_basename(static::class));

        return __('activitylog.' . $eventName, compact('user', 'model'));
    }
}
