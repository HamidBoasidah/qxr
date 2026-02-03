<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;

trait SuccessResponse
{
    /**
     * Return success response with data and message
     */
    protected function successResponse(
        $data = null, 
        string $message = 'تمت العملية بنجاح', 
        int $statusCode = 200
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
            'status_code' => $statusCode
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return success response for created resource
     */
    protected function createdResponse(
        $data = null, 
        string $message = 'تم إنشاء المورد بنجاح'
    ): JsonResponse {
        return $this->successResponse($data, $message, 201);
    }

    /**
     * Return success response for updated resource
     */
    protected function updatedResponse(
        $data = null, 
        string $message = 'تم تحديث المورد بنجاح'
    ): JsonResponse {
        return $this->successResponse($data, $message, 200);
    }

    /**
     * Return success response for deleted resource
     */
    protected function deletedResponse(
        string $message = 'تم حذف المورد بنجاح'
    ): JsonResponse {
        return $this->successResponse(null, $message, 200);
    }

    /**
     * Return success response for activated resource
     */
    protected function activatedResponse(
        $data = null, 
        string $message = 'تم تفعيل المورد بنجاح'
    ): JsonResponse {
        return $this->successResponse($data, $message, 200);
    }

    /**
     * Return success response for deactivated resource
     */
    protected function deactivatedResponse(
        $data = null, 
        string $message = 'تم تعطيل المورد بنجاح'
    ): JsonResponse {
        return $this->successResponse($data, $message, 200);
    }

    /**
     * Return success response for accepted resource
     */
    protected function acceptedResponse(
        $data = null, 
        string $message = 'تم قبول المورد بنجاح'
    ): JsonResponse {
        return $this->successResponse($data, $message, 200);
    }

    /**
     * Return success response for collection
     */
    protected function collectionResponse(
        $collection, 
        string $message = 'تم جلب البيانات بنجاح'
    ): JsonResponse {
        // إذا كانت البيانات عبارة عن Paginator أو LengthAwarePaginator.
        if ($collection instanceof \Illuminate\Pagination\Paginator || $collection instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $response = [
                'success' => true,
                'message' => $message,
                'status_code' => 200,
                'data' => $collection->items(),
                'pagination' => [
                    'current_page' => $collection->currentPage(),
                    'per_page' => $collection->perPage(),
                    'total' => $collection->total(),
                    'last_page' => $collection->lastPage(),
                ]
            ];
            return response()->json($response, 200);
        }
        // إذا لم تكن البيانات paginated
        return $this->successResponse($collection, $message, 200);
    }

    /**
     * Return success response for single resource
     */
    protected function resourceResponse(
        $resource, 
        string $message = 'تم جلب المورد بنجاح'
    ): JsonResponse {
        return $this->successResponse($resource, $message, 200);
    }
} 