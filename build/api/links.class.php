<?php

class Links{
	
	public static function list(){
		global $db;
		$links = [];
		$sth = $db->query('SELECT l.*, s.name AS script_name
		FROM links l
		LEFT JOIN scripts s ON l.script_id = s.id 
		ORDER BY l.id DESC');
		while($row = $sth->fetch(PDO::FETCH_ASSOC)) {
			$row['active'] = static::checkActive($row);
			$row['link'] = static::generateLink($row['link']);
			$links[] = $row;
		}
		return $links;
	}
	
	public static function checkActive(array $link){
		if($link['download_limit'] > 0 and date("Y-m-d") <= $link['date_finish']){
			return true;
		}
		return false;
	}
	
	public static function generateLink(string $link){
		return _BASE_URL_.'/download.php?link='.$link;
	}
	
	public static function add(array $data){
		global $db;

		if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
			throw new Exception('Nieprawidłowy adres email');
		}

		$sth = $db->prepare('INSERT INTO `links`(`script_id`, `email`, `license_number`, `link`, `date_finish`, `download_limit`, `date`) VALUES (:script_id,:email,:license_number,:link,:date_finish,:download_limit,NOW())');
		$sth->bindValue(':script_id', (int)$data['script_id'], PDO::PARAM_INT);
		$sth->bindValue(':email', $data['email'], PDO::PARAM_STR);
		$sth->bindValue(':license_number', (int)($data['license_number'] ?? 0), PDO::PARAM_INT);
		$sth->bindValue(':link', bin2hex(random_bytes(16)), PDO::PARAM_STR);
		$sth->bindValue(':date_finish', $data['date_finish'], PDO::PARAM_STR);
		$sth->bindValue(':download_limit', (int)$data['download_limit'], PDO::PARAM_INT);
		$sth->execute();
	}
	
	public static function edit(array $data){
		global $db;
		
		if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
			throw new Exception('Nieprawidłowy adres email');
		}

		$sth = $db->prepare('UPDATE `links` SET `script_id`=:script_id, `email`=:email, `license_number`=:license_number, `date_finish`=:date_finish, `download_limit`=:download_limit WHERE id=:id LIMIT 1');
		$sth->bindValue(':script_id', (int)$data['script_id'], PDO::PARAM_INT);
		$sth->bindValue(':email', $data['email'], PDO::PARAM_STR);
		$sth->bindValue(':license_number', (int)($data['license_number'] ?? 0), PDO::PARAM_INT);
		$sth->bindValue(':date_finish', $data['date_finish'], PDO::PARAM_STR);
		$sth->bindValue(':download_limit', (int)$data['download_limit'], PDO::PARAM_INT);
		$sth->bindValue(':id', (int)$data['id'], PDO::PARAM_INT);
		$sth->execute();
	}
	
	public static function show(int $id){
		global $db;
		$sth = $db->prepare('SELECT * FROM links WHERE id=:id LIMIT 1');
		$sth->bindValue(':id', (int)$id, PDO::PARAM_INT);
		$sth->execute();
		return $sth->fetch(PDO::FETCH_ASSOC);
	}
	
	public static function sendMail(int $id, string $subject, string $content){
		global $db;
		$link = static::show($id);
		if($link && filter_var($link['email'], FILTER_VALIDATE_EMAIL)){
			// Sanitize subject to prevent CRLF injection
			$cleanSubject = str_replace(["\r", "\n"], '', $subject);
			$header = "From: skrypty@itworksbetter.net\r\n"; 
			$header .= "Reply-To: skrypty@itworksbetter.net\r\n"; 
			$header .= "MIME-Version: 1.0\r\n"; 
			$header .= "Content-Type: text/plain; charset=utf-8\r\n"; 
			$message = $content;
			$subject64 = '=?utf-8?B?'.base64_encode($cleanSubject).'?=';
			if(mail($link['email'], $subject64, $message, $header)){
				return true;
			}
		}
		return false;
	}
	
	public static function remove(int $id){
		global $db;
		$sth = $db->prepare('DELETE FROM links WHERE id=:id LIMIT 1');
		$sth->bindValue(':id', $id, PDO::PARAM_INT);
		$sth->execute();
	}
}
