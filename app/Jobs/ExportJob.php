<?php

namespace App\Jobs;

use App\Models\DownloadLog;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

/*
 * 队列中不建议使用单例模式，因为是常驻内存的
 * 当前调用命令为 php artisan queue:work --queue=ExportJob
 */

class ExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $downloadLog;

    /**
     * Create a new job instance.
     * @return void
     */
    public function __construct(DownloadLog $downloadLog)
    {
        $this->downloadLog = $downloadLog;
        $this->queue = 'ExportJob';
    }

    /**
     * Execute the job.
     * @return void
     */
    public function handle()
    {
        $className = $this->downloadLog['class_name'];
        $actionName = $this->downloadLog['action_name'];
        $params = json_decode($this->downloadLog['params'], true);

        if (!$className || !$actionName || !$params) throw new \Exception('导出参数有问题');

        $user = User::find($this->downloadLog['creator_id']);
        $user && Auth::login($user);

        switch ($params['exportType']) {
            case 2:
                $this->export2local($className, $actionName, $params);
                break;
            case 3:
                $this->loopCall($className, $actionName, $params);
                break;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $throwable
     * @return void
     */
    public function failed(\Throwable $throwable)
    {
        $this->downloadLog->update(['status' => 3, 'error_message' => $throwable->getMessage()]);
    }

    /**
     * 异步导出 限制1000条
     * @param $className
     * @param $actionName
     * @param $params
     */
    private function export2local($className, $actionName, array $params = [])
    {
        $params['offset'] = 0;
        $params['limit'] = 1000;

        $params = (new Request())->merge($params);
        $res = (new $className)->$actionName($params);

        if (!isset($res['fileLink'])) throw new \Exception('导出失败：' . json_encode($res));

        $data = [
            'file_name' => $res['fileName'],
            'file_type' => $res['fileType'],
            'file_size' => $res['fileSize'],
            'file_link' => $res['fileLink'],
            'status' => 1
        ];

        $this->downloadLog->update($data);
    }

    /**
     * 循环调用
     * @param $className
     * @param $actionName
     * @param $params
     * @throws \Exception
     */
    private function loopCall($className, $actionName, array $params = [])
    {
        $i = 0;
        $defaultLimit = $params['defaultLimit'] ?? 500;

        do {
            if (is_object($params)) $params = $params->all();
            $params['offset'] = $i * $defaultLimit;
            $params['limit'] = $defaultLimit;

            $params = (new Request())->merge($params);
            $res = (new $className)->$actionName($params);

            $i++;
            usleep(1000);
        } while ($res === true);

        if (!isset($res['fileLink'])) throw new \Exception('导出失败：' . json_encode($res));

        $data = [
            'file_name' => $res['fileName'],
            'file_type' => $res['fileType'],
            'file_size' => $res['fileSize'],
            'file_link' => $res['fileLink'],
            'status' => 1
        ];

        $this->downloadLog->update($data);
    }

}
