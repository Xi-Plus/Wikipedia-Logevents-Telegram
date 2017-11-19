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

function rightparams(&$item, $key) {
	$name = [
		'ipblock-exempt' => 'IP 封鎖例外',
		'rollbacker' => '回退員',
		'patroller' => '巡查員',
		'autoreviewer' => '巡查豁免者',
		'flood' => '機器使用者',
		'massmessage' => '大量訊息傳送者',
		'confirmed' => '已確認的使用者'
	];
	if (isset($name[$item])) {
		$item = $name[$item];
	}
}
