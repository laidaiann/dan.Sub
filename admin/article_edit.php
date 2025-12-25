<?php
/**
 * Model: C.P.Sub 公告系統
 * Author: Cooltey Feng
 * Lastest Update: 2025/12/22
 */

// [Fix] Import global variables
global $config_upload_folder, $config_article_file_path, $config_ip_file_path, $getLib, $getCSRF;

// 確保必要的設定與物件存在
if (!isset($config_upload_folder) || !isset($config_article_file_path) || !isset($config_ip_file_path) || !isset($getLib)) {
	exit("System Error: Configuration missing.");
}

// set Article
$getArticle = new Article($config_upload_folder, $config_article_file_path, $config_ip_file_path, $getLib);

// 安全處理 ID
// [Fix] Defensive check for ID
$getId = isset($_GET['id']) ? intval($getLib->setFilter($_GET['id'])) : 0;

// Initial variables
$success_msg_array = array();
$error_msg_array = array();

// 處理更新請求
try {
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$getData = $getLib->setFilter($_POST);
		$getFile = $_FILES;

		// check CSRF
		if (isset($getCSRF)) {
			// [Fix] Pass $_POST safely
			$getCSRF->checkToken($_POST);
		}

		// set edit function
		$getResult = $getArticle->editArticle($getId, $getData, $getFile);

		if ($getResult['status'] == true) {
			$success_msg_array = $getResult['msg'];
		} else {
			$error_msg_array = $getResult['msg'];
		}
	}
} catch (Exception $e) {
	$error_msg_array[] = "系統錯誤: " . $e->getMessage();
}

// get single article
$getArticleResult = $getArticle->getArticle($getId);

if ($getArticleResult['status'] == true) {
	$getArticleData = $getArticleResult['data'];
	// get colum values
	// [Fix] Use defensive coding, accessing keys with ??
	$article_title = $getLib->setFilter($getArticleData['title'] ?? '');
	$article_author = $getLib->setFilter($getArticleData['author'] ?? '');
	$article_date = $getLib->setFilter($getArticleData['date'] ?? date('Y-m-d'));
	$article_content = $getLib->setFilter($getArticleData['content'] ?? ''); // 內容通常含有 HTML，setFilter 視設定而定，此處保留原意

	// 安全處理檔案列表
	$article_files = explode(",", $getArticleData['files'] ?? '');
	$article_files_name = explode(",", $getArticleData['files_name'] ?? '');

	if (($getArticleData['top'] ?? '0') == "1") {
		$article_top = " checked";
	} else {
		$article_top = "";
	}
} else {
	$return_page = "./manage.php?p=article_list";
	echo $getLib->showAlertMsg("參數錯誤");
	echo $getLib->getRedirect($return_page);
	exit;
}


?>
<?php $getLib->showErrorMsg($error_msg_array); ?>
<?php $getLib->showSuccessMsg($success_msg_array); ?>

<!--CK Editor -->
<script src="js/ckeditor/ckeditor.js"></script>
<script src="js/ckeditor/adapters/jquery.js"></script>
<!--CK Editor -->
<form class="form-horizontal" role="form" action="manage.php?p=article_edit&id=<?= $getId; ?>" method="post"
	enctype="multipart/form-data">
	<div class="form-group">
		<label for="article_title" class="col-lg-2 control-label">標題</label>
		<div class="col-lg-10">
			<!-- [Fix] htmlspecialchars -->
			<input type="text" class="form-control" id="article_title" name="article_title" placeholder="標題"
				value="<?= htmlspecialchars($article_title, ENT_QUOTES, 'UTF-8'); ?>">
		</div>
	</div>
	<div class="form-group">
		<label for="article_author" class="col-lg-2 control-label">發佈單位</label>
		<div class="col-lg-10">
			<!-- [Fix] htmlspecialchars -->
			<input type="text" class="form-control" id="article_author" name="article_author" placeholder="發佈單位"
				value="<?= htmlspecialchars($article_author, ENT_QUOTES, 'UTF-8'); ?>">
		</div>
	</div>
	<div class="form-group">
		<div class="col-lg-offset-2 col-lg-10">
			<div class="checkbox">
				<label>
					<input type="checkbox" name="article_top" value="1" <?= $article_top; ?>> 置頂
				</label>
			</div>
		</div>
	</div>
	<div class="form-group">
		<label for="article_file" class="col-lg-2 control-label">上傳附件</label>
		<div class="col-lg-10">
			<input type="file" name="article_file[]" id="article_file">
			<p class="help-block cursor-pointer" id="add_more_file"><span class="glyphicon glyphicon-plus"></span>添加更多附件
			</p>
			<?php
			if (count($article_files) > 0 && $getLib->checkVal($article_files[0])) {
				?>
				<div class="list-group">
					<a class="list-group-item active">
						檔案列表
					</a>
					<?php
					// show files
					$count = 0;
					foreach ($article_files as $fileData) {
						?>
						<div class="list-group-item">
							<!-- 確保輸出路徑安全 -->
							<a href="<?= $config_upload_folder . $getLib->setFilter($fileData); ?>"
								target="_blank"><?= htmlspecialchars($getLib->setFilter($article_files_name[$count] ?? ''), ENT_QUOTES, 'UTF-8'); ?></a>
							<input type="hidden"
								value="<?= htmlspecialchars($getLib->setFilter($fileData), ENT_QUOTES, 'UTF-8'); ?>"
								name="article_file_remain[]">
							<input type="hidden"
								value="<?= htmlspecialchars($getLib->setFilter($article_files_name[$count] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
								name="article_file_name_remain[]">
							<label class="pull-right">刪除檔案
								<input type="checkbox" value="<?= $count; ?>" name="article_file_del[]">
							</label>
						</div>

						<?php
						$count++;
					}
					?>
				</div>
				<?php
			}
			?>
		</div>
	</div>
	<div class="form-group">
		<label for="article_file" class="col-lg-2 control-label">發佈時間</label>
		<div class="col-lg-10">
			<!-- [Fix] htmlspecialchars -->
			<input type="text" name="article_date" value="<?= htmlspecialchars($article_date, ENT_QUOTES, 'UTF-8'); ?>"
				class="form-control auto_selectbar">
			(年-月-日 時:分:秒)
		</div>
	</div>
	<div class="form-group">
		<label for="article_author" class="col-lg-2 control-label">文章內容</label>
		<div class="col-lg-10">
			<!-- [Fix] htmlspecialchars for textarea content -->
			<textarea name="article_content"
				class="ckeditor"><?= htmlspecialchars($article_content, ENT_QUOTES, 'UTF-8'); ?></textarea>
		</div>
	</div>
	<div class="form-group">
		<div class="col-lg-offset-2 col-lg-10">
			<button type="submit" class="btn btn-default" name="send" value="send">更新</button>
		</div>
	</div>
	<?php if (isset($getCSRF)) {
		echo $getCSRF->genTokenField();
	} ?>
</form>