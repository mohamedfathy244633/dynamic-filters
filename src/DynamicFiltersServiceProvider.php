<?php

namespace MohamedFathy\DynamicFilters;

use Illuminate\Support\ServiceProvider;

class DynamicFiltersServiceProvider extends ServiceProvider
{
    public function boot()
    {
        //
    }

    public function register()
    {
        $this->app->bind(APIException::class, function ($app) {
            return new APIException();
        });
    }
}
