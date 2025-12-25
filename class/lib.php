<?php
/**
 * Model: C.P.Sub 公告系統
 * Author: Cooltey Feng
 * Lastest Update: 2020/12/19
 */

class Lib
{

	// 過濾文字設定
	public string $set_filter = "0";
	// 去除反斜線
	public string $set_stripslashes = "1";
	// 轉換字元
	// public string $set_htmlspecialchars = "0";

	public function __construct(string $get_filter, string $get_stripslahes)
	{
		$this->set_filter = $get_filter;
		$this->set_stripslashes = $get_stripslahes;
	}

	// gen random string
	public function generateRandomString(int $length = 10): string
	{
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$randomString = '';
		$charLen = strlen($characters);
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[random_int(0, $charLen - 1)];
		}
		return $randomString;
	}

	// 簡易文字過濾
	public function setFilter($get_string, bool $adv = false)
	{
		// 支援陣列或字串，不強制轉型
		if (is_array($get_string)) {
			foreach ($get_string as $k => $v) {
				$get_string[$k] = $this->setFilter($v, $adv);
			}
			return $get_string;
		}

		$returnVal = (string) $get_string;

		if ($this->set_filter == "1") {
			$returnVal = strip_tags($returnVal);
		}

		// [Fix] Removed get_magic_quotes_gpc check (PHP 7.4 removed it)
		// Assuming set_stripslashes property controls this behavior
		if ($this->set_stripslashes == "1") {
			$returnVal = stripslashes($returnVal);
		}


		// if($this->set_htmlspecialchars  == "1"){
		// 	$returnVal = htmlspecialchars($returnVal);
		// }

		if ($adv == true) {
			// xss escape
			$returnVal = htmlspecialchars($returnVal);
			$returnVal = htmlentities($returnVal, ENT_QUOTES, 'UTF-8');
		}

		return $returnVal;
	}

	// 檢查是否有數值
	public function checkVal($get_val): bool
	{

		$returnVal = false;

		// 修正：0 是有效數值，不應被視為空
		if (isset($get_val) && $get_val !== "") {
			$returnVal = true;
		}

		return $returnVal;
	}

	// check data status
	public function checkFileStatus(string $get_val): bool
	{

		$returnVal = false;

		// 確認是否有檔案
		if (is_file($get_val) || is_dir($get_val)) {
			// 確認權限
			$get_premission = substr(decoct(fileperms($get_val)), 2);
			if ($get_premission != "0777") {
				if (file_exists($get_val) && is_writable($get_val)) {
					// 嘗試更改權限，但若失敗不報錯 (部分 server 禁止 chmod)
					// 使用 if checks 取代 @
					chmod($get_val, 0777);
				}
			}
			$returnVal = true;
		}

		return $returnVal;
	}

	// get the path
	public function checkAdminPath(string $page): ?string
	{
		$returnVal = null;

		// default page path
		$admin_page_folder = "admin/";

		// 防止 LFI (Local File Inclusion)
		$page = basename($page);

		// check file exist	
		$target_file = $admin_page_folder . $page . ".php";
		if (is_file($target_file)) {
			$returnVal = $target_file;
		}

		return $returnVal;
	}

	// Success Msg
	public function showSuccessMsg(array $get_msg_array)
	{

		if (count($get_msg_array) > 0) {
			?>
			<div class="alert alert-success">
				<?php
				foreach ($get_msg_array as $showMsg) {
					?>
					<li><?php echo $showMsg; ?></li>
					<?php
				}
				?>
			</div>
			<?php
		}
	}

	// Error Msg
	public function showErrorMsg(array $get_error_array)
	{

		if (count($get_error_array) > 0) {
			?>
			<div class="alert alert-danger">
				<?php
				foreach ($get_error_array as $errorMsg) {
					?>
					<li><?php echo $errorMsg; ?></li>
					<?php
				}
				?>
			</div>
			<?php
		}
	}

	// success dialog
	public function showAlertMsg(string $get_string): string
	{
		$returnVal = $get_string;
		if ($this->checkVal($returnVal)) {
			// 簡單防 XSS
			$safe_string = addslashes($get_string);
			$returnVal = "<script> alert('{$safe_string}'); </script>";
		}

		return $returnVal;
	}

	// success dialog
	public function getRedirect(string $get_string): string
	{
		$returnVal = $get_string;
		if ($this->checkVal($returnVal)) {
			$returnVal = "<script>window.location.href='{$get_string}'</script>";
		}

		return $returnVal;
	}

	// for menu toggle
	public function toggleMenu(string $page, string $section)
	{
		if (preg_match("/^{$section}/", $page)) {
			echo "class=\"active\"";
		}
	}

	// general ip getter
	public function getIp(): string
	{
		$ipaddress = '';
		// 注意: HTTP_CLIENT_IP 和 HTTP_X_FORWARDED_FOR 容易被偽造
		if (getenv('HTTP_CLIENT_IP'))
			$ipaddress = getenv('HTTP_CLIENT_IP');
		else if (getenv('HTTP_X_FORWARDED_FOR'))
			$ipaddress = getenv('HTTP_X_FORWARDED_FOR');
		else if (getenv('HTTP_X_FORWARDED'))
			$ipaddress = getenv('HTTP_X_FORWARDED');
		else if (getenv('HTTP_FORWARDED_FOR'))
			$ipaddress = getenv('HTTP_FORWARDED_FOR');
		else if (getenv('HTTP_FORWARDED'))
			$ipaddress = getenv('HTTP_FORWARDED');
		else if (getenv('REMOTE_ADDR'))
			$ipaddress = getenv('REMOTE_ADDR');
		else
			$ipaddress = 'UNKNOWN';
		return (string) $ipaddress;
	}

	// file upload
	public function fileUpload(array $getFile, string $input_name, string $config_upload_folder): array
	{
		// [Fix] Ensure file_name array is initialized to prevent uninitialized key access
		$returnVal = array("status" => false, "file" => array(), "file_name" => array());

		// 檢查 isset 防止未定義索引錯誤
		if (!isset($getFile[$input_name]['name'])) {
			return $returnVal;
		}

		$getTotalUploadFiles = count($getFile[$input_name]['name']);

		// execute upload process
		if ($getTotalUploadFiles > 0) {
			// upload loop
			for ($i = 0; $i < $getTotalUploadFiles; $i++) {
				if (isset($getFile[$input_name]['name'][$i]) && $this->checkVal($getFile[$input_name]['name'][$i])) {
					// check file val
					if (isset($getFile[$input_name]['error'][$i]) && $getFile[$input_name]['error'][$i] == 0) {
						$folder = $config_upload_folder;

						$file_tmp_name = $getFile[$input_name]['tmp_name'][$i];
						// 這裡維持 setFilter, 但需注意原始檔名可能包含路徑
						$file_display_name = $this->setFilter($getFile[$input_name]['name'][$i]);

						// Security Fix: 使用 basename() 防止路徑遍歷
						$file_display_name = basename($file_display_name);

						$get_file_name_array = explode(".", $file_display_name);
						$get_file_subname = end($get_file_name_array); // 使用 end() 取得最後一個副檔名

						// Security Fix: 使用 random_int 替代 rand
						$file_name = date("YmdHis") . random_int(0, 999) . "." . $get_file_subname;

						// 確保目錄存在且路徑安全
						$target_path = $folder . $file_name;

						if (
							!file_exists($target_path)
							&& strtolower($get_file_subname) != "php"
							&& strtolower($get_file_subname) != "asp"
							&& strtolower($get_file_subname) != "exe"
						) {
							try {
								// 檢查目錄是否可寫入
								if (is_dir($folder) && is_writable($folder)) {
									if (move_uploaded_file($file_tmp_name, $target_path)) {
										$returnVal['status'] = true;
										$returnVal['file'][] = $file_name;  // 優化語法					
										$returnVal['file_name'][] = $file_display_name; // 優化語法
									}
								}
							} catch (Exception $e) {
							}
						}
					}
				}
			}
		}

		return $returnVal;
	}


}