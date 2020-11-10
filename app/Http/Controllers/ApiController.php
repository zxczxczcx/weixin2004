<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
// use SimpleXMLElement;
use Log;  //注释
use GuzzleHttp\Client;
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
                }
                //获取用户信息
                $token = $this->Aoken();        //获取accesstoken 
                $user_url='https://api.weixin.qq.com/cgi-bin/user/info?access_token=39_H1bWA8vAEOJgtE3A7CNs_WYmNaZIYm9ChaD9_rLRocbccNddxJAwsPzLRdVF0StkSx_WEAiEb3ajSium1W3sVFZlB4ZOEBtkMhKqFEj1cSUSxCEffcZwfUqgzlvOOT1qrV1SxaVQu20mPt6mSXBdAAAOTX&openid='.$xml_obj->FromUserName.'&lang=zh_CN';
                $client = new Client;
                $respones = $client->request('get',$user_url);
                file_put_contents('wx_event.log',$respones->sex,FILE_APPEND);



            }

            switch($xml_obj->MsgType=='text'){
                

            }
        }
    }

    /**
     * access_token
     */
    public function Aoken(){
        $key = 'wx:access_token';

        if(Redis::get($key)){
            $user_url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.Redis::get($key).'&openid=onRjS5msz_F_SCyA5Su2GSA-Ij1c&lang=zh_CN';
            dd($user_url);
        }else{
            
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".env('WX_APPID')."&secret=".env('WX_APPSECRET');
        
            $ken = file_get_contents($url);
            $data = json_decode($ken,true);
            
            Redis::set($key,$data['access_token']);
            $user_url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.Redis::get($key).'&openid=onRjS5msz_F_SCyA5Su2GSA-Ij1c&lang=zh_CN';
            dd($user_url);
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
