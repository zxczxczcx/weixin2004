<?php

namespace App\Http\Controllers\Test;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class TestController extends Controller
{
    public function test(){

        // echo PHPinfo();die;
        //测试
        // $a = DB::table('p_users')->limit(5)->get();
        
        //redis
        $key = 'user';
        Redis::set($key,time());
        $time = Redis::get($key);
        echo strtotime(date('Y-m-d'),$time);
        echo '<hr>';
        echo 'numberone';
        
    }
    
    /*
     * 接口
     */
    public function APIS(){
        
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        
        $token = TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
        
        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }
    
    
    
    
    
    
    
}
