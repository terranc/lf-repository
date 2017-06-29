<?php
namespace Lookfeel\Repository;
use Illuminate\Support\ServiceProvider;
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishConfig();
    }
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerRepoist();
    }
    /**
     * Register Repoist Commands.
     *
     * @return void
     */
    private function registerRepoist()
    {
        $this->app->singleton('command.lookfeel.repository', function ($app) {
            return $app['Lookfeel\Repository\Commands\CreateRepositoryCommand'];
        });
        $this->commands('command.lookfeel.repository');
    }
    private function publishConfig()
    {
        $this->publishes([
            __DIR__ . '/config/repository.php' => config_path('repository.php'),
        ]);
    }
}
