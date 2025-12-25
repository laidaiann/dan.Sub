<?php
/**
 * Model: C.P.Sub 公告系統
 * Author: Cooltey Feng
 * Lastest Update: 2020/12/19
 */

class Settings
{

	// [Fix] Upgrade property declarations
	public string $filePath = '';
	public array $fileContent = [];

	public function __construct(string $getPath)
	{
		$this->filePath = $getPath;
	}

	public function getFile()
	{
		// Read content
		if (file_exists($this->filePath)) {
			// [Fix] Defensive coding for file read
			$content = file($this->filePath, FILE_IGNORE_NEW_LINES);
			$this->fileContent = ($content !== false) ? $content : [];
		} else {
			$this->fileContent = [];
		}
	}

	public function updateSettings(array $getData, object $getLib)
	{

		// [Fix] Initialize variables
		$msg_array = array();
		$return_status = false;

		try {
			// check the submit btn has been submitted
			if (isset($getData['send']) && $getLib->checkVal($getData['send'])) {

				// set get values
				$system_title = $getLib->setFilter($getData['system_title'] ?? '');
				$system_filter = $getLib->setFilter($getData['system_filter'] ?? '');
				$system_stripslashes = $getLib->setFilter($getData['system_stripslashes'] ?? '');
				$system_display_num = $getLib->setFilter($getData['system_display_number'] ?? '');
				$system_display_page_num = $getLib->setFilter($getData['system_display_page_number'] ?? '');
				$system_csrf_protection = $getLib->setFilter($getData['system_csrf_protection'] ?? '');

				// check values
				if (!filter_has_var(INPUT_POST, "system_title") || !$getLib->checkVal($system_title)) {
					$error_msg = "請輸入標題";
					// [Fix] Use [] instead of array_push
					$msg_array[] = $error_msg;
				}

				// check values
				if (!filter_has_var(INPUT_POST, "system_display_number") || !$getLib->checkVal($system_display_num)) {
					$error_msg = "請輸入每頁顯示筆數";
					$msg_array[] = $error_msg;
				}

				// check values
				if (!filter_has_var(INPUT_POST, "system_display_page_number") || !$getLib->checkVal($system_display_page_num)) {
					$error_msg = "請輸入頁碼顯示筆數";
					$msg_array[] = $error_msg;
				}

				// 進行資料庫存取
				if (count($msg_array) == 0) {
					try {

						$fp = fopen($this->filePath, "w");
						if ($fp) {
							fwrite($fp, $system_title . "\n");
							fwrite($fp, $system_filter . "\n");
							fwrite($fp, $system_stripslashes . "\n");
							fwrite($fp, $system_display_num . "\n");
							fwrite($fp, $system_display_page_num . "\n");
							fwrite($fp, $system_csrf_protection);
							fclose($fp);

							$success_msg = "設定更新成功！";
							$msg_array[] = $success_msg;

							$return_status = true;
						} else {
							$msg_array[] = "無法寫入設定檔";
						}
					} catch (Exception $e) {
						$error_msg = "資料庫錯誤 <br />{$e}";
						$msg_array[] = $error_msg;
					}
				}
			}
		} catch (Exception $e) {
		}

		$returnVal = array("status" => $return_status, "msg" => $msg_array);

		return $returnVal;
	}

	public function getSettings()
	{
		$returnVal = array();

		try {
			// initial 
			$this->getFile();

			// [Fix] Ensure fileContent is array and pad it to avoid offsets
			$data = $this->fileContent;
			if (!is_array($data)) {
				$data = [];
			}
			// Pad to at least 6 elements
			$data = array_pad($data, 6, "");

			// [Fix] Replace list() with direct assignment
			$returnVal['title'] = trim($data[0] ?? '');
			$returnVal['filter'] = trim($data[1] ?? '');
			$returnVal['stripslashes'] = trim($data[2] ?? '');
			$returnVal['display_num'] = trim($data[3] ?? '');
			$returnVal['display_page_num'] = trim($data[4] ?? '');
			$returnVal['csrf_protection'] = trim($data[5] ?? '');

		} catch (Exception $e) {

		}

		return $returnVal;
	}
}