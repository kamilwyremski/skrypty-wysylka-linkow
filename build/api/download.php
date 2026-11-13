<?php

session_start();

error_reporting(E_ALL);
ini_set("display_errors", "1");
error_reporting(0);

require_once(realpath(dirname(__FILE__)).'/db.php');
require_once(realpath(dirname(__FILE__)).'/functions.php');
require_once(realpath(dirname(__FILE__)).'/admin.class.php');
require_once(realpath(dirname(__FILE__)).'/script.class.php');

$token = $_GET['token'] ?? getBearerToken();

if(!empty($_GET['script_id']) and (int)$_GET['script_id']>0 and !empty($token)){
	
	$admin = new Admin();
	
	if($admin->checkToken($token)){
		$script = Script::show((int)$_GET['script_id']);
		if(!$script){
			die('Nieprawidlowy link');
		}
		
		$file_url = _FOLDER_SCRIPTS_ . basename($script['file']);
		if(!file_exists($file_url)){
			die('Plik nie istnieje na serwerze');
		}

		$safeFilename = str_replace(['"', "\r", "\n", '/', '\\'], '', $script['filename']);
		header('Content-Type: application/octet-stream');
		header("Content-Transfer-Encoding: Binary"); 
		header('Content-Disposition: attachment; filename="' . $safeFilename . '"'); 
		header('Content-Length: ' . filesize($file_url));
		readfile($file_url);
		exit;
	}
	
}elseif(!empty($_GET['link'])){

	$correct = false;
	$link_id = $script_id = 0;
	
	$sth = $db->prepare('SELECT COUNT(1) FROM logs_download WHERE ip=:ip AND date > DATE_ADD(NOW(), INTERVAL -1 HOUR)');
	$sth->bindValue(':ip', get_client_ip(), PDO::PARAM_STR);
	$sth->execute();
	if($sth->fetchColumn()>10){
		echo('Przekroczono limit prób pobierania');
	}else{
		$sth = $db->prepare('SELECT * FROM links WHERE link=:link LIMIT 1');
		$sth->bindValue(':link', $_GET['link'], PDO::PARAM_STR);
		$sth->execute();
		$link = $sth->fetch(PDO::FETCH_ASSOC);
		if(!$link){
			echo('Nieprawidłowy link');
		}else{
			$link_id = $link['id'];
			if(date("Y-m-d") > $link['date_finish']) {
				echo('Link jest już nieaktywny');
			}else{
				if($link['download_limit']<1){
					echo('Przekroczono limit pobrań');
				}else{
					$sth = $db->prepare('SELECT * FROM scripts WHERE id=:id LIMIT 1');
					$sth->bindValue(':id', (int)$link['script_id'], PDO::PARAM_INT);
					$sth->execute();
					$script = $sth->fetch(PDO::FETCH_ASSOC);
					if(!$script){
						echo('Skrypt nie jest już dostępny');
					}else{
						$file_url = _FOLDER_SCRIPTS_ . basename($script['file']);
						if (!file_exists($file_url)) {
							echo('Plik nie istnieje na serwerze');
						} else {
							// Atomic decrement to prevent race conditions
							$sth = $db->prepare('UPDATE links SET download_limit=download_limit-1 WHERE id=:id AND download_limit > 0 LIMIT 1');
							$sth->bindValue(':id', (int)$link['id'], PDO::PARAM_INT);
							$sth->execute();
							
							if ($sth->rowCount() > 0) {
								$script_id = $script['id'];
								$correct = true;

								// Log successful download before streaming file
								$sthLog = $db->prepare('INSERT INTO `logs_download`(`link`, `correct`, `link_id`, `script_id`, `ip`, `date`) VALUES (:link,:correct,:link_id,:script_id,:ip,NOW())');
								$sthLog->bindValue(':link', $_GET['link'], PDO::PARAM_STR);
								$sthLog->bindValue(':correct', 1, PDO::PARAM_INT);
								$sthLog->bindValue(':link_id', $link_id, PDO::PARAM_INT);
								$sthLog->bindValue(':script_id', $script_id, PDO::PARAM_INT);
								$sthLog->bindValue(':ip', get_client_ip(), PDO::PARAM_STR);
								$sthLog->execute();
								
								$safeFilename = str_replace(['"', "\r", "\n", '/', '\\'], '', $script['filename']);
								header('Content-Type: application/octet-stream');
								header("Content-Transfer-Encoding: Binary"); 
								header('Content-Disposition: attachment; filename="' . $safeFilename . '"'); 
								header('Content-Length: ' . filesize($file_url));
								readfile($file_url);
								exit;
							} else {
								echo('Przekroczono limit pobrań');
							}
						}
					}
				}
			}
		}

		$sth = $db->prepare('INSERT INTO `logs_download`(`link`, `correct`, `link_id`, `script_id`, `ip`, `date`) VALUES (:link,:correct,:link_id,:script_id,:ip,NOW())');
		$sth->bindValue(':link', $_GET['link'], PDO::PARAM_STR);
		$sth->bindValue(':correct', $correct ? 1 : 0, PDO::PARAM_INT);
		$sth->bindValue(':link_id', $link_id, PDO::PARAM_INT);
		$sth->bindValue(':script_id', $script_id, PDO::PARAM_INT);
		$sth->bindValue(':ip', get_client_ip(), PDO::PARAM_STR);
		$sth->execute();
	}
}
