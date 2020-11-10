<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
// use SimpleXMLElement;
use Log;
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
            
            //接受数据
            $xml = file_get_contents('php://input');
            //记录日志
            
            file_put_contents('wx_event.log',$xml,FILE_APPEND);
            $xml_obj = simplexml_load_string($xml);
            
            //判断
            if($xml_obj->MsgType=='event'){
                //关注
                if($xml_obj->Event=='subscribe'){
                    $Content = '关注成功';
                    $resule = $this->attention($xml_obj,$Content);
                    return $resule;
                    // file_put_contents('wx_event.log',$Content,FILE_APPEND);
                }
                
                
            }
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
     * 关注  被动回复
     */
    public function attention($xml_obj,$Content){
        //拼凑数据
        $tousername = $xml_obj->FromUserName;
        $fromusername = $xml_obj->ToUserName;
        $xml_attention = 
                    '<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[%s]]></MsgType>
                        <Content><![CDATA[%s]]></Content>
                    </xml>';
        //返回数据
        $xml_info = sprintf($xml_attention,$tousername,$fromusername,time(),'text',$Content);
        return $xml_info;
        
    }





}
