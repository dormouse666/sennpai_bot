<?php

// OAuthスクリプトの読み込み
require_once(dirname(__FILE__).'/twitteroauth/autoload.php');
use Abraham\TwitterOAuth\TwitterOAuth;

// Config呼び出し
require_once("apiconfig.php");

// コネクション設定
$TwitterOAuth = new TwitterOAuth($consumerKey,$consumerSecret,$accessToken,$accessTokenSecret);

//timeZoneセット
date_default_timezone_set('Asia/Tokyo');

// リプライに反応
reply($TwitterOAuth, $screenName, $gohanAccesskey);

// 適当なつぶやき
randomTweet($TwitterOAuth);


/**
*  リプライに反応
*/
function reply($TwitterOAuth, $screenName, $gohanAccesskey)
{
    $reply = $TwitterOAuth->get('statuses/mentions_timeline');
    //var_dump($reply);

    foreach($reply as $key => $value)
    {
        //自分の前回の発言以降のリプライにのみ反応
        $timeLineResult = $TwitterOAuth->get('statuses/user_timeline', array('screen_name' => $screenName));
        $lastId = $timeLineResult[0]->id_str;
        if($lastId > $value->id_str || empty($timeLineResult))
        {
            if(empty($timeLineResult))
            {
                error_log(print_r("$timeLineResult is null", true));
            }

            continue;
        }

        // 自分には反応しない
        if($value->user->screen_name != $screenName)
        {
            //スクリーンネームを消す @とA-zと0-9とアンダーバー
            $tempText = preg_replace("/@[A-z0-9_]+/", "", $value->text);

            // 土下座
            if(preg_match("/土下座/", $tempText))
            {
                replyDogeza($TwitterOAuth, $value);
                continue;
            }

            // ごはん
            if(preg_match("/ごはん/", $tempText) || preg_match("/ご飯/", $tempText))
            {
                $tempText = preg_replace("/ごはん/", "", $tempText); //ごはんを消す
                $tempText = preg_replace("/ご飯/", "", $tempText); //ごはんを消す
                $tempText = preg_replace("/\s/", "", $tempText); //空白を消す
                $gohanText = preg_replace("/、/", ",", $tempText); //カンマに直す
                //var_dump($gohanText);
                replyGohan($TwitterOAuth, $value, $gohanAccesskey, $gohanText);
                continue;
            }

            //　上記以外
            $replyFilelist = file(dirname(__FILE__).'/replyList.txt');
            if(shuffle($replyFilelist))
            {
                $replyMessage = $replyFilelist[0];
            }
            $resMessage = '@'.$value->user->screen_name.' '.$replyMessage;
            $response = $TwitterOAuth->post('statuses/update', array('status' => $resMessage, 'in_reply_to_status_id'=>$value->id_str));

            // エラー出力
            if($TwitterOAuth->getLastHttpCode() != 200)
            {
                error_log(print_r($response, true));
            }
        }
    }
}

/**
*  土下座
*/
function replyDogeza($TwitterOAuth, $value)
{
    // 画像をランダムに抽出
    $dogezafilelist = file(dirname(__FILE__).'/dogezaList.txt', FILE_IGNORE_NEW_LINES);
    if( shuffle($dogezafilelist) )
    {
      $filePathTemp = $dogezafilelist[0];
    }
    $filePath = __DIR__.$filePathTemp;
    $picResult = $TwitterOAuth->upload('media/upload', array('media' => $filePath));

    // ランダムな謝罪メッセージとともに返信
    $filelist = file(dirname(__FILE__).'/apologyList.txt');
    if(shuffle($filelist))
    {
        $apologyMessage = $filelist[0];
    }
    $resMessage = '@'.$value->user->screen_name.' '.$apologyMessage;
    $response = $TwitterOAuth->post('statuses/update', array('status' => $resMessage, 'media_ids' => $picResult->media_id_string, 'in_reply_to_status_id'=>$value->id_str));

    // エラー出力
    if($TwitterOAuth->getLastHttpCode() != 200)
    {
        error_log(print_r($response, true));
    }
}

/**
*  ごはん@ぐるなび
*/
function replyGohan($TwitterOAuth, $value, $gohanAccesskey, $gohanText)
{
    // エンドポイントとパラメータ設定
    $endpoint = 'http://api.gnavi.co.jp/RestSearchAPI/20150630/';
    $hitNum = 50;
    $gohanParams = [
        'keyid' => $gohanAccesskey,
        'format' => 'json',
        'latitude' => '35.657988',  //渋谷マークシティの緯度＆経度 エリアコードの方がいいかもしらんが
        'longitude' => '139.698056',
        'range' => '5',             // 緯度/経度からの検索範囲(半径) 5:3000m
        'hit_per_page' => $hitNum,  // 取得件数 どうも50が上限
        'freeword' => $gohanText,   //フリーワード検索「,」区切りで複数ワードが検索可能（１０個まで）
    ];
    $gohanUrl = $endpoint.'?'.http_build_query($gohanParams, '', '&');

    // API実行
    $gohanJson = file_get_contents($gohanUrl);

    // 取得した結果をオブジェクト化
    $gonahObj  = json_decode($gohanJson);
    //var_dump($gonahObj);

    // エラーハンドリング
    if(empty($gonahObj->rest)) //失敗するとrestキーが存在しない
    {
        $errorCode = $gonahObj->error->code;
        $errorMessage = $gonahObj->error->message;
        if(!empty($errorMessage))
        {
            $errorMessage = "「".$errorMessage."」とのこと";
        }
        error_log(print_r($gonahObj, true));

        $resMessage = '@'.$value->user->screen_name.' '."すません…".$errorCode."エラーです…\n".$errorMessage;
        $response = $TwitterOAuth->post('statuses/update', array('status' => $resMessage, 'in_reply_to_status_id'=>$value->id_str));

        // エラー出力
        if($TwitterOAuth->getLastHttpCode() != 200)
        {
            error_log(print_r($response, true));
        }

        return;
    }

    // 成功してたら1件だけ取得
    $i = 0;
    $rand = rand(0, $hitNum-1);
    //var_dump($rand);
    foreach($gonahObj->rest as $restArray)
    {
        $gohanName = $restArray->name;
        $gohanUrl = $restArray->url;

        if($i >= $rand)
        {
            break;
        }
        $i++;
        //var_dump($i);
    }

    $gohanNum = $gonahObj->total_hit_count;
    if(!empty($gohanText))
    {
        $gohanText = "「".preg_replace("/,/", "、", $gohanText)."」は";
    }
    //var_dump($gohanText);

    $gohanStr = $gohanText.$gohanNum."件あった、オススメはこれかな\n".$gohanName."\n".$gohanUrl;
    $gohanMessage = 'ﾄﾞｿﾞｰ';
    $resMessage = '@'.$value->user->screen_name.' '.$gohanMessage."\n".$gohanStr;
    $response = $TwitterOAuth->post('statuses/update', array('status' => $resMessage, 'in_reply_to_status_id'=>$value->id_str));

    // エラー出力
    if($TwitterOAuth->getLastHttpCode() != 200)
    {
        error_log(print_r($response, true));
    }
}

/**
*  適当なつぶやき
*/
function randomTweet($TwitterOAuth)
{
    $now = new DateTime();
    $min = $now->format('i');
    $hour = $now->format('H');
    if($min == 10 && ($hour%2 == 0)) //2hに1回だけしゃべる
    {
        // 3〜7時は寝てる
        if(3 <= $hour && $hour <= 7)
        {
            //寝てる
        }
        else
        {
            // ファイルの行をランダムに抽出
            $filelist = file(dirname(__FILE__).'/tweetList.txt');
            if(shuffle($filelist))
            {
                $message = $filelist[0];
            }

            // 投稿
            $response = $TwitterOAuth->post('statuses/update', array('status' => $message));

            // 結果出力
            //var_dump($response);

            // エラー出力
            if($TwitterOAuth->getLastHttpCode() != 200)
            {
                error_log(print_r($response, true));
            }
        }
    }
}

?>
