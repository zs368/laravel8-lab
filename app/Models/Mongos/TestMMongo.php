<?php
/**
 * Created by PhpStorm
 * User: ZS
 * Date: 2021/3/31
 * Time: 6:14 下午
 */


namespace App\Models\Mongos;


use App\Models\Core\Mongo;
use Jenssegers\Mongodb\Eloquent\SoftDeletes;

class TestMMongo extends Mongo
{
    use SoftDeletes;

    protected $collection = 'test_m';

    protected $dates = ['deleted_at'];
}
