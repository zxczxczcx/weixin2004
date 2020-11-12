<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

use Log;  //注释
use GuzzleHttp\Client;

use App\Model\UserModel;
use App\Model\MediaModel;

class ApiController extends Controller
{
    protected $xml_obj;
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
            $xml_obj = simplexml_load_string($xml);         //将xml文件转换成对象
            //定义函数 
            $this->xml_obj = $xml_obj;
            //写入消息
            switch($xml_obj->MsgType){
                //图片
                case'image';
                return $this->image();
            break;
            }
            //判断
            if($xml_obj->MsgType=='event'){
                //关注
                if($xml_obj->Event=='subscribe'){
                    
                    $wx_user = UserModel::where(['openid'=>$xml_obj->FromUserName])->first();
                    if($wx_user){
                        $Content = '谢谢再次关注';
                    }else{
                        //关注 方法
                        $Content = '关注成功';
                        //用户信息
                        $access_token = $this->Aoken();             //获取access_token
                        // dd($access_token);
                        // $fromusername = $xml_obj->FromUserName;
                        $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token.'&openid='.$xml_obj->FromUserName.'&lang=zh_CN';
                        $user_json = file_get_contents($url);                 //发送地址  接回来 json 字符串
                        $user_data = json_decode($user_json,true);          //转换成数组

                        $data = [
                            'nickname'=>$user_data['nickname'],
                            'sex'=>$user_data['sex'],
                            'country'=>$user_data['country'],
                            'headimgurl'=>$user_data['headimgurl'],
                            'add_time'=>$user_data['subscribe_time'],
                            'openid'=>$user_data['openid'],
                        ];

                        UserModel::insertGetId($data);  //添加用户    
                    }
                    
                    
                    $resule = $this->attention($xml_obj,$Content);          //调用回复文本
                    return $resule;     //关注成功  返回值
                }
                //自定义 菜单回复
                if($xml_obj->Event=='CLICK'){
                    switch($xml_obj->EventKey){
                        //天气  按钮
                        case'V1001_TODAY_MUSIC';
                            $count_str = $this->weather();          //天气 返回参数
                            $weather = $this->attention($xml_obj,$count_str);           //xml  返回微信
                            echo $weather;
                        break;
                        //签到按钮
                        case'SIGN_IN';
                            $count_str = $this->sgin();
                            // $count_str = '签到成功';
                            $weather = $this->attention($xml_obj,$count_str);   
                            echo $weather;
                        break;
                        
                    }
                }
                
            }else if($xml_obj->MsgType=='text'){
                //信息 回复
                switch($xml_obj->Content){
                    case'天气';
                        $count_str = $this->weather();          //天气 返回参数
                        $weather = $this->attention($xml_obj,$count_str);           //xml  返回微信
                        echo $weather;
                    break; 
                    case'你好';
                        $Content = '欢迎来到我的世界';
                        $weather = $this->attention($xml_obj,$Content);           //xml  返回微信
                        echo $weather;
                    break;
                    case'时间';
                        $time = date('Y-m-d H:i:s',time());
                        $weather = $this->attention($xml_obj,$time);           //xml  返回微信
                        echo $weather;
                    break; 
                }
            }
        }
    }
    
    /**天气   和风 */
    public function weather(){
        $url = 'https://devapi.qweather.com/v7/weather/now?location=101010100&key=ef14d67e99d74715b691c012e9ff4285&gzip=n';
        $weather_url = file_get_contents($url);
        // $weather_url = '{"code":"200","updateTime":"2020-11-11T11:26+08:00","fxLink":"http://hfx.link/2ax1","now":{"obsTime":"2020-11-11T10:59+08:00","temp":"10","feelsLike":"8","icon":"100","text":"晴","wind360":"90","windDir":"东风","windScale":"1","windSpeed":"5","humidity":"49","precip":"0.0","pressure":"1027","vis":"6","cloud":"10","dew":"1"},"refer":{"sources":["Weather China"],"license":["no commercial use"]}}';
        // $weather_url = $client->request('get',$url,['verify'=>false]);
        $weather_url = json_decode($weather_url,true);
        $weather_data = $weather_url['now'];
        
        $count_str = '日期：'.date('Y-m-d H:i:s',time()+8).'天气：'.$weather_data['text'].';风向：'.$weather_data['windDir'].';风力等级：'.$weather_data['windScale'];
        return $count_str;
        
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
     *  被动回复 发送文本
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

    /**添加图片信息 */
    public function image(){
        $xml = $this->xml_obj;
        $data = [
            'openid'=>$xml->FromUserName,
            'msgtype'=>$xml->MsgType,
            'add_time'=>$xml->CreateTime,
            'msgid'=>$xml->MsgId,
            'picurl'=>$xml->PicUrl,
            'mediaid'=>$xml->MediaId
        ];
        MediaModel::insertGetId($data);
    }

    /**自定义菜单 */
    public function custom(){
        
        //自定义菜单   获取token
        $access_token = $this->Aoken();
        // echo $access_token;die;
        $url =  'https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$access_token;
        
        // $weather_url = 'https://devapi.qweather.com/v7/weather/now?location=101010100&key=ef14d67e99d74715b691c012e9ff4285';
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
                                [
                                "type"=>"view",
                                "name"=>"百度",
                                "url"=>"http://www.baidu.com"
                            ],[
                                "type"=>"click",
                                "name"=>"签到",
                                "key"=>"SIGN_IN"
                            ]
                        ]
                    
                ],
                
            ]
            
        ];
            // dd($menu);die;
        $client = new Client;
        $response = $client->request('post',$url,[
            'verify'=>false,
            'body'=>json_encode($menu,JSON_UNESCAPED_UNICODE)
            // JSON_UNESCAPED_UNICODE
        ]);
        $data = $response->getbody();
        echo $data;
        
    }

    






}
