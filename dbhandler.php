<?php

class DBHandler
{
	const TABLE_EXPOSES_TRUE = 'Y';
	const TABLE_EXPOSES_FALSE = 'N';

	private $db;
	private $TABLE_EXPOSES = 'create table if not exists exposes 
						(id varchar(20), name text, price_warm int, price_cold int, last_seen int, 
						first_seen int, care varchar(1), pets varchar(1),
						zipcode varchar(10), city varchar(30), dist_work int,
						kausion int, url text, collected varchar(1), rooms int, size int,
						online varchar(1), next_check int);';

	function __construct()
	{
		$this->db = new SQLite3('list.db');
		$this->db->query($this->TABLE_EXPOSES);
	}


	function Store_Expose($expose)
	{
		$stmt = $this->db->prepare('insert into exposes(id, name, price_warm, price_cold, first_seen, 
									last_seen, care, pets, zipcode, city, dist_work, kausion, url, 
									collected, rooms, size, online, next_check) values(:id, :name, :price_warm,
									:price_cold, :first_seen, :last_seen, :care, :pets, :zipcode,
									:city, :dist_work, :kausion, :url, :collected, :rooms, :size, :online,
									:next_check)');
		foreach ($expose as $key => $value) 
		{
			$stmt->bindValue(':'.$key, $value);
		}
		$result = $stmt->execute();	
	}


	function Get_Exposes_For_Collection()
	{
		$stmt = $this->db->prepare('select * from exposes where next_check < :now and online != :online');
		$stmt->bindValue(':now', time());
		$stmt->bindValue(':online', DBHandler::TABLE_EXPOSES_FALSE);
		$result = $stmt->execute();		
		$list = array();
		while ($row = $result->fetchArray()) {
			$list[] = $row;
		}
		return $list;
	}


	function Get_Expose_By_Id($id)
	{
		$stmt = $this->db->prepare('select * from exposes where id = :id');
		$stmt->bindValue(':id', $id);
		$result = $stmt->execute();		
		$list = array();
		while ($row = $result->fetchArray()) {
			$list[] = $row;
		}
		return reset($list);
	}
}


?>