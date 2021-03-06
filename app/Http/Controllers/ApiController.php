<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

use Log;  //注释
use GuzzleHttp\Client;

use App\Model\UserModel;
use App\Model\MediaModel;
use App\Model\SpellModel;

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

            //接受数据 网络数据
            $xml = file_get_contents('php://input');
            //记录日志
            file_put_contents('wx_event.log',$xml,FILE_APPEND);
            $xml_obj = simplexml_load_string($xml);         //将xml文件转换成对象a  

            //定义函数
            $this->xml_obj = $xml_obj;
            // echo __LINE__;die;

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

                    $this->custom();                               //自定义菜单
                    $resule = $this->attention($Content);          //调用回复文本
                    return $resule;                                //关注成功  返回值
                }
                //菜单  回复
                if($xml_obj->Event=='CLICK'){
                    switch($xml_obj->EventKey){
                        //天气  按钮
                        case'V1001_TODAY_MUSIC';
                            $count_str = $this->weather();          //天气 返回参数
                            $weather = $this->attention($count_str);           //xml  返回微信
                            return $weather;
                        break;
                        //签到按钮
                        case'SIGN_IN';
                            $count_str = $this->sign();
                            $weather = $this->attention($count_str);
                            echo $weather;
                        break;
                        case'SHOP';         //商城
                            $this->shoptype();
                        break;
                        case'HISTORY';
                            $history_str = $this->history();
                            $weather = $this->attention($history_str);
                            echo $weather;

                    }
                }

            }else if($xml_obj->MsgType=='text'){
                //信息 回复
                switch($xml_obj->Content){
                    case"%天气%";
                        $count_str = $this->weather();          //天气 返回参数
                        $weather = $this->attention($count_str);           //xml  返回微信
                        echo $weather;
                    break;
                    case'你好';
                        $Content = '欢迎来到我的世界';
                        $weather = $this->attention($Content);           //xml  返回微信
                        echo $weather;
                    break;
                    case'时间';
                        $time = date('Y-m-d H:i:s',time());
                        $weather = $this->attention($time);           //xml  返回微信
                        echo $weather;
                    break;
                    default:  
                            $Count =   $xml_obj->Content;
                            
                            $Content = $this->spell($Count);           //xml  返回微信
                            $weather = $this->attention($Content);
                            echo $weather;

                        // echo $this->else_Text();  
                }
            }

            //写入消息  只写入，不返回
            switch($xml_obj->MsgType){
                case'image';
                    $this->rand_image();
                break;
                case'text';
                    $this->rand_text();
                break;
                case'voice';
                    $this->rand_voice();
                break;

            }
        }
    }

    /**history  查询最新十条 拼音 */
    public function history(){
        $spInfo = SpellModel::limit(10)->orderBy('id','desc')->get()->toArray();
        $sp_str = '';
        foreach($spInfo as $k=>$v){
            $sp_str .=$v['pinyin']."\r\n";
        }
        return $sp_str;
    }

    /**天气   和风 */
    public function weather(){
        $url = 'https://devapi.qweather.com/v7/weather/now?location=101010100&key=ef14d67e99d74715b691c012e9ff4285&gzip=n';
        $weather_url = file_get_contents($url);

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
    public function attention($Content){
        $xml_obj = $this->xml_obj;
        // dd($xml_obj);
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

    /**回复图片 TODO */
    public function imgtype(){
        $xml_obj = $this->xml_obj;
        // dd($xml_obj);
        //拼凑数据
        $tousername = $xml_obj->FromUserName;
        $fromusername = $xml_obj->ToUserName;
        $img = $xml_obj->PicUrl;
        $data_img = '<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[%s]]></MsgType>
                        <Image>
                        <MediaId><![CDATA[%s]]></MediaId>
                        </Image>
                    </xml>';
        $xml_info = sprintf($data_img,$tousername,$fromusername,time(),'text',$img);
        return $xml_info;
    }

    /**回复信息  其他信息 */
    public function else_Text(){
        $xml_obj = $this->xml_obj;
        // dd($xml_obj);
        //拼凑数据
        $Content = '系统识别中.....';
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

        $menu = [
            "button"=>[
                [
                    "type"=>"click",
                    "name"=>"每日推荐",
                    "key"=>"RMDSHOP"
                ],
                [
                    "type"=>"click",
                    "name"=>"签到",
                    "key"=>"SIGN_IN"
                ],[
                    'name'=>'菜单',
                    'sub_button'=>[
                        [
                            "type"=>"view",
                            "name"=>"百度",
                            "url"=>"http://www.baidu.com"
                        ],[
                            "type"=>"click",
                            "name"=>"商城",
                            "key"=>"SHOP"
                        ],[
                            "type"=>"click",
                            "name"=>"天气",
                            "key"=>"V1001_TODAY_MUSIC"
                        ],[
                            "type"=>"click",
                            "name"=>"查询历史记录",
                            "key"=>"HISTORY"
                        ]
                    ]
                ]

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
        return  $data;

    }

    public function shoptype(){
        // $url = '';
    }

    /**保存照片 image  并回复 TODO */
    public function rand_image(){
        $xml = $this->xml_obj;
        // dd($xml->Picurl);
        $data = [
            'openid'=>$xml->FromUserName,
            'msgtype'=>$xml->MsgType,
            'add_time'=>$xml->CreateTime,
            'msgid'=>$xml->MsgId,
            'picurl'=>$xml->PicUrl,
            'mediaid'=>$xml->MediaId,
        ];
        MediaModel::insert($data);

        $photo = file_get_contents($xml->PicUrl);
    }

    /**写入文本信息  rand_text*/
    public function rand_text(){
        $xml = $this->xml_obj;
        $data = [
            'openid'=>$xml->FromUserName,
            'msgtype'=>$xml->MsgType,
            'add_time'=>$xml->CreateTime,
            'content'=>$xml->Content,
            'msgid'=>$xml->MsgId,

        ];
        MediaModel::insert($data);
    }

    /**写入 语音 */
    public function rand_voice(){
        $xml = $this->xml_obj;
        $data = [
            'openid'=>$xml->FromUserName,
            'msgtype'=>$xml->MsgType,
            'add_time'=>$xml->CreateTime,
            'msgid'=>$xml->MsgId,
            'format'=>$xml->Format,
            'mediaid'=>$xml->MediaId,
        ];
        return MediaModel::insert($data);
    }

    /**签到 sign   待优化==========*/
    public function sign(){
        $xml = $this->xml_obj;  //得到xml 数据
        $fromUser = $xml->FromUserName;
        $key = 'wx_user:'.$fromUser;        //定义key
        $time = strtotime(date('Y-m-d',time()));

        $user_time = Redis::zrange($key,0,-1);
        //判断
        if(in_array($time,$user_time)){
            $Content = '今日已签到';
        }else{
            Redis::zAdd($key,$time);    //有序集合
            $Content = '签到成功';
        }
        // dd($Content);
        return $Content;

    }

    /** 汉子转拼音  spell*/
    public function spell($Count){
        // $count = '天行数据是一个网络接口平台';
        $spellInfo = SpellModel::where('hanzi',$Count)->select('pinyin')->first();              //对象
        if(empty($spellInfo)){
            //得出数组
            $url = 'http://api.tianapi.com/txapi/pinyin/index?key=e64b4aed04815a9ecbfadd32234883af&text='.$Count;
            $spell = file_get_contents($url);

            $sInfo = json_decode($spell,true);
            $spe = $sInfo['newslist'];
            $pinyin = '';
            foreach($spe as $k=>$v){
                $pinyin= $v;
            }
            $data = [
                'pinyin'=>$pinyin['pinyin'],
                'hanzi'=>$Count
            ];
            SpellModel::insert($data);
        }else{
            $pinyin = $spellInfo->toArray();
        }
        return $pinyin['pinyin'];
    }









}
