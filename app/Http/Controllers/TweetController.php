<?php

namespace App\Http\Controllers;

use App\Models\TwitterAPIAccessor;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use App\Consts\Constants;
use App\Models\Tweet;

/**
 * ツイート関連のコントローラクラス
 * 
 * [索引]
 *  □ 1. タイムラインからRTを実施
 *  □ 2. 検索キーワードからRTを実施
 *  □ 3. トレンドのキーワードからRTを実施
 */
class TweetController extends Controller
{

    //======================================================
    //
    // 1. タイムラインからRTを実施
    //
    //======================================================

    /**
     * タイムラインからRTを実施
     * 　・タイムラインからツイートを取得
     * 　・リツイート対象外（一定のRT数未満、かつDB保存済）のツイートはスキップ
     * 　・該当ツイートをRTしDBに保存
     */
    public function rtFromTimeLine(Request $request, Response $response) {
        try {
            // タイムラインからツイートを取得
            $twApiAccessor = new TwitterAPIAccessor();
            $tweets = $twApiAccessor->getTweetsFromTimeLine();
            // ツイートを走査
            foreach ($tweets as $tw) {
                // リツイート対象外ならスキップ
                if (!$this->isRTTarget($tw)) {
                    continue;
                }

                // DB保存
                $tw->save();
                // RT実行
                $twApiAccessor->retweetTargetTweet($tw);   
            }
        } catch (\Exception $e) {
            Log::error($e);
        }

        return $response;
    }

    //======================================================
    // RT対象かを返す
    //======================================================

    /**
     * RT対象かを返す
     * 　・DB保存済ならfalse
     * 　・RT数が一定未満ならfalse
     * 　・該当ツイートが他のツイートをRTしたものであり、該当ツイートをスキップする設定の場合はfalse
     * 
     * @param Tweet $tw
     * @return bool
     */
    function isRTTarget(Tweet $tw)
    {
        try {
            // DB保存済ならfalse
            $res = Tweet::where('id_str_in_twitter', $tw->id_str_in_twitter)->exists();
            if ($res) {
                return false;
            }
            // RT数が一定未満ならfalse
            if ($tw->rt_count < Constants::RETWEET_LEAST_RT) {
                return false;
            }
            // 該当ツイートが他のツイートをRTしたものであり、スキップする設定ならfalse
            if (strpos($tw->tweet_text, Constants::RT_TWEET_KEYWORD)) {
                if (Constants::SKIP_TIMELINE_RT_TWEET) {
                    return false;
                }
            }
        } catch (\Exception $e) {
            Log::error($e);
        }

        return true;
    }

    //======================================================
    //
    // 2. 検索キーワードからRTを実施
    //
    //======================================================

    /**
     * 検索キーワードからRTを実施
     * 　・定数の検索キーワードで検索してツイートを取得
     * 　・一定のRT数未満、かつDB保存済（RT済）のツイートはスキップ
     * 　・該当ツイートをRTしDBに保存
     */
    public function rtFromSearch(Request $request, Response $response) {
        try {
            $twApiAccessor = new TwitterAPIAccessor();

            // 検索キーワードを走査
            foreach(Constants::SEARCH_TARGET_KEYWORDS as $q) {                
                // 検索キーワードからツイートを取得
                $tweets = $twApiAccessor->getTweetsBySearch($q);
                // ツイートを走査
                foreach ($tweets as $tw) {
                    // リツイート対象外ならスキップ
                    if (!$this->isRTTarget($tw)) {
                        continue;
                    }

                    // RT実施
                    $twApiAccessor->retweetTargetTweet($tw);
                    // DB保存
                    $tw->save();
                }
            }
        } catch (\Exception $e) {
            Log::error($e);
        }

        return $response;
    }

    //======================================================
    //
    // 3. トレンドのキーワードからRTを実施
    //
    //======================================================

    /**
     * トレンドのキーワードからRTを実施
     */
    public function rtFromTrend(Request $request, Response $response) {
        try {
            // トレンドのキーワードのうち、タイムラインに含まれるのものを取得
            $twApiAccessor = new TwitterAPIAccessor();
            $trWords = $twApiAccessor->getTrendKeywordsInHomeTimeLine();

            // キーワードを走査
            foreach ($trWords as $q) {
                // 検索キーワードからツイートを取得
                $tweets = $twApiAccessor->getTweetsBySearch($q);
                foreach ($tweets as $tw) {
                    // リツイート対象外ならスキップ
                    if (!$this->isRTTarget($tw)) {
                        continue;
                    }

                    // RT実施
                    $twApiAccessor->retweetTargetTweet($tw);
                    // DB保存
                    $tw->save();
                }                
            }
        } catch (\Exception $e) {
            Log::error($e);
        }

        return $response;
    }
}
