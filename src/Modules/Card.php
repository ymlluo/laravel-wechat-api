<?php


namespace ymlluo\WxApi\Modules;


use ymlluo\WxApi\Helpers\Http;
use ymlluo\WxApi\WxApi;

class Card
{

    const PATH_CARD_CREATE = '/card/create';
    const PATH_CARD_UPDATE = '/card/update';
    const PATH_CARD_GET = '/card/get';
    const PATH_CARD_BATCH_GET = '/card/batchget';
    const PATH_CARD_PAY_CELL_SET = '/card/paycell/set';
    const PATH_CARD_SELF_CONSUME_CELL_SET = '/card/selfconsumecell/set';
    const PATH_QR_CODE_CREATE = '/card/qrcode/create';
    const PATH_LANDING_PAGE_CREATE = '/card/landingpage/create';
    const PATH_MP_NEWS_GET_HTML = '/card/mpnews/gethtml';
    const PATH_CODE_GET = '/card/code/get';
    const PATH_CODE_CONSUME = '/card/code/consume';
    const PATH_CODE_DECRYPT = '/card/code/decrypt';
    const PATH_USER_CARD_LIST = '/card/user/getcardlist';

    protected $app;
    public $access_token;
    public $httpClient;

    public function __construct(WxApi $app)
    {
        $this->app = $app;
        $this->access_token = $app->accessToken();
        $this->httpClient = new Http();
    }

    public function uploadLogo($filepath, $filename = null, $useCache = true){
        return $this->app->resource()->mediaUpload($filepath,$filename,'buffer',$useCache);
    }

    /**
     * 创建卡券
     *
     * @link https://developers.weixin.qq.com/doc/offiaccount/Cards_and_Offer/Create_a_Coupon_Voucher_or_Card.html
     * @param $data
     * @return mixed
     * @throws \Exception
     */
    public function create($data){
        $url = $this->app->getConfig('api_origin') . self::PATH_CARD_CREATE . '?access_token=' . $this->access_token;
        $response = $this->httpClient->post($url, $data);
        $data = $response->throw()->json();
        return $data;
    }
    public function update($data){
        $url = $this->app->getConfig('api_origin') . self::PATH_CARD_UPDATE . '?access_token=' . $this->access_token;
        $response = $this->httpClient->post($url, $data);
        $data = $response->throw()->json();
        return $data;
    }

    /**
     * 卡券详情
     * @param $cardId
     * @return mixed
     * @throws \Exception
     */
    public function get($cardId){
        $url = $this->app->getConfig('api_origin') . self::PATH_CARD_GET . '?access_token=' . $this->access_token;
        $response = $this->httpClient->post($url, ['card_id'=>$cardId]);
        $data = $response->throw()->json();
        return $data;
    }

    /**
     * 批量查询卡券列表
     * @param int $offset 查询卡列表的起始偏移量，从0开始，即offset: 5是指从从列表里的第六个开始读取。
     * @param int $count 需要查询的卡片的数量（数量最大50）。
     * @param array $statusList 支持开发者拉出指定状态的卡券列表 “CARD_STATUS_NOT_VERIFY”, 待审核 ； “CARD_STATUS_VERIFY_FAIL”, 审核失败； “CARD_STATUS_VERIFY_OK”， 通过审核； “CARD_STATUS_DELETE”， 卡券被商户删除； “CARD_STATUS_DISPATCH”， 在公众平台投放过的卡券；
     * @return mixed
     * @throws \Exception
     */
    public function batchGet(int $offset,int $count,$statusList=[]){
        $url = $this->app->getConfig('api_origin') . self::PATH_CARD_GET . '?access_token=' . $this->access_token;
        $response = $this->httpClient->post($url, ['offset'=>$offset,'count'=>$count,'status_list'=>$statusList]);
        $data = $response->throw()->json();
        return $data;
    }

    /**
     * 设置买单接口
     * @param $cardId
     * @param bool $isOpen
     * @return mixed
     * @throws \Exception
     */
    public function payCellSet($cardId,$isOpen =true){

        $url = $this->app->getConfig('api_origin') . self::PATH_CARD_PAY_CELL_SET . '?access_token=' . $this->access_token;
        $response = $this->httpClient->post($url, ['card_id'=>$cardId,'is_open'=>$isOpen]);
        $data = $response->throw()->json();
        return $data;
    }

    public function selfConsumeCellSet($cardId,$isOpen =true,$need_verify_cod=false,$need_remark_amount=false){

        $url = $this->app->getConfig('api_origin') . self::PATH_CARD_PAY_CELL_SET . '?access_token=' . $this->access_token;
        $response = $this->httpClient->post($url, [
            'card_id'=>$cardId,
            'is_open'=>$isOpen,
            'need_verify_cod'=>$need_verify_cod,
            'need_remark_amount'=>$need_remark_amount
        ]);
        $data = $response->throw()->json();
        return $data;
    }

    /**
     * 创建二维码接口
     *
     * @link  https://developers.weixin.qq.com/doc/offiaccount/Cards_and_Offer/Distributing_Coupons_Vouchers_and_Cards.html
     * @param $data
     * @return mixed
     * @throws \Exception
     */
    public function qrCodeCreate($data){
        $url = $this->app->getConfig('api_origin') . self::PATH_QR_CODE_CREATE . '?access_token=' . $this->access_token;
        $response = $this->httpClient->post($url, $data);
        $data = $response->throw()->json();
        return $data;
    }
    public function landingPageCreate($data){
        $url = $this->app->getConfig('api_origin') . self::PATH_LANDING_PAGE_CREATE . '?access_token=' . $this->access_token;
        $response = $this->httpClient->post($url, $data);
        $data = $response->throw()->json();
        return $data;
    }

    /**
     * 获取卡券嵌入图文消息的标准格式代码,将返回代码填入 新增临时素材中content字段，即可获取嵌入卡券的图文消息素材。
     * @param $cardId
     * @return mixed
     * @throws \Exception
     */
    public function mpNewsGetHtml($cardId){
        $url = $this->app->getConfig('api_origin') . self::PATH_LANDING_PAGE_CREATE . '?access_token=' . $this->access_token;
        $response = $this->httpClient->post($url, ['card_id'=>$cardId]);
        $data = $response->throw()->json();
        return $data;
    }

    /**
     * 查询Code
     *
     * @link https://developers.weixin.qq.com/doc/offiaccount/Cards_and_Offer/Redeeming_a_coupon_voucher_or_card.html
     * @param $code
     * @param $cardId
     * @param bool $check
     * @return mixed
     * @throws \Exception
     */
    public function codeGet($code,$cardId,$check=false){
        $url = $this->app->getConfig('api_origin') . self::PATH_CODE_GET . '?access_token=' . $this->access_token;
        $response = $this->httpClient->post($url, ['code'=>$code,'card_id'=>$cardId,'check_consume'=>$check]);
        $data = $response->throw()->json();
        return $data;
    }

    /**
     * 将用户的优惠券进行核销
     * @param $code
     * @param $cardId
     * @return mixed
     * @throws \Exception
     */
    public function codeConsume($code,$cardId){
        $url = $this->app->getConfig('api_origin') . self::PATH_CODE_CONSUME . '?access_token=' . $this->access_token;
        $response = $this->httpClient->post($url, ['code'=>$code,'card_id'=>$cardId]);
        $data = $response->throw()->json();
        return $data;
    }

    /**
     * Code解码
     *
     * 1.商家获取choos_card_info后，将card_id和encrypt_code字段通过解码接口，获取真实code。
     * 2.卡券内跳转外链的签名中会对code进行加密处理，通过调用解码接口获取真实code。
     * @param $encryptCode
     * @return mixed
     * @throws \Exception
     */
    public function codeDecrypt($encryptCode){
        $url = $this->app->getConfig('api_origin') . self::PATH_CODE_DECRYPT . '?access_token=' . $this->access_token;
        $response = $this->httpClient->post($url, ['encrypt_code'=>$encryptCode]);
        $data = $response->throw()->json();
        return $data;
    }

    /**
     * 用于获取用户卡包里的，属于该appid下所有可用卡券，包括正常状态和异常状态
     *
     * @param $openid
     * @param string $cardId
     * @return mixed
     * @throws \Exception
     */
    public function userCardList($openid,$cardId=''){
        $url = $this->app->getConfig('api_origin') . self::PATH_CODE_DECRYPT . '?access_token=' . $this->access_token;
        $response = $this->httpClient->post($url, ['openid'=>$openid,'card_id'=>$cardId]);
        $data = $response->throw()->json();
        return $data;
    }



}
