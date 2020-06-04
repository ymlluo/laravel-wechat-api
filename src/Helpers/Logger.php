<?php


namespace ymlluo\WxApi\Helpers;


class Logger
{
    private static $instance;
    public static $enable;

    public static function getInstance($enable = false): Logger
    {
        if (null === static::$instance) {
            static::$instance = new static($enable);
        }

        return static::$instance;
    }

    private function __construct($enable)
    {
        self::$enable = $enable;
    }

    private function __clone()
    {

    }

    public function __call($name, $parameters)
    {
        if (self::$enable === true) {
            app('log')->{$name}(...$parameters);
        }
    }
}
