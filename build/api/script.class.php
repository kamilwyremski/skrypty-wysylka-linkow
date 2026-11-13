<?php

define('_FOLDER_SCRIPTS_',realpath(dirname(__FILE__)).'/upload/');

class Script{
	
	public static function list(){
		global $db;
		$scripts = [];
		$sth = $db->query('SELECT * FROM scripts ORDER BY name');
		while($row = $sth->fetch(PDO::FETCH_ASSOC)) {
			$row['filesize'] = filesize_formatted($row['filesize']);
			$scripts[] = $row;
		}
		return $scripts;
	}
	
	public static function show(int $id){
		global $db;
		$sth = $db->prepare('SELECT * FROM scripts WHERE id=:id LIMIT 1');
		$sth->bindValue(':id', $id, PDO::PARAM_INT);
		$sth->execute();
		return $sth->fetch(PDO::FETCH_ASSOC);
	}
	
	public static function add(string $name){
		global $db;
		
		if (empty($_FILES['file']['name']) || !isset($_FILES['file']['error']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($_FILES['file']['tmp_name'])) {
			throw new Exception('Błąd przesyłania pliku');
		}

		$filename = basename($_FILES['file']['name']);
		$filename = preg_replace('/[^\w\.\-\s]/u', '', $filename);
		if (empty($filename)) {
			$filename = 'script_' . time();
		}

		do{
			$file = bin2hex(random_bytes(16));	
		}
		while(file_exists(_FOLDER_SCRIPTS_.$file));
		
		if (!move_uploaded_file($_FILES['file']['tmp_name'], _FOLDER_SCRIPTS_.$file)) {
			throw new Exception('Nie udało się zapisać pliku na serwerze');
		}
		
		$sth = $db->prepare('INSERT INTO `scripts`(`name`, `filename`, `file`, `filesize`, `date`) VALUES (:name,:filename,:file,:filesize,NOW())');
		$sth->bindValue(':name', $name, PDO::PARAM_STR);
		$sth->bindValue(':filename', $filename, PDO::PARAM_STR);
		$sth->bindValue(':file', $file, PDO::PARAM_STR);
		$sth->bindValue(':filesize', (int)$_FILES['file']['size'], PDO::PARAM_INT);
		$sth->execute();
	}
	
	public static function edit(array $data){
		global $db;
		
		$script = static::show((int)$data['id']);
		if($script){
			if(!empty($_FILES['file']['name'])){
				if (!isset($_FILES['file']['error']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($_FILES['file']['tmp_name'])) {
					throw new Exception('Błąd przesyłania nowego pliku');
				}

				$oldFile = _FOLDER_SCRIPTS_ . basename($script['file']);
				if(!empty($script['file']) && file_exists($oldFile)){
					unlink($oldFile);
				}

				do{
					$file = bin2hex(random_bytes(16));	
				}
				while(file_exists(_FOLDER_SCRIPTS_.$file));

				if (!move_uploaded_file($_FILES['file']['tmp_name'], _FOLDER_SCRIPTS_.$file)) {
					throw new Exception('Nie udało się zapisać nowego pliku na serwerze');
				}

				$filename = basename($_FILES['file']['name']);
				$filename = preg_replace('/[^\w\.\-\s]/u', '', $filename);
				if (empty($filename)) {
					$filename = 'script_' . time();
				}
				$filesize = (int)$_FILES['file']['size'];
			}else{
				$filename = preg_replace('/[^\w\.\-\s]/u', '', basename($data['filename']));
				$file = basename($script['file']);
				$filesize = (int)$script['filesize'];
			}
			
			$sth = $db->prepare('UPDATE `scripts` SET `name`=:name, `filename`=:filename, `file`=:file, `filesize`=:filesize, `date`=NOW() WHERE id=:id LIMIT 1');
			$sth->bindValue(':name', $data['name'], PDO::PARAM_STR);
			$sth->bindValue(':filename', $filename, PDO::PARAM_STR);
			$sth->bindValue(':file', $file, PDO::PARAM_STR);
			$sth->bindValue(':filesize', $filesize, PDO::PARAM_INT);
			$sth->bindValue(':id', (int)$data['id'], PDO::PARAM_INT);
			$sth->execute();
		}
	}
	
	public static function listLogs(){
		global $db;
		$logs_download = [];
		$sth = $db->query('SELECT ld.*, s.name AS script_name
		FROM logs_download ld
		LEFT JOIN scripts s ON ld.script_id = s.id 
		ORDER BY ld.id DESC');
		while($row = $sth->fetch(PDO::FETCH_ASSOC)) {
			$logs_download[] = $row;
		}
		return $logs_download;
	}
	
	public static function remove(int $id){
		global $db;
		
		$script = static::show($id);
		if($script){
			$filePath = _FOLDER_SCRIPTS_ . basename($script['file']);
			if (!empty($script['file']) && file_exists($filePath)) {
				unlink($filePath);
			}
			$sth = $db->prepare('DELETE FROM scripts WHERE id=:id LIMIT 1');
			$sth->bindValue(':id', $id, PDO::PARAM_INT);
			$sth->execute();
		}
	}
}
