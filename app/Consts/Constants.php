<?php

namespace App\Consts;
use App\Consts\AppConfig;

/**
 * 定数定義用クラス
 * 　・複数アカウントで使い回せる値を定義
 * 　・アカウント固有の値はAppConfigに記載
 */
class Constants 
{
    //======================================================
    // リツイート設定
    //======================================================

    // この時間(h)より以前のツイートは無視
    const SKIP_PAST_HOUR = 24;

    // TLから1度に取得するツイートの数
    const TWEET_GET_COUNT_FROM_TL = 200;

    // 検索時、最新のツイートを検索するか　recency = 最新のツイートを検索, relevancy = 関連性の高いツイートを検索
    const SEARCH_TWEET_BY_RECENCY_OR_RELEVANT = 'relevancy';

    // 検索時、何件のツイートを検索するか　MAX:100
    const SEARCH_COUNT = 100;

    // 他の人のツイートをRTしているツイートに含まれるキーワード
    const RT_TWEET_KEYWORD = 'RT @';

    // この数以上のRT数でリツイート
    const RETWEET_LEAST_RT = AppConfig::RETWEET_LEAST_RT;

    // タイムライン上でRTされたツイートをRT対象外にするか
    const SKIP_TIMELINE_RT_TWEET = AppConfig::SKIP_TIMELINE_RT_TWEET;


    //======================================================
    // 検索キーワード
    //======================================================

    // 検索キーワード　このキーワードで検索し、RT数の多いツイートをRT
    const SEARCH_TARGET_KEYWORDS = AppConfig::SEARCH_TARGET_KEYWORDS;

    // RT時のNGワード　このキーワードを含むツイートはRTしない
    const RT_NG_KEYWORDS = AppConfig::RT_NG_KEYWORDS;

    // 検索時にキーワードの末尾につける文字列。日本語、RT以外、メンション以外をセット
    const SEARCH_KEYWORD_POSTFIX = ' lang:ja -is:retweet -has:mentions';


    //======================================================
    // トレンド
    //======================================================

    // 日本のWOEID　トレンドで使用
    const JAPAN_WOEID = 23424856;


    //======================================================
    // Twitterアカウント
    //======================================================

    // API_KEY
    const API_KEY = AppConfig::API_KEY;

    // API Secret
    const API_SECRET = AppConfig::API_SECRET;

    // AccessToken Key
    const ACCESS_TOKEN_KEY = AppConfig::ACCESS_TOKEN_KEY;
    // AccessToken Secret
    const ACCESS_TOKEN_SECRET = AppConfig::ACCESS_TOKEN_SECRET;
}
