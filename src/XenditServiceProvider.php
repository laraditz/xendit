<?php

namespace Laraditz\Xendit;

use Illuminate\Support\ServiceProvider;
use Laraditz\Xendit\Client\XenditClient;
use Laraditz\Xendit\Models\XenditPayment;
use Laraditz\Xendit\Models\XenditTransaction;
use Laraditz\Xendit\Models\XenditWebhookLog;
use Laraditz\Xendit\Observers\XenditPaymentObserver;
use Laraditz\Xendit\Observers\XenditTransactionObserver;
use Laraditz\Xendit\Observers\XenditWebhookLogObserver;

class XenditServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        // Register observers
        XenditPayment::observe(XenditPaymentObserver::class);
        XenditTransaction::observe(XenditTransactionObserver::class);
        XenditWebhookLog::observe(XenditWebhookLogObserver::class);

        if ($this->app->runningInConsole()) {
            // Publish configuration
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('xendit.php'),
            ], 'xendit-config');

            // Publish migrations
            $this->publishes([
                __DIR__.'/../database/migrations/create_xendit_payments_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time()).'_create_xendit_payments_table.php'),
                __DIR__.'/../database/migrations/create_xendit_transactions_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time() + 1).'_create_xendit_transactions_table.php'),
                __DIR__.'/../database/migrations/create_xendit_webhook_logs_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time() + 2).'_create_xendit_webhook_logs_table.php'),
            ], 'xendit-migrations');
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'xendit');

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
