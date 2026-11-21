<?php

namespace App\Providers;

use App\Services\Kafka\KafkaService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Синглтон, чтобы RdKafka\Producer переиспользовался в рамках
        // процесса. Продюсер создаётся лениво, поэтому без ext-rdkafka или
        // при kafka.enabled=false объект собирается, не трогая расширение.
        $this->app->singleton(KafkaService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
