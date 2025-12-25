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
		<h1>感謝您的使用</h1>
		<?php
		// [Fix] Check if aboutData is valid object
		if (is_object($aboutData)) {
			echo "<hr>";
			echo "<p>" . htmlspecialchars($aboutData->msg) . "</p>";
			echo "<hr>";
			echo "<p class=\"small_font\">最新版本：" . htmlspecialchars($aboutData->latest_version);
			if ($aboutData->latest_version != $config_current_version) {
				// [Fix] Fixed broken string concatenation and quoting
				echo "<a href=\"" . htmlspecialchars($aboutData->project_link) . "\" target=\"_blank\" class=\"label label-warning margin_box\">前往下載最新版本</a>";
			}
			echo "<p class=\"small_font\">更新日期：" . htmlspecialchars($aboutData->latest_update) . "</p>";
			echo "<p class=\"small_font\">作者：" . htmlspecialchars($aboutData->author) . "</p>";
			$count = 0;
			if (isset($aboutData->links) && is_object($aboutData->links)) {
				foreach ($aboutData->links as $linkKey => $linkVal) {
					// [Fix] Ensure index exists and avoid out of bounds
					$color = $label_color[$count] ?? 'default';
					echo "<a class=\"btn btn-" . $color . " btn-lg margin_box small_font\" href=\"" . htmlspecialchars($linkVal) . "\" target=\"_blank\">" . htmlspecialchars($linkKey) . "</a>";
					$count++;
					if ($count >= count($label_color))
						$count = 0; // wrap around color
				}
			}
		} else {
			echo "<p>無法載入遠端資訊，請稍後再試。</p>";
		}
		?>
	</div>
</div>