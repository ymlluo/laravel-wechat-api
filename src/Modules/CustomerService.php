<?php


namespace ymlluo\WxApi\Modules;


use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use ymlluo\WxApi\Exceptions\WxException;
use ymlluo\WxApi\Helpers\Http;
use ymlluo\WxApi\WxApi;

class CustomerService
{

    const PATH_ACCOUNT_ADD = '/customservice/kfaccount/add';
    const PATH_ACCOUNT_UPDATE = '/customservice/kfaccount/update';
    const PATH_ACCOUNT_DEL = '/customservice/kfaccount/del';
    const PATH_ACCOUNT_SET_AVATAR = '/customservice/kfaccount/uploadheadimg';
    const PATH_ACCOUNT_LIST = '/cgi-bin/customservice/getkflist';
    const PATH_MESSAGE_SEND = '/cgi-bin/message/custom/send';
    const PATH_MESSAGE_TYPING_SEND = '/cgi-bin/message/custom/typing';
    const PATH_MESSAGE_TEMPLATE_SEND = '/cgi-bin/message/template/send';
    const PATH_MESSAGE_TEMPLATE_SUBSCRIBE = '/cgi-bin/message/template/subscribe';

    protected $app;
    public $access_token;
    public $httpClient;
    public $message = [];


    public function __construct(WxApi $app)
    {
        $this->message = [];
        $this->app = $app;
        $this->access_token = $app->accessToken();
        $this->httpClient = new Http();
    }

    /**
     *  发送各种消息的便捷方法
     *
     * @param $type
     * @param $params
     * @return mixed
     * @throws WxException
     */
    public function reply($type, $params)
    {
        if (!method_exists(__CLASS__, $type)) {
            throw new WxException('unsupported message type');
        }
        return $this->{$type}($params);
    }

    /**
     * 发送文本消息
     *
     * @param string $text
     * @return $this|bool
     */
    public function text(string $text)
    {
        Arr::set($this->message, 'msgtype', 'text');
        Arr::set($this->message, 'text.content', $text);
        return $this->sendMessage();
    }

    /**
     * 发送图片消息
     *
     * @param string $media
     * @return $this|bool
     */
    public function image(string $media)
    {
        Arr::set($this->message, 'msgtype', 'image');
        Arr::set($this->message, 'image.media_id', $this->app->resource()->mediaId($media,'image'));
        return $this->sendMessage();
    }

    /**
     * 发送语音消息
     *
     * @param string $media
     * @return $this|bool
     */
    public function voice(string $media)
    {
        Arr::set($this->message, 'msgtype', 'voice');
        Arr::set($this->message, 'voice.media_id', $this->app->resource()->mediaId($media,'voice'));
        return $this->sendMessage();
    }

    /**
     * 发送视频消息
     *
     * @param string $title
     * @param string $description
     * @param string $media_id
     * @param string $thumb_media_id
     * @return $this|bool
     */
    public function video(string $title, string $description, string $media_id, string $thumb_media_id)
    {
        Arr::set($this->message, 'msgtype', 'video');
        Arr::set($this->message, 'video.media_id', $this->app->resource()->mediaId($media_id,'video'));
        if ($thumb_media_id){
            Arr::set($this->message, 'video.thumb_media_id', $this->app->resource()->mediaId($thumb_media_id, 'thumb'));
        }

        Arr::set($this->message, 'video.title', $title);
        Arr::set($this->message, 'video.description', $description);
        return $this->sendMessage();
    }

    /**
     * 发送音乐消息
     *
     * @param string $title
     * @param string $description
     * @param string $musicurl
     * @param string $hqmusicurl
     * @param string $thumb_media_id
     * @return $this|bool
     */
    public function music(string $title, string $description, string $musicurl, string $hqmusicurl, string $thumb_media_id)
    {
        Arr::set($this->message, 'msgtype', 'music');
        Arr::set($this->message, 'music.musicurl', $musicurl);
        Arr::set($this->message, 'music.hqmusicurl', $hqmusicurl);
        Arr::set($this->message, 'music.thumb_media_id', $this->app->resource()->mediaId($thumb_media_id));
        Arr::set($this->message, 'music.title', $title);
        Arr::set($this->message, 'music.description', $description);
        return $this->sendMessage();
    }

    /**
     * 发送图文消息（点击跳转到外链）
     *
     * @param string $title
     * @param string $description
     * @param string $url
     * @param string $picurl
     * @return $this|bool
     */
    public function news(string $title, string $description, string $url, string $picurl)
    {
        Arr::set($this->message, 'msgtype', 'news');
        Arr::set($this->message, 'news.articles.0.title', $title);
        Arr::set($this->message, 'news.articles.0.description', $description);
        Arr::set($this->message, 'news.articles.0.url', $url);
        Arr::set($this->message, 'news.articles.0.picurl', $picurl);
        return $this->sendMessage();
    }

    /**
     * 发送图文消息（点击跳转到图文消息页面）
     *
     * @param string $media
     * @return $this|bool
     */
    public function mpNews(string $media)
    {
        Arr::set($this->message, 'msgtype', 'mpnews');
        Arr::set($this->message, 'mpnews.media_id', $this->app->resource()->mediaId($media));
        return $this->sendMessage();
    }

    /**
     * 发送菜单消息
     *
     * @param array $list
     * [
     * {
     * "id": "101",
     * "content": "满意"
     * },
     * {
     * "id": "102",
     * "content": "不满意"
     * }
     * ]
     * @param string $head_content
     * @param string $tail_content
     * @return $this|bool
     */
    public function msgMenu(array $list, string $head_content = '', string $tail_content = '')
    {
        Arr::set($this->message, 'msgtype', 'msgmenu');
        Arr::set($this->message, 'msgmenu.head_content', $head_content);
        Arr::set($this->message, 'msgmenu.list', $list);
        Arr::set($this->message, 'msgmenu.tail_content', $tail_content);
        return $this->sendMessage();
    }

    /**
     * 发送卡券
     *
     * @param string $cardId
     * @return $this|bool
     */
    public function wxCard(string $cardId)
    {
        Arr::set($this->message, 'msgtype', 'wxcard');
        Arr::set($this->message, 'wxcard.card_id', $cardId);
        return $this->sendMessage();
    }

    /**
     * 发送小程序卡片（要求小程序与公众号已关联）
     *
     * @param string $title
     * @param string $appid
     * @param $pagepath
     * @param $thumb_media_id
     * @return $this|bool
     */
    public function miniProgramPage(string $title, string $appid, $pagepath, $thumb_media_id)
    {
        Arr::set($this->message, 'msgtype', 'miniprogrampage');
        Arr::set($this->message, 'miniprogrampage.title', $title);
        Arr::set($this->message, 'miniprogrampage.appid', $appid);
        Arr::set($this->message, 'miniprogrampage.pagepath', $pagepath);
        Arr::set($this->message, 'miniprogrampage.thumb_media_id', $this->app->resource()->mediaId($thumb_media_id));
        return $this->sendMessage();
    }

    public function template(string $template_id, array $data, $url = '', $miniprogram = [])
    {
        Arr::set($this->message, 'template_id', $template_id);
        Arr::set($this->message, 'data', $data);
        Arr::set($this->message, 'url', $url);
        Arr::set($this->message, 'miniprogram', $miniprogram);
        return $this->sendMessage();
    }

    public function subscribeOnce(string $scene, string $title, string $template_id, array $data, $url = '', $miniprogram = [])
    {
        Arr::set($this->message, 'scene', $scene);
        Arr::set($this->message, 'title', $title);
        Arr::set($this->message, 'template_id', $template_id);
        Arr::set($this->message, 'data', $data);
        Arr::set($this->message, 'url', $url);
        Arr::set($this->message, 'miniprogram', $miniprogram);
        return $this->sendMessage();
    }


    /**
     * 设置消息接收 openid
     *
     * @param string $openid
     * @return $this|bool
     */
    public function toUser(string $openid)
    {
        Arr::set($this->message, 'touser', $openid);
        return $this->sendMessage();
    }

    /**
     * 设置发送人（新版客服账号）
     *
     * @param string $kf_account
     * @return $this|bool
     */
    public function fromAccount(string $kf_account)
    {
        Arr::set($this->message, 'customservice.kf_account', $kf_account);
        return $this->sendMessage();
    }

    /**
     * 客服输入状态
     *
     * @return $this|bool
     * @throws \Exception
     */
    public function sendTyping()
    {
        Arr::set($this->message, 'command', 'Typing');
        if (!$openid = data_get($this->message, 'touser')) {
            return $this;
        }
        if ($this->typingDisabled($openid)) {
            return false;
        }
        $apiUrl = $this->getConfig('api_origin') . self::PATH_MESSAGE_TYPING_SEND . '?access_token=' . $this->access_token;
        $response = $this->httpClient->post($apiUrl, $this->message);
        $data = $response->throw()->json();
        if (data_get($data, 'errmsg') === 'ok') {

        }
        return $data;
    }

    /**
     * 发送客服消息
     *
     * @return $this|bool
     * @throws \Exception
     */
    private function sendMessage()
    {
        Log::info('bf', $this->message);
        $this->message = array_filter($this->message);
        if (data_get($this->message, 'command') == 'Typing') {
            return $this->sendTyping();
        }
        if (data_get($this->message, 'template_id') && data_get($this->message, 'touser')) {
            return $this->sendTemplate();
        }
        if (!data_get($this->message, 'msgtype') || !$openid = data_get($this->message, 'touser')) {
            return $this;
        }
        $apiUrl = $this->getConfig('api_origin') . self::PATH_MESSAGE_SEND . '?access_token=' . $this->access_token;
       return retry(1,function ()use ($apiUrl,$openid){
           try{
               $response = $this->httpClient->post($apiUrl, $this->message);
               $response->throw();
               $data = $response->json();
               if (data_get($data, 'errmsg') === 'ok') {
                   $this->setTypingAllowed($openid);
               }
               $data['source']=$this->message;
               return $data;
           }catch (\Exception $exception){
               if ($exception->getCode() == '42001'){
                   $this->access_token = $this->app->auth()->refreshToken();
               }
               throw $exception;
           }

        },100);

    }

    /**
     * 发送模板消息
     *
     * @return $this
     * @throws \Exception
     */
    public function sendTemplate()
    {
        $this->message = array_filter($this->message);
        if (!$openid = data_get($this->message, 'touser') || !data_get($this->message, 'template_id')) {
            return $this;
        }
        if (data_get($this->message, 'scene') && data_get($this->message, 'title')) {
            return $this->sendTemplateSubscribe();
        }
        $apiUrl = $this->getConfig('api_origin') . self::PATH_MESSAGE_TEMPLATE_SEND . '?access_token=' . $this->access_token;
        $response = $this->httpClient->post($apiUrl, $this->message);
        $data = $response->throw()->json();
        return $data;
    }

    /**
     * 通过API推送订阅模板消息给到授权微信用户
     *
     * @return mixed
     * @throws \Exception
     */
    public function sendTemplateSubscribe()
    {

        $apiUrl = $this->getConfig('api_origin') . self::PATH_MESSAGE_TEMPLATE_SUBSCRIBE . '?access_token=' . $this->access_token;
        $response = $this->httpClient->post($apiUrl, $this->message);
        $data = $response->throw()->json();
        return $data;
    }


    /**
     * 获取配置内容
     *
     * @param $key
     * @return array|mixed
     */
    private function getConfig($key)
    {
        return data_get($this->app->configs, $key);
    }

    /**
     * 添加客服账号
     *
     * @param $kf_account
     * @param $nickname
     * @param $password
     * @return mixed
     * @throws \Exception
     */
    public function accountAdd($kf_account, $nickname, $password)
    {
        $response = $this->httpClient->post($this->getConfig('api_origin') . self::PATH_ACCOUNT_ADD . '?access_token=' . $this->access_token, [
            'kf_account' => $kf_account,
            'nickname' => $nickname,
            'password' => md5($password)
        ]);

        return $response->throw()->json();
    }

    /**
     * 更新客服账号
     *
     * @param $kf_account
     * @param $nickname
     * @param $password
     * @return mixed
     * @throws \Exception
     */
    public function accountUpdate($kf_account, $nickname, $password)
    {
        $response = $this->httpClient->post($this->getConfig('api_origin') . self::PATH_ACCOUNT_UPDATE . '?access_token=' . $this->access_token, [
            'kf_account' => $kf_account,
            'nickname' => $nickname,
            'password' => md5($password)
        ]);

        return $response->throw()->json();
    }

    /**
     * 删除客服账号
     *
     * @param $kf_account
     * @param $nickname
     * @param $password
     * @return mixed
     * @throws \Exception
     */
    public function accountDel($kf_account, $nickname, $password)
    {
        $response = $this->httpClient->post($this->getConfig('api_origin') . self::PATH_ACCOUNT_DEL . '?access_token=' . $this->access_token, [
            'kf_account' => $kf_account,
            'nickname' => $nickname,
            'password' => md5($password)
        ]);

        return $response->throw()->json();
    }

    /**
     * 设置客服头像，头像图片文件必须是jpg格式，推荐使用640*640大小的图片
     *
     * @param $account
     * @param $path
     */
    public function accountSetAvatar($kf_account, $filepath)
    {
        $response = $this->httpClient->asMultipart()
            ->attach('media', fopen($filepath, 'r'))
            ->post($this->getConfig('api_origin') . self::PATH_ACCOUNT_SET_AVATAR . '?access_token=' . $this->access_token . '&kf_account=' . $kf_account);
        return $response->throw()->json();
    }

    /**
     * 获取客服列表
     *
     * @return mixed
     * @throws \Exception
     */
    public function accountList()
    {
        $response = $this->httpClient->get($this->getConfig('api_origin') . self::PATH_ACCOUNT_LIST . '?access_token=' . $this->access_token);
        return $response->throw()->json();
    }

    /**
     * typing 缓存 Key
     *
     * @param $openid
     * @return string
     */
    private function typeLockKey($openid)
    {
        return $this->app->getConfig('cache_prefix') . ':typing' . $openid;
    }

    /**
     * 释放 typing 不允许再发送 typing
     *
     * @param $openid
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    private function typingRelease($openid)
    {
        return cache()->delete($this->typeLockKey($openid));
    }

    /**
     * 设置可以发送typing
     *
     *
     * @param $openid
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    private function setTypingAllowed($openid)
    {
        return cache()->set($this->typeLockKey($openid), 1, 30);
    }

    /**
     * 查询是否可以发送 typing
     *
     * @param $openid
     * @return bool
     * @throws \Exception
     */
    private function typingDisabled($openid)
    {
        return cache()->get($this->typeLockKey($openid)) == null;
    }


}
