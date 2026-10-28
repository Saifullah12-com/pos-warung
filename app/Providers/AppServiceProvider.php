<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Carbon::setLocale('id');

        // Pemakaian di Blade: @rupiah($angka)  =>  Rp18.000
        Blade::directive('rupiah', function ($expression) {
            return "<?php echo 'Rp'.number_format((int) ({$expression}), 0, ',', '.'); ?>";
        });
    }
}
