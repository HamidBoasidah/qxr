<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingConflictException extends Exception
{
    protected array $conflictingBookings;
    protected string $errorType;

    public function __construct(
        string $message = 'Booking conflict detected',
        array $conflictingBookings = [],
        string $errorType = 'booking_conflict',
        int $code = 409
    ) {
        parent::__construct($message, $code);
        $this->conflictingBookings = $conflictingBookings;
        $this->errorType = $errorType;
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(Request $request): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $this->getMessage(),
            'error_type' => $this->errorType,
            'errors' => [$this->getMessage()]
        ];

        if (!empty($this->conflictingBookings)) {
            $response['conflicting_bookings'] = $this->conflictingBookings;
        }

        return response()->json($response, $this->getCode());
    }

    /**
     * Get conflicting bookings
     */
    public function getConflictingBookings(): array
    {
        return $this->conflictingBookings;
    }

    /**
     * Get error type
     */
    public function getErrorType(): string
    {
        return $this->errorType;
    }
}