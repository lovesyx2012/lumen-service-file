<?php

namespace App\Http\Controllers\V1;

use App\File;
use Dingo\Api\Http\Request;

class AppController extends BaseController
{
    public function login(Request $request){
      $code = $request->get("code");
      $api_url = "https://api.weixin.qq.com/sns/jscode2session?appid=".env("APPID")."&secret=".env("APPSECRET")."&js_code=".$code."&grant_type=authorization_code";

      $result = wget($api_url);
      return $result;
    }
}