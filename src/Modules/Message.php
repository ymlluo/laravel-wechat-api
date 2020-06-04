<?php


namespace ymlluo\WxApi\Modules;


use Illuminate\Support\Arr;
use ymlluo\WxApi\Events\MessageReply;
use ymlluo\WxApi\Support\Encrypt;
use ymlluo\WxApi\Support\XML;

class Message
{
    const MSG_TYPE_TEXT = 'text';
    const MSG_TYPE_IMAGE = 'image';
    const MSG_TYPE_LOCATION = 'location';
    const MSG_TYPE_LINK = 'link';
    const MSG_TYPE_EVENT = 'event';
    const MSG_TYPE_MUSIC = 'music';
    const MSG_TYPE_NEWS = 'news';
    const MSG_TYPE_VOICE = 'voice';
    const MSG_TYPE_VIDEO = 'video';
    const MSG_TYPE_SHORT_VIDEO = 'shortvideo';

    const EVENT_SUBSCRIBE = 'subscribe';
    const EVENT_UNSUBSCRIBE = 'unsubscribe';
    const EVENT_SCAN_QR = 'SCAN';
    const EVENT_LOCATION = 'LOCATION';
    const EVENT_MENU_VIEW = 'VIEW';
    const EVENT_MENU_CLICK = 'CLICK';
    const EVENT_TEMPLATE_SEND = 'TEMPLATESENDJOBFINISH';
    const EVENT_SCANCODE_PUSH = 'scancode_push';
    const EVENT_SCANCODE_WAIT_MSG = 'scancode_waitmsg';
    const EVENT_PIC_SYS_PHOTO = 'pic_sysphoto';
    const EVENT_PIC_WEI_XIN = 'pic_weixin';
    const EVENT_LOCATION_SELECT = 'location_select';
    const EVENT_VIEW_MINI_PROGRAM = 'view_miniprogram';
    const EVENT_USER_PAY_FROM_PAY_CELL = 'user_pay_from_pay_cell';


    public $message;
    public $encrypt;
    public $replyData;

    public function __construct($message, $encrypt)
    {
        $this->message = $message;
        $this->encrypt = $encrypt;
    }

    public function isText()
    {
        return $this->msgType() == self::MSG_TYPE_TEXT;
    }

    public function isMsgMenuClick()
    {
        return $this->msgType() == self::MSG_TYPE_TEXT && $this->bizmsgmenuid();
    }

    public function isImage()
    {
        return $this->msgType() == self::MSG_TYPE_IMAGE;
    }

    public function isVoice()
    {
        return $this->msgType() == self::MSG_TYPE_VOICE;
    }

    public function isVideo()
    {
        return $this->msgType() == self::MSG_TYPE_VIDEO;
    }


    public function isShortVideo()
    {
        return $this->msgType() == self::MSG_TYPE_SHORT_VIDEO;
    }

    public function isLocation()
    {
        return $this->msgType() == self::MSG_TYPE_LOCATION;
    }

    public function isLink()
    {
        return $this->msgType() == self::MSG_TYPE_LINK;
    }

    public function isEvent()
    {
        return $this->msgType() == self::MSG_TYPE_EVENT;
    }

    public function isSubscribe()
    {
        return $this->isEvent() && $this->msgEvent() == self::EVENT_SUBSCRIBE && !$this->eventTicket();
    }

    public function isScanQrCode()
    {
        return $this->isEvent() && $this->eventTicket() && ($this->msgEvent() == self::EVENT_SUBSCRIBE || $this->msgEvent() == self::EVENT_SCAN_QR);
    }

    public function isUnsubscribeScanQrCode()
    {
        return $this->isEvent() && $this->eventTicket() && $this->msgEvent() == self::EVENT_SUBSCRIBE;
    }

    public function isSubscribedScanQrCode()
    {
        return $this->isEvent() && $this->eventTicket() && $this->msgEvent() == self::EVENT_SCAN_QR;
    }

    public function isLocationReporting()
    {
        return $this->isEvent() && $this->msgEvent() == self::EVENT_LOCATION;
    }

    public function isMenuClick()
    {
        return $this->isEvent() && $this->msgEvent() == self::EVENT_MENU_CLICK;
    }

    public function isMenuView()
    {
        return $this->isEvent() && $this->msgEvent() == self::EVENT_MENU_CLICK;
    }

    public function isTemplateSend()
    {

        return $this->isEvent() && $this->msgEvent() == self::EVENT_TEMPLATE_SEND;
    }

    public function isTemplateSendSuccessful()
    {
        return $this->isTemplateSend() && $this->status() == 'success';
    }

    public function isTemplateSendFailed()
    {
        return $this->isTemplateSend() && $this->status() != 'success';
    }

    public function isTemplateSendBlock()
    {
        return $this->isTemplateSendFailed() && $this->status() == 'failed:user block';
    }

    public function isTemplateSendFailedOther()
    {
        return $this->isTemplateSendFailed() && !$this->isTemplateSendBlock();
    }


    public function eventTicket()
    {
        return data_get($this->message, 'Ticket');
    }

    /**
     * 是否扫码推事件的事件推送
     *
     * @return bool
     */
    public function isScancodePush()
    {
        return $this->isEvent() && $this->msgEvent() == self::EVENT_SCANCODE_PUSH;
    }

    /**
     * scancode_waitmsg：扫码推事件且弹出“消息接收中”提示框的事件推送
     * @return bool
     */
    public function isScancodeWaitMsg()
    {
        return $this->isEvent() && $this->msgEvent() == self::EVENT_SCANCODE_WAIT_MSG;
    }

    /**
     * pic_sysphoto：弹出系统拍照发图的事件推送
     * @return bool
     */
    public function isPicSysPhoto()
    {
        return $this->isEvent() && $this->msgEvent() == self::EVENT_PIC_SYS_PHOTO;
    }

    /**
     * pic_weixin：弹出微信相册发图器的事件推送
     * @return bool
     */
    public function isPicWeiXin()
    {
        return $this->isEvent() && $this->msgEvent() == self::EVENT_PIC_WEI_XIN;
    }

    /**
     * location_select：弹出地理位置选择器的事件推送
     *
     * @return bool
     */
    public function isLocationSelect()
    {
        return $this->isEvent() && $this->msgEvent() == self::EVENT_LOCATION_SELECT;
    }

    /**
     * 点击菜单跳转小程序的事件推送
     * @return bool
     */
    public function isViewMiniProgram()
    {
        return $this->isEvent() && $this->msgEvent() == self::EVENT_VIEW_MINI_PROGRAM;
    }

    public function isUserPayFromPayCell()
    {

        return $this->isEvent() && $this->msgEvent() == self::EVENT_USER_PAY_FROM_PAY_CELL;
    }

    /**
     * 卡券ID。
     * @return array|mixed
     */
    public function cardId()
    {
        return data_get($this->message, 'CardId');
    }

    /**
     * 卡券Code码
     * @return array|mixed
     */
    public function userCardCode()
    {
        return data_get($this->message, 'UserCardCode');
    }

    /**
     * 实付金额，单位为分
     * @return array|mixed
     */
    public function transId()
    {
        return data_get($this->message, 'TransId');
    }

    public function fee()
    {
        return data_get($this->message, 'Fee');
    }

    /**
     * 应付金额，单位为分
     * @return array|mixed
     */
    public function originalFee()
    {
        return data_get($this->message, 'OriginalFee');
    }

    /**
     * 门店名称，当前卡券核销的门店名称（只有通过卡券商户助手和买单核销时才会出现）
     * @return array|mixed
     */
    public function locationName()
    {
        return data_get($this->message, 'LocationName');
    }

    /**
     * 门店ID
     * @return array|mixed
     */
    public function locationId()
    {
        return data_get($this->message, 'LocationId');
    }


    public function sendLocationInfo()
    {
        return data_get($this->message, 'SendLocationInfo', []);
    }

    /**
     * 发送的图片信息
     * @return array|mixed
     */
    public function sendPicsInfo()
    {
        return data_get($this->message, 'SendPicsInfo', []);
    }

    /**
     *    图片 MD5 列表
     * @return array|mixed
     */
    public function picList()
    {
        return data_get($this->sendPicsInfo(), 'PicList', []);
    }

    /**
     * 发送的图片数量
     * @return array|mixed
     */
    public function picCount()
    {
        return data_get($this->sendPicsInfo(), 'Count', 0);
    }

    /**
     * 扫描信息
     * @return array|mixed
     */
    public function scanCodeInfo()
    {
        return data_get($this->message, 'ScanCodeInfo', []);
    }

    /**
     * 扫描类型，一般是qrcode
     *
     * @return array|mixed
     */
    public function scanType()
    {
        return data_get($this->scanCodeInfo(), 'ScanType');
    }

    /**
     * 扫描结果，即二维码对应的字符串信息
     *
     * @return array|mixed
     */
    public function scanResult()
    {
        return data_get($this->scanCodeInfo(), 'ScanResult');
    }

    public function menuId()
    {
        return data_get($this->message, 'MenuId');
    }

    public function toUserName()
    {
        return data_get($this->message, 'ToUserName');
    }

    public function fromUserName()
    {
        return data_get($this->message, 'FromUserName');
    }

    public function createTime()
    {
        return data_get($this->message, 'CreateTime');
    }

    public function msgType()
    {
        return data_get($this->message, 'MsgType');
    }

    public function msgEvent()
    {
        return data_get($this->message, 'Event');
    }

    public function msgEventKey()
    {
        return data_get($this->message, 'EventKey');
    }

    public function msgId()
    {
        return data_get($this->message, 'MsgId');
    }

    public function content()
    {
        return data_get($this->message, 'Content');
    }

    public function mediaId()
    {
        return data_get($this->message, 'MediaId');
    }

    public function picUrl()
    {
        return data_get($this->message, 'PicUrl');
    }

    public function voiceFormat()
    {
        return data_get($this->message, 'Format');
    }

    public function voiceRecognition()
    {
        return data_get($this->message, 'Recognition');
    }

    public function thumbMediaId()
    {
        return data_get($this->message, 'ThumbMediaId');
    }

    public function status()
    {
        return data_get($this->message, 'Status');
    }


    public function location()
    {
        if ($this->isEvent()) {
            return [
                'x' => $this->locationX(),
                'y' => $this->locationY(),
                'scale' => $this->locationScale(),
                'label' => $this->locationLabel(),
                'lat' => $this->locationX(),
                'lng' => $this->locationY()
            ];
        }
        return [
            'x' => $this->latitude(),
            'y' => $this->longitude(),
            'precision' => $this->precision(),
            'lat' => $this->latitude(),
            'lng' => $this->longitude()
        ];

    }

    public function locationX()
    {
        return data_get($this->message, 'Location_X');
    }

    public function locationY()
    {
        return data_get($this->message, 'Location_Y');
    }

    public function locationScale()
    {
        return data_get($this->message, 'Scale');
    }

    public function locationLabel()
    {
        return data_get($this->message, 'Label');
    }

    public function title()
    {
        return data_get($this->message, 'Title');
    }

    public function description()
    {
        return data_get($this->message, 'Description');
    }

    public function url()
    {
        return data_get($this->message, 'Url');
    }


    public function latitude()
    {
        return data_get($this->message, 'Latitude');
    }

    public function lat()
    {
        return $this->latitude();
    }

    public function longitude()
    {
        return data_get($this->message, 'Longitude');
    }

    public function lng()
    {

        return $this->longitude();
    }

    public function precision()
    {
        return data_get($this->message, 'Precision');
    }

    public function bizmsgmenuid()
    {
        return data_get($this->message, 'bizmsgmenuid');
    }


    public function text($text = '', $append = false)
    {
        Arr::set($this->replyData, 'MsgType', 'text');
        if (!$append){
            $this->replyData['Content'] = '';
        }
        Arr::set($this->replyData, 'Content', $this->replyData['Content'].$text);

        return $this->send();
    }

    public function image($mediaId)
    {
        Arr::set($this->replyData, 'MsgType', 'image');
        Arr::set($this->replyData, 'Image.MediaId', $mediaId);
        return $this->send();
    }

    public function voice($mediaId)
    {
        Arr::set($this->replyData, 'MsgType', 'voice');
        Arr::set($this->replyData, 'Voice.MediaId', $mediaId);
        return $this->send();
    }

    public function video($mediaId, $title = '', $description = '')
    {
        Arr::set($this->replyData, 'MsgType', 'video');
        Arr::set($this->replyData, 'Video.MediaId', $mediaId);
        Arr::set($this->replyData, 'Video.Title', $title);
        Arr::set($this->replyData, 'Video.Description', $description);
        return $this->send();
    }


    public function music($musicUrl, $thumbMediaId, $title = '', $description = '', $hqMusicUrl = '')
    {
        Arr::set($this->replyData, 'MsgType', 'music');
        Arr::set($this->replyData, 'Music.Title', $title);
        Arr::set($this->replyData, 'Music.Description', $description);
        Arr::set($this->replyData, 'Music.MusicUrl', $musicUrl);
        Arr::set($this->replyData, 'Music.HQMusicUrl', $hqMusicUrl ?: $musicUrl);
        Arr::set($this->replyData, 'Music.ThumbMediaId', $thumbMediaId);
        return $this->send();
    }

    public function addNews(array $news = [], $append = false)
    {
        if (!is_array(reset($news))) {
            $news = [$news];
        }
        if ($append) {
            $news = array_merge((array)$this->message['Articles'], $news);
        }
        Arr::set($this->replyData, 'MsgType', 'news');

        if (in_array($this->msgType(), [
            self::MSG_TYPE_TEXT,
            self::MSG_TYPE_IMAGE,
            self::MSG_TYPE_VIDEO,
            self::MSG_TYPE_NEWS,
            self::MSG_TYPE_LOCATION,
        ])) {
            $news = array_slice($news, 0, 1);
        }
        $news = array_slice($news, 0, 8);
        Arr::set($this->replyData, 'Articles', $news);
        Arr::set($this->replyData, 'ArticleCount', count($news));
        return $this;
    }

    public function news($title, $description, $picUrl, $url)
    {
        $news = [
            'Title' => $title,
            'Description' => $description,
            'PicUrl' => $picUrl,
            'Url' => $url,
        ];
        return $this->addNews($news, true);
    }


    public function reply()
    {
        Arr::set($this->replyData, 'ToUserName', $this->fromUserName());
        Arr::set($this->replyData, 'FromUserName', $this->toUserName());
        Arr::set($this->replyData, 'CreateTime', time());
        return $this->send();
    }

    public function success()
    {
        $this->replyData = [];
        return response('success')->send();
    }

    private function send()
    {
        if (!isset($this->replyData['MsgType']) || !isset($this->replyData['ToUserName'])) {
            return $this;
        }
        $xml = XML::generateXml($this->replyData);
        if ($this->encrypt) {
            $xml = Encrypt::encodeXML(
                app('wxapi')->getConfig('app_id'),
                app('wxapi')->getConfig('aes_key'),
                app('wxapi')->getConfig('token'),
                $xml
            );
        }
        if (function_exists('event')) {
            event(new MessageReply($this->replyData));
        }

        response($xml)->send();
        die();
    }

    public function __toString()
    {
        return json_encode($this->message,JSON_UNESCAPED_UNICODE);
    }

}
