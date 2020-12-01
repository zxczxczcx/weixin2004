<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Redis;

class Checktoken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $token = $request->get('token');
        $user_key = 'userId:'.$token;
        $userId = Redis::get($user_key);
        if($userId){
            $_SERVER['uid'] = $userId;
        }else{
            $response = [
                'error' => 4000033,
                'msg'   => '未授权'
            ];
            die(json_encode($response));
        }

        return $next($request);
    }
}
