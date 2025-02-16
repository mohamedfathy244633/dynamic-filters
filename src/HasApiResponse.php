<?php

namespace MohamedFathy\DynamicFilters;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

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
            'updatedData' => 'array',
        ]);
    }

    /**
     * Unified response handler.
     */
    protected function response(
        mixed $result,
        array $params = []
    ): JsonResponse {

        $params = array_merge([
            'status' => 200,
            'message' => 'Success',
            'page' => 1,
            'perPage' => 10,
            'type' => 'paginate'
        ], $params);

        $data = null;
        if ($result instanceof Builder) {
            match ($params['type']) {
                'get' => $data = $result->get(),
                'first' => $data = $result->first(),
                default => $data = $result->paginate($params['perPage'], ['*'], 'page', $params['page']),
            };
        } else {
            $data = $result;
        }

        $class = $this->getMainResourceClass() ?: $this->getDefaultResourceClass();

        $responseData = [
            'status' => 'success',
            'message' => $params['message'],
            'data' => $data instanceof LengthAwarePaginator
                ? ($class ? $class::collection($data) : $data->items())
                : ($class ? new $class($data) : $data),
        ];

        // Add pagination details if the data is paginated
        if ($data instanceof LengthAwarePaginator) {
            $responseData['pagination'] = [
                'current_page' => $data->currentPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'last_page' => $data->lastPage(),
            ];
        }

        return response()->json($responseData, $params['status']);
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
