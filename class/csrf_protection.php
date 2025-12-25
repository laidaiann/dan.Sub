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
	public function genToken()
	{
		// [Fix] Session safety: Start session if not started
		if (session_status() === PHP_SESSION_NONE) {
			session_start();
		}

		$_SESSION['csrf_token'] = $this->getLib->generateRandomString(7);

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

	// check token
	public function checkToken(array $postData)
	{
		if (!empty($postData)) {
			// [Fix] Session safety
			if (session_status() === PHP_SESSION_NONE) {
				session_start();
			}

			// [Fix] Use null coalescing operator to prevent undefined index
			$session_token = $_SESSION['csrf_token'] ?? '';
			$post_token = $postData['csrf_token'] ?? '';

			if ($session_token == $post_token && $post_token != "" && $session_token != "") {
				// pass
				$returnVal = true;
			} else {
				echo $this->getLib->showAlertMsg("驗證錯誤，請使用單一視窗操作");
				echo $this->getLib->getRedirect("./");
				exit;
			}
		}
	}
}
