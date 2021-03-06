<?php
/**
 * Created by PhpStorm
 * User: ZS
 * Date: 2021/1/11
 * Time: 3:04 下午
 */


namespace App\Listeners;


use App\Utils\Z\ZLog;
use Illuminate\Database\Events\QueryExecuted;

class QueryListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param object $event
     * @return void
     */
    public function handle(QueryExecuted $event)
    {
        $sqlLog = config('setting.logSql');
        if ($sqlLog !== true) return;

        $sql = str_replace('?', "'%s'", $event->sql);

        $sqlInfo = sprintf("%7s", $event->time) . 'ms;    ' . vsprintf($sql, $event->bindings);
        //$sqlInfo = 'execution time: ' . sprintf("%7s", $event->time) . 'ms; ' . vsprintf($sql, $event->bindings);

        ZLog::channel('sql')->info($sqlInfo);
    }
}
