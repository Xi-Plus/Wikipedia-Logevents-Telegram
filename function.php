<?php

function parsewikitext($text) {
	$text = html_entity_decode($text);
	$text = htmlspecialchars($text);
	$text = preg_replace("/\[\[([^\|\]]+?)]]/", '<a href="https://zh.wikipedia.org/wiki/$1">$1</a>', $text);
	$text = preg_replace("/\[\[([^\|\]]+?)\|([^]]+?)]]/", '<a href="https://zh.wikipedia.org/wiki/$1">$2</a>', $text);
	return $text;
}

function blockflags(&$item, $key) {
	$f = [
		'anononly' => '僅限匿名使用者',
		'noautoblock' => '停用自動封鎖',
		'nocreate' => '停用帳號建立',
		'noemail' => '停用電子郵件',
		'nousertalk' => '無法編輯自己的對話頁面'
	];
	if (isset($f[$item])) {
		$item = $f[$item];
	}
}

function protectparams($text) {
	return str_replace(
		["[edit=", "[move=", "[create=", "=autoconfirmed]", "=sysop]"],
		["[編輯=", "[移動=", "[建立=", "=僅允許已自動確認的使用者]", "=僅限管理員]"], $text);
}

function rightparams($group) {
	$name = [
		'accountcreator' => '帳號建立員',
		'autoreviewer' => '巡查豁免者',
		'bot' => '機器人',
		'bureaucrat' => '行政員',
		'confirmed' => '已確認的使用者',
		'eventparticipant' => '活動參與者',
		'filemover' => '檔案移動員',
		'flood' => '機器使用者',
		'interface-admin' => '介面管理員',
		'ipblock-exempt' => 'IP 封鎖例外',
		'massmessage-sender' => '大量訊息傳送者',
		'oversight' => '監督員',
		'patroller' => '巡查員',
		'rollbacker' => '回退員',
		'sysop' => '管理員'
	];
	return $name[$group];
}

function parserights($groups, $metadata) {
	$res = [];
	foreach ($groups as $key => $_) {
		$expiry = $metadata[$key]["expiry"];
		if ($expiry == "infinity") {
			$res []= rightparams($groups[$key]);
		} else {
			$res []= sprintf("%s（%s/%s/%s %s:%s）",
				rightparams($groups[$key]),
				substr($expiry, 0, 4),
				substr($expiry, 5, 2),
				substr($expiry, 8, 2),
				substr($expiry, 11, 2),
				substr($expiry, 14, 2)
			);
		}
	}
	return implode("、", $res);
}
