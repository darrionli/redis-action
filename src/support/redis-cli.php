<?php
class myRedis
{
	private static $redis = null;

	private function __construct()
	{
		$client = new Predis\Client([
		    'scheme' => 'tcp',
		    'host'   => '192.168.154.158',
		    'port'   => 6379,
		]);
		self::$redis = $client;
	}

	/**
	 * 返回一个redis实例
	 */
	public static function getInstance()
	{
		if(self::$redis==null){
			new self();
		}
		return self::$redis;
	}
}
