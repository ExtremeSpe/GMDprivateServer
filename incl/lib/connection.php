<?php
error_reporting(0);
include dirname(__FILE__)."/../../config/connection.php";
include_once dirname(__FILE__)."/../../config/security.php";
require_once dirname(__FILE__)."/ipCheck.php";
$ic = new ipCheck();
@header('Content-Type: text/html; charset=utf-8');
if(!isset($port)) $port = 3306;
try {
	$ic->checkIP();
    $db = new PDO("mysql:host=$servername;port=$port;dbname=$dbname", $username, $password, array(PDO::ATTR_PERSISTENT => true));
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$ip = $ic->getYourIP();
	if($activeBanIP) {
	    $banip = $db->prepare("SELECT IP FROM bannedips WHERE IP=:ip");
	  	$banip->execute([':ip' => $ip]);
	  	$banip = $banip->fetch();
	  	if($banip != 0) exit(-1);
	}
	if(!isset($_SESSION['accountID'])) {
		$getExtID = $db->prepare('SELECT extID FROM users WHERE isRegistered = 1 AND IP = :ip LIMIT 1');
		$getExtID->execute([':ip' => $ip]);
		$getExtID = $getExtID->fetchColumn();
	}
	if(!empty($getExtID) || isset($_SESSION['accountID'])) {
		$accountIDcheck = $getExtID ?? $_SESSION['accountID'];
		$timezone = $db->prepare('SELECT timezone FROM accounts WHERE accountID = :id');
		$timezone->execute([':id' => $accountIDcheck]);
		$timezone = $timezone->fetchColumn();
		if(!empty($timezone)) date_default_timezone_set($timezone);
		else {
			$json = file_get_contents('http://ip-api.com/json/'.$ip);
			$ipData = json_decode($json, true);
			if($ipData['timezone']) {
				$update = $db->prepare('UPDATE accounts SET timezone = :tz WHERE accountID = :id');
				$update->execute([':tz' => $ipData['timezone'], ':id' => $accountIDcheck]);
				date_default_timezone_set($ipData['timezone']);
			}
		}
	}
}
catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
