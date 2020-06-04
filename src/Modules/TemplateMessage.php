<?php


namespace ymlluo\WxApi\Modules;


use ymlluo\WxApi\Helpers\Http;
use ymlluo\WxApi\WxApi;

class TemplateMessage
{

    const PATH_INDUSTRY_GET = '/cgi-bin/template/get_industry';
    const PATH_INDUSTRY_SET = '/cgi-bin/template/api_set_industry';
    const PATH_TEMPLATE_ADD = '/cgi-bin/template/api_add_template';
    const PATH_TEMPLATE_DEL = '/cgi-bin/template/del_private_template';
    const PATH_TEMPLATE_LIST = '/cgi-bin/template/get_all_private_template';
    protected $app;
    public $access_token;
    public $httpClient;
    public $cs;

    public function __construct(WxApi $app)
    {
        $this->app = $app;
        $this->access_token = $app->accessToken();
        $this->httpClient = new Http();
    }

    /**
     * 获取设置的行业信息
     * 获取帐号设置的行业信息。可登录微信公众平台，在公众号后台中查看行业信息
     *
     * @return mixed
     * @throws \Exception
     */
    public function getIndustry()
    {
        $response = $this->httpClient->get($this->getConfig('api_origin') . self::PATH_INDUSTRY_GET . '?access_token=' . $this->access_token);

        return $response->throw()->json();
    }

    /**
     * 设置所属行业
     * 设置行业可在微信公众平台后台完成，每月可修改行业1次，帐号仅可使用所属行业中相关的模板
     *
     * @param $industry_id1
     * @param $industry_id2
     * @return mixed
     * @throws \Exception
     */
    public function setIndustry($industry_id1, $industry_id2)
    {
        $response = $this->httpClient->post($this->getConfig('api_origin') . self::PATH_INDUSTRY_SET . '?access_token=' . $this->access_token, [
            'industry_id1' => $industry_id1,
            'industry_id2' => $industry_id2,
        ]);
        return $response->throw()->json();

    }

    /**
     * 获得模板ID
     * 从行业模板库选择模板到帐号后台，获得模板ID的过程可在微信公众平台后台完成。
     *
     * @param $template_id_short
     * @return mixed
     * @throws \Exception
     */
    public function add($template_id_short)
    {
        $response = $this->httpClient->post($this->getConfig('api_origin') . self::PATH_TEMPLATE_ADD . '?access_token=' . $this->access_token, [
            'template_id_short' => $template_id_short,
        ]);
        return $response->throw()->json();
    }

    /**
     * 删除模板
     * 删除模板可在微信公众平台后台完成
     *
     * @param $template_id
     * @return mixed
     * @throws \Exception
     */
    public function del($template_id)
    {
        $response = $this->httpClient->post($this->getConfig('api_origin') . self::PATH_TEMPLATE_DEL . '?access_token=' . $this->access_token, [
            'template_id' => $template_id,
        ]);
        return $response->throw()->json();
    }

    /**
     * 获取模板列表
     * 获取已添加至帐号下所有模板列表，可在微信公众平台后台中查看模板列表信息
     *
     * @return mixed
     * @throws \Exception
     */
    public function list()
    {
        $response = $this->httpClient->get($this->app->getConfig('api_origin') . self::PATH_TEMPLATE_LIST . '?access_token=' . $this->access_token);
        return $response->throw()->json();
    }

    /**
     * 设置模板消息接收人 openid
     *
     * @param $openid
     * @return bool|CustomerService
     */
    function toUser($openid)
    {
        return $this->app->cs()->toUser($openid);
    }

    /**
     * 发送模板消息
     *
     * @param string $template_id
     * @param array $data
     * @param string $url
     * @param array $miniprogram
     * @return bool|CustomerService
     */
    public function send(string $template_id, array $data, $url = '', $miniprogram = [])
    {
        return $this->app->cs()->template($template_id, $data, $url, $miniprogram);
    }

    /**
     * 通过API推送订阅模板消息给到授权微信用户
     *
     * @param string $scene
     * @param string $title
     * @param string $template_id
     * @param array $data
     * @param string $url
     * @param array $miniprogram
     * @return bool|CustomerService
     */
    public function subscribeOnce(string $scene, string $title, string $template_id, array $data, $url = '', $miniprogram = [])
    {
        return $this->app->cs()->subscribeOnce($scene, $title, $template_id, $data, $url, $miniprogram);
    }

    /**
     * 获取一次给用户推送一条订阅模板消息的机会
     * @param int $scene
     * @param string $template_id
     * @param string $redirect_url
     * @param string $reserved
     * @return \Illuminate\Http\RedirectResponse
     */
    public function subscribeOnceRedirect(int $scene, string $template_id, string $redirect_url, $reserved = '')
    {
        $targetUrl = 'https://mp.weixin.qq.com/mp/subscribemsg?action=get_confirm&appid=' . $this->app->getConfig('app_id') . '&scene=' . $scene . '&template_id=' . $template_id . '&redirect_url=' . urlencode($redirect_url) . '&reserved=' . $reserved . '#wechat_redirect';
        return response()->redirectTo($targetUrl)->send();
    }

}
