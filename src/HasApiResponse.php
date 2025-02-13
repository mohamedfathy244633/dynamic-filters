<?php

namespace MohamedFathy\DynamicFilters;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

trait HasApiResponse
{
    public function validate($request)
    {
        return $request->validate([
            'page' => 'int|min:1',
            'perPage' => 'int|min:1',
            'orderBy' => 'string',
            'filters' => 'array',
            'relationFilters' => 'array',
            'customFilters' => 'array',
            'singleRecord' => 'string'
        ]);
    }

    /**
     * Unified response handler.
     */
    protected function response($query, $params = [], string $message = 'Success', int $status = 200): JsonResponse
    {
        if (!empty($params['singleRecord']) && $params['singleRecord'] == 'true') {
            $data = $query->first();
        } else {
            $perPage = $params['perPage'] ?? 10;
            $page = $params['page'] ?? 1;
            $data = $query->paginate($perPage, ['*'], 'page', $page);
        }

        $class = $this->getMainResourceClass() ?: $this->getDefaultResourceClass();

        if ($data instanceof LengthAwarePaginator) {
            $responseData = [
                'status' => 'success',
                'message' => $message,
                'data' => $class ? $class::collection($data) : $data->items(),
                'pagination' => [
                    'current_page' => $data->currentPage(),
                    'per_page' => $data->perPage(),
                    'total' => $data->total(),
                    'last_page' => $data->lastPage(),
                ],
            ];
        } elseif ($data) {
            $responseData = [
                'status' => 'success',
                'message' => $message,
                'data' => $class ? new $class($data) : $data,
            ];
        } else {
            $responseData = [
                'status' => 'success',
                'message' => $message,
                'data' => [],
            ];
        }

        return response()->json($responseData, $status);
    }

    public function json($response, $status = 200 ): JsonResponse
    {
        return response()->json($response, $status);
    }

    public function getMainResourceClass(): bool|string
    {
        $controllerName = str_replace(
            "Controller", '',
            str_replace("App\\Http\\Controllers\\", '', get_class(app('request')->route()->getController()))
        );

        $controllerMethod = app('request')->route()->getActionMethod();
        $class = "App\\Http\\Resources\\" . $controllerName . "\\" . ucfirst($controllerMethod) . 'Resource';

        return class_exists($class) ? $class : false;
    }

    public function getDefaultResourceClass(): bool|string
    {
        $controller = str_replace('Controller', '', get_class(app('request')->route()->getController()));
        $model = explode('\\', $controller);
        $modelName = end($model);

        $class = 'App\\Http\\Resources\\Base\\' . $modelName . 'Resource';
        return class_exists($class) ? $class : false;
    }
}
