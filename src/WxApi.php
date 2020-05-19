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
    protected $modules;
    public $configs;
    public $encrypt;
    public $postXml;
    public $receive;
    public $message;

    public function __construct($account = 'default')
    {
        $this->account($account);
        $this->receiveMessage();
    }

    public function account($account = 'default')
    {
        $this->configs = $this->getConfigs($account);
        return $this;
    }

    public function getConfigs($account)
    {
        if (!app('config')['wxapi.accounts.' . $account]) {
            throw new WxException('account not exists', -1);
        }
        return array_merge(app('config')['wxapi.accounts.' . $account], app('config')['wxapi.common']);
    }

    public function getConfig(string $key)
    {
        return data_get($this->configs, $key);
    }

    public function verify()
    {
        if (request()->isMethod('GET')) {
            response($this->checkSignature())->send();
            die();
        }

    }

    public function receiveMessage()
    {
        $this->postXml = request()->getContent();
        $encrypt = request()->get('encrypt_type');
        if ($encrypt) { //aes加密
            $this->postXml = Encrypt::decodeXML($this->postXml, $this->configs['aes_key']);
        }
        $receiveData = XML::xml2Array($this->postXml);
        return tap($this, function () use ($receiveData, $encrypt) {
            $this->receive = $receiveData;
            $this->encrypt = $encrypt;
            if (function_exists('event')) {
                event(new MessageReceived($receiveData));
            }
        });
    }

    public function message()
    {
//        return new Message($this->receive, $this->encrypt);

        if (!isset($this->modules['message'])) {
            $this->modules['message'] = new Message($this->receive, $this->encrypt);
        }
        return $this->modules['message'];
    }


    public function checkSignature()
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


    public function auth()
    {
        if (!isset($this->modules['auth'])) {
            $this->modules['auth'] = new AccessToken($this);
        }
        return $this->modules['auth'];
    }

    public function resource()
    {
        if (!isset($this->modules['resource'])) {
            $this->modules['resource'] = new Resource($this);
        }
        return $this->modules['resource'];
    }

    public function material()
    {
        return $this->resource();
    }

    public function menu()
    {
        if (!isset($this->modules['menu'])) {
            $this->modules['menu'] = new Menu($this);
        }
        return $this->modules['menu'];
    }

    public function accessToken()
    {
        return $this->auth()->getToken();
    }

    public function customerService()
    {
        if (!isset($this->modules['cs'])) {
            $this->modules['cs'] = new CustomerService($this);
        }
        return $this->modules['cs'];
    }

    public function cs()
    {
        return $this->customerService();
    }

    public function tpl()
    {
        if (!isset($this->modules['tpl'])) {
            $this->modules['tpl'] = new TemplateMessage($this);
        }
        return $this->modules['tpl'];
    }

    public function user()
    {
        if (!isset($this->modules['user'])) {
            $this->modules['user'] = new User($this);
        }
        return $this->modules['user'];
    }

}
