<?php
/**
 * Model: C.P.Sub 公告系統
 * Author: Cooltey Feng
 * Lastest Update: 2025/12/22
 */

// 確保必要的設定與物件存在
if (!isset($config_upload_folder) || !isset($config_article_file_path) || !isset($config_ip_file_path) || !isset($getLib)) {
    exit("System Error: Configuration missing.");
}

// set Article
$getArticle = new Article($config_upload_folder, $config_article_file_path, $config_ip_file_path, $getLib);

// 安全處理 ID
$getId = isset($_GET['id']) ? intval($getLib->setFilter($_GET['id'])) : 0;

try {
    // check CSRF - 針對 GET 請求也需要某種程度的驗證，或者確認此操作是否應該用 POST
    // 原程式碼使用 $_GET 進行 CSRF 檢查
    if (isset($getCSRF)) {
        // 若使用 GET 刪除，Token 應包含在 URL 中
        $getCSRF->checkToken($_GET);
    }

    // set add function
    // 再次確認 ID 有效
    if ($getId > 0) {
        $getResult = $getArticle->delArticle($getId);

        if (isset($getResult['msg']) && is_array($getResult['msg']) && count($getResult['msg']) > 0) {
            $msg = $getResult['msg'][0];
        } else {
            $msg = "刪除完成";
        }
    } else {
        $msg = "無效的 ID";
    }

} catch (Exception $e) {
    $msg = "系統錯誤: " . $e->getMessage();
}

// return to list
$rPage = "./manage.php?p=article_list";
echo $getLib->showAlertMsg($msg);
echo $getLib->getRedirect($rPage);
exit;

?>