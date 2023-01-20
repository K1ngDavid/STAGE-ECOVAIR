<?php

namespace App\Service;
use App\Service\CallApiService;
use function PHPUnit\Framework\throwException;

class Token
{
    private static $token;
    public function __construct(){
        $this->id = uniqid();
    }

    public static function getInstance(){
        if(is_null(self::$token)){
            self::$token = self::getRefreshedAccessToken();
        }
        return self::$token;
    }

    public static function getRefreshedAccessToken()
    {
//        dd(self::$token);
        if (is_null(self::$token)) {
            $access_token_url = $_ENV['ACCOUNT_URL'] . "/oauth/v2/token?refresh_token={$_ENV['REFRESH_TOKEN']}&client_id={$_ENV['CLIENT_ID']}&client_secret={$_ENV['CLIENT_SECRET']}&grant_type=refresh_token";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_URL, $access_token_url);
            curl_setopt($ch, CURLOPT_POST, 1);
            $result = curl_exec($ch);
            curl_close($ch);
            self::$token = json_decode($result);
            self::$token->expires_in = new \DateTime('now + 1 hour');
            return self::$token;
        }
    }
}