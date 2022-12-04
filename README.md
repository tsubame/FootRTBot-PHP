特定ジャンルでバズっているツイートを自動的にRTするTwitterBot。
PHP Laravellで作成。

<h4>Twitterアカウント</h4>
https://twitter.com/foot_rt_bot

<h4>フォルダ構成</h4>
<pre>
    app/ 
        Consts/
            Constants.php … 各Twitterアカウントで使い回せる定数定義用
            appConfig.php … 各Twitterアカウント固有の設定定義用
        Models/
            Tweet.php … ORM用ツイートデータ
            TwitterAPIAccessor.php … TwitterAPI操作用ビジネスロジック
        Http/
            Controllers/
                TweetController.php … Tweet関連のコントローラクラス
</pre>
        