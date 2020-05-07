<?php

namespace ymlluo\WxApi\Helpers;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use  ymlluo\WxApi\Exceptions\WxException;

class Http
{
    public $response;

    protected $decoded;
    /**
     * The base URL for the request.
     *
     * @var string
     */
    protected $baseUrl = '';

    /**
     * The request body format.
     *
     * @var string
     */
    protected $bodyFormat;

    /**
     * The pending files for the request.
     *
     * @var array
     */
    protected $pendingFiles = [];

    /**
     * The request cookies.
     *
     * @var array
     */
    protected $cookies;

    /**
     * The transfer stats for the request.
     *
     * \GuzzleHttp\TransferStats
     */
    protected $transferStats;

    /**
     * The request options.
     *
     * @var array
     */
    protected $options = [];
    /**
     * The number of times to try the request.
     *
     * @var int
     */
    protected $tries = 1;

    /**
     * The number of milliseconds to wait between retries.
     *
     * @var int
     */
    protected $retryDelay = 100;

    public $client;

    public function __construct()
    {
        $this->asJson();
        $this->options = [
            'http_errors' => false,
        ];
    }

    /**
     * Indicate the request contains JSON.
     *
     * @return $this
     */
    public function asJson()
    {
        return $this->bodyFormat('json')->contentType('application/json');
    }


    /**
     * Indicate the request contains form parameters.
     * @return Http
     */
    public function asForm()
    {
        return $this->bodyFormat('form_params')->contentType('application/x-www-form-urlencoded');
    }

    /**
     * Issue a GET request to the given URL.
     *
     * @param string $url
     * @param null $query
     * @return mixed
     * @throws \Exception
     */
    public function get(string $url, $query = null)
    {
        return $this->send('GET', $url, [
            'query' => $query,
        ]);
    }

    /**
     * Attach a file to the request.
     *
     * @param string $name
     * @param string $contents
     * @param string|null $filename
     * @param array $headers
     * @return $this
     */
    public function attach($name, $contents, $filename = null, array $headers = [])
    {
        $this->asMultipart();

        $this->pendingFiles[] = array_filter([
            'name' => $name,
            'contents' => $contents,
            'headers' => $headers,
            'filename' => $filename,
        ]);

        return $this;
    }

    /**
     * Indicate the request is a multi-part form request.
     *
     * @return $this
     */
    public function asMultipart()
    {
        return $this->bodyFormat('multipart');
    }


    /**
     * Specify the body format of the request.
     *
     * @param string $format
     * @return $this
     */
    public function bodyFormat(string $format)
    {
        return tap($this, function ($request) use ($format) {
            $this->bodyFormat = $format;
        });
    }

    /**
     * Specify the request's content type.
     *
     * @param string $contentType
     * @return $this
     */
    public function contentType(string $contentType)
    {
        return $this->withHeaders(['Content-Type' => $contentType]);
    }


    /**
     * Indicate that JSON should be returned by the server.
     *
     * @return $this
     */
    public function acceptJson()
    {
        return $this->accept('application/json');
    }

    /**
     * Indicate the type of content that should be returned by the server.
     *
     * @param string $contentType
     * @return $this
     */
    public function accept($contentType)
    {
        return $this->withHeaders(['Accept' => $contentType]);
    }

    public function asChrome()
    {
        return $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.132 Safari/537.36']);
    }

    /**
     * Add the given headers to the request.
     *
     * @param array $headers
     * @return $this
     */
    public function withHeaders(array $headers)
    {
        return tap($this, function ($request) use ($headers) {
            return $this->options = array_merge_recursive($this->options, [
                'headers' => $headers,
            ]);
        });
    }

    /**
     * Specify the basic authentication username and password for the request.
     *
     * @param string $username
     * @param string $password
     * @return $this
     */
    public function withBasicAuth(string $username, string $password)
    {
        return tap($this, function ($request) use ($username, $password) {
            return $this->options['auth'] = [$username, $password];
        });
    }

    /**
     * Specify the digest authentication username and password for the request.
     *
     * @param string $username
     * @param string $password
     * @return $this
     */
    public function withDigestAuth($username, $password)
    {
        return tap($this, function ($request) use ($username, $password) {
            return $this->options['auth'] = [$username, $password, 'digest'];
        });
    }

    /**
     * Specify an authorization token for the request.
     *
     * @param string $token
     * @param string $type
     * @return $this
     */
    public function withToken($token, $type = 'Bearer')
    {
        return tap($this, function ($request) use ($token, $type) {
            return $this->options['headers']['Authorization'] = trim($type . ' ' . $token);
        });
    }

    /**
     * Specify the cookies that should be included with the request.
     *
     * @param array $cookies
     * @param string $domain
     * @return $this
     */
    public function withCookies(array $cookies, string $domain)
    {
        return tap($this, function ($request) use ($cookies, $domain) {
            return $this->options = array_merge_recursive($this->options, [
                'cookies' => CookieJar::fromArray($cookies, $domain),
            ]);
        });
    }

    /**
     * Indicate that redirects should not be followed.
     *
     * @return $this
     */
    public function withoutRedirecting()
    {
        return tap($this, function ($request) {
            return $this->options['allow_redirects'] = false;
        });
    }

    /**
     * Indicate that TLS certificates should not be verified.
     *
     * @return $this
     */
    public function withoutVerifying()
    {
        return tap($this, function ($request) {
            return $this->options['verify'] = false;
        });
    }

    /**
     * Specify the timeout (in seconds) for the request.
     *
     * @param int $seconds
     * @return $this
     */
    public function timeout(int $seconds)
    {
        return tap($this, function () use ($seconds) {
            $this->options['timeout'] = $seconds;
        });
    }

    /**
     * Specify the number of times the request should be attempted.
     *
     * @param int $times
     * @param int $sleep
     * @return $this
     */
    public function retry(int $times, int $sleep = 0)
    {
        $this->tries = $times;
        $this->retryDelay = $sleep;

        return $this;
    }

    /**
     * Merge new options into the client.
     *
     * @param array $options
     * @return $this
     */
    public function withOptions(array $options)
    {
        return tap($this, function ($request) use ($options) {
            return $this->options = array_merge_recursive($this->options, $options);
        });
    }

    /**
     * Issue a POST request to the given URL.
     *
     * @param string $url
     * @param array $data
     * @return mixed
     * @throws \Exception
     */
    public function post(string $url, array $data = [])
    {
        return $this->send('POST', $url, $this->realData($data));
    }

    private function realData($data)
    {
        if ($this->bodyFormat == 'json') {
            return [
                'body' => json_encode($data, 256)
            ];
        }
        return [
            $this->bodyFormat => $data
        ];
    }

    /**
     * Issue a PATCH request to the given URL.
     * @param $url
     * @param array $data
     * @return mixed
     * @throws \Exception
     */
    public function patch($url, $data = [])
    {
        return $this->send('PATCH', $url, [
            $this->bodyFormat => $data,
        ]);
    }

    /**
     * Issue a PUT request to the given URL.
     *
     * @param $url
     * @param array $data
     * @return mixed
     * @throws \Exception
     */
    public function put($url, $data = [])
    {
        return $this->send('PUT', $url, [
            $this->bodyFormat => $data,
        ]);
    }

    /**
     * Issue a DELETE request to the given URL.
     *
     * @param $url
     * @param array $data
     * @return mixed
     * @throws \Exception
     */
    public function delete($url, $data = [])
    {
        return $this->send('DELETE', $url, empty($data) ? [] : [
            $this->bodyFormat => $data,
        ]);
    }

    /**
     * @param $url
     * @param $file_path
     * @return mixed
     * @throws \Exception
     */
    public function download($url, $file_path)
    {
        return $this->send('GET', $url, ['save_to' => $file_path]);
    }

    /**
     *  Send the request to the given URL
     * @param string $method
     * @param string $url
     * @param array $options
     * @return mixed
     * @throws \Exception
     */
    public function send(string $method, string $url, array $options = [])
    {
        $this->response =null;
        $url = ltrim(rtrim($this->baseUrl, '/') . '/' . ltrim($url, '/'), '/');

        if (isset($options[$this->bodyFormat])) {
            $options[$this->bodyFormat] = array_merge(
                $options[$this->bodyFormat], $this->pendingFiles
            );
        }
        $this->pendingFiles = [];
        Log::debug('http req', (array)func_get_args());
        return retry($this->tries ?? 1, function () use ($method, $url, $options) {
            try {
                return tap($this, function () use ($method, $url, $options) {
                    $response = $this->buildClient()->request($method, $url, $options);
                    $this->response = $response;
                    $this->response->cookies = $this->cookies;
                    $this->response->transferStats = $this->transferStats;
                });
            } catch (ConnectException $e) {
                throw $e;
            }
        }, $this->retryDelay ?? 100);
    }

    /**
     * Build the Guzzle client.
     *
     * @return \GuzzleHttp\Client
     */
    public function buildClient()
    {
        return new Client([
            'cookies' => true,
        ]);
    }

    /**
     * Merge the given options with the current request options.
     *
     * @param array $options
     * @return array
     */
    public function mergeOptions(...$options)
    {
        return array_merge_recursive($this->options, ...$options);
    }

    /**
     * Get the body of the response.
     *
     * @return string
     */
    public function body()
    {
        return (string)$this->response->getBody();
    }

    /**
     * Get the JSON decoded body of the response as an array.
     *
     * @return array
     */
    public function json()
    {
        $this->decoded = json_decode($this->body(), true);
        Log::debug('http res array', (array)$this->decoded);
        return $this->decoded;
    }

    /**
     * Get the JSON decoded body of the response as an object.
     *
     * @return object
     */
    public function object()
    {
        return json_decode($this->body(), false);
    }


    /**
     * Get a header from the response.
     *
     * @param string $header
     * @return string
     */
    public function header(string $header)
    {
        return $this->response->getHeaderLine($header);
    }

    /**
     * Get the headers from the response.
     *
     * @return array
     */
    public function headers()
    {
        return collect($this->response->getHeaders())->mapWithKeys(function ($v, $k) {
            return [$k => $v];
        })->all();
    }

    /**
     * Get the status code of the response.
     *
     * @return int
     */
    public function status()
    {
        return (int)$this->response->getStatusCode();
    }

    /**
     * Determine if the request was successful.
     *
     * @return bool
     */
    public function successful()
    {
        return $this->status() >= 200 && $this->status() < 300;
    }

    /**
     * Determine if the response code was "OK".
     *
     * @return bool
     */
    public function ok()
    {
        return $this->status() === 200;
    }

    /**
     * Determine if the response was a redirect.
     *
     * @return bool
     */
    public function redirect()
    {
        return $this->status() >= 300 && $this->status() < 400;
    }


    /**
     * Determine if the response indicates a client error occurred.
     *
     * @return bool
     */
    public function clientError()
    {
        return $this->status() >= 400 && $this->status() < 500;
    }


    /**
     * Determine if the response indicates a server error occurred.
     *
     * @return bool
     */
    public function serverError()
    {
        return $this->status() >= 500;
    }

    /**
     * Get the response cookies.
     *
     * @return array
     */
    public function cookies()
    {
        return $this->cookies;
    }

    /**
     * Get the underlying PSR response for the response.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function toPsrResponse()
    {
        return $this->response;
    }

    /**
     * Throw an exception if a server or client error occurred.
     * @return $this
     */
    public function throw()
    {
        if ($this->offsetExists('errcode') && $this->offsetGet('errcode') !== 0) {
            throw new WxException($this->offsetGet('errmsg'), $this->offsetGet('errcode'));
        }
        if ($this->serverError() || $this->clientError()) {
            throw new WxException('http response error');
        }

        return $this;
    }

    /**
     * Determine if the given offset exists.
     *
     * @param string $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->json()[$offset]);
    }

    /**
     * Get the value for a given offset.
     *
     * @param string $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->json()[$offset];
    }

    /**
     * Get the body of the response.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->body();
    }

}
