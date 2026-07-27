<?php

return [

    'oauth2' => [
        'base_url'      => env('OAUTH_BASE_URL'),
        'client_id'     => env('OAUTH_CLIENT_ID'),
        'client_secret' => env('OAUTH_CLIENT_SECRET'),
        'token_path'    => env('OAUTH_TOKEN_PATH', '/oauth/token'),
        'me_path'       => env('OAUTH_ME_PATH', '/api/me'),
        'revoke_path'   => env('OAUTH_REVOKE_PATH', '/oauth/revoke'),
    ]

];
