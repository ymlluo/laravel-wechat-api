<?php


namespace ymlluo\WxApi;

use Illuminate\Support\Facades\Log;
use  ymlluo\WxApi\Exceptions\ErrorCode;
use  ymlluo\WxApi\Exceptions\WxException;
use  ymlluo\WxApi\Helpers\Http;

class AccessToken
{
    protected $app;
    public $configs;
    const API_URL = 'https://api.weixin.qq.com/cgi-bin/token';

    public function __construct(WxApi $app)
    {
        $this->app = $app;
    }


    /**
     * @param bool $refresh
     * @return array|mixed
     * @throws \Exception
     */
    public function getToken()
    {

        $cacheKey = $this->getCacheKey();
        $data = (array)cache($cacheKey);
        if (!$data) {
            return $this->refreshToken();
        }
        return data_get($data, 'access_token');
    }

    public function refreshToken()
    {
        $data = $this->requestToken();
        return data_get($data, 'access_token');
    }


    /**
     * @return mixed
     * @throws \Exception
     */
    public function getTTL()
    {
        $cacheKey = $this->getCacheKey();
        return data_get((array)cache($cacheKey), 'expires_in', 0);
    }


    protected function getCacheKey()
    {
        $cacheKey = $this->getConfig('cache_prefix') . 'ACCESS_TOKEN:' . $this->getConfig('app_id');
        return $cacheKey;
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function requestToken()
    {
        $response = (new Http())->get($this->_endpoint());
        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['access_token']) && isset($data['expires_in'])) {
                cache()->put($this->getCacheKey(), $data, $data['expires_in'] - 1800);
                return $data;
            }
            throw new WxException('get access token error', ErrorCode::$accessTokenError);
        }
        return [];
    }

    private function _endpoint()
    {

        if ($url = $this->getConfig('access_token_url')) {
            return $url;
        };
        return self::API_URL . '?' . http_build_query([
                'grant_type' => 'client_credential',
                'appid' => $this->getConfig('app_id'),
                'secret' => $this->getConfig('app_secret')
            ]);
    }

    private function getConfig($key)
    {
        return data_get($this->app->configs, $key);
    }

    public function __call($method, $parameters)
    {
        return $this->$method(...$parameters);
    }

}
