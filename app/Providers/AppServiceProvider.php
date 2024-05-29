<?php

namespace App\Providers;
use Illuminate\Support\Facades\App;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Illuminate\Support\Facades\URL;

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
    public function boot(): void
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
        if(App::environment('production'))
        {
            URL::forceScheme('https');
        }
    }
}
