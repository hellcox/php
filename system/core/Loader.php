<?php
/**
 * Created by xLong.
 * Date: 2018/1/29
 * Time: 11:02
 */

namespace xcore;
class Loader
{
    /**
     * 路径映射
     * @var array
     */
    public static $vendorMap = [
        'xcore' => CORE_PATH,
        'app' => APP_PATH,
        'lib' => LIB_PATH,
    ];

    /**
     * 自动加载
     * @param $class
     */
    public static function autoload($class)
    {
        $file = self::findFile($class);
        if (file_exists($file)) {
            if (is_file($file)) {
                include $file;
            }
        }
    }

    /**
     * 注册系统自动加载
     */
    public static function register()
    {
        spl_autoload_register('xcore\\Loader::autoload');
    }

    /**
     * 查找类对应文件
     * @param $class
     * @return string
     */
    public static function findFile($class)
    {
        // 顶级命名空间
        $vendor = substr($class, 0, strpos($class, '\\'));
        // 文件基目录
        $vendorDir = self::$vendorMap[$vendor];
        // 文件相对路径
        $filePath = substr($class, strlen($vendor)) . '.php';
        // 文件标准路径
        $realFilePath = strtr($vendorDir . $filePath, '\\', DIRECTORY_SEPARATOR);
        return $realFilePath;
    }
}