<?php

namespace App\Models;

use PHPUnit\TextUI\XmlConfiguration\Constant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * TweetテーブルのORM用クラス
 * 
 * [索引]
 * 　□ 1. TwitterAPIレスポンスからデータをセット
 */
class Tweet extends Model
{
    use HasFactory;

    //======================================================
    //
    // 1. TwitterAPIレスポンスからデータをセット
    //
    //======================================================

    /**
     * TwitterAPIレスポンスからデータをセット
     * 　・ユーザ名、ユーザの@形式のアカウント名はAPIレスポンスのユーザのオブジェクトデータからセット
     * 　・上記以外はAPIレスポンスのツイートのオブジェクトデータからセット
     * 　・本文は改行を除去してセット
     * 
     * @param mixed $td APIレスポンスのdata内の1件のツイートオブジェクト
     * @param mixed $ud APIレスポンスのinclues->users内の該当ユーザのオブジェクト
     * @return void
     */
    public function setValsFromAPITwObj($td, $ud)
    {        
        try {
            // ツイートID
            $this->id_str_in_twitter = $td->id;
            // ユーザ名
            $this->user_name = $ud->name;
            // ユーザの@形式のアカウント名
            $this->user_screen_name = $ud->username;
            // 本文。改行を除去してセット
            $this->tweet_text = str_replace(array("\r\n", "\r", "\n"), '', $td->text);
            // RT数
            $this->rt_count = (int)$td->public_metrics->retweet_count;
            // クライアント名
            $this->client_name = $td->source;
            // 投稿日時
            $this->posted_date = date($td->created_at);
        } catch (\Exception $e) {
            Log::error($e);
        }            
    }
}
