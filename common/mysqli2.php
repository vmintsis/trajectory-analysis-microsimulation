<?php
/** ============================================
 *	php - MySQLI Server Database Connection Class
 *
 *	DNT Oct 2014 - Dimitrios Tsobanopoulos
 * ============================================ */

final class MySQLI2 {
	
	private $connection;
	private $hostname;
	private $username;
	private $password;
	private $database;
	private $ret_status;
	
	public function __construct($hostname, $username, $password, $database) 
	{
		try{
			$this->hostname = $hostname;
			$this->username = $username;
			$this->password = $password;
			$this->database = $database;
			$this->ret_status = "UNINITIALIZED";
			
			if (!$this->connection = @mysqli_connect($hostname, $username, $password)) 
			{
				$this->ret_status = "ERROR: ".mysqli_connect_error();
				// throw new Exception('Error: Could not make a database connection using ' . $username . '@' . $hostname);
			}
			else {
				try{
					if (!mysqli_select_db($this->connection, $database)) {
						$this->ret_status = 'Error: Could not connect to database ' . $database;
						// throw new Exception('Error: Could not connect to database ' . $database);
					} else {
						mysqli_query($this->connection, "SET NAMES 'utf8'");
						mysqli_query($this->connection, "SET CHARACTER SET utf8");
						mysqli_query($this->connection, "SET CHARACTER_SET_CONNECTION=utf8");
						mysqli_query($this->connection, "SET SQL_MODE = ''");
						
						$this->ret_status = "OK";
					}
				} 
				catch (Exception $select_ex) 
				{
					$this->ret_status = "ERROR: ".$select_ex->getMessage()."".$select_ex->getFile()."".$select_ex->getLine();
				}
			}
		} 
		catch (Exception $connect_ex) 
		{
			$this->ret_status = "ERROR: ".$connect_ex->getMessage()."".$connect_ex->getFile()."".$connect_ex->getLine();
		} 
  	}
		
	// DNT 24-July-2014
	public function prepare_stmt($sql)
	{
		$stmt = @mysqli_prepare($this->connection, $sql);
		if (!$stmt) $this->ret_status = "ERROR: ".mysqli_error($this->connection);
		return $stmt;
	}
	public function bind_stmt(&$stmt, $types, $data, $data1=null, $data2=null)
	{
		if ($stmt)
		{
			if (is_null($data1))
				return mysqli_stmt_bind_param($stmt, $types, $data);
			else
			if (is_null($data2))
				return mysqli_stmt_bind_param($stmt, $types, $data, $data1);
			else
				return mysqli_stmt_bind_param($stmt, $types, $data, $data1, $data2);
		}
		else 
		{
			return null;   // status should already be set
		}
	}
	public function exec_stmt($stmt)
	{
//		$t = amd_timer();
		$r = @mysqli_stmt_execute($stmt);
		$res = array();
		if ($r === false)
			$res["error"] = $this->ret_status = "ERROR: ".mysqli_stmt_error($stmt);
		else
		{
			mysqli_stmt_store_result($stmt);

			$meta = mysqli_stmt_result_metadata($stmt);
			if ($meta)
			{
			
				$bindVarsArray = array();
				$result = array();
				$fields = mysqli_fetch_fields($meta);
				for ($i=0; $i< count($fields); $i++)
					$bindVarsArray[] = &$result[$fields[$i]->name];

				call_user_func_array(array($stmt, 'bind_result'), $bindVarsArray);
		   
				$meta->close(); 
				
				$i=0;
				while(mysqli_stmt_fetch($stmt))
				{
					$f = 0;
					foreach($result as $fld => $val)
					{
						// DNT 24-July-2014, fix for prepared statements decimal bug: too many decimals are returned with bogus values
						if ($fields[$f]->name == $fld && ($fields[$f]->type == 4 || $fields[$f]->type == 5) )
	//						$val = round($val, $fields[$f]->decimals);
							$val = number_format($val, $fields[$f]->decimals);	// non-prepared returns strings
						$res[$i][$fld] = $val;
						$f++;
					}
					$i++;
				}

				unset($result);

				$this->ret_status = "OK";

			}
		}
	//		$t = amd_timer() - $t;
	//		echo ("++++++  $t  sec ++++++\n");
		return $res;
	}
	public function close_stmt($stmt)
	{
		mysqli_stmt_close($stmt);
	}
	
  	public function query($sql)
	{
		if ($this->connection) 
		{
			try {
				$resource = mysqli_query($this->connection, $sql);
			
				$data = array();
				if ($resource)
				{
					if ($resource instanceof mysqli_result)
					{
						$i = 0;
						while ($result = mysqli_fetch_assoc($resource)) 
						{
							$data[$i] = $result;
							$i++;
						}
						
						mysqli_free_result($resource);
						$this->ret_status = "OK";
					}
					elseif (mysqli_errno($this->connection) != 0)
					{
						$data["error"] = $this->ret_status = 'ERROR: ' . mysqli_error($this->connection) . ' ErrNo: ' . mysqli_errno($this->connection);
					}
					else  // else not an error, query returned no data, hence we get "OK" and empty array
						$this->ret_status = "OK";
				}
				elseif (mysqli_errno($this->connection) != 0)
				{
					$data["error"] = $this->ret_status = 'ERROR: ' . mysqli_error($this->connection) . ' ErrNo: ' . mysqli_errno($this->connection);
				}
				else  // else not an error, query returned no data, hence we get "OK" and empty array
					$this->ret_status = "OK";
			} catch (Exception $query_ex) 
			{
				$data["error"] = $this->ret_status = 'ERROR: '. $query_ex->toString();
			}
		}
		else
			$data["error"] = $this->ret_status = 'ERROR: not connected';
			
		return $data;	
  	}
	
	public function escape($value) 
	{
		if ($this->connection)
			return mysqli_real_escape_string($this->connection, $value);
		else
		{
			if ($this->ret_status != "OK") 
				$this->ret_status = "Error: no connection, cannot escape (".$this->ret_status.")";
			return $value;
		}
	}
	
  	public function countAffected() 
	{
		if ($this->connection)
	    	return mysqli_affected_rows($this->connection);
		else
		{
			if ($this->ret_status != "OK") 
				$this->ret_status = "Error: no connection, no affected rows (".$this->ret_status.")";
			return 0;
		}
  	}

  	public function getLastId() 
	{
		if ($this->connection)
	    	return mysqli_insert_id($this->connection);
		else
		{
			if ($this->ret_status != "OK") 
				$this->ret_status = "Error: no connection, cannot determine last autoincrement (".$this->ret_status.")";
			return $value;
		}
  	}
	
	public function status() 
	{
		return $this->ret_status;
	}
	
	public function shutdown()
	{
		if ($this->connection)
		{
			mysqli_close($this->connection);
			$this->connection = null;
		}
	}
	
	public function __destruct() 
	{
		$this->shutdown();
	}
}

?>
