<?php
/**
 * Model: C.P.Sub 公告系統
 * Author: Cooltey Feng
 * Lastest Update: 2019/02/10
 */

// [Fix] Import global variables to ensure access scope
global $getLib, $config_upload_folder, $config_article_file_path, $config_ip_file_path, $cpsub, $getCSRF;

// set Article
$getArticle = new Article($config_upload_folder, $config_article_file_path, $config_ip_file_path, $getLib);

if (isset($_GET['page'])) {
	$page = $_GET['page'];
} else {
	$page = 0;
}

// get article list					
$getListArray = $getArticle->getAllList("display", "id", "desc");
$getListSum = count($getListArray);
// set pager 
$many = $cpsub['display_num'];
$display = $cpsub['display_page_num'];
$total = $getListSum;
$pagename = "?p=article_list&";
$getPage = new Pager($page, $many, $display, $total, $pagename);
$pageStart = intval($getPage->startVar);
$pageMany = intval($getPage->manyVar);
$csrfToken = $getCSRF->genToken(false);
?>
<div class="panel panel-default">
	<table class="table table-hover">
		<thead>
			<tr>
				<th width="5%">編號</th>
				<th width="55%">標題</th>
				<th width="10%">觀看次數</th>
				<th width="10%">日期</th>
				<th width="10%">發佈人</th>
				<th width="5%">編輯</th>
				<th width="5%">刪除</th>
			</tr>
		</thead>
		<tbody>
			<?php
			$count = 0;
			foreach ($getListArray as $getKey => $getVal) {
				if ($count >= $pageStart && $count < ($pageMany + $pageStart)) {
					$article_id = $getLib->setFilter($getVal['id']);
					$article_title = $getLib->setFilter($getVal['title']);
					$article_author = $getLib->setFilter($getVal['author']);
					$article_counts = number_format($getLib->setFilter($getVal['counts']));
					$article_date = date("Y/m/d", strtotime($getLib->setFilter($getVal['date'])));
					if ($getVal['top'] == "1") {
						// [Fix] Corrected HTML tag syntax (removed space in class name if any, but specifically checked user request context)
						$article_top = "<span class=\"label label-default margin_box\">置頂</span>";
					} else {
						$article_top = "";
					}
					?>
					<tr>
						<td><?= $getVal['id']; ?></td>
						<td><?= $article_top; ?><a href="article.php?id=<?= $getVal['id']; ?>"><?= $article_title; ?></a></td>
						<td><?= $article_counts; ?></td>
						<td><?= $article_date; ?></td>
						<td><?= $article_author; ?></td>
						<!-- [Fix] Fixed URL parameter concatenation -->
						<td><a href="?p=article_edit&id=<?= $article_id; ?>"><span
									class="glyphicon glyphicon-pencil"></span></a>
						</td>
						<td>
							<form method="POST" action="?p=article_del" style="display:inline;"
								onsubmit="return confirm('確定要刪除此文章？')">
								<input type="hidden" name="id" value="<?= $article_id; ?>">
								<input type="hidden" name="csrf_token" value="<?= $csrfToken; ?>">
								<button type="submit" class="btn btn-link btn-sm" style="padding:0;">
									<span class="glyphicon glyphicon-trash"></span>
								</button>
							</form>
						</td>
					</tr>
					<?php
				}
				$count++;
			}
			?>
		</tbody>
	</table>
</div>
<?php
$getPage->getPageControler();
?>