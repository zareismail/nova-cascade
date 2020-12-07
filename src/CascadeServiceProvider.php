<?php

namespace Zareismail\Fields;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Nova;

class CascadeServiceProvider extends ServiceProvider implements DeferrableProvider
{

    /**
     * Get the events that trigger this service provider to register.
     *
     * @return array
     */
    public function when()
    {
        return [
            ServingNova::class,
        ];
    } 

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        Nova::script('zareismail-cascade-field', __DIR__.'/../dist/js/field.js'); 
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
