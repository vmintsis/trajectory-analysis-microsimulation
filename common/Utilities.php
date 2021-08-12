<?php


function tr_assert($assertion, $message)
{
    if (!$assertion) {
        echo "***Assertion failed: ".$message;
        die(-1);
    }
}

/**
 * Creates an application-specific session cookie and closes the session file.
 * This allows 
 * 
 * @global type $version
 * @param type $prefix
 * @param type $name
 * @return type
 */
function SessionInit2($prefix, $name = null)
{
	global $version;
	
	session_start();
	if (!isset($_SESSION["S_NAME"]))
	{
		if (is_null($name))
			$s_name = $prefix."-".str_replace(".", "-", $version)."-".uniqid();
		else
			$s_name = $name;

		$sid = session_id($s_name);
		if (empty($sid) || $sid != $s_name)
		{
			session_destroy();
			session_id($s_name);
			session_start();
			$_SESSION["S_NAME"] = $s_name;
		}
	}
	else
		$sid = $_SESSION["S_NAME"];

	session_write_close();  // unlock session storage so we can run async ajax calls

	return $sid;
}

/**
 * 	Returns the current UNIX timestamp in msec
 *
 */
function timer()
{
    $time = microtime();
    $time = explode(' ', $time);
    $time = $time[1] + $time[0];
    return $time;
}

/**
 * 	performs a simple rolling XOR encryption on the $str argument
 * 	@param $str 	a string of arbitrary length to scramble
 * 	@param $key 	a string acting as a key of arbitrary length
 * 	@return complex string	the return value is a string that may contain any character including zeros
 *
 */
function XORCrypt($str, $key)
{
    $klen = strlen($key);
    $slen = strlen($str);
    $j = 0;
    for ($i = 0; $i < $slen; $i++)
	{
        $str[$i] = $str[$i] ^ $key[$j];
        $j = ($j + 1) % $klen;
    }
    return $str;
}

/**
 * 	Converts a MYSQLI_TYPE_xxxx constant to string
 * 	@param $type_id 	a mysqli constant
 * 	@return string a string representation if the given constant is valid, NULL otherwise
 *
 */
function h_type2txt($type_id)
{
    static $types;

    if (!isset($types))
	{
        $types = array();
        $constants = get_defined_constants(true);
        foreach ($constants['mysqli'] as $c => $n)
            if (preg_match('/^MYSQLI_TYPE_(.*)/', $c, $m))
                $types[$n] = $m[1];
    }

    return array_key_exists($type_id, $types) ? $types[$type_id] : NULL;
}

/**
 * 	Converts an array of MYSQLI_XXXX_NNNN flags to an array of strings
 * 	@param $flags_num 	a mysqli flag array
 * 	@return array array of strings for each of the valid flags in the input array - all invalid flags are excluded
 *
 */
function h_flags2txt($flags_num)
{
    static $flags;

    if (!isset($flags))
	{
        $flags = array();
        $constants = get_defined_constants(true);
        foreach ($constants['mysqli'] as $c => $n)
            if (preg_match('/MYSQLI_(.*)_FLAG$/', $c, $m))
                if (!array_key_exists($n, $flags))
                    $flags[$n] = $m[1];
    }

    $result = array();
    foreach ($flags as $n => $t)
        if ($flags_num & $n)
            $result[] = $t;
    return implode(' ', $result);
}

/**
 * 	Find the IP of the clhttp request caller. It also filters and validates the IP.
 * 	@return string	The IP of the caller in string format
 *
 */
function getCallerIP()
{
    if (isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP']))
	{
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
	elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR']))
	{
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
	else
	{
        $ip = (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : '<Unknown IP>';
    }

    $ip = filter_var($ip, FILTER_VALIDATE_IP);
    $ip = ($ip === false) ? '<Invalid IP>' : $ip;
    return $ip;
}

function ErrLog($msg)
{
    error_log(getCallerIP() . ":" . $msg);
}

/**
 * 	Executes an SQL query given a $dbinfo array containing the connection information
 * 	@param $sql		The sql statement to execute
 * 	@param $dninfo	An associative array with the connection info of the following form:
 * 					array("hostname" => "<ip of DB host>", 
 * 						  "username" => "<user name>", 
 * 						  "passwd" => "<user password>",
 * 						  "dbname" => "<name of the database to connect to>")
 * 	@return array	For select queries, returns an array(colname1 => array(v1, v2, ...),  colname2 => array(k1, k2, ...), ...)
 * 					For other queries, returns an empty array()
 * 					In case of error, returns array( "error" => "error string...")
 */
function ExecSQL($sql, $dbinfo)
{
    return ExecSQLReal($sql, $dbinfo["hostname"], $dbinfo["username"], $dbinfo["passwd"], $dbinfo["dbname"]);
}

/**
 * 	Executes an SQL query given a $dbinfo array containing the connection information
 *
 *  11-Dec-2014 dnt: Added mysql charset setting utf8 to the connection
 * 
 * 	This is called by ExecSQL with parameters expanded.
 * 	@sa ExecSQL
 * 
 */
function ExecSQLReal($sql, $host, $user, $pass, $db)
{
    $r = array();
    $mysqli = @new mysqli($host, $user, $pass);
    if (!mysqli_connect_errno())
	{
		$mysqli->query("SET NAMES 'utf8'");
		$mysqli->query("SET CHARACTER SET utf8");
		$mysqli->query("SET CHARACTER_SET_CONNECTION=utf8");
		$mysqli->query("SET SQL_MODE = ''");

        if (!$mysqli->select_db($db))
			$r["error"] = "***DBTBL [$db] " . $mysqli->error;
		else
		{
			$r1 = $mysqli->query($sql);
			if ($r1 instanceof mysqli_result)
			{
				$n = 0;
				while ($row = $r1->fetch_assoc())
				{
					if (empty($r))
					{
						$k = array_keys($row);
						for ($i = 0; $i < count($k); $i++)
							$r[$k[$i]] = array();
					}

					foreach ($row as $k => $v)
						$r[$k][$n] = $v;
					$n++;
				}

				mysqli_free_result($r1);
			} 
			elseif (!empty($mysqli->error))
				$r["error"] = "***SQL [$sql] " . $mysqli->error;
		}
        $mysqli->close();
    }
    else
	{
        $r["error"] = '***DB Connection Failed';
    }

    return $r;
}

if (!function_exists('getallheaders'))
{

    /**
     * 	Returns all HTTP_nnnn headers present in the current http call (if any)
     * 	@return array 	returns all headers in an assocative of type: array("header name" => "<value>")
     */
    function getallheaders()
	{
        $headers = null;
        foreach ($_SERVER as $name => $value)
		{
            if (substr($name, 0, 5) == 'HTTP_')
			{
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }

}

if (!function_exists('hex2bin'))
{

    /**
     * 	Converts a string that contains hex tuples in ASCII format to a binary string.
	 *
	 *	@param string $hexstr	The string og hex ascii digits to convert.
     * 							Each hex tuple is of the form "HL" where H is the high nibble and L is the low nibble using
     * 							the character set ["0".."9"] | ["A".."F"]
	 *
     * 	@return string 	a string where each character represents a tuple of the original
     */
    function hex2bin($hexstr)
	{
        $n = strlen($hexstr);
        $sbin = "";
        $i = 0;

        while ($i < $n)
		{
            $a = substr($hexstr, $i, 2);
            $c = @pack("H*", $a);

            if ($i == 0)
                $sbin = $c;
            else
                $sbin.=$c;
            $i += 2;
        }
        return $sbin;
    }

}

/*
  if( !function_exists( 'http_parse_headers' ) ) {
  function http_parse_headers( $header )
  {
  $retVal = array();
  $fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $header));
  foreach( $fields as $field ) {
  if( preg_match('/([^:]+): (.+)/m', $field, $match) ) {
  $match[1] = preg_replace('/(?<=^|[\x09\x20\x2D])./e', 'strtoupper("\0")', strtolower(trim($match[1])));
  if( isset($retVal[$match[1]]) ) {
  $retVal[$match[1]] = array($retVal[$match[1]], $match[2]);
  } else {
  $retVal[$match[1]] = trim($match[2]);
  }
  }
  }
  return $retVal;
  }
  }
 */

/**
 *
 */
if (!function_exists('http_parse_headers'))
{

    function http_parse_headers($raw_headers)
	{
        $headers = array(); // $headers = [];
        foreach (explode("\n", $raw_headers) as $i => $h)
		{
            $h = explode(':', $h, 2);
            if (isset($h[1]))
			{
                if (!isset($headers[$h[0]]))
				{
                    $headers[$h[0]] = trim($h[1]);
                }
				elseif (is_array($headers[$h[0]]))
				{
                    $tmp = array_merge($headers[$h[0]], array(trim($h[1])));
                    $headers[$h[0]] = $tmp;
                }
				else
				{
                    $tmp = array_merge(array($headers[$h[0]]), array(trim($h[1])));
                    $headers[$h[0]] = $tmp;
                }
            }
			else
			{
                $headers[] = $h[0];
            }
        }
        return $headers;
    }

}

/**
 * 	Generic logger class with file rotation
 *
 */
interface ILogger
{
	public function setMaxSize($sz);
	public function maxSize();
	public function setPrefixWithDate($ld);
	public function prefixWithDate();
	
	public function log($msg);
}

class Logger implements ILogger
{

    private $_log_file;
    private $_max_size = 999999;
    private $_log_date = true;

    /**
     * 	Constructor
     * 	Requires a log file path, and optionally the maximum length of each log file and whether to prepend a date/time per line or not.
     * 	@param	string $log_file (required)		The full or relative pathname of the log file
     * 	@param 	integer $max_size (optional)	The maximum size of a log file before it is rotated (def. 999999 bytes)
     * 	@param	string $log_date (optional)		Prepend each log line with a date/time stamp if true, log as is otherwise (def. true)
     */
    public function __construct($log_file, $max_size = 999999, $log_date = true)
	{
        $this->_log_file = $log_file;
        $this->_max_size = $max_size;
        $this->_log_date = $log_date;
    }

    /**
     * 	Set the maximum size of a log file
     * 	@param	integer $sz	The size in bytes
     */
    public function setMaxSize($sz)
	{
        $this->_max_size = $sz;
    }

    /**
     * 	@return Returns the current maximum size of a log file
     */
    public function maxSize()
	{
        return $this->_max_size;
    }

    /**
     * 	Enables or disables datatime prefixing of each log line
     * 	@param boolean $ld	true - enable,  false - disable
     */
    public function setPrefixWithDate($ld)
	{
        $this->_log_date = $ld;
    }

    /**
     * 	@return Returns the current setting of datatime prefixing of each log line
     */
    public function prefixWithDate()
	{
        return $this->_log_date;
    }

	/**
	 * @return Returns the original log filename 
	 */
	public function fileName()
	{
		return $this->_log_file;
	}
	
    /**
     * 	Logs a message to the log file using current log options.
     * 	When log file reaches the currently set maximum size, then it is renamed to \<current file name\>.\<current date time\>
     * 	and a new log file is started with the name originally specified.
     * 	@param string $msg	a string with the message to log 
     */
    public function log($msg)
	{
        $dt = "";
        if ($this->_log_date)
            $dt = "[" . date("Y-m-d_H:i:s") . substr((string) microtime(), 1, 6) . "] ";
        if (@filesize($this->_log_file) > $this->_max_size)
		{
            rename($this->_log_file, $this->_log_file . "." . date("d_M_Y_H_i"));
        }
        @file_put_contents($this->_log_file, $dt . $msg, FILE_APPEND);
    }

}

class RotationLogger extends Logger implements ILogger
{
	private $_num_files;
	private $_curFile = 0;

    /**
     * 	Constructor
     * 	Requires a log file path, and optionally the maximum length of each log file and whether to prepend a date/time per line or not.
     * 	@param	string $log_file (required)		The full or relative pathname of the log file
     * 	@param 	integer $max_size (optional)	The maximum size of a log file before it is rotated (def. 999999 bytes)
     * 	@param	string $log_date (optional)		Prepend each log line with a date/time stamp if true, log as is otherwise (def. true)
     */
    public function __construct($log_file, $num_files=5, $max_size = 999999, $log_date = true)
	{
		parent::__construct($log_file, $max_size, $log_date);
		$this->_num_files = $num_files;
	}
	
	/**
	 * main log function overrfide that implements log rotation
	 * @param type $msg
	 */
    public function log($msg)
	{
        if (@filesize($this->fileName()) + strlen($msg) >= $this->maxSize())
		{
			do
			{
				$this->_curFile = ($this->_curFile + 1) % ($this->_num_files+1);
				if ($this->_curFile == 0)  
				{
					for ($i = $this->_num_files-1; $i>0; $i--)
					{
						$newName = $this->fileName() . "_" . ($i+1);
						@unlink($newName);
						@rename($this->fileName() . "_" . $i, $newName);
					}
					$this->_curFile++;
				}

				$newName = $this->fileName() . "_" . $this->_curFile;
				if (@stat($newName) === false)
				{
					rename($this->fileName(), $newName);
					break;
				}
			} while (True);
        }
        parent::log($msg);
    }
}

/**
 * A multi-level logger. Creates a number of MBL_loggers
 * each attached to a log level and a file provided in $log_file_array.
 * 
 * Log level, file pairs are optional so not all levels need be specified. The format is as follows:
 * ~~{.php}
 *	array("D" => "myfile_debug.log", "I" => "info.log", "W" => "mywarnings.log", "E" => "errorlog.log", "C" => "criticalerrros.log")
 *  // or
 * array("D" => "myfile_debug.log", "IW" => "infowarn.log", "E" => "errorlog.log", "C" => "criticalerrros.log")
 * ~~
 * 
 * More than one levels can be logged to the same file either by specifying the same file in more than one levels or by
 * specifying more that one log level identifier in a level key. Example:
 *	"D" : debug log
 *	"I" : information log
 *	"W" : warnings log
 *	"E" : errors log
 *	"C" : critical errors log
 * 
 * These log levels are indicative and not enforced. The user can define their own levels using a single capital letter per level
 * as long as the incoming log strings contain a log level indicator that matches the log level.
 * 
 * Log strings are treated as follows:
 * - if a string contains no level indicator, it is sent to the first logger as defined in $log_file_array
 * - if a string has a level indicator, then this indicator should have the following format:
 *		[<log level(s)>]
 *	Example:
 * ~~{.php}
 *		$logger->log("[D]This is a debug message");
 *		$logger->log("[IW]This is an informative warning message");
 * ~~ 
 */
class LevelLogger implements ILogger
{
	private $loggers = null;
	private $first = null;
	private $log_ip = true;
	
	public function __construct($log_file_array, $max_size = 999999, $log_date = true, $logip=true)
	{
		$this->log_ip = $logip;
		$this->loggers = array();
		foreach ($log_file_array as $level => $logfile)
		{
			if (is_null($this->first))  $this->first = $level;
			$this->loggers[$level] = new Logger($logfile, $max_size, $log_date);
		}
	}
    /**
     * 	Set the maximum size of a log file
     * 	@param	integer $sz	The size in bytes
     */
    public function setMaxSize($sz)
	{
		foreach($this->loggers as $key => $logger)
			$logger->setMaxSize($sz);
    }

    /**
     * 	@return Returns the current maximum size of a log file
     */
    public function maxSize()
	{
        return (!is_null($this->first) ? $this->loggers[$this->first]->maxSize() : 0);
    }

    /**
     * 	Enables or disables datatime prefixing of each log line
     * 	@param boolean $ld	true - enable,  false - disable
     */
    public function setPrefixWithDate($ld)
	{
		foreach($this->loggers as $key => $logger)
			$logger->setPrefixWithDate($ld);
    }

    /**
     * 	@return Returns the current setting of datatime prefixing of each log line
     */
    public function prefixWithDate()
	{
        return (!is_null($this->first) ? $this->loggers[$this->first]->prefixWithDate() : 0);
    }
	
	/**
	 * Logs the message to one or more loggers as defined in the constructor, based on the level(s)
	 * indicated in the message's prefix [<level(s)>]. If no such prefix exists, then the message is
	 * sent to the first logger specified. If no loggers are specified, then nothing happens.
	 * 
	 * @param type $s
	 */
	public function log($s)
	{
		if (!is_null($this->loggers))
		{
			$level = '';
			if (strlen($s) > 0 && strpos($s, '[') === 0 && ($end = strpos($s, ']')) !== false)
			{
				// [IWE]
				// 01234
				$level = substr($s, 1, $end-1);
				if (array_key_exists($level, $this->loggers))
				{
//						$s = substr($s, $end+1);
					if ($this->log_ip)
						$str = getCallerIP()." ".$s;
					else
						$str = $s;
					$this->loggers[$level]->log($str);
				}
				else
				{
//						$s = substr($s, $end+1);
					if ($this->log_ip)
						$str = getCallerIP()." ".$s;
					else
						$str = $s;
					// NOTE: this covers the cases:
					//	1. logger keys are single or multiple, AND log string keys are single
					//	2. string keys are single or multiple, AND logger keys are single
					//	3. both can be single or multiple (NOT COVERED - needs level-by-level intersection test)
					foreach ($this->loggers as $key => $logger)
						if (strpos($level, $key) !== false || strpos($key, $level) !== false)
								$logger->log($str);
				}
			}
			else
			{
				if ($this->log_ip)
					$this->loggers[$this->first]->log(getCallerIP()." ".$s);
				else
					$this->loggers[$this->first]->log($s);
			}
		}
	}
}



/**
 * 	Generic Instance class to check whether is running or not.
 *
 */
class Instance {

	private $lock_file = '';
	private $is_running = false;

	public function __construct($id = __FILE__) 
	{
		$id = md5($id);

		$this->lock_file = sys_get_temp_dir() . '/' . $id;

		if (file_exists($this->lock_file)) 
		{
			$lockingPid = trim(file_get_contents($this->lock_file));
		
			$pids = explode("\n", trim(`ps -e | awk '{print $1$4}'`));
		
			if(in_array($lockingPid, $pids)) 
			{ 
				$this->is_running = true;
			} 
			else 
			{
				unlink($this->lock_file);
				file_put_contents($this->lock_file, getmypid() . "php\n");
			}
		}	 
		else 
			file_put_contents($this->lock_file, getmypid() . "php\n");
	}

	public function __destruct() 
	{
		if (file_exists($this->lock_file) && !$this->is_running) 
		{
			unlink($this->lock_file);
		}
	}

	public function is_running() 
	{
		return $this->is_running;
	}
}

/**
 * 	exeCURL: Generic cURL Method
 * 	Parameters: 
 * 		@param $ip (string), the IP or Domain the cURL will call. ex: "89.31.1.41"
 * 		@param $path (string), the path after the IP or Domain that will be called. ex: "/folder1/folder2/"
 * 		@param $params (array, optional), the array of parameters that will be added inside the cURL. ex: array("id" => "ab345b12a34", "name" => "john")
 * 		@param $port (string, optional), the port the cURL call, will use to make the request [default value ""]. ex: "8080"
 * 		@param $method (string, optional), the method of the request [default value "GET"]. ex: "POST"
 * 		@param $ssl (boolean, optional), if the call will be on SSL URL [default value FALSE]. ex: TRUE
 * 	Return Value:
 * 		@return (string), The content of the response in plain text format OR FALSE if the request fails
 */
function exeCURL($ip, $path, $params = array(), $port = "", $method = "GET", $ssl = FALSE)
{
    $http_ = ($ssl) ? "https" : "http";
    $port_ = (strlen($port) > 0) ? ":".$port : "";
	
    $url = $http_ . "://" . $ip . "" . $port_ . "" . $path;
	
    $params_string = "";
    foreach ($params as $key => $val)
	{
        $params_string .= $key . "=" . $val . "&";
    }
    $params_string = (strlen($params_string) > 0) ? substr($params_string, 0, -1) : "";
    $callFull = (strlen($params_string) > 0) ? $url . "?" . $params_string : $url;

    $ch = curl_init();
    if ($method == "POST")
	{
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, count($params));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params_string);
    }
	else
	{
        curl_setopt($ch, CURLOPT_URL, $callFull);
    }

    if ($ssl)
	{
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
    }
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7.5) Gecko/20041107 Firefox/1.0');
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    $content = curl_exec($ch);
    curl_close($ch);

    return ($content) ? trim($content) : FALSE;
}

/**
 * 	General PropertySet interface
 * 	Allows modifying properties in any derived class by calling setParams() method
 * 	with an array of the form:
 * 		["property name" => <property value>]
 * 	If the property exists, even protected or private it will be modified,
 * 	and if not, a warning will be issued.
 * 	
 * 	Requires: PHP 5.3 and above
 */
interface IPropSet
{

    public function status();

    public function setStatus($st);
}

/**
 * 	Base implementation of IPropertySet interface
 * 	@sa	IPropertySet
 */
class PropSet implements IPropSet
{

    private $m_status = "OK";

    public function status()
	{
        return $this->m_status;
    }

    public function setStatus($st)
	{
        $this->m_status = $st;
    }

    /**
     * 	Sets an array of named parameters to a derived class.
     * 	The array is of the form: array ("param_name" => param_value, ...)
     * 	If a "param_name" corresponds to a public/protected of private member of the derived class
     * 	then param_value will be set to that member. If not, then m_status will be set to a warning.
     *
     * 	@param	$prm	An associative array containing names and values of the parameters.
     */
    public function setParams($prm = null)
	{
        $this->m_status = "OK";
        $this->m_params = $prm;
        if (!is_null($prm))
		{
            $me = new ReflectionClass(get_class($this));
            foreach ($prm as $var => $value)
			{
                if ($me->hasProperty($var))
				{
                    try {
                        $prop = $me->getproperty($var);
                        if (method_exists($prop, "setAccessible")) {
                            $prop->setAccessible(true);
                        }
                        $prop->setValue($this, $value);
                    }
					catch (ReflectionException $e)
					{
                        $this->m_status = "*** Exception setting property [$var] - " . $e->getMessage() . "";
                    }
                }
				else
				{
                    $this->m_status = "*** WARNING:" . get_class($this) . " has no property '$var'";
                }
            }
        }
    }

}

/**
 * RequestParser
 * 
 * This class wraps utility functions that find parameters in the $_REQUEST superglobal
 * and set their values based on metadata provided externally.
 * In the simple case, the metadata define required parameters as a simple array of URL parameter names (strings).
 * In the complex case, each level of the array defines a set of parameters, each of which can
 * have requirements for more parameters. Each parameter is defined as a key to a subarray of values (strings) and
 * each value is a key to another subarray of required parameter names.
 * 
 * If a parameter is found, then its value is checked, and if it is a string, the corresponding value from $_REQUEST
 * is given to the object's setVal($key, $_REQUEST[$key]) function.
 * If the parameter's value is an array, then it must have either the names of further required parameters indicating
 * that when a key=value is present in a URL, then the keys required by 'value' must also be present as parameters with values.
 * This scheme can continue recursively.
 * 
 * Examples:
 * ~~~{.php}
 *		// action must be present and can have values "val", "reg", "ddd", "test", "p2"
 *		// if action == "val" then "device_id" must also be present as a parameter in the URL
 *		// if action == "reg" then both "device_id" and "gcm_regid" must be present 
 *		// if (action == "ddd" then "d1" must be present. 
 *		//			Parameter "d1" value can be either "a" or "b".
 *		//			If d1 == "b" then "X" and "Y" must also be present and have values
 *		pInfo = ["action" => [	"val" => ["device_id"],	
 *								"reg" => ["device_id","gcm_regid"], 
 *								"ddd" => ["d1" => ["a", "b" => ["X", "Y"] ]],
 *								"test", "p2"]
 *				];
 * 
 *		// cmd must be present and "p1", "p2", "p3" have to be also present
 *		pInfo = ["cmd" => ["p1", "p2", "p3"]]
 * 
 *		// Either or all of cmd, action, val can be present as parameter
 *		pInfo = ["cmd", "action", "val"]
 * ~~~
 */
class RequestParser
{
	private $_strict = false;	//!< defines whether parameters not defined by the external object are acceptable or not (default is false)
	
	/**
	 * Constructor
	 * 
	 * @param boolean $strict	if true then extran (not specified) parameters will be accepted, 
	 *							else an error will result and the return value of findAndSetObjParam method will be false.
	 */
	public function __construct($strict = false)
	{
		$this->_strict = $strict;
	}
	
	/**
	 * Allows setting the value of 'strict' after construction
	 * @param boolesn $s	see member $_strict
	 */
	public function setStrict($s) { $this->_strict = $s; }
	
	/**
	 * Returns the current value of 'strict' condition (see $_strict)
	 * @return boolean
	 */
	public function isStrict() { return $this->_strict; }
	
	/**
	 * Checks $_REQUEST for the given parameter and sets its value to an conformant object.
	 * Conformant objects must have a setVal($key, $value) method for setting parameters.
	 * This method can be used to attempt to set a parameter to an object directly.
	 * 
	 * @param object $obj		An object that conforms with the specification
	 * @param string $prm		name of a parameter expected to exist in $_REQUEST
	 * @param Logger $logger	A logger object to log errors
	 * @return boolean			true if parameter was set, false if an error occurred.
	 */
	public function setObjParam(&$obj, $prm, $logger)
	{
		$canProceed = true;
		if (array_key_exists($prm, $_REQUEST))
		{
			if (method_exists($obj, "setVal"))
			{
				$obj->setVal($prm, $_REQUEST[$prm]);
			}
			else
			{
				$logger->log("***ERROR: '".get_class($obj)."' does not have 'setVal' method. Cannot set value for '$prm'\n");
				$canProceed = false;
			}
		}
		else
		{
			if (method_exists($obj, "isOptional"))
			{
				$canProceed = $obj->isOptional($prm);
				if ($canProceed)
					$logger->log("NOTICE: Optional Parameter '$prm' not provided for '".get_class($obj)."'\n");
				else
					$logger->log("***ERROR: Mandatory Parameter '$prm' not provided for '".get_class($obj)."'\n");
			}
			else
			{
				$logger->log("***ERROR: Parameter '$prm' not provided for '".get_class($obj)."'\n");
//			$canProceed = false;
			}
		}
		return $canProceed;
	}

	/**
	 * Attempts to find the parameter specified for the given object in $_REQUEST and
	 * sets it if the object supports the standard setVal() method by calling setObjParam() member of this class.
	 *  
	 * ~~~{.php}
	 * pInfo :  array("param1", "param2",...);   // simple parameters array
	 *			array("param1" => array(	// complex parameter, sub-params are dependent on value of parent parameter
	 *									"param1_val1" => array("sub1_param1", "sub1_param2",....),
	 *									"param1_val2" => array("sub2_param1", "sub2_param2",....)
	 *								) )
	 * ~~~  
	 * @param object $obj		This is an object that has a setVal($key, $value) function (checked)
	 * @param string $pInfo		The parameter definition metadata (see pInfo above)
	 * @param Logger $logger	The logger to use to log errors
	 * @return boolean			true if sucessful, false otherwise (error logged)
	 */
	public function findAndSetObjParam(&$obj, $pInfo, $logger)
	{
		$canProceed = true;
		foreach ($pInfo as $key => $prm)
		{
//			echo "findAndSet: $key => ".print_r($prm, true)."<br>";
			if (is_numeric($key)) // simple parameters metadata
			{
//				$logger->log("+++NUMERIC $key defined by ".get_class($obj)."\n");
				$canProceed = $this->setObjParam($obj, $prm, $logger);
			}
			elseif (is_string($key) && is_array($prm))   // complex parameters metadata
			{
				if (isset($_REQUEST[$key]))
				{
					$sub_key = $_REQUEST[$key];
//					echo "findAndSet sub_key: $sub_key prm=".print_r($prm, true)."<br>";
					$canProceed = $this->setObjParam($obj, $key, $logger);
					if (array_key_exists($sub_key, $prm))  // if it is still deeper, keep going
					{
						$canProceed = $canProceed && $this->findAndSetObjParam($obj, $prm[$sub_key], $logger);
					}
					elseif ($this->_strict && array_search($sub_key, $prm) === false)
					{
						$logger->log("***ERROR: $sub_key not defined by ".get_class($obj)."\n");
						$canProceed = false;			
					}
				}
				else
				{
					$logger->log("***ERROR: $key required by ".get_class($obj)." not found\n");
					$canProceed = false;
					break;
				}
			}

			if (!$canProceed)
				break;
		}
		return 	$canProceed;
	}

}

?>