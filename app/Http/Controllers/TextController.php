<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Redis;
use Illuminate\Http\Request;

class TextController extends Controller
{
    public function wx(Request $request){
            $echostr=$request->get('echostr');
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = env("WX_TOKNE");
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature ){
            //接收数据
            $xml_str=file_get_contents("php://input");
            //记录日志
            file_put_contents("wx_event,log",$xml_str);
            //把xml转换PHP数据
            echo "echostr";
            die;
        }else{
           echo "";
        }
    }
    //获取access_token
    public function access_token(){
        $key="access_token:";
        //判断是否 有缓存
        $token=Redis::get($key);
        if($token){
            echo '有缓存';
        }else{
            echo '无缓存';
            $url= "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".env('WX_APPID')."&secret=".env('WX_APPSECRET')."";
            // dd($url);
            $response=file_get_contents($url);
            $data=json_decode($response,true);
            $token=$data['access_token'];
            //dd($token);
            //存入redis

            Redis::set($key,$token);
            //设置过期时间
            Redis::expire($key,20);
        }

        echo "access_token:".$token;
    }

}
