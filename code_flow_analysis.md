# C.P.Sub 公告系統 - 程式碼執行順序與資料流向分析

## 專案概述

本專案為 PHP 公告系統，使用 CSV 檔案作為資料儲存（非 SQL 資料庫）。目前正在進行 PHP 5.3 → PHP 7.4 的重構升級。

---

## 一、使用者登入流程

### 1.1 請求入口
```
HTTP Request → login.php
```

### 1.2 執行順序詳細追蹤

| 步驟 | 檔案位置 | Class/Function | 說明 |
|------|----------|----------------|------|
| 1 | `login.php` (Line 9) | `include_once("config/config.php")` | 載入設定檔、啟動 Session |
| 2 | `config/config.php` (Line 9-11) | `session_start()` | 安全檢查後啟動 Session |
| 3 | `login.php` (Line 11) | `include_once("class/settings.php")` | 載入設定類別 |
| 4 | `login.php` (Line 13) | `include_once("class/lib.php")` | 載入工具庫類別 |
| 5 | `login.php` (Line 15) | `include_once("class/auth.php")` | 載入認證類別 |
| 6 | `login.php` (Line 17) | `include_once("class/template.php")` | 載入模板類別 |
| 7 | `login.php` (Line 20) | `include_once("class/csrf_protection.php")` | 載入 CSRF 保護類別 |
| 8 | `login.php` (Line 24) | `new Settings($config_setting_file_path)` | 實例化設定物件 |
| 9 | `class/settings.php` | `Settings::getSettings()` | 讀取 `db/settings.txt` 取得系統設定 |
| 10 | `login.php` (Line 31) | `new Lib($filter_val, $stripslashes_val)` | 實例化工具庫 |
| 11 | `login.php` (Line 32) | `new Auth($config_account_data, $getLib)` | 實例化認證物件 |
| 12 | `login.php` (Line 34) | `new CSRFProtection($getLib)` | 實例化 CSRF 保護物件 |
| 13 | `login.php` (Line 47) | `$getAuth->setLogin($getData)` | 【核心】執行登入驗證 |
| 14 | `class/auth.php` (Line 21-108) | `Auth::setLogin()` | 驗證帳號密碼 |
| 15 | `class/auth.php` (Line 54-57) | 迴圈比對 `$this->accountData` | 比對 config.php 中的帳號陣列 |
| 16 | `class/auth.php` (Line 61-63) | `session_start()` | 再次確認 Session 狀態 |
| 17 | `class/auth.php` (Line 66) | `session_regenerate_id(true)` | 防止 Session 劫持 |
| 18 | `class/auth.php` (Line 68-73) | 設定 `$_SESSION` | 儲存登入狀態 |
| 19 | `login.php` (Line 54) | `$getCSRF->checkToken($getData)` | 驗證 CSRF Token |
| 20 | `class/csrf_protection.php` (Line 55-76) | `CSRFProtection::checkToken()` | 比對 Session 與 POST Token |
| 21 | `login.php` (Line 60) | `$getLib->getRedirect($rPage)` | 重導向至 `manage.php` |

### 1.3 資料流向圖
```
[Browser] 
    ↓ POST: cpsub_username, cpsub_password, csrf_token
[login.php]
    ↓ $_POST
[Auth::setLogin()]
    ↓ 比對 $config_account_data (config/config.php)
    ↓ 
[設定 $_SESSION]
    ↓
[CSRFProtection::checkToken()]
    ↓ 比對 $_SESSION['csrf_token'] vs $_POST['csrf_token']
    ↓
[Redirect → manage.php]
```

---

## 二、使用者新增公告流程

### 2.1 請求入口
```
HTTP Request → manage.php?p=article_add (POST)
```

### 2.2 執行順序詳細追蹤

| 步驟 | 檔案位置 | Class/Function | 說明 |
|------|----------|----------------|------|
| 1 | `manage.php` (Line 9) | `include_once("config/config.php")` | 載入設定檔 |
| 2 | `manage.php` (Line 11-23) | 載入所有必要類別 | Settings, Lib, Article, Auth, Template, CSRFProtection |
| 3 | `manage.php` (Line 25-30) | 實例化所有物件 | $getSettings, $getLib, $getAuth, $getTmp, $getCSRF |
| 4 | `manage.php` (Line 38-42) | 取得 `$_GET['p']` | 取得頁面參數 `article_add` |
| 5 | `manage.php` (Line 49) | `$getAuth->checkAuth($_COOKIE, $_SESSION, $p)` | 驗證使用者是否已登入 |
| 6 | `class/auth.php` (Line 111-158) | `Auth::checkAuth()` | 檢查 Session/Cookie 登入狀態 |
| 7 | `manage.php` (Line 52) | `$getLib->checkAdminPath($p)` | 取得管理頁面路徑 |
| 8 | `class/lib.php` (Line 111-128) | `Lib::checkAdminPath()` | 使用 `basename()` 防止 LFI 攻擊 |
| 9 | `manage.php` (Line 100-101) | `include($include_path)` | 動態載入 `admin/article_add.php` |
| 10 | `admin/article_add.php` (Line 16) | `new Article(...)` | 實例化文章物件 |
| 11 | `admin/article_add.php` (Line 25) | 檢查 `$_SERVER['REQUEST_METHOD'] === 'POST'` | 確認為 POST 請求 |
| 12 | `admin/article_add.php` (Line 28) | `$getLib->setFilter($_POST)` | 過濾輸入資料 |
| 13 | `admin/article_add.php` (Line 34) | `$getCSRF->checkToken($_POST)` | 驗證 CSRF Token |
| 14 | `class/csrf_protection.php` (Line 55-76) | `CSRFProtection::checkToken()` | 比對 Token |
| 15 | `admin/article_add.php` (Line 38) | `$getArticle->addNewArticle($getData, $getFile)` | 【核心】新增文章 |
| 16 | `class/article.php` (Line 344-472) | `Article::addNewArticle()` | 處理新增邏輯 |
| 17 | `class/article.php` (Line 358-361) | `$this->getLib->setFilter()` | 過濾各欄位 |
| 18 | `class/article.php` (Line 391) | `$this->getLib->fileUpload()` | 處理檔案上傳 |
| 19 | `class/article.php` (Line 427) | `$this->getAllList()` | 讀取現有文章列表 |
| 20 | `class/article.php` (Line 25-154) | `Article::getAllList()` | 從 CSV 讀取所有文章 |
| 21 | `class/article.php` (Line 34) | `fopen($this->filePath, "r")` | 開啟 `db/article.txt` |
| 22 | `class/article.php` (Line 35) | `fgetcsv($handle, 0, ",")` | 讀取 CSV 資料 |
| 23 | `class/article.php` (Line 438) | 計算新 ID | `$getLastId + 1` |
| 24 | `class/article.php` (Line 444) | `fopen($this->filePath, "w")` | ⚠️ 開啟檔案準備寫入 |
| 25 | `class/article.php` (Line 447) | `fputcsv($fp, $fields)` | ⚠️ 寫入 CSV 資料 |
| 26 | `class/article.php` (Line 450) | `fclose($fp)` | 關閉檔案 |

### 2.3 資料流向圖
```
[Browser]
    ↓ POST: article_title, article_author, article_content, article_date, article_top, article_file[], csrf_token
[manage.php]
    ↓ Auth::checkAuth() 驗證登入
    ↓ include admin/article_add.php
[admin/article_add.php]
    ↓ CSRFProtection::checkToken() 驗證 CSRF
    ↓ Article::addNewArticle()
[class/article.php]
    ↓ Lib::setFilter() 過濾輸入
    ↓ Lib::fileUpload() 處理上傳
    ↓ getAllList() 讀取現有資料 (fopen → fgetcsv)
    ↓ 
    ↓ ⚠️ fopen("db/article.txt", "w") 
    ↓ ⚠️ fputcsv() 寫入所有資料（包含新資料）
    ↓ fclose()
[db/article.txt] ← CSV 檔案更新
```

---

## 三、使用者刪除公告流程

### 3.1 請求入口
```
HTTP Request → manage.php?p=article_del&id=X&csrf_token=XXX (GET)
```

### 3.2 執行順序詳細追蹤

| 步驟 | 檔案位置 | Class/Function | 說明 |
|------|----------|----------------|------|
| 1 | `manage.php` (Line 9-30) | 載入與實例化 | 同新增流程步驟 1-6 |
| 2 | `manage.php` (Line 49) | `Auth::checkAuth()` | 驗證登入狀態 |
| 3 | `manage.php` (Line 52) | `Lib::checkAdminPath("article_del")` | 取得路徑 |
| 4 | `manage.php` (Line 100-101) | `include($include_path)` | 載入 `admin/article_del.php` |
| 5 | `admin/article_del.php` (Line 17) | `new Article(...)` | 實例化文章物件 |
| 6 | `admin/article_del.php` (Line 21) | `intval($getLib->setFilter($_GET['id']))` | 安全處理 ID |
| 7 | `admin/article_del.php` (Line 29) | `$getCSRF->checkToken($_GET)` | ⚠️ 使用 GET 驗證 CSRF |
| 8 | `admin/article_del.php` (Line 35) | `$getArticle->delArticle($getId)` | 【核心】刪除文章 |
| 9 | `class/article.php` (Line 660-709) | `Article::delArticle()` | 處理刪除邏輯 |
| 10 | `class/article.php` (Line 672) | `intval($this->getLib->setFilter($getId))` | 再次過濾 ID |
| 11 | `class/article.php` (Line 678) | `$this->getAllList()` | 讀取所有文章 |
| 12 | `class/article.php` (Line 681-684) | `foreach` 過濾 | 排除要刪除的 ID |
| 13 | `class/article.php` (Line 688) | `fopen($this->filePath, "w")` | ⚠️ 開啟檔案 |
| 14 | `class/article.php` (Line 690-691) | `fputcsv($fp, $fields)` | ⚠️ 重新寫入 |
| 15 | `class/article.php` (Line 694) | `fclose($fp)` | 關閉檔案 |
| 16 | `admin/article_del.php` (Line 52-53) | `getRedirect()` | 重導向至列表頁 |

### 3.3 資料流向圖
```
[Browser]
    ↓ GET: id, csrf_token
[manage.php]
    ↓ Auth::checkAuth()
    ↓ include admin/article_del.php
[admin/article_del.php]
    ↓ CSRFProtection::checkToken($_GET) ⚠️ 注意：使用 GET 方式
    ↓ Article::delArticle($getId)
[class/article.php]
    ↓ getAllList() 讀取所有資料
    ↓ 過濾排除目標 ID
    ↓ 
    ↓ ⚠️ fopen("db/article.txt", "w")
    ↓ ⚠️ fputcsv() 重新寫入（不含已刪除的資料）
    ↓ fclose()
[db/article.txt] ← CSV 檔案更新
```

---

## 四、使用者編輯公告流程

### 4.1 請求入口
```
HTTP Request → manage.php?p=article_edit&id=X (GET 顯示表單)
HTTP Request → manage.php?p=article_edit&id=X (POST 提交修改)
```

### 4.2 執行順序詳細追蹤

| 步驟 | 檔案位置 | Class/Function | 說明 |
|------|----------|----------------|------|
| 1 | `manage.php` (Line 9-30) | 載入與實例化 | 同前述流程 |
| 2 | `manage.php` (Line 49) | `Auth::checkAuth()` | 驗證登入 |
| 3 | `manage.php` (Line 100-101) | `include($include_path)` | 載入 `admin/article_edit.php` |
| 4 | `admin/article_edit.php` (Line 17) | `new Article(...)` | 實例化文章物件 |
| 5 | `admin/article_edit.php` (Line 21) | `intval($getLib->setFilter($_GET['id']))` | 安全處理 ID |
| 6 | `admin/article_edit.php` (Line 29) | 檢查 `$_SERVER['REQUEST_METHOD'] === 'POST'` | 判斷請求方式 |
| 7 | `admin/article_edit.php` (Line 30) | `$getLib->setFilter($_POST)` | 過濾 POST 資料 |
| 8 | `admin/article_edit.php` (Line 36) | `$getCSRF->checkToken($_POST)` | 驗證 CSRF Token |
| 9 | `admin/article_edit.php` (Line 40) | `$getArticle->editArticle($getId, $getData, $getFile)` | 【核心】編輯文章 |
| 10 | `class/article.php` (Line 474-658) | `Article::editArticle()` | 處理編輯邏輯 |
| 11 | `class/article.php` (Line 491-494) | `$this->getLib->setFilter()` | 過濾各欄位 |
| 12 | `class/article.php` (Line 541-560) | 處理檔案刪除 | 使用 `basename()` 防止目錄遍歷 |
| 13 | `class/article.php` (Line 563) | `$this->getLib->fileUpload()` | 處理新上傳檔案 |
| 14 | `class/article.php` (Line 607) | `$this->getAllList()` | 讀取所有文章 |
| 15 | `class/article.php` (Line 610-622) | `foreach` 更新 | 找到目標 ID 並更新 |
| 16 | `class/article.php` (Line 628) | `fopen($this->filePath, "w")` | ⚠️ 開啟檔案 |
| 17 | `class/article.php` (Line 630-631) | `fputcsv($fp, $fields)` | ⚠️ 重新寫入所有資料 |
| 18 | `class/article.php` (Line 634) | `fclose($fp)` | 關閉檔案 |
| 19 | `admin/article_edit.php` (Line 53) | `$getArticle->getArticle($getId)` | 重新讀取顯示 |

### 4.3 資料流向圖
```
[Browser]
    ↓ POST: article_title, article_author, article_content, article_date, 
    ↓       article_top, article_file[], article_file_remain[], 
    ↓       article_file_del[], csrf_token
[manage.php]
    ↓ Auth::checkAuth()
    ↓ include admin/article_edit.php
[admin/article_edit.php]
    ↓ CSRFProtection::checkToken($_POST)
    ↓ Article::editArticle()
[class/article.php]
    ↓ Lib::setFilter() 過濾輸入
    ↓ 處理檔案刪除 (basename 防護)
    ↓ Lib::fileUpload() 處理新上傳
    ↓ getAllList() 讀取所有資料
    ↓ foreach 找到目標 ID 並更新欄位
    ↓ 
    ↓ ⚠️ fopen("db/article.txt", "w")
    ↓ ⚠️ fputcsv() 重新寫入所有資料
    ↓ fclose()
[db/article.txt] ← CSV 檔案更新
```

---

## 五、子階段 3-1：資料庫並發保護審計結果

### 5.1 🔴 嚴重問題：Race Condition（競態條件）

根據程式碼分析，以下位置存在嚴重的並發問題：

#### 問題一：Article::addNewArticle() - 新增文章
```php
// class/article.php Line 427-450
$resultArray = $this->getAllList();           // 讀取
// ... 處理資料 ...
$fp = fopen($this->filePath, "w");            // ⚠️ 無鎖定機制
foreach ($resultArray as $fields) {
    fputcsv($fp, $fields);                    // ⚠️ 可能覆蓋其他進程的寫入
}
fclose($fp);
```

**風險情境**：
```
Process A: getAllList() → 取得 [1,2,3]
Process B: getAllList() → 取得 [1,2,3]
Process A: fopen("w") → 寫入 [1,2,3,4]
Process B: fopen("w") → 覆蓋為 [1,2,3,5]  ← 文章 4 遺失！
```

#### 問題二：Article::editArticle() - 編輯文章
```php
// class/article.php Line 607-634
$resultArray = $this->getAllList();           // 讀取
// ... 處理資料 ...
$fp = fopen($this->filePath, "w");            // ⚠️ 無鎖定機制
foreach ($dataArray as $fields) {
    fputcsv($fp, $fields);                    // ⚠️ 同樣問題
}
fclose($fp);
```

#### 問題三：Article::delArticle() - 刪除文章
```php
// class/article.php Line 678-694
$resultArray = $this->getAllList();           // 讀取
// ... 過濾資料 ...
$fp = fopen($this->filePath, "w");            // ⚠️ 無鎖定機制
foreach ($dataArray as $fields) {
    fputcsv($fp, $fields);                    // ⚠️ 同樣問題
}
fclose($fp);
```

#### 問題四：Article::addViewCounts() - 增加瀏覽次數
```php
// class/article.php Line 1321-1327 (概略位置)
$fp = fopen($this->filePath, "w");            // ⚠️ 無鎖定機制
foreach ($dataArray as $fields) {
    fputcsv($fp, $fields);
}
fclose($fp);
```

#### 問題五：Article::ipChecker() - IP 檢查器
```php
// class/article.php Line 270-276
$fp = fopen($this->checkerPath, "w");         // ⚠️ 無鎖定機制
fwrite($fp, $getLine);
fclose($fp);
```

### 5.2 📋 目前已完成的安全修復

| 項目 | 狀態 | 說明 |
|------|------|------|
| Session 安全啟動 | ✅ 已修復 | `session_status() === PHP_SESSION_NONE` 檢查 |
| Session Hijacking 防護 | ✅ 已修復 | `session_regenerate_id(true)` |
| CSRF Token 保護 | ✅ 已修復 | CSRFProtection 類別 |
| XSS 防護 | ✅ 已修復 | `htmlspecialchars()` |
| LFI 防護 | ✅ 已修復 | `basename()` 過濾 |
| 目錄遍歷防護 | ✅ 已修復 | 檔案刪除使用 `basename()` |
| 型別宣告 | ✅ 已修復 | PHP 7.4 型別提示 |
| Null 安全存取 | ✅ 已修復 | `??` 運算子 |
| Magic Quotes 移除 | ✅ 已修復 | 移除 `get_magic_quotes_gpc()` |

### 5.3 🔧 待重構項目：並發保護實作建議

#### 方案一：檔案鎖定機制（建議採用）
```php
// 建議新增方法
private function writeToCSV(array $data): bool
{
    $fp = fopen($this->filePath, "c+");  // 使用 c+ 模式
    if ($fp === false) {
        return false;
    }
    
    // 取得排他鎖
    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        return false;
    }
    
    try {
        // 清空檔案
        ftruncate($fp, 0);
        rewind($fp);
        
        // 寫入資料
        foreach ($data as $fields) {
            fputcsv($fp, $fields);
        }
        
        // 刷新到磁碟
        fflush($fp);
        
        return true;
    } finally {
        // 釋放鎖定
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}
```

#### 方案二：原子性讀寫
```php
private function atomicWrite(array $data): bool
{
    $tempFile = $this->filePath . '.tmp.' . uniqid();
    $fp = fopen($tempFile, 'w');
    
    if ($fp === false) {
        return false;
    }
    
    foreach ($data as $fields) {
        fputcsv($fp, $fields);
    }
    
    fclose($fp);
    
    // 原子性重命名
    return rename($tempFile, $this->filePath);
}
```

#### 方案三：讀取時加鎖
```php
private function readWithLock(): array
{
    $fp = fopen($this->filePath, "r");
    if ($fp === false) {
        return [];
    }
    
    // 取得共享鎖
    if (!flock($fp, LOCK_SH)) {
        fclose($fp);
        return [];
    }
    
    $data = [];
    while (($row = fgetcsv($fp)) !== false) {
        $data[] = $row;
    }
    
    flock($fp, LOCK_UN);
    fclose($fp);
    
    return $data;
}
```

---

## 六、article.txt 資料結構

根據分析，CSV 欄位結構如下：

| 索引 | 欄位名稱 | 說明 |
|------|----------|------|
| 0 | id | 文章 ID |
| 1 | title | 標題 |
| 2 | author | 發佈單位/作者 |
| 3 | top | 是否置頂 (0/1) |
| 4 | content | 文章內容 (HTML) |
| 5 | files | 附件檔名（逗號分隔） |
| 6 | files_name | 附件原始名稱（逗號分隔） |
| 7 | date | 發佈日期 |
| 8 | ip | 發佈者 IP |
| 9 | counts | 瀏覽次數 |
| 10 | lastview | 最後瀏覽時間戳 |

---

## 七、重構優先順序建議

| 優先級 | 項目 | 理由 |
|--------|------|------|
| 🔴 P0 | 檔案鎖定機制 | 資料完整性關鍵 |
| 🔴 P0 | 原子性寫入 | 防止資料損毀 |
| 🟡 P1 | 密碼雜湊升級 | 目前為明文比對 |
| 🟡 P1 | 刪除操作改用 POST | 目前使用 GET + CSRF Token |
| 🟢 P2 | ID 自動遞增優化 | 目前讀取全部資料取得最後 ID |
| 🟢 P2 | 分離讀寫方法 | 提高程式碼可維護性 |

---

*文件產生時間：2025-12-27*
*分析版本：dan.Sub V1.0 / C.P.Sub v5.35*
