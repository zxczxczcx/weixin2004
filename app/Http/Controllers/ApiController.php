<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class ApiController extends Controller
{
    /**
     * Undocumented function
     *         微信接口调用
     * @return void
     */
    public function checkwx(){
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        
        $token = env('WX_TOKEN');
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
        
        if( $tmpStr == $signature ){
            echo $_GET['echostr'];
            
        }else{
            echo 111;
        }
    }

    /**
     * access_token
     */
    public function Aoken(){
        $key = 'wx:access_token';
        $token = Redis::get($key);
        if($token){
            echo 111;
        }else{
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".env('WX_APPID')."&secret=".env('WX_APPSECRET');
        
        $ken = file_get_contents($url);
        $data = json_decode($ken,true);
        
        Redis::set($key,$data['access_token']);
        Redis::expire($key,3600);
        

        }
        
    }

    /**ceshi  */
    public function event(){
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        
        $token = env('WX_TOKEN');
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
        
        if( $tmpStr == $signature ){
            //接受数据
            $xml_str = file_get_contents('php://input');
            //写入文档
            $data = simplexml_load_string($xml_str, 'SimpleXMLElement', LIBXML_NOCDATA);
            file_put_contents('wx_event .log',$data,FILE_APPEND);
            echo '';
    
        }else{
            echo 111;
        }

        

}
