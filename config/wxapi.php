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
            'original_id'=>'gh_2d686c71d734',
            'app_id' => env('WECHAT_DEFAULT_APP_ID', ''), // AppID
            'app_secret' => env('WECHAT_DEFAULT_APP_SECRET', ''), // AppSecret
            'token' => env('WECHAT_DEFAULT_TOKEN', ''), // Token
            'aes_key' => env('WECHAT_DEFAULT_AES_KEY', ''),
            'access_token_url' => env('WECHAT_ACCESS_TOKEN_URL', ''), // get access_token url
            'access_token_expires_in' => env('WECHAT_ACCESS_TOKEN_EXPIRES_IN', 7200)
        ],

    ]
];
