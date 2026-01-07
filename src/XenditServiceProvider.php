<?php

namespace Laraditz\Xendit;

use Illuminate\Support\Str;
use Illuminate\Support\ServiceProvider;
use Laraditz\Xendit\Client\XenditClient;
use Laraditz\Xendit\Models\XenditPayment;
use Laraditz\Xendit\Models\XenditWebhookLog;
use Laraditz\Xendit\Models\XenditTransaction;
use Laraditz\Xendit\Observers\XenditPaymentObserver;
use Laraditz\Xendit\Observers\XenditWebhookLogObserver;
use Laraditz\Xendit\Observers\XenditTransactionObserver;

class XenditServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        // Register observers
        XenditPayment::observe(XenditPaymentObserver::class);
        XenditTransaction::observe(XenditTransactionObserver::class);
        XenditWebhookLog::observe(XenditWebhookLogObserver::class);

        if ($this->app->runningInConsole()) {
            // Publish configuration
            $this->publishes([
                __DIR__ . '/../config/config.php' => config_path('xendit.php'),
            ], 'xendit-config');

            // Publish migrations
            $this->publishMigrations();
        }
    }

    protected function publishMigrations()
    {
        $databasePath = __DIR__ . '/../database/migrations/';
        $migrationPath = database_path('migrations/');

        $files = array_diff(scandir($databasePath), array('.', '..'));
        $date = date('Y_m_d');
        $time = date('His');

        $migrationFiles = collect($files)
            ->mapWithKeys(function (string $file) use ($databasePath, $migrationPath, $date, &$time) {
                $filename = Str::replaceEnd('.stub', '', $file);

                $found = glob($migrationPath . '*' . $filename);
                $time = date("His", strtotime($time) + 1); // ensure in order
    
                return !!count($found) === true ? []
                    : [
                        $databasePath . $file => $migrationPath . $date . '_' . $time . $filename,
                    ];
            });

        if ($migrationFiles->isNotEmpty()) {
            $this->publishes($migrationFiles->toArray(), 'xendit-migrations');
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'xendit');

        // Register XenditClient as singleton
        $this->app->singleton(XenditClient::class, function () {
            return new XenditClient();
        });

        // Register the main class to use with the facade
        $this->app->singleton('xendit', function ($app) {
            return new Xendit($app->make(XenditClient::class));
        });
    }
}
