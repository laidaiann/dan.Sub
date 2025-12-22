<?php
/**
 * Model: C.P.Sub 公告系統
 * Author: Cooltey Feng
 * Lastest Update: 2020/12/19
 */

class Auth
{

	public array $accountData;
	public object $getLib;

	function __construct(array $account_data, object $getLib)
	{
		$this->accountData = $account_data;
		$this->getLib = $getLib;
	}

	function setLogin(array $getData): array
	{

		$return_status = false;
		$msg_array = array();

		$returnVal = array("status" => $return_status, "msg" => $msg_array);

		try {
			if (isset($getData['send']) && $this->getLib->checkVal($getData['send'])) {

				// set get values
				$cpsub_username = $this->getLib->setFilter($getData['cpsub_username']);
				$cpsub_password = $this->getLib->setFilter($getData['cpsub_password']);

				// check values
				if (!filter_has_var(INPUT_POST, "cpsub_username") || !$this->getLib->checkVal($cpsub_username)) {
					$error_msg = "請輸入帳號";
					$msg_array[] = $error_msg;
				}

				// check values
				if (!filter_has_var(INPUT_POST, "cpsub_password") || !$this->getLib->checkVal($cpsub_password)) {
					$error_msg = "請輸入密碼";
					$msg_array[] = $error_msg;
				}

				// check
				if (count($msg_array) == 0) {
					try {

						// start check 
						$login_flag = false;
						foreach ($this->accountData as $aData) {
							if ($aData['username'] == $cpsub_username) {
								// TODO: Upgrade to password_hash
								if ($aData['password'] == $cpsub_password) {

									// 啟動 Session 前的檢查
									if (session_status() === PHP_SESSION_NONE) {
										session_start();
									}

									// 防止 Session 劫持
									session_regenerate_id(true);

									$_SESSION['login'] = "1";
									$_SESSION['cpsub_username'] = $aData['username'];
									$_SESSION['cpsub_password'] = $aData['password']; // 建議不要存密碼在 Session
									$_SESSION['cpsub_nickname'] = $aData['nickname'];
									// TODO: need improvment by using the CSRF class
									$_SESSION['csrf_token'] = $getData['csrf_token'] ?? '';

									$success_msg = "登入成功！";
									$msg_array[] = $success_msg;

									// set status
									$return_status = true;
									$login_flag = true;
									break; // 登入成功後跳出迴圈
								} else {
									$error_msg = "密碼錯誤";
									$msg_array[] = $error_msg;
								}
							}
						}

						if (!$login_flag && count($msg_array) == 0) {
							$error_msg = "查無帳號";
							$msg_array[] = $error_msg;
						}

					} catch (Exception $e) {
						$error_msg = "登入失敗 <br />{$e}";
						$msg_array[] = $error_msg;
					}
				}
			}
		} catch (Exception $e) {
			$error_msg = "登入失敗 <br />{$e}";
			$msg_array[] = $error_msg;
		}

		$returnVal = array("status" => $return_status, "msg" => $msg_array);

		return $returnVal;
	}

	// check auth
	function checkAuth(array $cookie, array $session, string $page): void
	{
		if (!preg_match("/login/", $page)) {

			$login = "";
			$username = "";

			if ($this->getLib->checkVal($cookie)) {
				if (isset($cookie['cpsub_username']) && isset($cookie['cpsub_password'])) {
					$username = $this->getLib->setFilter($cookie['cpsub_username']);
					$password = $this->getLib->setFilter($cookie['cpsub_password']);
				} else {
					$username = "";
					$password = "";
				}

				if ($this->getLib->checkVal($username)) {
					$login = "1";
					// 注意：這裡修改傳入的 $session 陣列不會影響 $_SESSION，
					// 但原邏輯似乎只是為了接下來的判斷使用
					$session['login'] = $login;
					$session['cpsub_username'] = $username;
				}
			}

			if ($this->getLib->checkVal($session)) {
				$login = $this->getLib->setFilter($session['login'] ?? '');
				$username = $this->getLib->setFilter($session['cpsub_username'] ?? '');
			}

			$rPage = "./login.php?re={$page}";
			if ($this->getLib->checkVal($login) && $this->getLib->checkVal($username)) {

				if ($login == "1" && !preg_match("/logout/", $page)) {
					// do nothing

				} else {
					$this->clearAuth();
					echo $this->getLib->getRedirect($rPage);
					exit;
				}
			} else {
				$this->clearAuth();
				echo $this->getLib->getRedirect($rPage);
				exit;
			}
		}
	}

	function clearAuth(): void
	{
		if (session_status() === PHP_SESSION_NONE) {
			session_start();
		}
		setcookie("cpsub_username", "", time() - 1296000, "/");
		session_destroy();
	}
}