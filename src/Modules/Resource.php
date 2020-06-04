<?php


namespace ymlluo\WxApi\Modules;


use ymlluo\WxApi\Events\WxMediaDownload;
use ymlluo\WxApi\Events\WxMediaUpload;
use ymlluo\WxApi\Exceptions\ErrorCode;
use ymlluo\WxApi\Exceptions\WxException;
use ymlluo\WxApi\Helpers\Http;
use ymlluo\WxApi\WxApi;

class Resource
{
    const PATH_TEMP_MEDIA_UPLOAD = '/cgi-bin/media/upload';
    const PATH_TEMP_MEDIA_GET = '/cgi-bin/media/get';
    const PATH_MEDIA_UPLOAD_IMG = '/cgi-bin/media/uploadimg';
    const PATH_MATERIAL_ADD_NEWS = '/cgi-bin/material/add_news';
    const PATH_MATERIAL_ADD = '/cgi-bin/material/add_material';
    const PATH_MATERIAL_GET = '/cgi-bin/material/get_material';
    const PATH_MATERIAL_DEL = '/cgi-bin/material/del_material';
    const PATH_MATERIAL_COUNT = '/cgi-bin/material/get_materialcount';
    const PATH_MATERIAL_LIST = '/cgi-bin/material/batchget_material';

    const UPLOAD_IMAGE_INLINE_MAX_SIZE = 1048576; //图文消息内的图片 大小必须在1MB以下
    const UPLOAD_IMAGE_FILE_MAX_SIZE = 2097152; //图片（image）: 2M，支持bmp/png/jpeg/jpg/gif格式
    const UPLOAD_VOICE_MAX_SIZE = 2097152; //语音（voice）：2M，播放长度不超过60s，mp3/wma/wav/amr格式
    const UPLOAD_VIDEO_MAX_SIZE = 10485760;//视频（video）：10MB，支持MP4格式
    const UPLOAD_IMAGE_THUMB_MAX_SIZE = 65536;//缩略图（thumb）：64KB，支持JPG格式

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
     * @param $filepath
     * @param string $type
     * @param null $filename
     * @param string $fileFieldName media 临时素材 buffer 卡券商户图标
     * @param bool $useCache
     * @return array|mixed
     * @throws WxException
     */
    public function mediaUpload($filepath, $type = '', $filename = null, $fileFieldName = 'media', $useCache = true)
    {

        $guessPath = $this->_guessFilepath($filepath);

        if ($useCache && $cache = cache($this->_getMediaCacheKey($guessPath))) {
            return $cache;
        }
        if (filter_var($filepath, FILTER_VALIDATE_URL)) {
            if (!file_exists($guessPath)) {
                $this->_download($filepath);
            }
            $filepath = $guessPath;
        }
        if (!file_exists($guessPath)) {
            throw new WxException('file not exists!', ErrorCode::$resourceUploadFileNotExists);
        }

        $type = $type ?: $this->guessFileType($filepath);
        if (!$this->checkFileSize($type, $filepath)) {
            throw new WxException('upload temp media file size error', ErrorCode::$resourceFileSizeError);
        }

        $url = $this->app->getConfig('api_origin') . self::PATH_TEMP_MEDIA_UPLOAD . '?access_token=' . $this->access_token . '&type=' . $type;

        $response = $this->httpClient->attach($fileFieldName, fopen($filepath, 'r'), $filename)->post($url);
        $data = $response->throw()->json();
        $ttl = 3 * 24 * 60 * 60 - 300;
        cache()->put($this->_getMediaCacheKey($filepath), $data, $ttl);
        $mediaId = data_get($data, 'media_id');
        if (function_exists('event')) {
            event(new WxMediaUpload($mediaId, $filepath, $filename, $ttl));
        }
        return $data;


    }

    public function mediaGet($mediaId, $savePath = null)
    {
        $savePath = $savePath ?: $this->_tempPath();
        $url = $this->app->getConfig('api_origin') . self::PATH_TEMP_MEDIA_GET . '?access_token=' . $this->access_token . '&media_id=' . $mediaId;
        $response = $this->httpClient->asChrome()->get($url);
        $response->throw();
        $filename = uniqid();
        $filePath = $savePath . '/' . $filename;
        if (preg_match('/filename="(.*?)"/is', (string)$response->header('Content-disposition'), $match)) {
            $filename = $match[1];
            $filePath = $savePath . '/' . $filename;
            if (!is_dir($savePath)) {
                mkdir($savePath, 644, true);
            }
            file_put_contents($filePath, $response->body());
        }
        if ($video_url = data_get($response->json(), 'video_url')) {
            $filename = $mediaId . '.mp4';
            $filePath = $savePath . '/' . $filename;
            $response = $this->httpClient->asChrome()->download($video_url, $filePath);
            $response->throw();
        }

        if (file_exists($filePath)) {
            if (function_exists('event')) {
                event(new WxMediaDownload($mediaId, $filePath, $filename));
            }
            return $filePath;
        }
        throw new WxException('download temp media file error');
    }

    /**
     * 新增永久图文素材
     * @link  https://developers.weixin.qq.com/doc/offiaccount/Asset_Management/Adding_Permanent_Assets.html
     * @param array $news
     * @return mixed {"media_id":MEDIA_ID}
     * @throws \Exception
     */
    public function materialAddNews(array $news)
    {
        $url = $this->app->getConfig('api_origin') . self::PATH_MATERIAL_ADD_NEWS . '?access_token=' . $this->access_token;
        $response = $this->httpClient->post($url, $news);
        return $response->throw()->json();
    }

    /**
     * 上传图文消息内的图片获取URL
     * @param $filepath
     * @param null $filename
     * @return mixed  http://mmbiz.qpic.cn/mmbiz/xxxxx
     * @throws WxException
     */
    public function uploadInlineImage($filepath, $filename = null, $useCache = true)
    {
        $guessPath = $this->_guessFilepath($filepath);
        if ($useCache && $cache = cache($this->_getInlineImageCacheKey($guessPath))) {
            return data_get($cache, 'url');
        }
        if (!file_exists($guessPath) && filter_var($filepath, FILTER_VALIDATE_URL)) {
            $this->httpClient->asChrome()->download($filepath, $guessPath);
        }
        if (!file_exists($guessPath)) {
            throw new WxException('file not exists!', ErrorCode::$resourceUploadFileNotExists);
        }
        if (filesize($filepath) >= self::UPLOAD_IMAGE_INLINE_MAX_SIZE) {
            throw new WxException('upload inline image file size error', ErrorCode::$resourceFileSizeError);
        }
        $url = $this->app->getConfig('api_origin') . self::PATH_MEDIA_UPLOAD_IMG . '?access_token=' . $this->access_token;
        $response = $this->httpClient->attach('media', fopen($filepath, 'r'), $filename)->post($url);
        $data = $response->throw()->json();
        cache()->put($this->_getInlineImageCacheKey($filepath), $data);
        return data_get($data, 'url');
    }

    public function materialAdd($filepath, $type = '', $filename = null, $title = '', $introduction = '', $useCache = true)
    {
        $guessPath = $this->_guessFilepath($filepath);
        if ($useCache && $cache = cache($this->_getMaterialCacheKey($guessPath))) {
            return $cache;
        }
        if (!file_exists($guessPath) && filter_var($filepath, FILTER_VALIDATE_URL)) {
            $this->httpClient->asChrome()->download($filepath, $guessPath);
        }
        if (!file_exists($guessPath)) {
            throw new WxException('file not exists!', ErrorCode::$resourceUploadFileNotExists);
        }
        $type = $type ?: $this->guessFileType($filepath);
        if (!$this->checkFileSize($type, $filepath)) {
            throw new WxException('upload temp media file size error', ErrorCode::$resourceFileSizeError);
        }
        $url = $this->app->getConfig('api_origin') . self::PATH_MATERIAL_ADD . '?access_token=' . $this->access_token . '&type=' . $type;
        $request = $this->httpClient->attach('media', fopen($filepath, 'r'), $filename);
        if ($type == 'video') {
            $request->attach('description', json_encode(['title' => $title, 'introduction' => $introduction], 256));
        }
        $res = $request->post($url);
        $data = $res->throw()->json();
        cache()->put($this->_getMaterialCacheKey($filepath), $data);
        $mediaId = data_get($data, 'media_id', '');
        $url = data_get($data, 'url', '');
        if (function_exists('event')) {
            event(new WxMediaUpload($mediaId, $filepath, $filename, 0, $url));
        }
        return $data;
    }

    public function materialGet($mediaId, $savePath = null)
    {
        $savePath = $savePath ?: $this->_tempPath();
        $url = $this->app->getConfig('api_origin') . self::PATH_MATERIAL_GET . '?access_token=' . $this->access_token;
        $response = $this->httpClient->post($url, ['media_id' => $mediaId]);
        $data = $response->throw()->json();
        if (data_get($data, 'news_item')) {
            return $data;
        }
        $filename = uniqid() . '.tmp';
        $filePath = $savePath . '/' . $filename;
        if (preg_match('/filename="(.*?)"/is', (string)$response->header('Content-disposition'), $match)) {
            $filename = $match[1];
            $filePath = $savePath . '/' . $filename;
            if (!is_dir($savePath)) {
                mkdir($savePath, 644, true);
            }
            file_put_contents($filePath, $response->body());
            $data = array_merge(['mediaId' => $mediaId, 'filepath' => $filePath, 'filename' => $filename]);
        }
        if ($video_url = data_get($response->json(), 'down_url')) {
            $filename = $mediaId . '.mp4';
            $filePath = $savePath . '/' . $filename;
            $response = $this->httpClient->asChrome()->download($video_url, $filePath);
            $data = $response->throw()->json();
            $data = array_merge($data, ['mediaId' => $mediaId, 'filepath' => $filePath, 'filename' => $filename]);
        }
        if (file_exists($filePath)) {
            if (function_exists('event')) {
                event(new WxMediaDownload($mediaId, $filePath, $filename, $data));
            }
            return $data;
        }
        throw new WxException('download temp media file error');
    }

    public function materialDel(string $mediaId)
    {
        $url = $this->app->getConfig('api_origin') . self::PATH_MATERIAL_DEL . '?access_token=' . $this->access_token;
        $response = $this->httpClient->post($url, ['media_id' => $mediaId]);
        $data = $response->throw()->json();
        return $data;
    }

    public function materialCount()
    {
        $url = $this->app->getConfig('api_origin') . self::PATH_MATERIAL_COUNT . '?access_token=' . $this->access_token;
        $response = $this->httpClient->get($url);
        $data = $response->throw()->json();
        return $data;
    }

    /**
     * 分类型获取永久素材的列表
     * @param $type  素材的类型，图片（image）、视频（video）、语音 （voice）、图文（news）
     * @param int $offset 从全部素材的该偏移位置开始返回，0表示从第一个素材 返回
     * @param int $count 返回素材的数量，取值在1到20之间
     * @return mixed
     * @throws \Exception
     */
    public function materialList($type, $offset = 0, $count = 20)
    {
        $url = $this->app->getConfig('api_origin') . self::PATH_MATERIAL_LIST . '?access_token=' . $this->access_token;
        $response = $this->httpClient->post($url, [
            'type' => $type,
            'offset' => $offset,
            'count' => $count
        ]);
        $data = $response->throw()->json();
        return $data;
    }


    public function mediaId(string $path = '', $type = '', $filename = null)
    {

        if (!preg_match('![\/\.]{1,}!is', $path)) {
            return $path;
        }
        return data_get($this->mediaUpload($path, $type, $filename), 'media_id');
    }


    private function _getMediaCacheKey($path)
    {
        $cacheKey = app('wxapi')->getConfig('cache_prefix') . ':MEDIA:' . md5($path);
        return $cacheKey;
    }

    private function _getMaterialCacheKey($path)
    {
        $cacheKey = app('wxapi')->getConfig('cache_prefix') . ':MATERIAL:' . md5($path);
        return $cacheKey;
    }

    private function _getInlineImageCacheKey($path)
    {
        $cacheKey = app('wxapi')->getConfig('cache_prefix') . ':INLINE:IMAGE:' . md5($path);
        return $cacheKey;
    }


    private function guessFileType($path)
    {
        if ($mimeType = mime_content_type($path)) {
            $arr = explode('/', $mimeType);
            switch ($arr[0]) {
                case 'image':
                    return 'image';
                case 'audio':
                    return 'voice';
                case 'video':
                    return 'video';
                default:
                    break;
            }
        }
        throw new WxException('unsupported type ', ErrorCode::$resourceUnsupportedType);
    }

    private function checkFileSize($type, $filepath)
    {

        $fileSize = filesize($filepath);
        switch ($type) {
            case 'image':
                return $fileSize <= self::UPLOAD_IMAGE_FILE_MAX_SIZE;
            case 'audio':
                return $fileSize <= self::UPLOAD_VOICE_MAX_SIZE;
            case 'video':
                return $fileSize <= self::UPLOAD_VIDEO_MAX_SIZE;
            case 'thumb':
                return $fileSize <= self::UPLOAD_IMAGE_THUMB_MAX_SIZE;
            default:
                return false;
        }
    }

    private function _guessFilepath($filepath)
    {
        if (filter_var($filepath, FILTER_VALIDATE_URL)) {
            if (preg_match('/\/([^\/]+\.\w+$)/', $filepath, $match)) {
                $filename = $match[1];
            }
            $filename = $filename ?: (md5($filepath) . '.tmp');
            $savePath = $this->_tempPath();
            $filepath = $savePath . '/' . $filename;
        }
        return $filepath;
    }

    private function _tempPath()
    {
        return storage_path('wx_media_temp/' . date('Ymd'));
    }

    private function _download($downloadUrl, $filename = null, $savePath = null)
    {
        $filepath = $this->_guessFilepath($downloadUrl);
        $savePath = dirname($filepath);
        if (!is_dir($savePath)) {
            mkdir($savePath, 644, true);
        }
        $this->httpClient->asChrome()->download($downloadUrl, $filepath);
        if (!file_exists($filepath)) {
            throw new WxException('download file error', -1);
        }
        return $filepath;
    }
}
