<?php

namespace ymlluo\WxApi\Facades;

use Illuminate\Support\Facades\Facade;

class WxApi extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'wxapi';
    }
}
