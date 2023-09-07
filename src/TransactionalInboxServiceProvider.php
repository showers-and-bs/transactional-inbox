<?php

namespace ShowersAndBs\TransactionalInbox;

use Illuminate\Support\ServiceProvider;

class TransactionalInboxServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->registerCommands();
            $this->registerMigrations();
            $this->publishConfigFiles();
        }
    }

    /**
     * Register the package's console commands.
     *
     * @return void
     */
    protected function registerCommands(): void
    {
        $this->commands([
            \ShowersAndBs\TransactionalInbox\Console\Commands\MessageConsumer::class,
        ]);
    }

    /**
     * Register the package's database migrations.
     *
     * @return void
     */
    protected function registerMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /**
     * Register the package's database migrations.
     *
     * @return void
     */
    protected function publishConfigFiles()
    {
        $this->publishes([
            __DIR__.'/../config/transactional_inbox.php' => config_path('transactional_inbox.php'),
        ], 'transactional-inbox-config');
    }
}
