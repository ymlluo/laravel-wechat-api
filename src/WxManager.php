<?php


namespace ymlluo\WxApi;


use ymlluo\WxApi\Modules\CustomerService;
use ymlluo\WxApi\Modules\Menu;
use ymlluo\WxApi\Modules\Resource;
use ymlluo\WxApi\Modules\TemplateMessage;
use ymlluo\WxApi\Modules\User;

class WxManager
{
    protected $app;

    protected $configs;

    /**
     * The array of resolved mailers.
     *
     * @var array
     */
    protected $accounts = [];


    public function __construct($app)
    {
        $this->app = $app;
    }

    public function account($account = null)
    {
        $account = $account ?: 'default';
        $this->configs = $this->getConfigs($account);
        return $this->accounts[$account] = $this->get($account);
    }

    protected function get($name)
    {
        return $this->accounts[$name] ?? $this->resolve($name);
    }

    protected function resolve($name)
    {
        $configs = $this->getConfigs($name);

        if (is_null($configs)) {
            throw new \InvalidArgumentException("Account  [{$name}] is not defined.");
        }
        $wxApi = new WxApi($configs);
        return $wxApi;
    }

    protected function getConfigs(string $name)
    {
        return array_merge($this->app['config']["wxapi.accounts.{$name}"], $this->app['config']['wxapi.common']);
    }

    public function getConfig($key)
    {
        return $this->configs[$key] ?? null;
    }



}
