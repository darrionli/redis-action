<?php
require "../vendor/autoload.php";

/**
 * 根据文章的发布时间和文章获得的投票数量计算出一个评分，然后按照这个评分决定如何展示文章
 */
$conn = myRedis::getInstance();
$week = 86400 * 7;
$voted_basic = 432;

/**
 * 文章发布
 * 以hash类型将文章详情保存，另外需要按照发布时间和投票分数分别存到zet
 */
$createArticle = function($uid, $title) use ($conn, $voted_basic, $week){
	$article_id = $conn->incr('article:');
	$key = 'article:'.$article_id;
	$time = time();

	$data = [
		'uid' => $uid,
		'title' => $title,
		'time' => $time,
		'article_id' => $article_id,
		'voted' => 0
	];

	$voted_key = 'voted:' . $article_id;
	$conn->sadd($voted_key, $uid);		// 将当前用户添加到已投票集合里
	$conn->expire($voted_key, $week);	// 设置过期时间

	$conn->zadd('time', $time, $key);
	$conn->zadd('score', $time + $voted_basic, $key);
	$conn->hmset($key, $data);			// 保存文章详情
};

// $createArticle(1002, 'redis测试2');

/**
 * 获取文章详情
 */
$getArticle = function($article_id) use ($conn){
	$key = 'article:'.$article_id;
	$detail = $conn->hgetall($key);
	return $detail;
};

/**
 * 发起投票
 */
$sendVoted = function($user, $article) use ($conn, $week, $voted_basic){
	// 计算文章投票的截止时间
	$diff = time() - $week;
	if($conn->zscore('score', $article) < $diff){
		return false;
	}

	$article_id = substr($article, strpos($article, ':')+1);
	$voted = 'voted:' . $article_id;
	if($conn->sadd($voted, $user)){
		$conn->zincrby('score', $article, (float)$voted_basic);
		$conn->hincryby($article, 'voted', 1);
	}

};

// $sendVoted(1010, 'article:1');

