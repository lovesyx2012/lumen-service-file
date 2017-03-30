<?php

namespace App\Http\Controllers\V1;

use App\File;
use Dingo\Api\Http\Request;

class AppController extends BaseController
{
    public function login(Request $request){
      $code = $request->get("code");
      $api_url = "https://api.weixin.qq.com/sns/jscode2session?appid=".env("APPID")."&secret=".env("APPSECRET")."&js_code=".$code."&grant_type=authorization_code";

      $result = file_get_contents($api_url);

      $result = json_decode($result,true);

      \Log::debug("debug",[$result]);
      
      $openid = $result['openid'];
      $session_key = $result['session_key'];

      $client_session_key = $openid."_"."syx"."_".$session_key;
      \Log::debug("session",[$client_session_key]);
      return base64_encode($client_session_key);
    }
}
