<?PHP

	// Example sub class
	//
	// class User extends DBObject
	// {
	//	function __construct($id = "")
	// 	{                        table    primary_key      column names                             [load record with this id]
	// 		parent::__construct('users', 'user_id', array('username', 'password', 'level', 'email'), $id);
	// 	}
	// }
	//

	class DBObject
	{
		public $id;
		public $id_name;
		public $table_name;
		private $columns = array();

		function __construct($table_name, $id_name, $columns, $id = "")
		{
			$this->table_name = $table_name;
			$this->id_name = $id_name;

			foreach($columns as $key)
				$this->columns[$key] = null;
				
			if($id != "")
				$this->select($id);
		}

		function __get($key)
		{
			return $this->columns[$key];
		}

		function __set($key, $value)
		{
			if(array_key_exists($key, $this->columns))
			{
				$this->columns[$key] = $value;
				return true;
			}
			return false;
		}

		function select($id, $column = "")
		{
			global $db;
			
			if($column == "") $column = $this->id_name;

			$id = mysqli_real_escape_string($db->db, $id);
			$column = mysqli_real_escape_string($db->db, $column);

			$db->query("SELECT * FROM " . $this->table_name . " WHERE `$column` = '$id'");
			if(mysqli_num_rows($db->result) == 0)
				return false;
			else
			{
				$this->id = $id;
				$row = mysqli_fetch_array($db->result, MYSQLI_ASSOC);
				foreach($row as $key => $val)
					$this->columns[$key] = $val;
			}
		}

		function replace()
		{
			return $this->insert("REPLACE INTO");
		}

		function insert($cmd = "INSERT INTO")
		{
			global $db;
			
			if(count($this->columns) > 0)
			{
				unset($this->columns[$this->id_name]);

				$columns = "`" . join("`, `", array_keys($this->columns)) . "`";
				$values  = "'" . join("', '", $this->quote_column_vals()) . "'";

				$db->query("$cmd " . $this->table_name . " ($columns) VALUES ($values)");

				$this->id = mysqli_insert_id($db->db);
				return $this->id;
			}
		}

		function update()
		{
			global $db;

			$arrStuff = array();
			unset($this->columns[$this->id_name]);
			foreach($this->quote_column_vals() as $key => $val)
				$arrStuff[] = "`$key` = '$val'";
			$stuff = implode(", ", $arrStuff);
			
			$id = mysqli_real_escape_string($this->id, $db->db);
		
			$db->query("UPDATE " . $this->table_name . " SET $stuff WHERE " . $this->id_name . " = '" . $id . "'");
			return mysql_affected_rows($db->db); // Not always correct due to mysql update bug/feature
		}

		function delete()
		{
			global $db;
			$id = mysqli_real_escape_string($this->id, $db->db);
			$db->query("DELETE FROM " . $this->table_name . " WHERE `" . $this->id_name . "` = '" . $id . "'");
			return mysql_affected_rows($db->db);
		}
		
		function postload() { $this->load($_POST); }
		function getload()  { $this->load($_GET); }
		function load($arr)
		{
			if(is_array($arr))
			{
				foreach($arr as $key => $val)
					if(array_key_exists($key, $this->columns) && $key != $this->id_name)
						$this->columns[$key] = fix_slashes($val);
				return true;
			}
			else
				return false;
		}
		
		function quote_column_vals()
		{
			global $db;
			$columnVals = array();
			foreach($this->columns  as $key => $val)
				$columnVals[$key] = mysqli_real_escape_string($val, $db->db);
			return $columnVals;
		}
	}
?>
