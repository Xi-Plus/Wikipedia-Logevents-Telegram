<?php

function parsewikitext($text) {
	$text = preg_replace("/\[\[([^\|\]]+?)]]/", '<a href="https://zh.wikipedia.org/wiki/$1">$1</a>', $text);
	$text = preg_replace("/\[\[([^\|\]]+?)\|([^]]+?)]]/", '<a href="https://zh.wikipedia.org/wiki/$1">$2</a>', $text);
	return $text;
}

function blockflags(&$item, $key) {
	$f = [
		'anononly' => '僅限匿名使用者',
		'nocreate' => '停用帳號建立',
		'noautoblock' => '停用自動封鎖',
		'nousertalk' => '無法編輯自己的對話頁面'
	];
	if (isset($f[$item])) {
		$item = $f[$item];
	}
}

function protectparams($text) {
	return str_replace(
		["[edit=", "[move=", "[create=", "=autoconfirmed]", "=sysop]"],
		["[編輯=", "[移動=", "[建立=", "=僅允許已自動確認的使用者]", "=僅限管理員]"], $item);
}
