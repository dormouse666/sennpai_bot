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

// リプライに反応 start*********************************************

$reply = $TwitterOAuth->get('statuses/mentions_timeline');
//var_dump($reply);

foreach($reply as $key => $value)
{
    //自分の前回の発言以降のリプライにのみ反応
    $timeLineResult = $TwitterOAuth->get('statuses/user_timeline', array('screen_name' => 'sennpai_bot'));
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
    if($value->user->screen_name != "sennpai_bot")
    {
        $tempText = preg_replace("/@[A-z_]+/", "", $value->text); //スクリーンネームを消す

        // 土下座
        if(preg_match("/土下座/", $tempText))
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

            continue;
        }

        //　土下座以外
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

// リプライに反応 end*********************************************


// 適当なつぶやき start*********************************************

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

// 適当なつぶやき  end*********************************************

?>
