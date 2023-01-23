<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;

use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
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

    public function testInsertFile(){
//        dd($_FILES['file']);
        $formFields = [
            'regular_field' => 'test',
            'file_field' => DataPart::fromPath($_FILES['file']['tmp_name']),
        ];
        $formData = new FormDataPart($formFields);
        $response = $this->client->request(
            'POST',
            'https://www.zohoapis.eu/crm/v2/Deals/13058000000606261/Attachments',
            [
                'headers' => [
                    'Accept' => '*/*',
                    'Accept-Encoding' => 'gzip, deflate, br',
                    'Connection' => 'keep-alive',
                    'Authorization' => 'Zoho-oauthtoken 1000.f3dd4f073e98b8080d9bc1efe964c7f4.ba449e2f5cfb4e9a9a9452244af5cb45',
                    'Content-Type' => 'multipart/form-data'
                ],
                'body' => $formData->bodyToIterable(),

            ]
        );
        dd($response);
        return $response;
    }

    public function finalInsertFile($dealId){
        $curl_pointer = curl_init();
//        dd($_FILES['file']['tmp_name'][0]);
        $curl_options = array();
        $curl_options[CURLOPT_URL] = "https://www.zohoapis.eu/crm/v2/Deals/{$dealId}/Attachments";
        $curl_options[CURLOPT_RETURNTRANSFER] = true;
        $curl_options[CURLOPT_HEADER] = 1;
        $curl_options[CURLOPT_CUSTOMREQUEST] = "POST";
//        dd(sizeof($_FILES['file']['name']));
        for($i=0;$i < sizeof($_FILES['file']['name']);$i++){

            $fileName = $_FILES['file']['name'][$i];
            $filePath = $_FILES['file']['tmp_name'][$i];
            $file = fopen($filePath, "rb");
            $fileData = fread($file, filesize($filePath));
            $date = new \DateTime();
            $current_time_long= $date->getTimestamp();
            $lineEnd = "\r\n";

            $hypen = "--";

            $contentDisp = "Content-Disposition: form-data; name=\""."file"."\";filename=\"".$fileName."\"".$lineEnd.$lineEnd;


            $data = utf8_encode($lineEnd);
            $boundaryStart = utf8_encode($hypen.(string)$current_time_long.$lineEnd) ;

            $data = $data.$boundaryStart;

            $data = $data.utf8_encode($contentDisp);

            $data = $data.$fileData.utf8_encode($lineEnd);

            $boundaryend = $hypen.(string)$current_time_long.$hypen.$lineEnd.$lineEnd;

            $data = $data.utf8_encode($boundaryend);

            $curl_options[CURLOPT_POSTFIELDS]= $data;
            $headersArray = array();

            $headersArray = ['ENCTYPE: multipart/form-data','Content-Type:multipart/form-data;boundary='.(string)$current_time_long];
            $headersArray[] = "content-type".":"."multipart/form-data";
            $headersArray[] = "Authorization". ":" . "Zoho-oauthtoken " .self::$token->access_token;

            $curl_options[CURLOPT_HTTPHEADER]=$headersArray;
            curl_setopt_array($curl_pointer, $curl_options);

            $result = curl_exec($curl_pointer);
        }
        $responseInfo = curl_getinfo($curl_pointer);
        curl_close($curl_pointer);
        list ($headers, $content) = explode("\r\n\r\n", $result, 2);
        if(strpos($headers," 100 Continue")!==false){
            list( $headers, $content) = explode( "\r\n\r\n", $content , 2);
        }
        $headerArray = (explode("\r\n", $headers, 50));
        $headerMap = array();
        foreach ($headerArray as $key) {
            if (strpos($key, ":") != false) {
                $firstHalf = substr($key, 0, strpos($key, ":"));
                $secondHalf = substr($key, strpos($key, ":") + 1);
                $headerMap[$firstHalf] = trim($secondHalf);
            }
        }
        $jsonResponse = json_decode($content, true);
        if ($jsonResponse == null && $responseInfo['http_code'] != 204) {
            list ($headers, $content) = explode("\r\n\r\n", $content, 2);
            $jsonResponse = json_decode($content, true);
        }

        return $jsonResponse;

    }

    public function insertFile(){
//        dd($_FILES['file']);
        $url ="https://www.zohoapis.eu/crm/v2/Deals/13058000000606261/Attachments";
        $ch = curl_init();
        $cfile = new \CURLFile($_FILES['file']['tmp_name'],$_FILES['file']['type'],$_FILES['file']['name']);
//        dd($cfile);
        $data = array('file' => $cfile);
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch,CURLOPT_HTTPHEADER, array(
            'Accept' => '*/*',
            'Connection' => 'keep-alive',
            'Authorization' => 'Zoho-oauthtoken 1000.f3dd4f073e98b8080d9bc1efe964c7f4.ba449e2f5cfb4e9a9a9452244af5cb45',
            'Content-Type' => 'multipart/form-data'
        ));
        curl_setopt($ch,CURLOPT_POSTFIELDS,$data);

        $response = curl_exec($ch);
        if($response){
            return "File posted";
        }
        return "Error : " .curl_error($ch);
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

    public function getNbContrats(): array
    {
        $liste = array();
        foreach($this->getDeals()['data'] as &$deal){
                if($deal['Stage'] == "Gagnés fermés"){
                    $liste['validé'][] = $deal;
                }elseif($deal['Stage'] == "Rétracté"){
                    $liste['rétracté'][] = $deal;
                }else{
                    $liste['en cours'][]= $deal;
                }
        }
        if(!isset($liste['en cours'])) $liste['en cours'][] = '';
        return $liste;
    }
}
