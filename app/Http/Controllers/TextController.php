<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Redis;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use App\model\User;
class TextController extends Controller
{
    public function wx(Request $request){
        //echo __METHOD__;die;
        //echo __LINE__;die;

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
            echo "";
            $this->responseMsg();
            $this->getweather();

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
            //echo '有缓存';
            return $token;
        }else{
          //  echo '无缓存';
            $url= "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".env('WX_APPID')."&secret=".env('WX_APPSECRET')."";
            // dd($url);
            $response=file_get_contents($url);
            $data=json_decode($response,true);
            $token=$data['access_token'];
            //dd($token);
            //存入redis
            Redis::set($key,$token);
            //设置过期时间
            Redis::expire($key,3600);
        }

        return $token;
    }
    //关注回复
    public function responseMsg(){
        $postStr = file_get_contents("php://input");
        $postArray= simplexml_load_string($postStr,"SimpleXMLElement",LIBXML_NOCDATA);
        if ($postArray->MsgType=="event"){
            if($postArray->Event=="subscribe"){
                $openid=$postArray->FromUserName;   //获取用户的openid
                $AccessToken=$this->access_token();   //获取token
                $url="https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$AccessToken."&openid=".$openid."&lang=zh_CN";
//                            dd($url);
                $user=file_get_contents($url);    //获取第三方 的数据
                $user=json_decode($user,true);

                if(isset($user['errcode'])){
                    $this->writeLog("获取用户信息失败了");

                }else{
                    $user_id=User::where('openid',$openid)->first();   //查询一条
                    if($user_id){
                        $user_id->subscribe=1;   //查看这个用户的状态  1关注   0未关注
                        $user_id->save();
                        $content="谢谢你们再次关注,我们加倍努力的";
//                                    echo $this->text($obj,$content);
                    }else{
                        $res=[
                            "subscribe"=>$user["subscribe"],
                            "openid"=>$user["openid"],
                            "nickname"=>$user["nickname"],
                            "sex"=>$user["sex"],
                            "city"=>$user["city"],
                            "country"=>$user["country"],
                            "province"=>$user["province"],
                            "language"=>$user["language"],
                            "headimgurl"=>$user["headimgurl"],
                            "subscribe_time"=>$user["subscribe_time"],
                            "subscribe_scene"=>$user["subscribe_scene"]
                        ];
                        User::insert($res);
                        $content="官人，谢谢关注！";
//                                    echo $this->text($obj,$content);

                    }
                }

            }
            //取消关注
            if($postArray->Event=="unsubscribe"){
//                            $content="取消关注成功,期待你下次关注";
//                            $openid=$obj->FromUserName;
//                            $user_id=User::where('user_id',$openid)->first();
                $user_id->subscribe=0;
                $user_id->save();
            }
            echo   $this->text($postArray,$content);
        }elseif ($postArray->MsgType=="text"){
            $msg=$postArray->Content;
            switch ($msg){
                case '你好';
                    $content='enen';
                    $this->text($postArray,$content);
                    break;
                case '时间';
                    $content=date('Y-m-d H:i:s',time());
                    $this->text($postArray,$content);
                    break;
                case  '天气';
                    $content = $this->getweather();
                    $this->text($postArray,$content);
                    break;
                default;
                    $content='陈嘉尚';
                    $this->text($postArray,$content);
                    break;
            }
        }
    }


    //天气
    public function getweather(){
        $url='http://api.k780.com/?app=weather.realtime&weaid=1&ag=today,futureDay,lifeIndex,futureHour&appkey=10003&sign=b59bc3ef6191eb9f747dd4e83c99f2a4&format=json';
        $weather=file_get_contents($url);
        $weather=json_decode($weather,true);
        if($weather['success']){
            $content = '';
            $v=$weather['result']['realTime'];
            $content .= "日期:".$v['week']."当日温度:".$v['wtTemp']."天气:".$v['wtNm']."风向:".$v['wtWindNm'];

        }
        return $content;
    }

    public function text($postArray,$content){
        $toUser = $postArray->FromUserName;
        $fromUser = $postArray->ToUserName;
        $template = "<xml>
                                    <ToUserName><![CDATA[%s]]></ToUserName>
                                    <FromUserName><![CDATA[%s]]></FromUserName>
                                    <CreateTime>%s</CreateTime>
                                    <MsgType><![CDATA[%s]]></MsgType>
                                    <Content><![CDATA[%s]]></Content>
                                </xml>";
        $info = sprintf( $template, $toUser, $fromUser, time(), 'text', $content);
        echo $info;
    }

    public function test5(){
        $client = new Client();
        $this->access_token();
        $res=   $this->access_token();
        //dd($res);
        $url='https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$res;
       // echo $url;
        $menu = [
            'button'    => [
                [
                    'type'  => 'click',
                    'name'  => 'WX.2004',
                    'key'   => 'k_wx_2004'
                ],
                [
                    'type'  => 'view',
                    'name'  => '商城',
                    'url'   => 'http://2004gqs.comcto.com/'
                ],

                [
                    'name'          => '菜单',
                    'sub_button'    => [
                        [
                            'type'  => 'click',
                            'name'  => '签到',
                            'key'   => 'checkin'
                        ],
                        [
                            'type'  => 'click',
                            'name'  => '点赞',
                            'key'   => 'Like'
                        ],
                        [
                            'type'  => 'pic_photo_or_album',
                            'name'  => '传图',
                            'key'   => 'uploadimg'
                        ],
                        [
                            'type'  => 'click',
                            'name'  => '天气',
                            'key'   => 'weather'
                        ]
                    ]
                ],

            ]
        ];

        $menu = json_encode($menu,256);
        $res = $client->request('post', $url, [
            'auth' => ['user', 'pass'],
            'verify'=>false,
            'body'  =>$menu
        ]);
       // echo $res->getStatusCode();

        $json_data = $res->getBody();

        //判断接口返回
        $info = json_decode($json_data,true);
       // dd($info);
        if($info['errcode'] > 0)        //判断错误码
        {
          echo '创建失败';
        }else{
           echo '创建成功';
        }


    }


}
