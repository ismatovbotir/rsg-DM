<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // @kb on an input: suppress the Android on-screen keyboard (inputmode="none") unless the device is set to
        // "keyboard on". The layout script decides the final mode on the client and adds the keyboard button.
        // Livewire re-runs these route middleware on its update requests too (admin pages, deactivated users, the PDA device profile).
        \Livewire\Livewire::addPersistentMiddleware([
            \App\Http\Middleware\EnsureAdmin::class,
            \App\Http\Middleware\EnsureActive::class,
            \App\Http\Middleware\DeviceProfile::class,
            \App\Http\Middleware\EnsureMenuAccess::class,
        ]);

        Blade::directive('kb', fn () => '<?php echo ($kbOff ?? true) ? \'inputmode="none"\' : \'\'; ?>');
    }
}
