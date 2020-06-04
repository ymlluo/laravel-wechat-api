<?php

return [
    'common' => [
        'api_origin' => 'https://api.weixin.qq.com',
        'cache_prefix' => env('WECHAT_CACHE_PREFIX', 'wx:cache:'),
        'log_enable' => env('WECHAT_LOG_ENABLE', false),
    ],
    //公众号配置
    'accounts' => [
        'default' => [
            'original_id' => env('WX_ORIGINAL_ID', ''),
            'app_id' => env('WX_APP_ID', ''), // AppID
            'app_secret' => env('WX_APP_SECRET', ''), // AppSecret
            'token' => env('WX_TOKEN', ''), // Token
            'aes_key' => env('WX_AES_KEY', ''),
            'access_token_url' => env('WX_ACCESS_TOKEN_URL', ''), // get access_token url
            'access_token_expires_in' => env('WX_ACCESS_TOKEN_EXPIRES_IN', 7200),
            'log_enable' => env('WX_LOG_ENABLE', 7200),
        ],
    ]
];
