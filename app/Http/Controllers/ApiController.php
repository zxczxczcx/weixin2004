<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
// use SimpleXMLElement;



class ApiController extends Controller
{
    /**
     * Undocumented function
     *         微信接口调用     服务器上运行
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
            $data = file_get_contents("php://input");
            $data_res = simplexml_load_string($data,"SimplXMLement",LIBXML_NOCDATA);
            if($data_res['MsgType']=='event'){  // 事件
                if($data_res['Event']=='subscribe'){
                    $content = '谢谢关注';
                    echo "<xml>
                    <ToUserName><![CDATA[".$data_res['FromUserName']."]]></ToUserName>
                    <FromUserName><![CDATA[".$data_res['ToUserName']."]]></FromUserName>
                    <CreateTime>".time()."</CreateTime>
                    <MsgType><![CDATA[text]]></MsgType>
                    <Content><![CDATA[".$content."]]></Content>
                    </xml>";
                }
            }
        }else{
            echo $_GET['echostr'];
        }
    }

    /**
     * access_token
     */
    public function Aoken(){
        $key = 'wx:access_token';

        if(empty(Redis::get($key))){
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".env('WX_APPID')."&secret=".env('WX_APPSECRET');
            $ken = file_get_contents($url);
            $data = json_decode($ken,true);
            Redis::set($key,$data['access_token']);
            Redis::expire($key,3600);
        }
        echo Redis::get($key);
    }

    /**发送消息 */
    public function ResponseTest(){
        

    }

   





}
