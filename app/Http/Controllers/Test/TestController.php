<?php
/**
 * Created by PhpStorm
 * User: ZS
 * Date: 2020/12/17
 * Time: 2:31 下午
 */


namespace App\Http\Controllers\Test;


use App\Http\Controllers\Core\Controller;
use App\Jobs\ExcelDownload;
use App\Models\User;
use App\Services\Utils\Excel;
use App\Services\Utils\File;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class TestController extends Controller
{
    public function user(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|integer'
        ]);

        $params = request()->all();

        $data = User::where('id', '=', $params['id'])->first()->toArray();

        return response()->json($data);
    }

    public function fileReader()
    {
        $fileUrl = "https://kyyx-dept1-store-test.oss-cn-hangzhou.aliyuncs.com/source/20201221/e761e7a20402d5a515964d20a016d930.xlsx";

        $fileFullName = array_reverse(explode('/', $fileUrl))[0];
        $fileType = array_reverse(explode('.', $fileFullName))[0];

        if (strtoupper($fileType) == 'XLSX') {
            $reader = new Xlsx();
        } elseif (strtoupper($fileType) == 'XLS') {
            $reader = new Xls();
        } elseif (strtoupper($fileType) == 'CSV') {
            $reader = new Csv();
        } else {
            $this->respFail('文件格式错误');
        }

        $tmp = 'uploads/excel/' . uniqid() . '.' . $fileType;
        $filePath = File::storageFromUrl($fileUrl, $tmp);

        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestRow();      // 最大行数（可以分批处理）

        if ($highestRow - 1 <= 0) {
            $this->respFail('Excel表格中没有数据');
        }
        $data = [];
        for ($row = 2; $row <= $highestRow; $row++) {
            // 将 excel 数据存储到 数组 中
            $data[] = [
                'date' => $worksheet->getCellByColumnAndRow(1, $row)->getFormattedValue(),
                'id' => $worksheet->getCellByColumnAndRow(3, $row)->getFormattedValue(),
                '消耗' => $worksheet->getCellByColumnAndRow(4, $row)->getFormattedValue(),
                '曝光数' => $worksheet->getCellByColumnAndRow(5, $row)->getFormattedValue(),
                '点击数' => $worksheet->getCellByColumnAndRow(6, $row)->getFormattedValue(),
                '渠道号' => $worksheet->getCellByColumnAndRow(7, $row)->getFormattedValue(),
            ];
        }

        rrmDir(dirname($filePath));

        // 处理数据
        var_dump($data);
    }

    public function fileExport()
    {
        //头信息
        $header = [
            'a' => '姓名',
            'b' => '性别',
            'c' => '学历',
            'd' => '年龄',
            'e' => '身高',
        ];
        //内容
        $data = [
            [
                'a' => '小明',
                'b' => '男',
                'c' => '专科',
                'd' => '18',
                'e' => '175'
            ],
            [
                'a' => '小红',
                'b' => '女',
                'c' => '本科',
                'd' => '18',
                'e' => '155'
            ],
            [
                'a' => '小蓝',
                'b' => '男',
                'c' => '专科',
                'd' => '20',
                'e' => '170'
            ],
            [
                'a' => '张三',
                'b' => '男',
                'c' => '本科',
                'd' => '19',
                'e' => '165'
            ],
            [
                'a' => '李四',
                'b' => '男',
                'c' => '专科',
                'd' => '22',
                'e' => '175'
            ]
        ];

        Excel::export2Browser($header, $data, 'aaa', 'Csv');
    }

    public function tree()
    {
        $list = [
            ['id' => 1, 'name' => '爷', 'parent' => 0],
            ['id' => 2, 'name' => '父', 'parent' => 1],
            ['id' => 3, 'name' => '伯', 'parent' => 1],
            ['id' => 4, 'name' => 'me', 'parent' => 2],
            ['id' => 5, 'name' => '二爷爷', 'parent' => 0],
        ];

        $tree = list2tree($list, 'id', 'parent');
        $myTree = getSubtree($tree, '爷');
        $tmp[] = $myTree;
        $list2 = tree2list($tmp);
        print_r($list2);
    }

    public function queue(Request $request)
    {
        $this->validate($request, [
            'download' => 'integer'
        ]);
        //头信息
        $header = [
            'a' => '姓名',
            'b' => '性别',
            'c' => '学历',
            'd' => '年龄',
            'e' => '身高',
        ];
        //内容
        $data = [
            [
                'a' => '小明',
                'b' => '男',
                'c' => '专科',
                'd' => '18',
                'e' => '175'
            ],
            [
                'a' => '小红',
                'b' => '女',
                'c' => '本科',
                'd' => '18',
                'e' => '155'
            ],
            [
                'a' => '小蓝',
                'b' => '男',
                'c' => '专科',
                'd' => '20',
                'e' => '170'
            ],
            [
                'a' => '张三',
                'b' => '男',
                'c' => '本科',
                'd' => '19',
                'e' => '165'
            ],
            [
                'a' => '李四',
                'b' => '男',
                'c' => '专科',
                'd' => '22',
                'e' => '175'
            ]
        ];

        try {
            DB::beginTransaction();
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $className = $backtrace[0]['class'];
            $actionName = $backtrace[0]['function'];
            $data = [

            ];
            var_dump($className, $actionName);
            die();
        } catch (\Throwable $throwable) {
            DB::rollBack();
            return $this->respFail('插入失败');
        }
        DB::commit();

        $job = (new ExcelDownload())->delay(Carbon::now()->addMinutes(1));
        dispatch($job)->onQueue('ExcelDownload');

        return $this->respSuccess([], '下载队列添加成功');
    }
}