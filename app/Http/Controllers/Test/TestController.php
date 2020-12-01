<?php

namespace App\Http\Controllers\Test;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

use App\Model\GoodsModel;
use App\Model\Xcx\WxUserModel;
use App\Model\Xcx\CartModel;

class TestController extends Controller
{
    /**小程序 测试   类 */
    public function test(Request $request){
        // TODO
        // print_r($_GET);
        $data = [
            'goods_name'=>'zhagnsan',
            'price'=>'jiujiujiu'
        ];
        echo json_encode($data);

    }

    /**登录测试 */
    public function login(Request $request){
        //接受code
        $code = $request->get('code');
        $user = json_decode($request->get('user'),true);          //接受用户信息
        
        // dd($user);
        //获取  openid  session_key
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.env('WX_XCX_APPID').'&secret='.env('WX_XCX_SECRET').'&js_code='.$code.'&grant_type=authorization_code';
        //转化数据
        $data = json_decode(file_get_contents($url),true);
        

        //添加用户信息
        $uid = WxUserModel::where('openid',$data['openid'])->value('id');
        if(empty($uid)){
            $user_data = [
                'nickName'=>$user['nickName'],
                'gender'=>$user['gender'],
                'language'=>$user['language'],
                'country'=>$user['country'],
                'avatarUrl'=>$user['avatarUrl'],
                'openid'=>$data['openid']
            ];
            $uid = WxUserModel::insertGetId($user_data);
        }

        $key = sha1($data['openid'].$data['session_key'].time());   //Z定义token
        //存入redis 中 并设置过期时间
        $keys = 'wx_xcx_token:'.$key;
        Redis::set($keys,$data['openid']);
        Redis::expire($key,7200);
        //判断错误信息
        if(isset($data['errcode'])){
            $error = [
                'error'=>500001,
                'msg'=>$data['errmsg']
            ];
        }else{
            //储存当前用户id
            $user_key = 'userId:'.$key;
            Redis::set($user_key,$uid);
            Redis::expire($user_key,7200);

            $error= [
                'error'=>0,
                'msg'=>'登陆成功',
                'data'=>[
                    'token'=>$key
                ]
            ];
        }
        return $error;

    }

    /**商品列表 */
    public function detail(Request $request){
        $page = $request->get('page');      //每页显示数量
        $pagesize = $request->get('pagesize');      //页码
        $goods_info = GoodsModel::select('goods_name','shop_price','goods_id')->paginate($pagesize,$page);     //分页查看

        $response = [
            'error'=>0,
            'msg'=>'ok',
            'data'=>[
                'goods'=>$goods_info->items()
            ]
        ];

        return $response;
    }

    /**商品详情信息 */
    public function shoppage(Request $request){
        $goods_id = $request->get('goods_id');
        // dd($goods_id);
        //存缓存
        $key = 'goods_id'.$goods_id;
        $goodsInfo = Redis::hgetAll($key);
        if(empty($goodsInfo)){
            $goodsInfo = GoodsModel::where('goods_id',$goods_id)->first()->toArray();
            Redis::hMset($key,$goodsInfo);
        }
        $goodsInfo = [
            'error'=> 0,
            'msg' => 'ok',
            'data'=>[
                'data'=>$goodsInfo
            ]
        ];
        echo json_encode($goodsInfo);
    }

    /**Setcart 添加 商品 至购物者 */
    public function Setcart(Request $request){
        $goods_id = $request->get('goods_id');  //接受商品 id

        // $token = $request->get('token');        //获取token
        // $openid_key = 'wx_xcx_token:'.$token;          //定义key
        // $openid = Redis::get($openid_key);
        // dd($openid);
        // $uid = WxUserModel::where('openid',$openid)->select('id')->first();
        $uid = $_SERVER['uid'];

        if($uid){
            $cartInfo = CartModel::where([['user_id','=',$uid],['goods_id','=',$goods_id]])->first();
            if($cartInfo){
                CartModel::where([['user_id','=',$uid],['goods_id','=',$goods_id]])->update(['cart_num'=>$cartInfo->cart_num+1]);
            }else{
                $data = [
                    'user_id'  => $uid,
                    'goods_id' => $goods_id,
                    'add_time' => time()
                ];
                CartModel::insertGetId($data);
            }
            $cart_error=[
                'error' => 0,
                'msg'   =>'成功加入购物车',
            ];
            
        }else{
            $cart_error = [
                'error'=>500001,
                'msg'=>'网络错误'
            ];
        }
        echo json_encode($cart_error);
    }

    /**
     *购物车
     */
    public function GetCart(Request $request){
        //获取用户 自增ID  查询当前用户的购物车商品
        $token = $request->get('token');
        $user_key = 'userId:'.$token;
        $userId = Redis::get($user_key);
        //查询 当前用户下的购物车  商品
        $cartInfo = CartModel::leftjoin('p_goods','p_goods.goods_id','=','wx_xcx_cart.goods_id')->where('user_id',$userId)->select('wx_xcx_cart.goods_id','shop_price','goods_name','cart_num')->get()->toArray();
        // print_r($cartInfo);die;
        echo json_encode($cartInfo);die;
    }
    
    /**
     * decr   减数量 
     */
    public function decr(Request $request){
        $uid = $_SERVER['uid'];                 //用户id
        $gid = $request->post('gid');           //商品id
        $num = $request->post('num');           //商品数量
        
        $cartInfo = CartModel::where([['user_id','=',$uid],['goods_id','=',$gid]])->update(['cart_num'=>$num]);
        $data = [
            'error'=>0,
            'msg' => '修改成功(减)',
        ];
        die(json_encode($data));
    }

    /**
     * incr     加数量
     */
    public function incr(Request $request){
        $uid = $_SERVER['uid'];                 //用户id
        $gid = $request->post('gid');           //商品id
        $num = $request->post('num');           //商品数量

        $cartInfo = CartModel::where([['user_id','=',$uid],['goods_id','=',$gid]])->update(['cart_num'=>$num]);
        $data = [
            'error'=>0,
            'msg' => '修改成功(加)',
        ];
        die(json_encode($data));
    }

    /**
     * 收藏 collect     判断集合中是否有该商品
     */
    public function collect(Request $request){
        $goods_id = $request->get('goodsId');
        $uid = $_SERVER['uid'];
        
        $user_collect = 'collect'.$uid;
        Redis::zAdd($user_collect,time(),$goods_id);
        $data = [
            'error'=>0,
            'msg'=>'收藏成功'
        ];
        return $data;
    }

    /**
     * 购物车删除
     */
    public function delgoods(Request $request){
        $uid = $_SERVER['uid'];
        $gid = $request->post('goods');
        $gid = explode(',',$gid);
        CartModel::where('user_id',$uid)->whereIn('goods_id',$gid)->delete();
        $data = [
            'error'=>0,
            'msg'=>'删除成功'
        ];
        die(json_encode($data));
    }
        





}
