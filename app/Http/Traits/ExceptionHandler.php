<?php

namespace App\Http\Traits;

use App\Exceptions\NotFoundException;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\BusinessLogicException;
use App\Exceptions\ResourceInUseException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
// Logging removed per project policy

trait ExceptionHandler
{
    /**
     * Throw not found exception
     */
    protected function throwNotFoundException(string $message = 'المورد المطلوب غير موجود'): void
    {
        throw new NotFoundException($message);
    }

    /**
     * Throw unauthorized exception
     */
    protected function throwUnauthorizedException(string $message = 'غير مصرح لك بالوصول لهذا المورد'): void
    {
        throw new UnauthorizedException($message);
    }

    /**
     * Throw forbidden exception
     */
    protected function throwForbiddenException(string $message = 'ممنوع الوصول لهذا المورد'): void
    {
        throw new ForbiddenException($message);
    }

    /**
     * Throw business logic exception
     */
    protected function throwBusinessLogicException(string $message = 'خطأ في منطق الأعمال'): void
    {
        throw new BusinessLogicException($message);
    }

    /**
     * Throw resource in use exception
     */
    protected function throwResourceInUseException(string $message = 'المورد مستخدم ولا يمكن حذفه'): void
    {
        throw new ResourceInUseException($message);
    }

    /**
     * Check if model exists or throw not found exception
     */
    protected function findOrFail(?Model $model, ?string $message = null): Model
    {
        if (!$model) {
            throw new NotFoundException($message ?? 'المورد المطلوب غير موجود');
        }

        return $model;
    }

    /**
     * Validate business logic
     */
    protected function validateBusinessLogic(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new BusinessLogicException($message);
        }
    }

    /**
     * Handle database exceptions and transform them to appropriate custom exceptions
     * 
     * @param QueryException $e The database exception
     * @param Model $model The model being operated on
     * @param array $relationshipChecks Array of relationship names to check (e.g., ['bookings' => 'حجوزات'])
     * @throws ResourceInUseException
     * @throws BusinessLogicException
     * @throws QueryException
     */
    protected function handleDatabaseException(
        QueryException $e, 
        Model $model, 
        array $relationshipChecks = []
    ): void {
        // Database exception occurred (logging removed)
        
        // Check for foreign key constraint violation
        if ($e->getCode() == 23000) {
            // Check if it's a duplicate entry error
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                throw new BusinessLogicException('البيانات المراد إدخالها موجودة بالفعل');
            }
            
            // Otherwise it's a foreign key constraint
            $message = $this->buildRelationshipErrorMessage($model, $relationshipChecks);
            throw new ResourceInUseException($message);
        }
        
        // Re-throw for other database errors
        throw $e;
    }

    /**
     * Build a descriptive error message for relationship constraints
     * 
     * @param Model $model The model being operated on
     * @param array $relationshipChecks Array of relationship names to check
     * @return string The error message in Arabic
     */
    protected function buildRelationshipErrorMessage(
        Model $model, 
        array $relationshipChecks
    ): string {
        $modelName = class_basename($model);
        $arabicModelName = $this->getArabicModelName($modelName);
        
        foreach ($relationshipChecks as $relationship => $arabicName) {
            if (method_exists($model, $relationship) && $model->$relationship()->exists()) {
                return "لا يمكن حذف {$arabicModelName} مرتبط بـ {$arabicName}";
            }
        }
        
        return "لا يمكن حذف {$arabicModelName} لوجود مراجع مرتبطة به في قاعدة البيانات";
    }

    /**
     * Get Arabic name for model
     * 
     * @param string $modelName The model class name
     * @return string The Arabic name
     */
    protected function getArabicModelName(string $modelName): string
    {
        $arabicNames = [
            'Address' => 'العنوان',
            'Admin' => 'المشرف',
            'Area' => 'المنطقة',
            'BaseModel' => 'النموذج الأساسي',
            'Booking' => 'الحجز',
            'BookingTransaction' => 'معاملة الحجز',
            'Category' => 'الفئة',
            'Chef' => 'الطاهي',
            'ChefCategory' => 'فئة الطاهي',
            'ChefGallery' => 'معرض الطاهي',
            'ChefServiceRating' => 'تقييم خدمة الطاهي',
            'ChefService' => 'خدمة الطاهي',
            'ChefServiceImage' => 'صورة خدمة الطاهي',
            'ChefServiceTag' => 'وسم خدمة الطاهي',
            'ChefWallet' => 'محفظة الطاهي',
            'ChefWalletTransaction' => 'معاملة محفظة الطاهي',
            'ChefWithdrawalRequest' => 'طلب سحب الطاهي',
            'District' => 'المديرية',
            'Governorate' => 'المحافظة',
            'Kyc' => 'التحقق من الهوية',
            'Permission' => 'الصلاحية',
            'Role' => 'الدور',
            'Tag' => 'الوسم',
            'User' => 'المستخدم',
            'WithdrawalMethod' => 'طريقة السحب',
        ];
        
        return $arabicNames[$modelName] ?? 'المورد';
    }
} 