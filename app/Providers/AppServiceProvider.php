<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        Model::unguard();
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $locale = config('app.locale')?? 'en';
        Inertia::share([
            'locale' => $locale,
            'language' => function () use ($locale) {
                return translations(
                    resource_path('lang/'. $locale .'.json')
                );
            },
            'dir' => function () use ($locale) {
                $rtlCodes = ['sa','he','ur'];
                return in_array($locale, $rtlCodes) ? 'rtl' : 'ltr';
            }
        ]);
    }
}
