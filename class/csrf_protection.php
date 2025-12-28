<?php
/**
 * Model: C.P.Sub 公告系統
 * Author: Cooltey Feng
 * Lastest Update: 2019/02/09
 */

class CSRFProtection
{

	// [Fix] Modernize property declarations
	public string $getToken = '';
	public object $getLib;

	// [Fix] Standardize constructor with type hint
	public function __construct(object $get_lib)
	{
		$this->getLib = $get_lib;
	}

	// save token into session
	public function genToken(bool $forceNew = false)
	{
		// [Fix] Session safety: Start session if not started
		if (session_status() === PHP_SESSION_NONE) {
			session_start();
		}

		// 若 Session 中已有有效 Token 且非強制更新，則直接使用
		if (!$forceNew && !empty($_SESSION['csrf_token'])) {
			$this->getToken = $_SESSION['csrf_token'];
			return $this->getToken;
		}

		// 產生新 Token
		$_SESSION['csrf_token'] = $this->getLib->generateRandomString(32);

		$this->getToken = $_SESSION['csrf_token'];

		return $this->getToken;
	}

	// gen token hidden field
	public function genTokenField()
	{

		// [Fix] Session safety for potential token check
		if (session_status() === PHP_SESSION_NONE) {
			session_start();
		}

		// gen token if not exists or empty
		if (empty($this->getToken)) {
			$this->genToken();
		}
		// [Fix] Ensure $this->getToken is used, if genToken failed for some reason, provide empty string
		$token = $this->getToken ?? '';
		return "<input type='hidden' name='csrf_token' value='" . $token . "'>";
	}

	/**
	 * 取得目前的 CSRF Token（若不存在則產生）
	 * @return string
	 */
	public function getToken(): string
	{
		if (session_status() === PHP_SESSION_NONE) {
			session_start();
		}

		if (empty($this->getToken)) {
			$this->genToken();
		}

		return $this->getToken;
	}

	// check token
	public function checkToken(array $requestData, bool $regenerateAfterCheck = true): bool
	{
		// [Fix] Session safety
		if (session_status() === PHP_SESSION_NONE) {
			session_start();
		}

		// 若無資料則視為驗證失敗
		if (empty($requestData)) {
			echo $this->getLib->showAlertMsg("驗證錯誤，請求資料為空");
			echo $this->getLib->getRedirect("./");
			exit;
		}

		$session_token = $_SESSION['csrf_token'] ?? '';
		$request_token = $requestData['csrf_token'] ?? '';

		// 使用 hash_equals 防止時序攻擊
		if (!empty($session_token) && !empty($request_token) && hash_equals($session_token, $request_token)) {
			// 驗證成功後重新產生 Token（防止重複使用）
			if ($regenerateAfterCheck) {
				$this->genToken(true);
			}
			return true;
		} else {
			echo $this->getLib->showAlertMsg("驗證錯誤，請使用單一視窗操作");
			echo $this->getLib->getRedirect("./");
			exit;
		}
	}
}
