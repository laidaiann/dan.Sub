<?php
/**
 * Model: C.P.Sub 公告系統
 * Author: Cooltey Feng
 * 修改者： Laidaiann
 * Lastest Update: 2025/12/22
 */

class Article
{

	public string $filePath;
	public string $checkerPath;
	public string $folderPath;
	public object $getLib;

	public function __construct(string $getFolder, string $getPath, string $getChecker, object $getLib)
	{
		$this->filePath = $getPath;
		$this->checkerPath = $getChecker;
		$this->folderPath = $getFolder;
		$this->getLib = $getLib;
	}

	public function getAllList(?string $mode = null, ?string $ordercolumn = null, ?string $orderby = null, ?string $keywords = null)
	{

		$returnVal = array();

		// set two array for display mode
		$topArray = array();
		$normalArray = array();
		// read csv file
		if (($handle = fopen($this->filePath, "r")) !== FALSE) {
			while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
				// [Fix] Defensive coding: Check if data is array and pad it to avoid undefined offsets
				if (!is_array($data) || count($data) < 1) {
					continue;
				}
				$data = array_pad($data, 11, "");

				// set initial array
				$setList = array();
				if ($mode == "display") {
					// set search
					if ($this->getLib->checkVal($keywords)) {
						if (
							preg_match("/" . preg_quote($keywords, '/') . "/", $data[1]) ||
							preg_match("/" . preg_quote($keywords, '/') . "/", $data[2]) ||
							preg_match("/" . preg_quote($keywords, '/') . "/", $data[4]) ||
							preg_match("/" . preg_quote($keywords, '/') . "/", $data[6])
						) {
							$setList = array(
								"id" => $data[0],
								"title" => $data[1],
								"author" => $data[2],
								"top" => $data[3],
								"content" => $data[4],
								"files" => $data[5],
								"files_name" => $data[6],
								"date" => $data[7],
								"ip" => $data[8],
								"counts" => $data[9],
								"lastview" => $data[10]
							);

						}
					} else {
						$setList = array(
							"id" => $data[0],
							"title" => $data[1],
							"author" => $data[2],
							"top" => $data[3],
							"content" => $data[4],
							"files" => $data[5],
							"files_name" => $data[6],
							"date" => $data[7],
							"ip" => $data[8],
							"counts" => $data[9],
							"lastview" => $data[10]
						);
					}


					if (!empty($setList)) {
						// set column
						if ($ordercolumn == null) {
							$setKey = $setList['id'];
							// check key
							if (array_key_exists($setKey, $topArray) || array_key_exists($setKey, $normalArray)) {
								$setKey = $setKey . "_" . date("YmdHisu");
							}
						} else {
							try {
								$setKey = $setList[$ordercolumn];
								// check key
								if (array_key_exists($setKey, $topArray) || array_key_exists($setKey, $normalArray)) {
									$setKey = $setKey . "_" . date("YmdHisu");
								}
							} catch (Exception $e) {
								$setKey = $setList['id'];
								// check key
								if (array_key_exists($setKey, $topArray) || array_key_exists($setKey, $normalArray)) {
									$setKey = $setKey . "_" . date("YmdHisu");
								}
							}
						}

						if ($data[3] == "1") {
							$topArray[$setKey] = $setList;
						} else {
							$normalArray[$setKey] = $setList;
						}

					}

				} else {
					$setList = array(
						$data[0],
						$data[1],
						$data[2],
						$data[3],
						$data[4],
						$data[5],
						$data[6],
						$data[7],
						$data[8],
						$data[9],
						$data[10]
					);
					$returnVal[] = $setList;
				}
			}
			fclose($handle);

			// sort
			if ($mode == "display") {

				// set order
				if ($orderby == null || $orderby == "asc") {
					ksort($topArray);
					ksort($normalArray);
				} else {
					krsort($topArray);
					krsort($normalArray);
				}

				// merge array
				$returnVal = $topArray + $normalArray;
			}
		}

		return $returnVal;
	}

	public function getArticle(int $getId)
	{

		// [Fix] Initialize variables before try block
		$return_status = false;
		$return_data = array();

		$returnVal = array("status" => false, "data" => array());

		try {
			// check id & show contents
			if (filter_has_var(INPUT_GET, "id")) {
				if (filter_var($getId, FILTER_VALIDATE_INT)) {
					$getId = intval($this->getLib->setFilter($getId));

					// get article data
					$getList = $this->getAllList("display");
					// get single article
					// get single article
					// [Fix] Add null coalescing operator to prevent undefined index error
					$getArticleData = $getList[$getId] ?? [];

					if (!empty($getArticleData)) {
						$return_status = true;
						$return_data = $getArticleData;
					}
				}
			}
		} catch (Exception $e) {
		}

		$returnVal = array("status" => $return_status ?? false, "data" => $return_data ?? []);

		return $returnVal;
	}

	public function ipChecker()
	{

		// get now time
		$getNowTime = strtotime("now");
		$getIp = $this->getLib->getIp();

		// get data
		// get data
		$contents = "";
		if (file_exists($this->checkerPath)) {
			// [Fix] Defensive coding: check file existence and handle fopen failure
			$fp = fopen($this->checkerPath, "r");
			if ($fp !== false) {
				// get content
				$fsize = filesize($this->checkerPath);
				$contents = ($fsize > 0) ? fread($fp, $fsize) : "";
				fclose($fp);
			}
		}

		// split contents
		$maxData = 50;
		$limitVisit = 25;
		$countData = 0;
		$getLineArray = array();

		$dataArray = explode("|", $contents);
		foreach ($dataArray as $ipData) {
			$ipArray = explode(",", $ipData);
			$getTheIp = $ipArray[0];
			$getTheDate = $ipArray[1] ?? null;

			if ($countData < $maxData) {
				$countData++;
				$getLineArray[] = $ipData;
			} else {
				break;
			}
		}

		// check the array
		$checkArray = array();
		foreach ($getLineArray as $ipData) {
			$ipArray = explode(",", $ipData);
			$getTheIp = $ipArray[0];
			$getTheDate = $ipArray[1] ?? null;

			if ($getTheIp == $getIp) {
				$checkArray[] = $getTheDate;
				// echo $getTheDate;
				// echo "<br>";
			}

		}

		$returnVal = "pass";


		// check the time range
		if (count($checkArray) > $limitVisit) {
			$lastIndex = count($checkArray) - 1;
			$oldestDate = $checkArray[$lastIndex];
			$newestDate = $checkArray[0];

			$timeDiff = $newestDate - $oldestDate;
			//echo $timeDiff;

			if ($timeDiff > 0 && $timeDiff <= 60) {
				$returnVal = "block";
			}
		}


		// save ip 
		$newData = $getIp . "," . strtotime(date("Y-m-d H:i:s"));

		// put data into csv
		$fp = fopen($this->checkerPath, "w");

		$getLine = $newData . "|" . $contents;

		fwrite($fp, $getLine);

		fclose($fp);

		return $returnVal;
	}

	public function addViewCounts(int $getId)
	{
		// [Fix] Initialize return status
		$return_status = false;
		$returnVal = array("status" => false);

		// checking IP
		if ($this->ipChecker() == "pass") {
			try {
				// check id & show contents
				if (filter_has_var(INPUT_GET, "id")) {
					if (filter_var($getId, FILTER_VALIDATE_INT)) {
						$getId = intval($this->getLib->setFilter($getId));
						// start updating					
						$dataArray = array();

						// update array
						$resultArray = $this->getAllList();

						// get last update time
						$nowTime = strtotime(date("Y-m-d H:i:s"));
						$getLastViewTime = strtotime(date("Y-m-d H:i:s"));

						// update exist data
						foreach ($resultArray as $existData) {
							if ($existData[0] == $getId) {

								// get the last view time
								$getLastViewTime = $existData[10];

								$existData[9] = intval($existData[9]) + 1;
								$existData[10] = strtotime(date("Y-m-d H:i:s"));
							}

							$dataArray[] = $existData;
						}

						// check time
						if (($nowTime - $getLastViewTime) > 30) {
							// put data into csv
							$fp = fopen($this->filePath, "w");

							foreach ($dataArray as $fields) {
								fputcsv($fp, $fields);
							}

							fclose($fp);
						}

						$return_status = true;
					}
				}
			} catch (Exception $e) {
			}
		} else {
			$return_status = false;
		}

		$returnVal = array("status" => $return_status ?? false);

		return $returnVal;
	}

	public function addNewArticle(array $getData, array $getFile)
	{

		$msg_array = array();
		$return_status = false;

		$returnVal = array("status" => $return_status, "msg" => $msg_array);

		try {

			// check the submit btn has been submitted
			if (isset($getData['send']) && $this->getLib->checkVal($getData['send'])) {

				// set get values
				$article_title = $this->getLib->setFilter($getData['article_title']);
				$article_author = $this->getLib->setFilter($getData['article_author']);
				$article_content = $this->getLib->setFilter($getData['article_content']);
				$article_date = $this->getLib->setFilter($getData['article_date']);
				$article_ip = $this->getLib->getIp();

				$article_top = "";
				if (isset($getData['article_top'])) {
					$article_top = $getData['article_top'];
				}


				// check values
				if (!filter_has_var(INPUT_POST, "article_title") || !$this->getLib->checkVal($article_title)) {
					$error_msg = "請輸入標題";
					$msg_array[] = $error_msg;
				}

				if (!filter_has_var(INPUT_POST, "article_author") || !$this->getLib->checkVal($article_author)) {
					$error_msg = "請輸入發佈單位";
					$msg_array[] = $error_msg;
				}

				if (!filter_has_var(INPUT_POST, "article_content") || !$this->getLib->checkVal($article_content)) {
					$error_msg = "請輸入內文";
					$msg_array[] = $error_msg;
				}

				if (!$this->getLib->checkVal($article_top)) {
					$article_top = "0";
				}

				// set upload
				$uploadResult = $this->getLib->fileUpload($getFile, "article_file", $this->folderPath);

				$getTotalUploadFiles = count($getFile['article_file']['name']);

				$article_files = "";
				$article_files_name = "";
				if ($this->getLib->checkVal($getFile['article_file']['name'][0])) {
					if ($uploadResult['status'] != true) {
						$error_msg = "上傳檔案錯誤，請檢查您的檔案！";
						$msg_array[] = $error_msg;
					} else {
						$article_files = implode(",", $uploadResult['file']);
						$article_files_name = implode(",", $uploadResult['file_name']);
					}
				}

				// 進行資料庫存取
				if (count($msg_array) == 0) {
					try {

						// add new data
						$columnArray = array(
							"",
							$article_title,
							$article_author,
							$article_top,
							$article_content,
							$article_files,
							$article_files_name,
							$article_date,
							$article_ip,
							0,
							strtotime(date("Y-m-d H:i:s"))
						);

						// update array
						$resultArray = $this->getAllList();

						// check the last id
						$getSize = count($resultArray);

						// [Fix] Prevent offset -1 error by checking size
						$getLastId = 0;
						if ($getSize > 0) {
							$getLastId = $resultArray[$getSize - 1][0] ?? 0;
						}

						$columnArray[0] = $getLastId + 1;

						// add new data
						$resultArray[] = $columnArray;

						// put data into csv
						$fp = fopen($this->filePath, "w");

						foreach ($resultArray as $fields) {
							fputcsv($fp, $fields);
						}

						fclose($fp);

						$success_msg = "新增文章成功！";
						$msg_array[] = $success_msg;

						// set status
						$return_status = true;
					} catch (Exception $e) {
						$error_msg = "資料庫錯誤 <br />{$e}";
						$msg_array[] = $error_msg;
					}
				}
			}

		} catch (Exception $e) {
			$error_msg = "資料庫錯誤 <br />{$e}";
			$msg_array[] = $error_msg;
		}

		$returnVal = array("status" => $return_status, "msg" => $msg_array);

		return $returnVal;
	}

	public function editArticle(int $getId, array $getData, array $getFile)
	{

		$msg_array = array();
		$return_status = false;

		$returnVal = array("status" => $return_status, "msg" => $msg_array);

		try {
			// check id & show contents
			if (filter_has_var(INPUT_GET, "id")) {
				if (filter_var($getId, FILTER_VALIDATE_INT)) {
					$getId = intval($this->getLib->setFilter($getId));
					// check the submit btn has been submitted
					if (isset($getData['send']) && $this->getLib->checkVal($getData['send'])) {

						// set get values
						$article_title = $this->getLib->setFilter($getData['article_title']);
						$article_author = $this->getLib->setFilter($getData['article_author']);
						$article_content = $this->getLib->setFilter($getData['article_content']);
						$article_date = $this->getLib->setFilter($getData['article_date']);
						$article_ip = $this->getLib->getIp();
						$article_files = array();
						$article_files_name = array();

						$article_top = "";
						if (isset($getData['article_top'])) {
							$article_top = $getData['article_top'];
						}

						$file_del_array = [];
						if (isset($getData['article_file_del'])) {
							$file_del_array = $getData['article_file_del'];
						}

						$file_remain = "";
						if (isset($getData['article_file_remain'])) {
							$file_remain = $getData['article_file_remain'];
						}

						$file_name_remain = "";
						if (isset($getData['article_file_name_remain'])) {
							$file_name_remain = $getData['article_file_name_remain'];
						}


						// check values
						if (!filter_has_var(INPUT_POST, "article_title") || !$this->getLib->checkVal($article_title)) {
							$error_msg = "請輸入標題";
							$msg_array[] = $error_msg;
						}

						if (!filter_has_var(INPUT_POST, "article_author") || !$this->getLib->checkVal($article_author)) {
							$error_msg = "請輸入發佈單位";
							$msg_array[] = $error_msg;
						}

						if (!filter_has_var(INPUT_POST, "article_content") || !$this->getLib->checkVal($article_content)) {
							$error_msg = "請輸入內文";
							$msg_array[] = $error_msg;
						}

						if (!$this->getLib->checkVal($article_top)) {
							$article_top = "0";
						}

						// orgnize the upload column
						if (!empty($file_remain)) {
							$count = 0;
							foreach ($file_remain as $fileData) {
								// skip del file
								if (in_array($count, $file_del_array)) {
									// del file
									// Security fix: use basename() to prevent directory traversal
									$targetFile = $this->folderPath . basename($fileData);
									if (file_exists($targetFile)) {
										unlink($targetFile);
									}
								} else {
									// push data
									$article_files[] = $fileData;
									// [Fix] Add null coalescing operator to prevent undefined offset
									$article_files_name[] = $file_name_remain[$count] ?? '';
								}
								$count++;
							}
						}

						// set upload
						$uploadResult = $this->getLib->fileUpload($getFile, "article_file", $this->folderPath);

						$getTotalUploadFiles = count($getFile['article_file']['name']);

						if ($this->getLib->checkVal($getFile['article_file']['name'][0])) {
							if ($uploadResult['status'] != true) {
								$error_msg = "上傳檔案錯誤，請檢查您的檔案！";
								$msg_array[] = $error_msg;
							} else {
								// merge array
								$new_article_files_array = array_merge($article_files, $uploadResult['file']);
								$new_article_files_name_array = array_merge($article_files_name, $uploadResult['file_name']);
								$article_files = implode(",", $new_article_files_array);
								$article_files_name = implode(",", $new_article_files_name_array);
							}
						} else {
							$article_files = implode(",", $article_files);
							$article_files_name = implode(",", $article_files_name);
						}


						// 進行資料庫存取
						if (count($msg_array) == 0) {
							try {

								// update new data
								$columnArray = array(
									$getId,
									$article_title,
									$article_author,
									$article_top,
									$article_content,
									$article_files,
									$article_files_name,
									$article_date,
									$article_ip,
									0,
									strtotime(date("Y-m-d H:i:s"))
								);

								// start updating					
								$dataArray = array();

								// update array
								$resultArray = $this->getAllList();

								// update exist data
								foreach ($resultArray as $existData) {
									if ($existData[0] == $columnArray[0]) {
										$existData[1] = $columnArray[1];
										$existData[2] = $columnArray[2];
										$existData[3] = $columnArray[3];
										$existData[4] = $columnArray[4];
										$existData[5] = $columnArray[5];
										$existData[6] = $columnArray[6];
										$existData[7] = $columnArray[7];
										$existData[8] = $columnArray[8];

										// [Fix] Removed redundant self-assignments to [9] and [10]
									}

									$dataArray[] = $existData;
								}

								// put data into csv
								$fp = fopen($this->filePath, "w");

								foreach ($dataArray as $fields) {
									fputcsv($fp, $fields);
								}

								fclose($fp);

								$success_msg = "更新文章成功！";
								$msg_array[] = $success_msg;

								// set status
								$return_status = true;
							} catch (Exception $e) {
								$error_msg = "資料庫錯誤 <br />{$e}";
								$msg_array[] = $error_msg;
							}
						}
					}
				}
			}

		} catch (Exception $e) {
			$error_msg = "資料庫錯誤 <br />{$e}";
			$msg_array[] = $error_msg;
		}

		$returnVal = array("status" => $return_status, "msg" => $msg_array);

		return $returnVal;
	}

	public function delArticle(int $getId)
	{
		$returnVal = array("status" => false, "msg" => array());
		// [Fix] Initialize variables
		$msg_array = array();
		$return_status = false;

		try {

			// check id & show contents
			if (filter_has_var(INPUT_GET, "id")) {
				if (filter_var($getId, FILTER_VALIDATE_INT)) {
					$getId = intval($this->getLib->setFilter($getId));

					// start updating					
					$dataArray = array();

					// update array
					$resultArray = $this->getAllList();

					// update exist data
					foreach ($resultArray as $existData) {
						if ($existData[0] != $getId) {
							$dataArray[] = $existData;
						}
					}

					// put data into csv
					$fp = fopen($this->filePath, "w");

					foreach ($dataArray as $fields) {
						fputcsv($fp, $fields);
					}

					fclose($fp);

					$success_msg = "文章刪除成功！";
					$msg_array[] = $success_msg;

					// set status
					$return_status = true;
				}
			}
		} catch (Exception $e) {
		}

		$returnVal = array("status" => $return_status, "msg" => $msg_array);

		return $returnVal;
	}
}