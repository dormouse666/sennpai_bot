# sennpai_bot

身内にしか楽しくない先輩botです。  
テキストファイルで動くごくごく原始的なbotです。

フォルダ直下に apiconfig.php を作成し、以下の情報を定義してもらえると使えます。

```
<?PHP
$consumerKey = 'hogehoge';        // twitterのconsumerKey
$consumerSecret = 'hogehoge';     // twitterのconsumerSecret
$accessToken = 'hogehoge';        // twitterのaccessToken
$accessTokenSecret = 'hogehoge';  // twitterのaccessTokenSecret
$screenName = 'hogehoge';         // 使用するtwitterのアカウント名　（例: sennpai_bot）
$gohanAccesskey = 'hogehoge';     // ぐるなびapiのAccesskey
?>
```

cronかなんかで `sennpai_bot.php` を定期的に叩いてください。

2017/9/20: ごはん機能を追加しました。
- 「ごはん」とリプライすると、渋谷マークシティ近辺の飲食店をぐるなびで探して持ってきます
- 「ごはん hogehoge」とリプライすると、渋谷マークシティ近辺の飲食店をぐるなびでhogehogeで検索して持ってきます
- 複数指定したい場合は「ごはん hogehoge、fugafuga」みたいに「、」で区切って指定してあげてください
※hogehoge、fugafugaは任意の文字列です
