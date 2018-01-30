<?php
/**
 * Created by xLong.
 * Date: 2018/1/30
 * Time: 11:12
 */

namespace app\controller;
class Index
{
    public function __construct()
    {
    }

    public function index()
    {
        echo '<hr>index111<hr>';
    }

    public function test(){
        echo '<h1>test</h1>';
        echo 's<br>';
        $res = new \lib\Test();
        $res->libtest();
    }

    public function db()
    {
        $db = \lib\Db::getInstance();
        $sql  = "select * from user";
        $rows = $db->query($sql)->all();
        print_r($rows);
    }
}