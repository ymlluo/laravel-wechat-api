<?php


namespace ymlluo\WxApi\Modules;


use ymlluo\WxApi\Helpers\Http;
use ymlluo\WxApi\WxApi;
use ymlluo\WxApi\WxManager;

class Menu
{
    const PATH_MENU_CREATE = '/cgi-bin/menu/create';
    const PATH_MENU_DELETE = '/cgi-bin/menu/delete';
    const PATH_MENU_GET = '/cgi-bin/menu/get';
    const PATH_GET_CURRENT_SELF_MENU = '/cgi-bin/get_current_selfmenu_info';
    const PATH_MENU_CONDITIONAL_ADD = '/cgi-bin/menu/addconditional';
    const PATH_MENU_CONDITIONAL_DEL = '/cgi-bin/menu/delconditional';
    const PATH_MENU_CONDITIONAL_MATCH = '/cgi-bin/menu/trymatch';

    protected $app;
    public $access_token;
    public $httpClient;

    public function __construct(WxApi $app)
    {
        $this->app = $app;
        $this->access_token = $app->accessToken();
        $this->httpClient = new Http();
    }

    /**
     * 创建自定义菜单
     * @param array $data
     * @return mixed
     * @throws \Exception
     */
    public function create(array $data)
    {
        $url = $this->app->getConfig('api_origin') . self::PATH_MENU_CREATE . '?access_token=' . $this->access_token;
        $response = $this->httpClient->post($url, $data);
        $data = $response->throw()->json();
        return $data;
    }

    /**
     * 查询自定义菜单的配置
     * @return mixed
     * @throws \Exception
     */
    public function get()
    {
        $url = $this->app->getConfig('api_origin') . self::PATH_MENU_GET . '?access_token=' . $this->access_token;
        $response = $this->httpClient->get($url);
        $data = $response->throw()->json();
        return $data;
    }
 /**
     * 查询自定义菜单的配置
     * @return mixed
     * @throws \Exception
     */
    public function getCurrentSelfMenu()
    {
        $url = $this->app->getConfig('api_origin') . self::PATH_GET_CURRENT_SELF_MENU . '?access_token=' . $this->access_token;
        $response = $this->httpClient->get($url);
        $data = $response->throw()->json();
        return $data;
    }

    /**
     * 删除当前使用的自定义菜单
     * @return mixed
     * @throws \Exception
     */
    public function delete()
    {
        $url = $this->app->getConfig('api_origin') . self::PATH_MENU_DELETE . '?access_token=' . $this->access_token;
        $response = $this->httpClient->get($url);
        $data = $response->throw()->json();
        return $data;
    }

    /**
     * 创建个性化菜单
     * @param array $data
     * @return mixed
     * @throws \Exception
     */
    public function conditionalCreate(array $data)
    {
        $url = $this->app->getConfig('api_origin') . self::PATH_MENU_CONDITIONAL_ADD . '?access_token=' . $this->access_token;
        $response = $this->httpClient->post($url, $data);
        $data = $response->throw()->json();
        return $data;
    }

    /**
     * 删除个性化菜单
     * @param int $menuId 菜单id，可以通过自定义菜单查询接口获取
     * @return mixed
     * @throws \Exception
     */
    public function conditionalDelete(int $menuId)
    {
        $url = $this->app->getConfig('api_origin') . self::PATH_MENU_CONDITIONAL_DEL . '?access_token=' . $this->access_token;
        $response = $this->httpClient->post($url, ['menuid' => $menuId]);
        $data = $response->throw()->json();
        return $data;
    }

    /**
     * 测试个性化菜单匹配结果
     *
     * @param string $userId 可以是粉丝的OpenID，也可以是粉丝的微信号。
     * @return mixed
     * @throws \Exception
     */
    public function conditionalMatch(string $userId)
    {
        $url = $this->app->getConfig('api_origin') . self::PATH_MENU_CONDITIONAL_MATCH . '?access_token=' . $this->access_token;
        $response = $this->httpClient->post($url, ['user_id' => $userId]);
        $data = $response->throw()->json();
        return $data;
    }

}
