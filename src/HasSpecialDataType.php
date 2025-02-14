<?php

namespace MohamedFathy\DynamicFilters;

use App\Services\MediaService;
use Illuminate\Support\Facades\Hash;

trait HasSpecialDataType
{
    public function __get($key)
    {
        return match (true) {
            $this->isJsonColumn($key) => $this->decodeJsonAttribute($key),
            $this->isFileColumn($key) => $this->resolveFileUrl($key),
            $this->isBooleanColumn($key) => $this->resolveBooleanAttribute($key),
            default => parent::__get($key),
        };
    }

    public function setAttribute($key, $value)
    {
        match (true) {
            $this->isJsonColumn($key) => $this->encodeJsonAttribute($key, $value),
            $this->isPasswordColumn($key) => $this->hashPasswordAttribute($key, $value),
            $this->isFileColumn($key) => $this->processFileUpload($key, $value),
            $this->isBooleanColumn($key) => $this->attributes[$key] = (bool) $value,
            default => parent::setAttribute($key, $value),
        };
    }

    protected function isJsonColumn(string $key): bool
    {
        return isset($this->jsonColumns) && in_array($key, $this->jsonColumns, true);
    }

    protected function isPasswordColumn(string $key): bool
    {
        return isset($this->passwordColumns) && in_array($key, $this->passwordColumns, true);
    }

    protected function isFileColumn(string $key): bool
    {
        return isset($this->fileColumns) && in_array($key, $this->fileColumns, true);
    }

    protected function isBooleanColumn(string $key): bool
    {
        return isset($this->booleanColumns) && in_array($key, $this->booleanColumns, true);
    }

    protected function decodeJsonAttribute(string $key): mixed
    {
        return json_decode($this->attributes[$key] ?? 'null', true);
    }

    protected function resolveFileUrl(string $key): ?string
    {
        return !empty($this->attributes[$key])
            ? config('modules.media.storage_url') . $this->attributes[$key]
            : null;
    }

    protected function resolveBooleanAttribute(string $key): bool
    {
        return (bool) ($this->attributes[$key] ?? false);
    }

    protected function encodeJsonAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = is_array($value) ? json_encode($value) : $value;
    }

    protected function hashPasswordAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = Hash::make($value);
    }

    protected function processFileUpload(string $key, mixed $value): void
    {
        $mediaService = new MediaService();
        $params = $mediaService->handleUploadFile([
            'file' => $value,
            'disk' => $this->fileColumns['disk'] ?? config('modules.media.storage_disk'),
            'directory' => $this->fileColumns['directory'] ?? config('modules.media.storage_directory'),
        ]);

        $params['directory'] = str_replace('public/', '', $params['directory']);
        $this->attributes[$key] = "{$params['directory']}/{$params['file_name']}";
    }
}
