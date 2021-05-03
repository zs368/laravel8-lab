<?php

namespace App\Jobs;

use App\Models\DownloadLogModel;
use App\Models\UserModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

class ExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    protected $downloadLog;

    /**
     * Create a new job instance.
     * @return void
     */
    public function __construct(DownloadLogModel $downloadLog)
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

        $user = UserModel::find($this->downloadLog['creator_id']);
        $user && Auth::login($user);

        switch ($params['exportType']) {
            case 2:
                $this->export2local($className, $actionName, $params);
                break;
            case 3:
                $this->bigDataExport($className, $actionName, $params);
                break;
            case 4:
                $this->bigDataExport2($className, $actionName, $params);
                break;
        }
    }

    private function export2local($className, $actionName, $params)
    {
        $params['offset'] = 0;
        $params['limit'] = 1000;

        $params = (new Request())->merge($params);
        $res = (new $className)->$actionName($params);

        $data = [
            'file_name' => $res['fileName'],
            'file_type' => $res['fileType'],
            'file_size' => $res['fileSize'],
            'file_link' => $res['fileLink'],
            'status' => 1
        ];

        $this->downloadLog->update($data);
    }

    private function bigDataExport($className, $actionName, $params)
    {
        $params['downloadLogId'] = $this->downloadLog['id'];

        $params = (new Request())->merge($params);
        (new $className)->$actionName($params);
    }

    private function bigDataExport2($className, $actionName, $params)
    {
        if (empty($params['total'])) throw new \Exception('导出数据为空');

        $params['downloadLogId'] = $this->downloadLog['id'];

        $defaultLimit = $params['defaultLimit'] ?? 300;
        $times = ceil($params['total'] / $defaultLimit);

        for ($i = 0; $i < $times; $i++) {
            $params['offset'] = $i * $defaultLimit;
            $params['limit'] = $defaultLimit;

            $params = (new Request())->merge($params);
            (new $className)->$actionName($params);
        }

    }
}
