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
	"leprop" => "ids|title|type|user|timestamp|comment|details",
	"leend" => $data["lasttime"],
	"lelimit" => $C["limit"]
)));
if ($res === false) {
	exit("fetch page fail\n");
}
$res = json_decode($res, true);
echo count($res["query"]["logevents"])."\n";
if (count($res["query"]["logevents"])) {
	file_put_contents($datafile, json_encode([
		"lasttime" => $res["query"]["logevents"][0]["timestamp"],
		"lastid" => $res["query"]["logevents"][0]["logid"]
	]));

	foreach (array_reverse($res["query"]["logevents"]) as $log) {
		if ($log["logid"] <= $data["lastid"]) {
			continue;
		}
		if (count($C['allowlogtype']) > 0 && !in_array($log["type"], $C['allowlogtype'])) {
			continue;
		}
		if (in_array($log['user'], $C['ignoreuser']) && $log["type"] != 'rights') {
			echo "ignore user ".$log['user']."\n";
			continue;
		}
		$logorig = $log;

		$time = strtotime($log["timestamp"])+3600*8;
		$message = "";
		$pass = false;
		switch ($log["type"]) {
			case 'block':
				$message .= "#封禁 ";
				$message .= date("Y年m月d日", $time).' ('.$C["day"][date("w", $time)].') '.date("H:i", $time).' ';
				$message .= '<a href="https://zh.wikipedia.org/wiki/Special:Contributions/'.rawurlencode($log["user"]).'">'.$log["user"].'</a> (<a href="https://zh.wikipedia.org/wiki/User_talk:'.rawurlencode($log["user"]).'">對話</a>) ';
				$title = substr($log["title"], 5);
				switch ($log["action"]) {
					case 'block':
						$message .= '已封鎖 <a href="https://zh.wikipedia.org/wiki/Special:Contributions/'.rawurlencode($title).'">'.$title.'</a> (<a href="https://zh.wikipedia.org/wiki/User_talk:'.rawurlencode($title).'">對話</a>) ';
						$message .= '期限為 '.$log["params"]["duration"].' ';
						break;
					case 'reblock':
						$message .= '已變更 <a href="https://zh.wikipedia.org/wiki/Special:Contributions/'.rawurlencode($title).'">'.$title.'</a> (<a href="https://zh.wikipedia.org/wiki/User_talk:'.rawurlencode($title).'">對話</a>) ';
						$message .= '的封鎖設定期限為 '.$log["params"]["duration"].' ';
						break;
					case 'unblock':
						$message .= '已解除封鎖 <a href="https://zh.wikipedia.org/wiki/Special:Contributions/'.rawurlencode($title).'">'.$title.'</a> (<a href="https://zh.wikipedia.org/wiki/User_talk:'.rawurlencode($title).'">對話</a>) ';
						break;
					default:
						$pass = true;
						break;
				}
				if (count($log["params"]["flags"])) {
					array_walk($log["params"]["flags"], 'blockflags');
					$message .= '('.implode("、", $log["params"]["flags"]).') ';
				}
				$message .= '('.parsewikitext($log["comment"]).')';
				break;
			
			case 'protect':
				$message .= "#保護 ";
				$message .= date("Y年m月d日", $time).' ('.$C["day"][date("w", $time)].') '.date("H:i", $time).' ';
				$message .= '<a href="https://zh.wikipedia.org/wiki/Special:Contributions/'.rawurlencode($log["user"]).'">'.$log["user"].'</a> (<a href="https://zh.wikipedia.org/wiki/User_talk:'.rawurlencode($log["user"]).'">對話</a>) ';
				switch ($log["action"]) {
					case 'protect':
						$message .= '已保護 <a href="https://zh.wikipedia.org/wiki/'.rawurlencode($log["title"]).'">'.$log["title"].'</a> ';
						$message .= protectparams($log["params"]["description"]).' ';
						break;
					case 'unprotect':
						$message .= '已移除 <a href="https://zh.wikipedia.org/wiki/'.rawurlencode($log["title"]).'">'.$log["title"].'</a> ';
						$message .= '的保護 ';
						break;
					case 'modify':
						$message .= '已更改 <a href="https://zh.wikipedia.org/wiki/'.rawurlencode($log["title"]).'">'.$log["title"].'</a> ';
						$message .= '的保護層級 ';
						$message .= protectparams($log["params"]["description"]).' ';
						break;
					default:
						$pass = true;
						break;
				}
				$message .= '('.parsewikitext($log["comment"]).')';
				break;
			
			case 'delete':
				$message .= "#刪除 ";
				$message .= date("Y年m月d日", $time).' ('.$C["day"][date("w", $time)].') '.date("H:i", $time).' ';
				$message .= '<a href="https://zh.wikipedia.org/wiki/Special:Contributions/'.rawurlencode($log["user"]).'">'.$log["user"].'</a> (<a href="https://zh.wikipedia.org/wiki/User_talk:'.rawurlencode($log["user"]).'">對話</a>) ';
				switch ($log["action"]) {
					case 'delete':
						$message .= '刪除頁面 ';
						break;
					case 'restore':
						$message .= '還原頁面 ';
						break;
					default:
						$pass = true;
						break;
				}
				$message .= '<a href="https://zh.wikipedia.org/wiki/'.rawurlencode($log["title"]).'">'.$log["title"].'</a> ';
				$message .= '('.parsewikitext($log["comment"]).')';
				break;
			
			case 'rights':
				$message .= "#權限 ";
				$message .= date("Y年m月d日", $time).' ('.$C["day"][date("w", $time)].') '.date("H:i", $time).' ';
				$message .= '<a href="https://zh.wikipedia.org/wiki/Special:Contributions/'.rawurlencode($log["user"]).'">'.$log["user"].'</a> (<a href="https://zh.wikipedia.org/wiki/User_talk:'.rawurlencode($log["user"]).'">對話</a>) ';
				$title = substr($log["title"], 5);
				$message .= '已更改 <a href="https://zh.wikipedia.org/wiki/Special:Contributions/'.rawurlencode($title).'">'.$title.'</a> (<a href="https://zh.wikipedia.org/wiki/User_talk:'.rawurlencode($title).'">對話</a>) 的群組成員資格由 ';
				if (count($log["params"]["oldgroups"])) {
					$message .= parserights($log["params"]["oldgroups"], $log["params"]["oldmetadata"]);
				} else {
					$message .= '無 ';
				}
				$message .= '成為 ';
				if (count($log["params"]["newgroups"])) {
					$message .= parserights($log["params"]["newgroups"], $log["params"]["newmetadata"]);
				} else {
					$message .= '無 ';
				}
				$message .= '('.parsewikitext($log["comment"]).')';
				break;
			
			case 'newusers':
				$message .= "#新用戶 ";
				$message .= date("Y年m月d日", $time).' ('.$C["day"][date("w", $time)].') '.date("H:i", $time).' ';
				$newuser = substr($log["title"], 5);
				$message .= '<a href="https://zh.wikipedia.org/wiki/Special:Contributions/'.rawurlencode($newuser).'">'.$newuser.'</a> (<a href="https://zh.wikipedia.org/wiki/User_talk:'.rawurlencode($newuser).'">對話</a>) ';
				if (isset($C['usernamecheckapi']) && $C['usernamecheckapi'] !== "") {
					$check = file_get_contents($C['usernamecheckapi'].rawurlencode($newuser));
					if ($check !== false) {
						$check = json_decode($check, true);
						foreach ($check["levenshtein"] as $value) {
							$message .= "\n編輯距離=".$value["value"].": ".$value["user"];
						}
						foreach ($check["similar_text"] as $value) {
							$message .= "\n相同文字=".$value["value"].": ".$value["user"];
						}
						foreach ($check["similar_text_precent"] as $value) {
							$message .= "\n相似比例=".round($value["value"], 0)."%: ".$value["user"];
						}
					}
				}
				break;

			default:
				$pass = true;
				break;
		}
		if (!$pass && $message !== "") {
			foreach ($C['chat_id'] as $chat_id => $check) {
				if ($check($logorig)) {
					$commend = 'curl https://api.telegram.org/bot'.$C['token'].'/sendMessage -d "chat_id='.$chat_id.'&parse_mode=HTML&disable_web_page_preview=1&text='.urlencode($message).'" > /dev/null 2>&1';
					system($commend);
				}
			}
		} else {
			// var_dump($log);
		}
	}
}
