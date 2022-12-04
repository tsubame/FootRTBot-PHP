<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Abraham\TwitterOAuth\TwitterOAuth;
use App\Consts\Constants;
use App\Models\Tweet;

/**
 * TwitterAPIアクセス用クラス
 * 
 * [索引]
 * 　□ 0. コンストラクタ
 * 　□ 1. タイムラインからツイートを取得
 * 　□ 2. 1件のツイートのRTを実行
 * 　□ 3. 対対象キーワードの検索結果のツイートデータを取得
 * 　□ 4. トレンドのキーワードのうち、ホームタイムラインでつぶやかれているキーワード一覧を取得
 */
class TwitterAPIAccessor
{
    // API connection
    private $connection;

    // 自分のTwitterID（数値文字列形式。APIから取得）
    private $myTwitterID = '';

    //======================================================
    //
    // 0. コンストラクタ
    //
    //======================================================

    /**
     * コンストラクタ
     * 　・TwitterAPI接続
     * 　・自分のTwitterIDをセット
     */
    function __construct() {
        try {
            // API接続
            $this->connection = new TwitterOAuth(Constants::API_KEY, Constants::API_SECRET, Constants::ACCESS_TOKEN_KEY, Constants::ACCESS_TOKEN_SECRET);
            // 自分のTwitterIDをセット
            $this->setMyTwitterID();
        } catch (\Exception $e) {
            Log::error($e);
        }
    }

    //======================================================
    // 自分のTwitterIDをセット
    //======================================================

    /**
     * 自分のTwitterIDをセット
     * 　・数値文字列形式のIDをセット
     * 
     * @return void
     */
    public function setMyTwitterID()
    {
        try {
            // API Ver2を使用
            $this->connection->setApiVersion("2");
            // 自分のTwitterIDをセット
            $res = $this->connection->get('users/me', ['expansions'=> ['pinned_tweet_id']]);
            $this->myTwitterID = $res->data->id;
        } catch (\Exception $e) {
            Log::error($e);
        }
    }

    //======================================================
    //
    // 1. タイムラインからツイートを取得
    //
    //======================================================

    /**
     * タイムラインからツイートを取得
     * 　・APIv2でタイムラインからデータを取得
     * 
     * @return array
     */
    public function getTweetsFromTimeLine()
    {
        $tweets = [];

        try {              
            // APIv2でタイムラインからデータを取得
            $this->connection->setApiVersion('2');            
            $res = $this->connection->get("users/{$this->myTwitterID}/timelines/reverse_chronological", 
                ['tweet.fields' => ['public_metrics,created_at,source,author_id'], 'user.fields' => ['name,username'], 'expansions'=> ['referenced_tweets.id,author_id']]);   

            // エラー時はロギング
            if (isset($res->errors)) {
                Log::error("Twitter API TimeLine Connect Error. {$res->errors[0]->message}");

                return $tweets;
            }

            // APIのツイートを走査
            foreach($res->data as $d) {
                // APIのユーザオブジェクトデータをセット
                $u = $this->getUserDataFromAPIResVal($res, $d->author_id);
                // APIのツイートオブジェクトデータからTweetクラスのデータをセット
                $tw = new Tweet;    
                $tw->setValsFromAPITwObj($d, $u);

                // 配列に追加
                array_push($tweets, $tw);
            }                              
        } catch (\Exception $e) {
            Log::error($e);
        }

        return $tweets;
    }

    //======================================================
    // APIのレスポンスデータから該当ユーザのオブジェクトデータを返す
    //======================================================

    /**
     * APIのレスポンスデータから該当ユーザのオブジェクトデータを返す
     * 　・APIのinclueds->usersフィールドから該当ユーザIDに該当する要素を返す
     * 
     * @param mixed $apiRes
     * @param string $uid
     * @return mixed
     */
    function getUserDataFromAPIResVal($apiRes, $uid)
    {
        try {
            // APIのユーザデータを連想配列にセット                
            foreach($apiRes->includes->users as $u) {
                if ($u->id == $uid) {
                    return $u;
                }
            }
        } catch (\Exception $e) {
           Log::error($e);
        }

        return null;
    }

    //======================================================
    //
    // 2. 1件のツイートのRTを実行
    //
    //======================================================

     /**
      * 1件のツイートのRTを実行
      * 　・RT実行
      * 　・ロギング、画面上に出力
      *
      * @param Tweet $tw 
      * @return void
      */
     public function retweetTargetTweet(Tweet $tw) {
        try {  
            // RT実行    
            $this->connection->setApiVersion('2');
            $res = $this->connection->post("users/{$this->myTwitterID}/retweets", ['tweet_id' => $tw->id_str_in_twitter], true);
            // エラー時はロギング
            if (isset($res->errors)) {
                Log::error("Twitter API Retweet Error. {$res->errors[0]->message}");

                return;
            }

            // 結果をロギング、画面上に出力
            $txt = '  [RT実施]' . $tw->user_name . ' (' . $tw->client_name . ')' . $tw->rt_count . 'RT ' . $tw->tweet_text;
            echo($txt);
            Log::debug($txt);            
        } catch (\Exception $e) {
            Log::error($e);
        }
    }

    //======================================================
    //
    // 3. 対象キーワードの検索結果のツイートデータを取得
    //
    //======================================================

    /**
     * 対象キーワードの検索結果のツイートデータを取得
     * 　・検索用のパラメータをセット。RT、メンションは除外。日本語のみを検索。24時間以内のツイートのみを取得
     * 　・APIv2で検索実行
     * 　・本文にキーワードが含まれないツイートはスキップ（名前も検索に引っかかるため）
     * 　・NGワードを含むツイートはスキップ
     * 
     * @param string q 検索キーワード
     * @return array Tweetデータの配列
     */
    public function getTweetsBySearch(String $q) 
    {        
        $tweets = [];

        try {
            // 検索用のパラメータをセット
            $params = $this->getSearchParams($q);
            // APIv2で検索実行
            $this->connection->setApiVersion('2');
            $res = $this->connection->get('tweets/search/recent', $params);
            // エラー時はロギング
            if (isset($res->errors)) {
                Log::error("Twitter Search Retweet Error. {$res->errors[0]->message}");

                return $tweets;
            } 

            // APIの検索結果のツイートを走査
            foreach($res->data as $d) {
                // ユーザデータをセット
                $u = $this->getUserDataFromAPIResVal($res, $d->author_id);                              
                // Tweetデータをセット
                $tw = new Tweet;
                $tw->setValsFromAPITwObj($d, $u);

                // ツイート本文にキーワードを含まなければスキップ
                if (strpos($tw->tweet_text, $q) === false) {
                    continue;
                // NGワードを含めばスキップ
                } elseif ($this->checkTargetTweetContainsNGWords($tw)) {
                    continue;
                }

                array_push($tweets, $tw);                                
            }             
        } catch (\Exception $e) {
            Log::error($e);
        }

        return $tweets;
    }

    //======================================================
    // 検索用のパラメータを返す
    //======================================================

    /**
     * 検索用のパラメータを返す
     * 　・RT、メンションは除外。日本語のみを検索
     * 　・24時間以内のツイートのみを取得
     * 　・関連性の高いツイートを取得
     * 
     * @param string $q
     * @return array
     */
    function getSearchParams(string $q)
    {
        $params = [];

        try {
            // 検索キーワードをセット。キーワード + 日本語のみ、かつRT、メンション除外
            $qParamTxt = $q . Constants::SEARCH_KEYWORD_POSTFIX;
            // 24時間前をテキストでセット
            $sdtTxt = date('c', strtotime('now -' . Constants::SKIP_PAST_HOUR .' hours'));
            // パラメータをセット
            $params = [
                'query'        => $qParamTxt,
                'start_time'   => $sdtTxt,
                'tweet.fields' => 'public_metrics,referenced_tweets,created_at,source,author_id', 
                'expansions'   => 'author_id,referenced_tweets.id',
                'user.fields'  => 'name,username',
                'sort_order'   => Constants::SEARCH_TWEET_BY_RECENCY_OR_RELEVANT,
                'max_results'  => Constants::SEARCH_COUNT 
            ];
        } catch (\Exception $e) {
            Log::error($e);
        }

        return $params;
    }

    //======================================================
    // 該当ツイートがNGワードを含むかを返す
    //======================================================

    /**
     * 該当ツイートがNGワードを含むかを返す
     * 　・定数のNGワードを本文、ユーザ名、TwitterClientに含めばTrue
     * 
     * @param Tweet $tw
     * @return bool
     */
    private function checkTargetTweetContainsNGWords(Tweet $tw)
    {
        try {
            foreach (Constants::RT_NG_KEYWORDS as $q) {
                // 本文に含めばTrue
                if (strpos($tw->tweet_text, $q) !== false) {
                    return true;
                }
                // クライアント名に含めばTrue
                if (strpos($tw->client_name, $q) !== false) {
                    return true;
                }
                // 名前に含めばTrue
                if (strpos($tw->user_name, $q) !== false) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            Log::error($e);
        }

        return false;
    }

    //======================================================
    //
    // 4. トレンドのキーワードのうち、ホームタイムラインでつぶやかれているキーワード一覧を取得
    //
    //======================================================

    /**
     * トレンドのキーワードのうち、ホームタイムラインでつぶやかれているキーワード一覧を取得
     * 
     * @return array キーワードの配列 
     */
    public function getTrendKeywordsInHomeTimeLine()
    {
        $relatedTrWords = [];

        try { 
            // 日本のトレンドのキーワードを取得
            $allTrWords = $this->getJPTrendWords();
            // タイムラインのツイートを取得
            $htTweets = $this->getTweetsFromTimeLine();
            // トレンドのキーワードを走査
            foreach ($allTrWords as $q) {
                // タイムラインに含まれなければスキップ
                if (!$this->checkTimeLineTweetsContainsTargetWord($htTweets, $q)) {
                    continue;
                }

                // デバッグ用ロギング
                Log::debug("[トレンドから取得した検索ワード] {$q}");
                // 配列に追加
                array_push($relatedTrWords, $q);
            }
        } catch (\Exception $e) {
            Log::error($e);
        }

        return $relatedTrWords;
    }

    //======================================================
    // 日本のトレンドのキーワードを配列で取得
    //======================================================

    /**
     * 日本のトレンドのキーワードを配列で取得
     * 
     * @return array
     */
    function getJPTrendWords()
    {
        $trWords = [];

        try { 
            // 日本のトレンドを取得
            $this->connection->setApiVersion('1.1');                   
            $res = $this->connection->get('trends/place', ['id' => Constants::JAPAN_WOEID]);
            // エラー時はロギング
            if (isset($res->errors)) {
                Log::error("Twitter Trend Retweet Error. {$res->errors[0]->message}");

                return $trWords;
            } 

            // キーワードを配列に追加
            foreach ($res[0]->trends as $tr) {
                array_push($trWords, $tr->name);
            }
        } catch (\Exception $e) {
            Log::error($e);
        }

        return $trWords;
    }

    //======================================================
    // タイムラインのツイートに該当キーワードが含まれているかを返す
    //======================================================

    /**
     * タイムラインのツイートに該当キーワードが含まれているかを返す
     * 　・RTされたツイートはスキップ（フォローしているユーザ以外のツイートは該当ジャンル以外を含む可能性があるため）
     * 
     * @param array $tweets タイムラインのツイートデータ
     * @param string $q キーワード
     * @return bool
     */
    function checkTimeLineTweetsContainsTargetWord(array $htTweets, string $q)
    {
        try {
            // ツイートを走査
            foreach ($htTweets as $tw) {
                // RTされたツイートはスキップ
                if (strpos($tw->tweet_text, 'RT @') !== false) {
                    continue;
                }

                // キーワードを含めばtrue
                if (strpos($tw->tweet_text, $q) !== false) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            Log::error($e);
        }

        return false;
    }
}

?>