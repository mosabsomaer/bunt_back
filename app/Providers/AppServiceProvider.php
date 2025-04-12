<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //

		$this->app->bind(
			\App\Interfaces\TestServiceInterface::class,
			\App\Services\TestService::class
		);

		$this->app->bind(
			\App\Interfaces\OrderServiceInterface::class,
			\App\Services\OrderService::class
		);

		$this->app->bind(
			\App\Interfaces\FileServiceInterface::class,
			\App\Services\FileService::class
		);

		$this->app->bind(
			\App\Interfaces\MachineServiceInterface::class,
			\App\Services\MachineService::class
		);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
