<?php
/**
 * Created by xLong.
 * Date: 2018/1/29
 * Time: 18:03
 */
namespace xcore;

include CORE_PATH.'Loader.php';

// 注册自动加载
Loader::register();

// 执行应用
App::run();

