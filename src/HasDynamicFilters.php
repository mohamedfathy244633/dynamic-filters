<?php

namespace MohamedFathy\DynamicFilters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

trait HasDynamicFilters
{
    /**
     * List of allowed operators for filtering.
     */
    protected array $allowedOperators = [
        'eq' => '=',
        'neq' => '!=',
        'gt' => '>',
        'lt' => '<',
        'gte' => '>=',
        'lte' => '<=',

        'like' => 'LIKE',
        'nLike' => 'NOT LIKE',

        'null' => 'IS NULL',
        'nNull' => 'IS NOT NULL',

        'in' => 'IN',
        'nIn' => 'NOT IN',

        'between' => 'BETWEEN',
        'nBetween' => 'NOT BETWEEN',

        'regexp' => 'REGEXP',
        'nRegexp' => 'NOT REGEXP',
    ];

    /**
     * Apply dynamic filters to the query.
     */
    public function scopeFilter(Builder $query, array $params): Builder
    {
        $this->applyFilters($query, $params['filters'] ?? []);
        $this->applyRelationFilters($query, $params['relationFilters'] ?? []);
        $this->applyCustomFilters($query, $params['customFilters'] ?? []);
        $this->applyOrdering($query, $params['orderBy'] ?? null);

        return $query;
    }

    /**
     * Apply standard column filters.
     * @throws \Exception
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        foreach ($filters as $keyWithOperator => $value) {
            [$key, $operator] = explode(':', $keyWithOperator) + [null, null];

            if (!$key || !$operator) {
                continue;
            }

            if (!in_array($key, $this->getAllowedFilters(), true)) {
                throw new \Exception("The filter '{$key}' is not allowed.");
            }

            $this->applyCondition($query, $key, $operator, $value);
        }
    }

    /**
     * Apply filters on relationships.
     * @throws \Exception
     */
    protected function applyRelationFilters(Builder $query, array $relationFilters): void
    {
        foreach ($relationFilters as $keyWithOperator => $value) {
            if (preg_match('/^([^\.]+)\.(.+)$/', $keyWithOperator, $matches)) {
                [$relation, $fieldWithOperator] = array_slice($matches, 1);
                [$field, $operator] = explode(':', $fieldWithOperator) + [null, null];

                if (!in_array($relation, $this->getAllowedRelations(), true)) {
                    throw new \Exception("The relation '{$relation}' is not allowed.");
                }

                $query->whereHas($relation, fn($q) => $this->applyCondition($q, $field, $operator, $value));
            }
        }
    }

    /**
     * Apply custom filters via a dedicated filter class.
     * @throws \Exception
     */
    protected function applyCustomFilters(Builder $query, array $filters): void
    {
        $filterClass = "App\\Filters\\" . class_basename(get_called_class()) . "Filters";

        if (!class_exists($filterClass)) {
            return;
        }

        $filterInstance = new $filterClass($query);

        foreach ($filters as $method => $value) {
            if (!method_exists($filterInstance, $method)) {
                throw new \Exception("Custom filter {$method} does not exist in " . get_class($filterInstance));
            }
            $filterInstance->$method($value);
        }
    }

    /**
     * Apply sorting on allowed columns.
     * @throws \Exception
     */
    protected function applyOrdering(Builder $query, ?string $orderBy): void
    {
        if ($orderBy) {
            $column = ltrim($orderBy, '-');

            if (!in_array($column, $this->getAllowedOrdering(), true)) {
                throw new \Exception("orderBy {$column} is not allowed.");
            }
            $query->orderBy($column, str_starts_with($orderBy, '-') ? 'desc' : 'asc');
        }
    }

    /**
     * Apply filter conditions to the query.
     */
    protected function applyCondition(Builder $query, string $key, string $operator, mixed $value): void
    {
        if (!isset($this->allowedOperators[$operator])) {
            return;
        }

        $sqlOperator = $this->allowedOperators[$operator];

        match ($operator) {
            'null' => $query->whereNull($key),
            'nNull' => $query->whereNotNull($key),
            'in', 'nIn' => $query->{$operator === 'in' ? 'whereIn' : 'whereNotIn'}($key, explode(',', $value)),
            'between', 'nBetween' => $query->{$operator === 'between' ? 'whereBetween' : 'whereNotBetween'}($key, explode(',', $value)),
            'like', 'nLike' => $query->where($key, $sqlOperator, "%$value%"),
            default => $query->where($key, $sqlOperator, $value),
        };
    }

    /**
     * Fetch records based on filter conditions.
     */
    public function scopeFetchRecords(Builder $query, array $params): Collection
    {
        return $this->scopeFilter($query, $params)->get();
    }

    /**
     * Fetch a single record based on filter conditions.
     */
    public function scopeFetchSingleRecord(Builder $query, array $params): ?Model
    {
        return $this->scopeFilter($query, $params)->first();
    }

    /**
     * Fetch aggregated records based on filter conditions.
     */
    public function scopeFetchAggregatedRecords(Builder $query, array $params, string $aggregationType, string $aggregationColumn): float
    {
        return (float)$this->scopeFilter($query, $params)->{$aggregationType}($aggregationColumn);
    }

    /**
     * Update multiple records.
     */
    public function scopeMultiUpdate(Builder $query, array $params): int
    {
        return $this->scopeFilter($query, $params)->update($params['updatedData'] ?? []);
    }

    /**
     * Delete multiple records.
     */
    public function scopeMultiDelete(Builder $query, array $params): int
    {
        return $this->scopeFilter($query, $params)->delete();
    }

    /**
     * Store a new record.
     */
    public static function storeRecord(array $params): static
    {
        $model = new static();
        $model->fill($params)->save();

        return $model;
    }

    /**
     * Update an existing record.
     */
    public static function updateRecord(array $params): Model
    {
        $model = static::findOrFail($params['id']);
        $model->fill($params)->save();

        return $model;
    }

    /**
     * Create or update a single record.
     */
    public function saveOne(array $params): Model
    {
        return isset($params['id']) ? $this->updateRecord($params) : $this->storeRecord($params);
    }

    /**
     * Create or update multiple records.
     * @example: $params = [['product_id'=> 11, 'quantity'=> 3],['product_id'=> 12, 'quantity'=> 2]]
     * @example: $extraParams = ['order_id' => '2']
     */
    public function saveMany(array $params, array $extraParams = []): void
    {
        foreach ($params as $param) {
            $this->saveOne(array_merge($param, $extraParams));
        }
    }

    /**
     * Delete all records in a many-to-many table and create new ones.
     * @example: $params = ['order_id' => ['1', '2', '3'], 'user_id' => '1']
     */
    public function saveManyToMany(array $params): void
    {
        $keys = array_keys($params);
        if (count($keys) < 2) {
            return;
        }

        [$keyOne, $keyTwo] = $keys;
        [$valuesOne, $valueTwo] = array_values($params);

        $this->delete([[$keyTwo, $valueTwo]]);

        $data = array_map(fn($item) => [$keyOne => $item, $keyTwo => $valueTwo], array_unique($valuesOne));
        $this->saveMany($data);
    }

    /**
     * Get allowed filters.
     */
    public function getAllowedFilters(): array
    {
        return array_diff($this->allowedFilters ?? [], ['password', 'api_token']);
    }

    /**
     * Get allowed relations.
     */
    public function getAllowedRelations(): array
    {
        return $this->allowedRelations ?? [];
    }

    /**
     * Get allowed ordering columns.
     */
    public function getAllowedOrdering(): array
    {
        return $this->allowedOrdering ?? [];
    }
}
