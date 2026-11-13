<?php

class Admin {
	
	public function login($data){
		global $db;
		
		$sth = $db->prepare('SELECT 1 FROM admin_logs WHERE logged=0 AND date > DATE_ADD(NOW(), INTERVAL -30 MINUTE) AND ip=:ip');
		$sth->bindValue(':ip', get_client_ip(), PDO::PARAM_STR);
		$sth->execute();
		if($sth->rowCount() < 5){
			
			$sth = $db->prepare('SELECT a.id, a.email, a.password FROM admin a, admin_session ase WHERE a.email=:email AND ase.code=:code AND ase.ip=:ip LIMIT 1');
			$sth->bindValue(':email', $data['email'], PDO::PARAM_STR);
			$sth->bindValue(':code', $data['session_code'], PDO::PARAM_STR);
			$sth->bindValue(':ip', get_client_ip(), PDO::PARAM_STR);
			$sth->execute();
			$user = $sth->fetch(PDO::FETCH_ASSOC);
			if($user && $this->verifyPassword($data['password'], $user['password'], (int)$user['id'])){

				$sth = $db->prepare('UPDATE `admin_session` SET user_id=:user_id WHERE code=:code');
				$sth->bindValue(':user_id', $user['id'], PDO::PARAM_INT);
				$sth->bindValue(':code', $data['session_code'], PDO::PARAM_STR);
				$sth->execute();
				$sth->closeCursor();
				
				$this->saveLogs(true, $user['email']);
				
				return true;
			}
		}
		$this->removeSessionCode($data['session_code']);
		$this->saveLogs(false, $data['email']);
		return false;
	}
	
	public function saveLogs($logged=false, $email=''){
		global $db;
		$sth = $db->prepare('INSERT INTO `admin_logs`(`email`, `logged`, `ip`) VALUES (:email, :logged, :ip)');
		$sth->bindValue(':email', $email, PDO::PARAM_STR);
		$sth->bindValue(':logged', $logged, PDO::PARAM_INT);
		$sth->bindValue(':ip', get_client_ip(), PDO::PARAM_STR);
		$sth->execute();
	}
	
	public function newSessionCode(){
		global $db;
		$session_code = bin2hex(random_bytes(32));		
		$sth = $db->prepare('INSERT INTO `admin_session`(`code`, `ip`) VALUES (:code,:ip)');
		$sth->bindValue(':code', $session_code, PDO::PARAM_STR);
		$sth->bindValue(':ip', get_client_ip(), PDO::PARAM_STR);
		$sth->execute();
		return $session_code;
	}
	
	public function removeSessionCode($session_code){
		global $db;
		$sth = $db->prepare('DELETE FROM `admin_session` WHERE code=:code');
		$sth->bindValue(':code', $session_code, PDO::PARAM_STR);
		$sth->execute();
	}
	
	public function verifyPassword(string $password, string $dbPassword, ?int $userId = null): bool {
		global $db;
		$valid = false;
		if (strlen($dbPassword) === 32 && ctype_xdigit($dbPassword)) {
			// Legacy MD5 password check
			if (hash_equals($dbPassword, md5($password))) {
				$valid = true;
				// Migrate to modern password_hash
				if ($userId) {
					$newHash = $this->createPassword($password);
					$sth = $db->prepare('UPDATE admin SET password=:password WHERE id=:id');
					$sth->bindValue(':password', $newHash, PDO::PARAM_STR);
					$sth->bindValue(':id', $userId, PDO::PARAM_INT);
					$sth->execute();
				}
			}
		} else {
			if (password_verify($password, $dbPassword)) {
				$valid = true;
				if ($userId && password_needs_rehash($dbPassword, PASSWORD_DEFAULT)) {
					$newHash = $this->createPassword($password);
					$sth = $db->prepare('UPDATE admin SET password=:password WHERE id=:id');
					$sth->bindValue(':password', $newHash, PDO::PARAM_STR);
					$sth->bindValue(':id', $userId, PDO::PARAM_INT);
					$sth->execute();
				}
			}
		}
		return $valid;
	}

	public function createPassword(string $password): string {
		return password_hash($password, PASSWORD_DEFAULT);
	}
	
	public function checkToken(string $token){
		global $db;
		$sth = $db->prepare('SELECT 1 FROM admin_session WHERE code=:code AND user_id>0 LIMIT 1');
		$sth->bindValue(':code', $token, PDO::PARAM_STR);
		$sth->execute();
		$user = $sth->fetch(PDO::FETCH_ASSOC);
		if($user){
			return true;
		}else{
			return false;
		}
	}
	
	public function list(){
		global $db;
		$users = [];
		$sth = $db->query('SELECT id, email FROM admin ORDER BY id');
		while($row = $sth->fetch(PDO::FETCH_ASSOC)) {$users[] = $row;}
		return $users;
	}
	
	public function listLogs(){
		global $db;
		$logs = array();
		$sth = $db->query('SELECT * FROM admin_logs ORDER BY id DESC');
		while($row = $sth->fetch(PDO::FETCH_ASSOC)) {$logs[] = $row;}
		return $logs;
	}
	
	public function changeUser($data, ?string $token = null){
		global $db;

		$sessionCode = $token ?: ($data['token'] ?? '');
		if (empty($sessionCode)) {
			throw new Exception('Brak tokenu autoryzacji');
		}

		if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
			throw new Exception('Nieprawidłowy adres email');
		}

		$sth = $db->prepare('SELECT a.id, a.email FROM admin_session ase, admin a WHERE a.id = ase.user_id AND ase.code=:code LIMIT 1');
		$sth->bindValue(':code', $sessionCode, PDO::PARAM_STR);
		$sth->execute();
		$user = $sth->fetch(PDO::FETCH_ASSOC);

		if($user){
			if($data['email'] != $user['email']){
				$sth = $db->prepare('SELECT 1 FROM admin WHERE email=:email AND id!=:id LIMIT 1');
				$sth->bindValue(':email', $data['email'], PDO::PARAM_STR);
				$sth->bindValue(':id', $user['id'], PDO::PARAM_INT);
				$sth->execute();
				if($sth->fetchColumn()){
					throw new Exception('Adres email jest już zajęty');
				}
			}
			
			$sth = $db->prepare('UPDATE admin SET email=:email, password=:password WHERE id=:id LIMIT 1');
			$sth->bindValue(':email', $data['email'], PDO::PARAM_STR);
			$sth->bindValue(':password', $this->createPassword($data['password']), PDO::PARAM_STR);
			$sth->bindValue(':id', $user['id'], PDO::PARAM_INT);
			$sth->execute();
		}else{
			throw new Exception('Nie znaleziono użytkownika');
		}
	}
	
	public function addUser($data){
		global $db;

		if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
			throw new Exception('Nieprawidłowy adres email');
		}

		$sth = $db->prepare('SELECT 1 FROM admin WHERE email=:email LIMIT 1');
		$sth->bindValue(':email', $data['email'], PDO::PARAM_STR);
		$sth->execute();
		if(!$sth->fetchColumn()){
			$sth = $db->prepare('INSERT INTO admin (email, password) VALUES(:email, :password)');
			$sth->bindValue(':email', $data['email'], PDO::PARAM_STR);
			$sth->bindValue(':password', $this->createPassword($data['password']), PDO::PARAM_STR);
			$sth->execute();
		}else{
			throw new Exception('Adres email jest już zajęty');
		}			
	}
	
	public function removeUser(int $id, ?string $token){
		global $db;

		if (!$token) {
			throw new Exception('Brak tokenu autoryzacji');
		}

		$sth = $db->prepare('SELECT a.id FROM admin_session ase, admin a WHERE a.id = ase.user_id AND ase.code=:code LIMIT 1');
		$sth->bindValue(':code', $token, PDO::PARAM_STR);
		$sth->execute();
		$user = $sth->fetch(PDO::FETCH_ASSOC);
		if($user){
			if($user['id'] == $id){
				throw new Exception('Nie można usunąć zalogowanego użytkownika');
			}
			$sth = $db->prepare('DELETE FROM admin WHERE id=:id LIMIT 1');
			$sth->bindValue(':id', $id, PDO::PARAM_INT);
			$sth->execute();
		}else{
			throw new Exception('Nie można znaleźć użytkownika');
		}
	}
	
	public function logOutAll(){
		global $db;
		$db->query('TRUNCATE admin_session');
	}
}
