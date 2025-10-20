<?php

namespace App\Providers;

use App\Models\Module;
use App\Support\AppSettingManager;
use App\Support\Debug\DebugTimeline;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Carbon;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(DebugTimeline::class, function ($app) {
            return new DebugTimeline((bool) $app['config']->get('app.debug'));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale(config('app.locale', 'en'));

        View::share('appSettings', AppSettingManager::current());

        Module::syncFromConfig();

        if (config('app.debug')) {
            View::composer('*', function ($view): void {
                $view->with('debugTimeline', app(DebugTimeline::class));
            });
        }
    }
}
