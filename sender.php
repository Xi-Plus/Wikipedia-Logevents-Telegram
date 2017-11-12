<?php
require_once(__DIR__.'/config/config.php');
date_default_timezone_set('UTC');
require(__DIR__.'/curl.php');
require(__DIR__.'/login.php');
require(__DIR__.'/function.php');
if (!in_array(PHP_SAPI, $C["allowsapi"])) {
	exit("No permission");
}

echo "The time now is ".date("Y-m-d H:i:s")." (UTC)\n";

$datafile = __DIR__."/data/setting.json";
$data = @file_get_contents($datafile);
if ($data === false) {
	$data = $C['defaultdata'];
} else if (($data = json_decode($data, true)) === null) {
	$data = $C['defaultdata'];
}
$data += $C['defaultdata'];

login();

$res = cURL($C["wikiapi"]."?".http_build_query(array(
	"action" => "query",
	"format" => "json",
	"list" => "logevents",
	"leend" => $data["lasttime"],
	// "ledir" => "newer",
	"lelimit" => "max"
)));
if ($res === false) {
	exit("fetch page fail\n");
}
$res = json_decode($res, true);
echo count($res["query"]["logevents"])."\n";
if (count($res["query"]["logevents"])) {
	foreach (array_reverse($res["query"]["logevents"]) as $log) {
		if ($log["logid"] <= $data["lastid"]) {
			continue;
		}
		if (count($C['allowlogtype']) > 0 && !in_array($log["type"], $C['allowlogtype'])) {
			continue;
		}
		$time = strtotime($log["timestamp"])+3600*8;
		$message = "";
		switch ($log["type"]) {
			case 'block':
				$message .= date("Y年m月d日", $time).' ('.$C["day"][date("w", $time)].') '.date("H:i", $time).' ';
				$message .= '<a href="https://zh.wikipedia.org/wiki/Special:Contributions/'.rawurlencode($log["user"]).'">'.$log["user"].'</a> (<a href="https://zh.wikipedia.org/wiki/User_talk:'.rawurlencode($log["user"]).'">對話</a>) ';
				$title = substr($log["title"], 5);
				$message .= '已封鎖 <a href="https://zh.wikipedia.org/wiki/Special:Contributions/'.rawurlencode($title).'">'.$title.'</a> (<a href="https://zh.wikipedia.org/wiki/User_talk:'.rawurlencode($title).'">對話</a>) ';
				$message .= '期限為 '.$log["params"]["duration"].' ';
				if (count($log["params"]["flags"])) {
					$message .= '('.implode("、", array_walk($log["params"]["flags"]), 'parsename', $C['blockflags']).') ';
				}
				$message .= '('.parsewikitext($log["comment"]).')';
				break;
			
			case 'protect':
				$message .= date("Y年m月d日", $time).' ('.$C["day"][date("w", $time)].') '.date("H:i", $time).' ';
				$message .= '<a href="https://zh.wikipedia.org/wiki/Special:Contributions/'.rawurlencode($log["user"]).'">'.$log["user"].'</a> (<a href="https://zh.wikipedia.org/wiki/User_talk:'.rawurlencode($log["user"]).'">對話</a>) ';
				$message .= '已保護 <a href="https://zh.wikipedia.org/wiki/'.rawurlencode($log["title"]).'">'.$log["title"].'</a> ';
				$message .= protectparams($log["params"]["description"]).' ';
				$message .= '('.parsewikitext($log["comment"]).')';
				break;
			
			case 'delete':
				$message .= date("Y年m月d日", $time).' ('.$C["day"][date("w", $time)].') '.date("H:i", $time).' ';
				$message .= '<a href="https://zh.wikipedia.org/wiki/Special:Contributions/'.rawurlencode($log["user"]).'">'.$log["user"].'</a> (<a href="https://zh.wikipedia.org/wiki/User_talk:'.rawurlencode($log["user"]).'">對話</a>) ';
				$message .= '刪除頁面 <a href="https://zh.wikipedia.org/wiki/'.rawurlencode($log["title"]).'">'.$log["title"].'</a> ';
				$message .= '('.parsewikitext($log["comment"]).')';
				break;
			
			case 'rights':
				$message .= date("Y年m月d日", $time).' ('.$C["day"][date("w", $time)].') '.date("H:i", $time).' ';
				$message .= '<a href="https://zh.wikipedia.org/wiki/Special:Contributions/'.rawurlencode($log["user"]).'">'.$log["user"].'</a> (<a href="https://zh.wikipedia.org/wiki/User_talk:'.rawurlencode($log["user"]).'">對話</a>) ';
				$title = substr($log["title"], 5);
				$message .= '已更改 <a href="https://zh.wikipedia.org/wiki/Special:Contributions/'.rawurlencode($title).'">'.$title.'</a> (<a href="https://zh.wikipedia.org/wiki/User_talk:'.rawurlencode($title).'">對話</a>) 的群組成員資格由 ';
				if (count($log["params"]["oldgroups"])) {
					$message .= implode("、", $log["params"]["oldgroups"]).' ';
				} else {
					$message .= '無 ';
				}
				$message .= '成為 ';
				if (count($log["params"]["newgroups"])) {
					$message .= implode("、", $log["params"]["newgroups"]).' ';
				} else {
					$message .= '無 ';
				}
				$message .= '('.parsewikitext($log["comment"]).')';
				break;
			
			default:
				# code...
				break;
		}
		if ($message !== "") {
			$commend = 'curl https://api.telegram.org/bot'.$C['token'].'/sendMessage -d "chat_id='.$C['chat_id'].'&parse_mode=HTML&disable_web_page_preview=1&text='.urlencode($message).'"';
			system($commend);
			echo "\n";
		} else {
			// var_dump($log);
		}
	}
	$data["lasttime"] = $res["query"]["logevents"][0]["timestamp"];
	$data["lastid"] = $res["query"]["logevents"][0]["logid"];
}

file_put_contents($datafile, json_encode($data));
