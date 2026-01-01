<?php
/**
 * Model: C.P.Sub 公告系統
 * Author: Cooltey Feng
 * Lastest Update: 2014/6/9
 */

// [Fix] Globalize variables
global $config_about_author, $config_current_version;

// get json information
// [Fix] Defensive JSON check
$json_content = @file_get_contents($config_about_author);
$aboutData = ($json_content) ? json_decode($json_content) : null;
$label_color = array("default", "primary", "info", "danger", "success");
?>
<div class="jumbotron">
	<div class="container">
		<h1>龍潭國中公告系統</h1>
		<hr>
		<p>這是龍潭國中的公告管理系統。</p>
		<p>源自 C.P.Sub V5.3 公告系統</p>
		<p>如有任何問題，請聯繫資訊組。</p>
		<p>分機 214 @ 龍潭國中</p>
		<hr>
		<p class="small_font">版本：v1.0</p>
		<p class="small_font">維護單位：資訊組</p>
	</div>
</div>