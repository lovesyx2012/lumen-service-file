<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\ProcessIdProcessor;

class LumenServiceProvider extends ServiceProvider
{

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {

    }

    public function boot()
    {
        // 配置日志处理器
        $this->app->configureMonologUsing(function (Logger $logger) {
            $logFile = env('STORAGE_LOG_PATH', storage_path("logs/%s.log"));

            $logger->pushHandler(new StreamHandler(sprintf($logFile, 'warning'), Logger::WARNING, false));
            $logger->pushHandler(new StreamHandler(sprintf($logFile, 'debug'), Logger::DEBUG, false));
            $logger->pushHandler(new StreamHandler(sprintf($logFile, 'info'), Logger::INFO, false));
            $logger->pushHandler(new StreamHandler(sprintf($logFile, 'error'), Logger::ERROR, false));

            $logger->pushProcessor(new ProcessIdProcessor());

            return $logger;
        });
    }
}