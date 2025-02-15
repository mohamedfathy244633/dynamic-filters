<?php

namespace MohamedFathy\DynamicFilters;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class APIException extends Exception
{
    /**
     * Render the exception into an HTTP response.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function render(Request $request): JsonResponse
    {
        $code = $this->getCode() != 0 ? $this->getCode() : 400;
        $response = [
            'code' => $code,
            'type' => 'general-error',
            'message' => __($this->getMessage())
        ];

        if (env('APP_DEBUG')) {
            $response['file'] = $this->getFile();
            $response['line'] = $this->getLine();
        }

        Log::debug(__CLASS__ . " " . __FUNCTION__ . " api-exception", $response);
        return response()->json($response, $code);
    }

}