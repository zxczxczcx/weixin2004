<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
// use SimpleXMLElement;

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

        if(Redis::get($key)){
            echo $key;
        }else{
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".env('WX_APPID')."&secret=".env('WX_APPSECRET');
        
            $ken = file_get_contents($url);
            $data = json_decode($ken,true);
            
            Redis::set($key,$data['access_token']);
            Redis::expire($key,3600);
        }
        
    }

    /**
     * 推送事件
     */
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

            //记录日志
            file_put_contents('wx_event.log',$xml_str,FILE_APPEND);
            echo '';
            $data = simplexml_load_string($xml_str,'SimpleXMLElement',LIBXML_NOCDATA);
            
            //判断
            if($data->MsgType=='event'){
                if($data->Event=='subscribe'){

                    $this->attention($data);
                }
            }
            

        }else{
            echo 111;
        }
    }

    /**
     * 关注 
     */
    public function attention($data){
        //拼凑数据
        $tousername = $data->ToUserName;
        $fromusername = $data->FromUserName;
        $event = 'text';
        $content = '欢迎您的到来';
        
        $xml_attention = 
                    '<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[%s]]></MsgType>
                        <Event><![CDATA[%s]]></Event>
                        <Conten><![CDATA[%s]]></Conten>
                    </xml>';
        
        //返回数据
        $atten_str = sprintf($xml_attention,$tousername,$fromusername,time(),$event,$content);
        return $atten_str;
    }





}
