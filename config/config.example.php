<?php

$C['token'] = 'token';
$C['chat_id'] = [
	'chat_id_1' => function($log) {
		return true;
	},
	'chat_id_2' => function($log) {
		if ($log['type'] == 'block') {
			return true;
		} else {
			return false;
		}
	} 
];

$C['allowlogtype'] = [];
$C['limit'] = 50;
$C['ignoreuser'] = ['Jimmy-abot'];
$C['usernamecheckapi'] = '';

$C["wikiapi"] = "https://zh.wikipedia.org/w/api.php";
$C["user"] = "";
$C["pass"] = "";
$C["cookiefile"] = __DIR__."/../data/cookie.txt";
$C["User-Agent"] = "LogEventsBot";
$C["allowsapi"] = array("cli");

$C["day"] = ["日", "一", "二", "三", "四", "五", "六"];

$C['defaultdata'] = array(
	"lasttime" => date("Y-m-d", time()-60*10)."T".date("H:i:s", time()-60*10)."Z",
	"lastid" => 0
);
