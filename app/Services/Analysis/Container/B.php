<?php
/**
 * Created by PhpStorm
 * User: ZS
 * Date: 2021/5/21
 * Time: 2:01 下午
 */


namespace App\Services\Analysis\Container;


class B
{
    private $a;

    public function __construct(A $z)
    {
        $this->a = $z;
    }

    public function doSomething()
    {
        $this->a->doSomething();

        echo __METHOD__ . PHP_EOL;
    }
}
