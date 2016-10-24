<?php

class DBHandler
{
	const TABLE_EXPOSES_TRUE = 'Y';
	const TABLE_EXPOSES_FALSE = 'N';
	const TABLE_EXPOSES_MAYBE = 'M';

	private $db;
	private $TABLE_EXPOSES = 'create table if not exists exposes 
						(id varchar(20), name text, price_warm int, price_cold int, last_seen int, 
						first_seen int, score int, pets varchar(1),
						zipcode varchar(10), city varchar(50), dist_work int,
						kausion int, url text, collected varchar(1), rooms int, size int,
						online varchar(1), next_check int, floor varchar(50), access varchar(50),
						street varchar(50), next_mail int);';
	private $CONFIG;

	function __construct($config)
	{
		$this->CONFIG = $config;
		$this->db = new SQLite3($this->CONFIG['db_file_path']);
		$this->db->query($this->TABLE_EXPOSES);
	}


	function Store_Expose($expose)
	{
		$query = 'insert into exposes(';
		$itr = 0;
		foreach ($expose as $key => $value) 
		{
			$query .= $key;
			$query .= ($itr < count($expose) -1 ? ', ' : ') ');
			$itr++;
		}
		$query .= 'values(';
		$itr = 0;
		foreach ($expose as $key => $value) 
		{
			$query .= ':'.$key;
			$query .= ($itr < count($expose) -1 ? ', ' : ')');
			$itr++;
		}
		$stmt = $this->db->prepare($query);
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
			$list[] = $this->_clean_expose_result($row);
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
            $list[] = $this->_clean_expose_result($row);
		}
		return reset($list);
	}


    function Get_Exposes_For_Scorecalc()
    {
        $stmt = $this->db->prepare('select * from exposes where online != :online');
        $stmt->bindValue(':online', DBHandler::TABLE_EXPOSES_FALSE);
        $result = $stmt->execute();
        $list = array();
        while ($row = $result->fetchArray()) {
            $list[] = $this->_clean_expose_result($row);
        }
        return $list;
    }


    function Get_Exposes_By_Score($score)
    {
        $stmt = $this->db->prepare('select * from exposes where score > :score and next_mail < :now');
        $stmt->bindValue(':score', $score);
        $stmt->bindValue(':now', time());
        $result = $stmt->execute();
        $list = array();
        while ($row = $result->fetchArray()) {
            $list[] = $this->_clean_expose_result($row);
        }
        return $list;
    }


	function Update_Expose($expose) 
	{
		$query = 'update exposes set ';
		$itr = 0;
		foreach ($expose as $key => $value) 
		{
			if ($key != 'id')
			{
				$query .= $key.'=:'.$key;
				$query .= ($itr < count($expose) -1 ? ', ' : ' ');
			}
			$itr++;
		}
		$query .= 'where id=:id';
		$stmt = $this->db->prepare($query);
		foreach ($expose as $key => $value) 
		{
			$stmt->bindValue(':'.$key, $value);
		}
		$result = $stmt->execute();	
	}


	function Get_default_expose()
	{
		return 	array(
			'id' => '',
			'name' => '',
			'price_warm' => 0,
			'price_cold' => 0,
			'first_seen' => 0,
			'last_seen' => 0,
			'score' => 0,
			'pets' => '',
			'zipcode' => '',
			'city' => '',
			'dist_work' => 0,
			'kausion' => 0,
			'url' => '',
			'collected' => '',
			'rooms' => 0,
			'size' => 0,
			'online' => '',
			'next_check' => 0,
			'floor' => '',
			'access' => '',
			'next_mail' => 0
		);
	}


	function _clean_expose_result($expose)
	{
		$default_expose = $this->Get_default_expose();
		$result = $this->Get_default_expose();
		foreach ($default_expose as $key => $value)
		{
			$result[$key] = $expose[$key];
		}
		return $result;
	}
}


?>
