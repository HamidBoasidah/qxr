<?php

namespace App\Http\Controllers;

use App\Http\Requests\SetLocaleRequest;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;

class LocaleController extends Controller
{
    public function __invoke(SetLocaleRequest $request): Response|JsonResponse
    {
        $locale = $request->locale();

        // Session + Cookie
        session(['locale' => $locale]);
        
        // تحديث اللغة للمستخدم المسجل دخوله (User أو Admin)
        $user = null;
        
        // التحقق من Admin guard أولاً
        if (auth('admin')->check()) {
            $user = auth('admin')->user();
        }
        // ثم التحقق من User guard
        elseif (auth('web')->check()) {
            $user = auth('web')->user();
        }
        
        if ($user) {
            $tableName = $user->getTable(); // 'users' أو 'admins'
            
            if (Schema::hasColumn($tableName, 'locale')) {
                $user->forceFill(['locale' => $locale])->save();
            }
        }
        
        // إذا كان الطلب من Inertia، أعد JSON response
        if ($request->header('X-Inertia')) {
            return response()->json([
                'message' => 'Locale updated successfully',
                'locale' => $locale
            ])->cookie(
                'locale', $locale, 60*24*365, '/', null, false, false, false, 'lax'
            );
        }
        
        // للطلبات العادية، أعد 204 No Content
        return response()->noContent(204)->cookie(
            'locale', $locale, 60*24*365, '/', null, false, false, false, 'lax'
        );
    }
}