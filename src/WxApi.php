<?php

namespace ymlluo\WxApi;

use ymlluo\WxApi\Events\MessageReceived;
use ymlluo\WxApi\Exceptions\WxException;
use ymlluo\WxApi\Modules\CustomerService;
use ymlluo\WxApi\Modules\Menu;
use ymlluo\WxApi\Modules\Message;
use ymlluo\WxApi\Modules\Resource;
use ymlluo\WxApi\Modules\TemplateMessage;
use ymlluo\WxApi\Modules\User;
use ymlluo\WxApi\Support\Encrypt;
use ymlluo\WxApi\Support\XML;

class WxApi
{
    protected $modules = [];
    public $configs = [];
    public $encrypt;
    public $postXml;
    public $receive;
    public $message;
    public $account;

    public function __construct(array $configs = [])
    {
        $this->setConfigs($configs);
    }

    /**
     * 从配置文件读取配置
     *
     * @param string $account
     * @return $this
     * @throws WxException
     */
    public function account($account = 'default')
    {
        $this->account = $account;
        $this->setConfigs(array_merge(app('config')['wxapi.accounts.' . $account], app('config')['wxapi.common']));
        return $this;
    }

    /**
     * 设置配置
     *
     * @param array $configs
     * @return $this
     */
    public function setConfigs(array $configs)
    {
        $this->configs = array_merge($configs, app('config')['wxapi.common']);
        return $this;
    }

    /**
     * 获取全部配置
     *
     * @return array
     */
    public function getConfigs()
    {
        return $this->configs;
    }

    /**
     * 获取配置项
     *
     * @param string $key
     * @return mixed
     */
    public function getConfig(string $key)
    {
        return data_get($this->configs, $key, '');
    }

    /**
     * 发送验证消息
     */
    public function verify()
    {
        if (request()->isMethod('GET')) {
            response($this->checkSignature())->send();
            die();
        }
    }

    /**
     * 验证消息
     *
     * @return string
     */
    public function checkSignature(): string
    {
        $echostr = 'no signature';
        $signature = request()->get('signature');
        $timestamp = request()->get('timestamp');
        $nonce = request()->get('nonce');
        if (Encrypt::signature($this->configs['token'], $timestamp, $nonce) == $signature) {
            $echostr = request()->get('echostr');
        }
        return $echostr;
    }


    /**
     * 接收微信服务器 Post 的消息
     * @return mixed|WxApi
     * @throws WxException
     */
    public function receiveMessage()
    {
        $this->postXml = request()->getContent();
        $encrypt = request()->get('encrypt_type');
        \Log::debug($this->postXml);
        if ($encrypt) { //aes加密
            $this->postXml = Encrypt::decodeXML($this->postXml, $this->configs['aes_key']);
        }
        $receiveData = XML::xml2Array($this->postXml);
        return tap($this, function () use ($receiveData, $encrypt) {
            $this->receive = $receiveData;
            $this->encrypt = $encrypt;
            \Log::info('receive msg',$this->receive);
            if ($receiveData && function_exists('event')) {
                event(new MessageReceived($receiveData));
            }
        });
    }


    /**
     * 消息相关
     * @return mixed
     * @throws WxException
     */
    public function message()
    {
        if (!$this->receive) {
            $this->receiveMessage();
        }
        return new Message($this->receive, $this->encrypt);
        if (!isset($this->modules['message'])) {
            $this->modules['message'] = new Message($this->receive, $this->encrypt);
        }
        return $this->modules['message'];
    }

    /**
     * AccessToken 相关
     * @return mixed
     */
    public function auth()
    {
        if (!isset($this->modules['auth'])) {
            $this->modules['auth'] = new AccessToken($this);
        }
        return $this->modules['auth'];
    }

    /**
     * 素材相关
     *
     * @return mixed
     * @throws \Exception
     */
    public function resource()
    {
        if (!isset($this->modules['resource'])) {
            $this->modules['resource'] = new Resource($this);
        }
        return $this->modules['resource'];
    }

    /**
     * 素材相关
     *
     * @return mixed
     * @throws \Exception
     */
    public function material()
    {
        return $this->resource();
    }

    /**
     * 菜单操作
     *
     * @return mixed
     */
    public function menu()
    {
        if (!isset($this->modules['menu'])) {
            $this->modules['menu'] = new Menu($this);
        }
        return $this->modules['menu'];
    }

    /**
     * 获取 AccessToken
     *
     * @return mixed
     */
    public function accessToken()
    {
        return $this->auth()->getToken();
    }

    /**
     * 客服相关
     *
     * @return mixed
     */
    public function customerService()
    {
        if (!isset($this->modules['cs'])) {
            $this->modules['cs'] = new CustomerService($this);
        }
        return $this->modules['cs'];
    }

    /**
     * 客服相关
     *
     * @return mixed
     */
    public function cs()
    {
        return $this->customerService();
    }

    /**
     * 模板相关
     *
     * @return mixed
     */
    public function tpl()
    {
        if (!isset($this->modules['tpl'])) {
            $this->modules['tpl'] = new TemplateMessage($this);
        }
        return $this->modules['tpl'];
    }

    /**
     * 用户相关
     *
     * @return mixed
     * @throws \Exception
     */
    public function user()
    {
        if (!isset($this->modules['user'])) {
            $this->modules['user'] = new User($this);
        }
        return $this->modules['user'];
    }

}
