<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use App\Models\websiteSetting;
use View;
use Validator;

class AppServiceProvider extends ServiceProvider {

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {
        Schema::defaultStringLength(191);
        $websiteSetting = websiteSetting::first();
        View::share('websiteSetting', $websiteSetting);
        Validator::extend('video', function ($attribute, $value, $parameters, $validator) {
            // validation logic, e.g
            return filter_var($value, FILTER_VALIDATE_URL);
        });
        Validator::extend('alpha_spaces', function ($attribute, $value) {
            return preg_match('/^[\pL\s]+$/u', $value);
        });

        //
    }

}
