<?php

namespace App\Providers;

use App\Contracts\EmployeeRepositoryInterface;
use App\Contracts\TrackTikServiceInterface;
use App\Http\Controllers\Api\Provider1EmployeeController;
use App\Http\Controllers\Api\Provider2EmployeeController;
use App\Repositories\EmployeeRepository;
use App\Services\Auth\OAuth2TokenManager;
use App\Services\Employee\EmployeeProcessingService;
use App\Services\Mapping\Provider1StatusMapper;
use App\Services\Mapping\Provider2StatusMapper;
use App\Services\Mapping\StatusMapperInterface;
use App\Services\Provider1EmployeeMapper;
use App\Services\Provider2EmployeeMapper;
use App\Services\TrackTik\TrackTikApiClient;
use App\Services\TrackTikService;
use App\Services\Provider\ProviderResolver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(EmployeeRepositoryInterface::class, EmployeeRepository::class);

        // TrackTik service bindings
        $this->app->bind(OAuth2TokenManager::class, function ($app) {
            $credentials = config('services.tracktik');
            return new OAuth2TokenManager(
                $credentials['token_url'],
                $credentials['client_id'],
                $credentials['client_secret'],
                $credentials['scope']
            );
        });

        $this->app->bind(TrackTikApiClient::class, function ($app) {
            $credentials = config('services.tracktik');
            return new TrackTikApiClient(
                $credentials['base_url'],
                $app->make(OAuth2TokenManager::class)
            );
        });

        $this->app->bind(TrackTikServiceInterface::class, TrackTikService::class);

        // Provider resolver
        $this->app->singleton(ProviderResolver::class, function () {
            return new ProviderResolver();
        });

        // Status mapper bindings with contextual binding
        $this->app->when(Provider1EmployeeMapper::class)
            ->needs(StatusMapperInterface::class)
            ->give(Provider1StatusMapper::class);

        $this->app->when(Provider2EmployeeMapper::class)
            ->needs(StatusMapperInterface::class)
            ->give(Provider2StatusMapper::class);

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
