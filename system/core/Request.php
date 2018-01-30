<?php
/**
 * Created by xLong.
 * Date: 2018/1/29
 * Time: 15:24
 */

namespace xcore;
class Request
{

    protected static $instance;
    protected $method;
    protected $pathinfo;
    protected $path;

    protected $dispatch = [];
    protected $controller;
    protected $action;

    protected function __construct()
    {
    }

    /**
     * 初始化
     * @access public
     * @param array $options 参数
     * @return \xcore\Request
     */
    public static function instance($options = [])
    {
        if (is_null(self::$instance)) {
            self::$instance = new static($options);
        }
        return self::$instance;
    }

    /**
     * 获取当前请求URL的pathinfo信息
     */
    public function pathInfo()
    {
        if (is_null($this->pathinfo)) {
            $this->pathinfo = ltrim($_SERVER['PATH_INFO'], '/');
        }
        if (is_null($this->method)) {
            $this->method = $_SERVER['REQUEST_METHOD'];
        }
        return $this->pathinfo;
    }

    /**
     * 解析URL
     * @return array
     */
    public function praiseUrl()
    {
        $this->pathinfo;
        if(empty($this->pathinfo)){
            $controller = 'index';
            $action = 'index';
        }else{
            $path = explode('/',$this->pathInfo());
            $controller = array_shift($path);
            $action = array_shift($path);
        }
        $this->controller = $controller;
        $this->action = $action;
        $route = [$controller,$action];
        $this->dispatch = $route;

        return $route;
    }


}