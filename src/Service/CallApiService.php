<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;

use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;



class CallApiService
{
    private HttpClientInterface $client;
    private static $token;
    private $accessTokenUrl;
    private $test;
    private $fp;

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public  function __construct()
    {
//        dd("hello45");
        $this->client = HttpClient::create();
        if(file_get_contents('../php_sdk_token.json') == ""){
            $test = $this->setRefreshedAccessToken();
            file_put_contents('../php_sdk_token.json',json_encode($test));
        }
        self::$token = json_decode(file_get_contents('../php_sdk_token.json'));
        dump(self::$token);
        file_put_contents('../php_sdk_token.json',json_encode($this->setRefreshedAccessToken()));
//        dd(self::$token);
//        else{
//            self::$token = $this->getRefreshedAccessToken();
//            dd(self::$token);
//        }
    }

    public static function getInstance()
    {
        $test = self::$token;
        if(is_null(self::$token)){
            self::$token = new CallApiService();
        }
        return self::$token;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \Exception
     */
    public function getDeals(): array
    {
        $response = $this->client->request(
            'GET',
            'https://www.zohoapis.eu/crm/v2/Deals',
            [
                'headers' => [
                    'Accept' => '*/*',
                    'Connection' => 'keep-alive',
                    'Authorization' => 'Zoho-oauthtoken ' . self::$token->access_token,
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ]
            ]
        );
        return $response->toArray();
    }

    public function getDealsByNameCommercialAndByStatus($nom){
        $liste = array();
        foreach($this->getDeals()['data'] as &$deal){
            if($deal['Commercial'] == $nom){
                if($deal['Stage'] == "Gagnés fermés"){
                    $liste['validé'][] = $deal;
                }elseif($deal['Stage'] == "Rétracté"){
                    $liste['rétracté'][] = $deal;
                }else{
                    $liste['en cours'][]= $deal;
                }
            }
        }
        if(!isset($liste['en cours'])) $liste['en cours'][] = '';
        return $liste;
    }

    public function getDealsByNameCommercial($nom){
        $liste = array();
        foreach($this->getDeals()['data'] as &$deal){
            if($deal['Commercial'] == $nom){
                $liste[] = $deal;
            }
        }
        return $liste;
    }


    public function setRefreshedAccessToken(){
        if(is_null(self::$token)){
            $this->getRefreshedAccessToken();
        }elseif (self::$token->expires_in <= (new \DateTime(('now')))->format('Y-m-d H:i:s')){
            $this->getRefreshedAccessToken();
        }
        return self::$token;
    }

    private function getRefreshedAccessToken(){
            $this->accessTokenUrl = $_ENV['ACCOUNT_URL'] . "/oauth/v2/token?refresh_token={$_ENV['REFRESH_TOKEN']}&client_id={$_ENV['CLIENT_ID']}&client_secret={$_ENV['CLIENT_SECRET']}&grant_type=refresh_token";
            $ch = curl_init();
            curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch,CURLOPT_URL,$this->accessTokenUrl);
            curl_setopt($ch,CURLOPT_POST,1);
            $result = curl_exec($ch);
            curl_close($ch);
//           $result = self::$token;
            $result = json_decode($result);
            $result->expires_in = (new \DateTime(('now + 1 hour')))->format('Y-m-d H:i:s');
//            dd(self::$token->expires_in->format('Y-m-d H:i:s') > (new \DateTime(('now')))->format('Y-m-d H:i:s'));
            self::$token = $result;
            return $result;
    }

    public function getAllCommerciaux():array
    {
        $response = $this->getDeals()['data'];
        $commerciaux = [];
        foreach ($response as &$value){
//            dump($value);
            if(!in_array($value['Commercial'],$commerciaux) && $value['Commercial'] != null){
                $commerciaux[] = $value['Commercial'];
            }
        }
        return $commerciaux;
    }
}
