# sennpai_bot

身内にしか楽しくない先輩botです。  
テキストファイルで動くごくごく原始的なbotです。

フォルダ直下に apiconfig.php を作成し、以下の情報を定義してもらえると使えます。

```
<?PHP
$consumerKey = 'hogehoge';
$consumerSecret = 'hogehoge';
$accessToken = 'hogehoge';
$accessTokenSecret = 'hogehoge';
?>
```

cronかなんかで `sennpai_bot.php` を定期的に叩いてください。


