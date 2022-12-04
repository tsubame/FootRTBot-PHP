<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tweets', function (Blueprint $table) {
            // ID            
            $table->increments('id');
            // Twitter内の数値形式のID
            $table->string('id_str_in_twitter');
            // Twitterアカウント名　例：田中
            $table->string('user_name');
            // Twitter内のユーザの文字列形式のID 	例：@test
            $table->string('user_screen_name');            
            // 本文
            $table->string('tweet_text');  
            // RT数
            $table->integer('rt_count');
            // クライアント名
            $table->string('client_name');  
            // 投稿日時
            $table->dateTime('posted_date');
            // DB登録、更新日時
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tweets');
    }
};
