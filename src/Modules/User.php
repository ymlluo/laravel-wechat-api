<?php


namespace ymlluo\WxApi\Modules;


use ymlluo\WxApi\Helpers\Http;
use ymlluo\WxApi\WxApi;

class User
{
    const PATH_TAGS_CREATE = '/cgi-bin/tags/create';
    const PATH_TAGS_UPDATE = '/cgi-bin/tags/update';
    const PATH_TAGS_GET = '/cgi-bin/tags/get';
    const PATH_TAGS_DELETE = '/cgi-bin/tags/delete';
    const PATH_TAGS_USER_LIST = '/cgi-bin/user/tag/get';
    const PATH_TAGS_ADD_USERS = '/cgi-bin/tags/members/batchtagging';
    const PATH_TAGS_REMOVE_USERS = '/cgi-bin/tags/members/batchuntagging';
    const PATH_USER_TAGS = '/cgi-bin/tags/getidlist';
    const PATH_USER_REMARK = '/cgi-bin/user/info/updateremark';
    const PATH_USER_INFO = '/cgi-bin/user/info';
    const PATH_USER_INFO_BATCH_GET = '/cgi-bin/user/info/batchget';
    const PATH_USER_LIST = '/cgi-bin/user/get';
    const PATH_USER_BLACK_LIST = '/cgi-bin/tags/members/getblacklist';
    const PATH_USER_BLACK_ADD = '/cgi-bin/tags/members/batchblacklist';
    const PATH_USER_BLACK_REMOVE = '/cgi-bin/tags/members/batchunblacklist';


    protected $app;
    public $access_token;
    public $httpClient;

    /**
     * Resource constructor.
     * @param string $access_token
     * @throws \Exception
     */
    public function __construct(WxApi $app)
    {
        $this->app = $app;
        $this->access_token = $app->accessToken();
        $this->httpClient = new Http();
    }

    /**
     *  创建标签 一个公众号，最多可以创建100个标签
     * @param $name
     * @return mixed
     * @throws \Exception
     */
    public function tagCreate($name)
    {
        $url = $this->app->getConfig('api_origin') . self::PATH_TAGS_CREATE . '?access_token=' . $this->access_token;
        $response = $this->httpClient->post($url, ['tag' => ['name' => $name]]);
        $data = $response->throw()->json();
        return $data;
    }

    /**
     * 获取公众号已创建的标签
     *
     * @return mixed
     * @throws \Exception
     */
    public function tagGet()
    {
        $url = $this->app->getConfig('api_origin') . self::PATH_TAGS_GET . '?access_token=' . $this->access_token;
        $response = $this->httpClient->get($url);
        $data = $response->throw()->json();
        return $data;
    }

    /**
     * 编辑标签
     * @param $tagId
     * @param $name
     * @return mixed
     * @throws \Exception
     */
    public function tagUpdate($tagId, $name)
    {
        $url = $this->app->getConfig('api_origin') . self::PATH_TAGS_CREATE . '?access_token=' . $this->access_token;
        $response = $this->httpClient->post($url, ['tag' => ['id' => $tagId, 'name' => $name]]);
        $data = $response->throw()->json();
        return $data;
    }

    /**
     * 删除标签
     *
     * @param $tagId
     * @return mixed
     * @throws \Exception
     */
    public function tagDelete($tagId)
    {
        $url = $this->app->getConfig('api_origin') . self::PATH_TAGS_DELETE . '?access_token=' . $this->access_token;
        $response = $this->httpClient->post($url, ['tag' => ['id' => $tagId]]);
        $data = $response->throw()->json();
        return $data;
    }

    /**
     * 获取标签下粉丝列表
     *
     * @param $tagId
     * @param string $nextOpenid
     * @return mixed
     * @throws \Exception
     */
    public function tagUserList($tagId, $nextOpenid = '')
    {
        $url = $this->app->getConfig('api_origin') . self::PATH_TAGS_USER_LIST . '?access_token=' . $this->access_token;
        $response = $this->httpClient->post($url, ['tagid' => $tagId, 'next_openid' => $nextOpenid]);
        $data = $response->throw()->json();
        return $data;
    }

    /**
     * 批量为用户打标签
     *
     * @param int $tagId
     * @param array $openidList
     * @return mixed
     * @throws \Exception
     */
    public function tagAddUsers(int $tagId,  $openidList)
    {
        $url = $this->app->getConfig('api_origin') . self::PATH_TAGS_ADD_USERS . '?access_token=' . $this->access_token;
        $response = $this->httpClient->post($url, ['tagid' => $tagId, 'openid_list' => (array)$openidList]);
        $data = $response->throw()->json();
        return $data;
    }

    /**
     * 批量为用户取消标签
     * @param int $tagId
     * @param array $openidList
     * @return mixed
     * @throws \Exception
     */
    public function tagRemoveUsers(int $tagId, array $openidList)
    {
        $url = $this->app->getConfig('api_origin') . self::PATH_TAGS_REMOVE_USERS . '?access_token=' . $this->access_token;
        $response = $this->httpClient->post($url, ['tagid' => $tagId, 'openid_list' => $openidList]);
        $data = $response->throw()->json();
        return $data;
    }

    /**
     * 获取用户身上的标签列表
     * @param string $openid
     * @return mixed
     * @throws \Exception
     */
    public function userTags(string $openid)
    {
        $url = $this->app->getConfig('api_origin') . self::PATH_USER_TAGS . '?access_token=' . $this->access_token;
        $response = $this->httpClient->post($url, ['openid' => $openid]);
        $data = $response->throw()->json();
        return $data;
    }

    /**
     * 对指定用户设置备注名
     * @param string $openid
     * @param string $remark
     * @return mixed
     * @throws \Exception
     */
    public function remark(string $openid, string $remark)
    {
        $url = $this->app->getConfig('api_origin') . self::PATH_USER_REMARK . '?access_token=' . $this->access_token;
        $response = $this->httpClient->post($url, ['openid' => $openid, 'remark' => $remark]);
        $data = $response->throw()->json();
        return $data;
    }

    /**
     * 获取用户基本信息
     *
     * @param string $openid
     * @return mixed
     * @throws \Exception
     */
    public function info(string $openid)
    {
        $url = $this->app->getConfig('api_origin') . self::PATH_USER_INFO . '?access_token=' . $this->access_token . '&openid=' . $openid . '&lang=zh_CN';
        $response = $this->httpClient->get($url);
        $data = $response->throw()->json();
        \Log::debug('info',$data);
        return $data;
    }

    /**
     * 批量获取用户基本信息。最多支持一次拉取100条
     * @param string $lang
     * @param mixed ...$openid
     * @return mixed
     * @throws \Exception
     */
    public function batchInfo(array $openids, $lang = 'zh_CN')
    {
        $userList = [];
        foreach ($openids as $openid) {
            $userList[] = ['lang' => $lang, 'openid' => $openid];
        }
        $url = $this->app->getConfig('api_origin') . self::PATH_USER_INFO_BATCH_GET . '?access_token=' . $this->access_token;
        $response = $this->httpClient->post($url, ['user_list' => $userList]);
        $data = $response->throw()->json();
        return $data;
    }

    /**
     * 获取帐号的关注者列表
     *
     * @param string $nextOpenid
     * @return mixed
     * @throws \Exception
     */
    public function list(string $nextOpenid = '')
    {
        $url = $this->app->getConfig('api_origin') . self::PATH_USER_LIST . '?access_token=' . $this->access_token . '&next_openid=' . $nextOpenid;
        $response = $this->httpClient->get($url);
        $data = $response->throw()->json();
        return $data;
    }

    /**
     * 获取公众号的黑名单列表
     *
     * @param string $beginOpenid
     * @return mixed
     * @throws \Exception
     */
    public function blackList(string $beginOpenid = '')
    {
        $url = $this->app->getConfig('api_origin') . self::PATH_USER_BLACK_LIST . '?access_token=' . $this->access_token . '&next_openid=' . $beginOpenid;
        $response = $this->httpClient->post($url, ['begin_openid' => $beginOpenid]);
        $data = $response->throw()->json();
        return $data;
    }

    /**
     * 拉黑用户
     *
     * @param $openidList //只能拉黑20个用户
     * @return mixed
     * @throws \Exception
     */
    public function blackAdd($openidList)
    {
        $url = $this->app->getConfig('api_origin') . self::PATH_USER_BLACK_ADD . '?access_token=' . $this->access_token;
        $response = $this->httpClient->post($url, ['openid_list' => (array)$openidList]);
        $data = $response->throw()->json();
        return $data;
    }

    /**
     * 取消拉黑用户
     *
     * @param $openidList //只能拉黑20个用户
     * @return mixed
     * @throws \Exception
     */
    public function blackRemove($openidList)
    {
        $url = $this->app->getConfig('api_origin') . self::PATH_USER_BLACK_REMOVE . '?access_token=' . $this->access_token;
        $response = $this->httpClient->post($url, ['openid_list' => (array)$openidList]);
        $data = $response->throw()->json();
        return $data;
    }


}
