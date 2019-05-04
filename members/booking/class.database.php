<?PHP
	class Database
	{
		var $onError   = ""; // die, email, or nothing
		var $errorTo   = "email@domain.com";
		var $errorFrom = "errors@domain.com";
		var $errorPage = "database-error.php";

		var $db;
		var $dbname;
		var $host;
		var $password;
		var $queries;
		var $result;
		var $user;
		var $redirect = false;

		function __construct($host, $user, $password, $dbname = null)
		{
			$this->host     = $host;
			$this->user     = $user;
			$this->password = $password;
			$this->dbname   = $dbname;			
			$this->queries  = array();
		}
		
		function connect()
		{
			$this->db = mysql_connect($this->host, $this->user, $this->password) or $this->notify();
			if(!empty($this->dbname))
				mysql_select_db($this->dbname, $this->db) or $this->notify();
		}

		function query($sql)
		{
			$this->queries[] = $sql;
			$this->result = mysql_query($sql, $this->db) or $this->notify();
			return $this->result;
		}

		// You can pass in nothing, a string, or a db result
		function getValue($arg = null)
		{
			if(is_null($arg) && $this->isValid())
				return mysql_result($this->result, 0, 0);
			elseif(is_resource($arg) && $this->isValid($arg))
				return mysql_result($arg, 0, 0);
			elseif(is_string($arg))
			{
				$this->query($arg);
				if($this->isValid())
					return mysql_result($this->result, 0, 0);
			}
			return false;
		}

		function numRows($arg = null)
		{
			if(is_null($arg) && $this->isValid())
				return mysql_num_rows($this->result);
			elseif(is_resource($arg) && $this->isValid($arg))
				return mysql_num_rows($arg);
			elseif(is_string($arg))
			{
				$this->query($arg);
				if($this->isValid())
					return mysql_num_rows($this->result);
			}
			return false;
		}

		// You can pass in nothing, a string, or a db result
		function getRow($arg = null)
		{
			if(is_null($arg) && $this->isValid())
				return mysql_fetch_array($this->result, MYSQL_ASSOC);
			elseif(is_resource($arg) && $this->isValid($arg))
				return mysql_fetch_array($arg, MYSQL_ASSOC);
			elseif(is_string($arg))
			{
				$this->query($arg);
				if($this->isValid())
					return mysql_fetch_array($this->result, MYSQL_ASSOC);
			}
			return false;
		}

		function getRows($arg = null)
		{
			if(is_null($arg) && $this->isValid())
				$result = $this->result;
			elseif(is_resource($arg) && $this->isValid($arg))
				$result = $arg;
			elseif(is_string($arg))
			{
				$this->query($arg);
				if($this->isValid())
					$result = $this->result;
				else
					return array();
			}
			else
				return array();

			$rows = array();
			mysql_data_seek($result, 0);
			while($row = mysql_fetch_array($result, MYSQL_ASSOC))
				$rows[] = $row;
			return $rows;
		}

		// You can pass in nothing, a string, or a db result
		function getObject($arg = null)
		{
			if(is_null($arg) && $this->isValid())
				return mysql_fetch_object($this->result);
			elseif(is_resource($arg) && $this->isValid($arg))
				return mysql_fetch_object($arg);
			elseif(is_string($arg))
			{
				$this->query($arg);
				if($this->isValid())
					return mysql_fetch_object($this->result);
			}
			return false;
		}

		function getObjects($arg = null)
		{
			if(is_null($arg) && $this->isValid())
				$result = $this->result;
			elseif(is_resource($arg) && $this->isValid($arg))
				$result = $arg;
			elseif(is_string($arg))
			{
				$this->query($arg);
				if($this->isValid())
					$result = $this->result;
				else
					return array();
			}
			else
				return array();

			$objects = array();
			mysql_data_seek($result, 0);
			while($object = mysql_fetch_object($result))
				$objects[] = $object;
			return $objects;
		}

		function isValid($result = null)
		{
			if(is_null($result)) $result = $this->result;
			return is_resource($result) && (mysql_num_rows($result) > 0);
		}

		function quote($var) { return "'" . mysql_real_escape_string($var, $this->db) . "'"; }
		function quoteParam($var) { return $this->quote($this->fix_slashes($_REQUEST[$var])); }
		function numQueries() { return count($this->queries); }
		function lastQuery() { return $this->queries[count($this->queries) - 1]; }

		function fix_slashes($arr = "")
		{
			if(empty($arr)) return;
			if(!get_magic_quotes_gpc()) return $arr;
			return is_array($arr) ? array_map('fix_slashes', $arr) : stripslashes($arr);
		}

		function notify()
		{
			global $auth;
			
			$err_msg = mysql_error($this->db);
			error_log($err_msg);

			switch($this->onError)
			{
				case "die":
					echo "<p style='border:5px solid red;background-color:#fff;padding:5px;'><strong>Database Error:</strong><br/>$err_msg</p>";
					echo "<p style='border:5px solid red;background-color:#fff;padding:5px;'><strong>Last Query:</strong><br/>" . $this->lastQuery() . "</p>";
					echo "<pre>";
					debug_print_backtrace();
					echo "</pre>";
					die();
					break;
				
				case "email":
					$msg  = $_SERVER['PHP_SELF'] . " @ " . date("Y-m-d H:ia") . "\n";
					$msg .= $err_msg . "\n\n";
					$msg .= implode("\n", $this->queries) . "\n\n";
					$msg .= "CURRENT USER\n============\n"     . var_export($auth, true)  . "\n" . $_SERVER['REMOTE_ADDR'] . "\n\n";
					$msg .= "POST VARIABLES\n==============\n" . var_export($_POST, true) . "\n\n";
					$msg .= "GET VARIABLES\n=============\n"   . var_export($_GET, true)  . "\n\n";
					mail($this->errorTo, $_SERVER['PHP_SELF'], $msg, "From: {$this->errorFrom}");
					break;
			}

			if($this->redirect === true)
			{
				header("Location: {$this->errorPage}");
				exit();
			}			
		}
	}
?>