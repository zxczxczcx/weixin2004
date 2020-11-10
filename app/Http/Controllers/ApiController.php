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
                $respones_json = $client->request('get',$user_url,['verify'=>false]);
                $respones_obj = json_decode($respones_json,true);
                

                
            }
            //文本多选模式   
            switch($xml_obj->Content){
                case'天气';
                $weather = $this->weather($xml_obj);
                return $weather;
                break; 

            }
        }
    }

    /**天气   和风 */
    public function weather($xml_obj){
        $url = 'https://devapi.qweather.com/v7/weather/now?location=101010100&key=ef14d67e99d74715b691c012e9ff4285';

        $weather_url = file_get_contents($url);
        
        file_put_contents('wx_event.log',$weather_url,FILE_APPEND);



        
    }

    /**
     * access_token
     */
    public function Aoken(){
        $key = 'wx:access_token';

        if(empty(Redis::get($key))){
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".env('WX_APPID')."&secret=".env('WX_APPSECRET');
            // echo $url;die;
            $ken = file_get_contents($url);
            $data = json_decode($ken,true);
            
            Redis::set($key,$data['access_token']);
            
            Redis::expire($key,3600);
        }
        return Redis::get($key);
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

    /**自定义菜单 */
    public function custom(){
        
        //自定义菜单   获取token
        $access_token = $this->Aoken();
        // echo $access_token;die;
        $url =  'https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$access_token;
        
        $weather_url = 'https://devapi.qweather.com/v7/weather/now?location=101010100&key=ef14d67e99d74715b691c012e9ff4285';
        $menu = [
            "button"=>[
                [	
                    "type"=>"click",
                    "name"=>"天气",
                    "key"=>"V1001_TODAY_MUSIC"
                ],
                [
                    'name'=>'菜单',
                    'sub_button'=>[
                        "type"=>"view",
                        "name"=>"百度",
                        "url"=>"http://www.baidu.com"
                    ]
                    
                ],
                
            ]
            
        ];
            // dd($menu);die;
        $client = new Client;
        $response = $client->request('post',$url,[
            'verify'=>false,
            'body'=>json_encode($menu,JSON_UNESCAPED_UNICODE)
            // ,JSON_UNESCAPED_UNICODE
        ]);
        $data = $response->getbody();
        echo $data;
        
        
    }





}
