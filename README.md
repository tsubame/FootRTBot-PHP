特定ジャンルでバズっているツイートを自動的にRTするBot。
PHP Laravellで作成。

<h4>フォルダ構成</h4>
<pre>
    app/ 
        Consts/
            Constants.php … 定数定義用
        Models/
            Tweet.php … ORM用ツイートデータ
            TwitterAPIAccessor.php … TwitterAPI操作用ビジネスロジック
        Http/
            Controllers/
                TweetController.php … Tweet関連のコントローラクラス
</pre>
        