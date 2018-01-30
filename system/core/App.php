<?php
/**
 * Created by xLong.
 * Date: 2018/1/29
 * Time: 18:39
 */

namespace xcore;


class App
{
    public static function run()
    {
        $request = Request::instance(); // 实例化
        $request->pathInfo(); // 解析pathinfo
        $dispatch = $request->praiseUrl(); // 解析路由

        self::exec($dispatch); // 执行
    }

    // 执行
    public static function exec($dispatch)
    {
        if (is_string($dispatch)) {
            $dispatch = explode('/', $dispatch);
        }

        $controller = $dispatch[0];

        $class = 'app\\controller\\' . ucfirst($controller);
        $action = $dispatch[1];

        $instance = new $class;

        $reflectionMethod = new \ReflectionMethod($class, $action);
        $res = $reflectionMethod->invoke($instance);
        return $res;
    }
}