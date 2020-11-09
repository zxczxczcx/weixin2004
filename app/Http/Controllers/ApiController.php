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
            //获取xml 格式
            $postArr = file_get_contents("php://input");
            $post_obj = simplexml_load_string($postArr,"SimplXMLement",LIBXML_NOCDATA);
            file_put_contents('wx_error.logs',$post_obj,FILE_APPEND);
            if($post_obj->MsgType=='Event'){  // 事件
                //关注
                if($post_obj->Event == 'subscribe'){

                    $toUser = $post_obj->ToUserName;
                    $fromUser   = $post_obj->FromUserName;
                    //回复用户文本  信息  格式
                    $msgText = 'text';
                    $content = '欢迎关注';
                    $this->WxText($toUser,$fromUser,$content);


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

    /**
     * 回复文本  WxText
     */
    public function WxText($toUser,$fromUser,$content){
        $template = "<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[%s]]></MsgType>
                    <Content><![CDATA[%s]]></Content>
                    </xml>";
        $info = sprintf($template, $toUser, $fromUser, time(), 'text', $content);
        return $info;
    }


    






}
