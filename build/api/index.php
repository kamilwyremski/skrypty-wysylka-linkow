<?php

session_start();

error_reporting(E_ALL);
ini_set("display_errors", "1");
error_reporting(0);

define('_BASE_URL_','https://skrypty.itworksbetter.net/api');

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	header("HTTP/1.1 204 No Content");
	exit(0);
}

header("Content-Type: application/json; charset=utf-8");
header("HTTP/1.1 200 OK");

require_once(realpath(dirname(__FILE__)).'/db.php');
require_once(realpath(dirname(__FILE__)).'/functions.php');
require_once(realpath(dirname(__FILE__)).'/admin.class.php');
require_once(realpath(dirname(__FILE__)).'/script.class.php');
require_once(realpath(dirname(__FILE__)).'/links.class.php');

if(file_get_contents('php://input')){
	$_POST = json_decode(file_get_contents('php://input'), true);
}

$admin = new Admin();

if(isset($_GET['path'])){
	if($_GET['path'] == 'get-session-code'){
		
		echo json_encode(['session_code' => $admin->newSessionCode()]);
	
	}elseif($_GET['path'] == 'login' and !empty($_POST['email']) and !empty($_POST['password']) and !empty($_POST['session_code'])){

		$user = $admin->login($_POST);
		if($user){
			echo json_encode(['token' => $_POST['session_code']]);
		}else{
			echo json_encode(['error' => 'Wpisane dane są niepoprawne', 'session_code' => $admin->newSessionCode()]);
		}

	}elseif($_GET['path'] == 'check-token' and !empty($_POST['token'])){
		
		echo json_encode(['status' => $admin->checkToken($_POST['token'])]);
		
	}else{
		
		if(!$admin->checkToken(getBearerToken())){
			echo json_encode(['error' => 'Brak dostępu']);
			die();
		}
		
		if($_GET['path'] == 'get-scripts'){
			
			echo json_encode(['scripts' => Script::list()]);
			
		}elseif($_GET['path'] == 'add-script' and !empty($_POST['name']) and !empty($_FILES['file']['name'])){
			
			try {
				Script::add($_POST['name']);
				echo json_encode(['status' => true]);
			} catch(Exception $e) {
				echo json_encode(['error' => $e->getMessage()]);
			}
			
		}elseif($_GET['path'] == 'edit-script' and !empty($_POST['name']) and !empty($_POST['filename']) and !empty($_POST['id']) and $_POST['id']>0){
			
			try {
				Script::edit($_POST);
				echo json_encode(['status' => true]);
			} catch(Exception $e) {
				echo json_encode(['error' => $e->getMessage()]);
			}
			
		}elseif($_GET['path'] == 'remove-script' and !empty($_POST['id']) and $_POST['id']>0){
			
			try {
				Script::remove($_POST['id']);
				echo json_encode(['status' => true]);
			} catch(Exception $e) {
				echo json_encode(['error' => $e->getMessage()]);
			}
			
		}elseif($_GET['path'] == 'get-links'){
			
			echo json_encode(['links' => Links::list()]);
			
		}elseif($_GET['path'] == 'add-link' and !empty($_POST['script_id']) and !empty($_POST['email']) and !empty($_POST['date_finish']) and !empty($_POST['download_limit'])){
			
			try {
				Links::add($_POST);
				echo json_encode(['status' => true]);
			} catch(Exception $e) {
				echo json_encode(['error' => $e->getMessage()]);
			}
			
		}elseif($_GET['path'] == 'edit-link' and !empty($_POST['id']) and $_POST['id']>0 and !empty($_POST['script_id']) and !empty($_POST['email']) and !empty($_POST['date_finish']) and !empty($_POST['download_limit'])){
			
			try {
				Links::edit($_POST);
				echo json_encode(['status' => true]);
			} catch(Exception $e) {
				echo json_encode(['error' => $e->getMessage()]);
			}
			
		}elseif($_GET['path'] == 'remove-link' and !empty($_POST['id']) and $_POST['id']>0){
			
			try {
				Links::remove($_POST['id']);
				echo json_encode(['status' => true]);
			} catch(Exception $e) {
				echo json_encode(['error' => $e->getMessage()]);
			}
			
		}elseif($_GET['path'] == 'send-mail' and !empty($_POST['id']) and $_POST['id']>0 and !empty($_POST['subject']) and !empty($_POST['content'])){
			
			try {
				if(Links::sendMail($_POST['id'],$_POST['subject'],$_POST['content'])){
					echo json_encode(['status' => true]);
				}else{
					echo json_encode(['error' => 'Problem z wysyłką maila z serwera']);
				}
			} catch(Exception $e) {
				echo json_encode(['error' => $e->getMessage()]);
			}
			
		}elseif($_GET['path'] == 'get-logs-download'){
			
			echo json_encode(['logs' => Script::listLogs()]);
			
		}elseif($_GET['path'] == 'get-logs-admin'){
			
			echo json_encode(['logs' => $admin->listLogs()]);
			
		}elseif($_GET['path'] == 'logout-all'){
			
			$admin->logOutAll();
			echo json_encode(['status' => true]);
			
		}elseif($_GET['path'] == 'change-user' and !empty($_POST['email']) and !empty($_POST['password'])){
			
			try {
				$admin->changeUser($_POST, getBearerToken());
				echo json_encode(['status' => true]);
			} catch(Exception $e) {
				echo json_encode(['error' => $e->getMessage()]);
			}
			
		}elseif($_GET['path'] == 'add-user' and !empty($_POST['email']) and !empty($_POST['password'])){
			
			try {
				$admin->addUser($_POST);
				echo json_encode(['status' => true]);
			} catch(Exception $e) {
				echo json_encode(['error' => $e->getMessage()]);
			}
			
		}elseif($_GET['path'] == 'get-users'){
			
			echo json_encode(['users' => $admin->list()]);
			
		}elseif($_GET['path'] == 'remove-user' and !empty($_POST['id']) and $_POST['id']>0){
			
			try {
				$admin->removeUser($_POST['id'],getBearerToken());
				echo json_encode(['status' => true]);
			} catch(Exception $e) {
				echo json_encode(['error' => $e->getMessage()]);
			}
			
		}
	}
}else{
	echo json_encode(['error' => 'Brak dostępu']);
}