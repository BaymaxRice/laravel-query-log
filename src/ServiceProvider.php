<?php

/**
 * Created by PhpStorm.
 * User: baymax
 * Date: 2018/11/19
 * Time: 19:18
 */

namespace Baymax\LaravelQueryLog;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->log('============ URL: '.request()->fullUrl().' ===============');
        DB::listen(function (QueryExecuted $query) {
            $sqlWithPlaceholders = str_replace(['%', '?'], ['%%', '%s'], $query->sql);

            $bindings = $query->connection->prepareBindings($query->bindings);
            $pdo = $query->connection->getPdo();
            $realSql = vsprintf($sqlWithPlaceholders, array_map([$pdo, 'quote'], $bindings));
            $duration = $this->formatDuration($query->time / 1000);

            $this->log(sprintf('[%s] %s', $duration, $realSql));
        });
    }

    /**
     * Register the application services.
     */
    public function register()
    {
    }

    /**
     * Format duration.
     *
     * @param float $seconds
     *
     * @return string
     */
    private function formatDuration($seconds)
    {
        if ($seconds < 0.001) {
            return round($seconds * 1000000) . 'μs';
        } elseif ($seconds < 1) {
            return round($seconds * 1000, 2) . 'ms';
        }

        return round($seconds, 2) . 's';
    }

    /**
     * Log msg
     */
    private function log($msg)
    {
        $default_hander = Log::getMonolog()->popHandler();
        Log::useDailyFiles(storage_path('logs/db/db.log'));
        Log::info($msg . "\n\n\t");
        Log::getMonolog()->popHandler();
        Log::getMonolog()->pushHandler($default_hander);
    }
}
