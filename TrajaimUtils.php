<?php


function GetR($x, $default=null)
{
	return (isset($_REQUEST[$x]) ? $_REQUEST[$x] : $default);
}

class FzpFile
{
	protected $_fileName;
	protected $_file;
	protected $_status = "UNUSED";
	protected $_header = array();
	
	public function __construct()
	{
	}
	
	public function __destruct()
	{
		$this->close();
	}
	
	public function status() { return $this->_status; }
	public function header() { return $this->_header; }
	public function eof() { return $this->_file ? feof($this->_file) : true; }
	
	public function open($path)
	{
		$this->_file = fopen($path, "r");
		$this->_status = $this->_file !== false ? "OK" : "File [$path] open failed";
		return $this->_file !== false;
	}
	
	public function close()
	{
		if ($this->_file) 
		{
			fclose($this->_file);
			$this->_status = "CLOSED";
		}
	}
	
	public function readHeader()
	{
		$this->_header = array();
		$ok = true;
		if ($this->_file)
		{
			// information header
			for ($i=1; $i <= 25; ++$i)
			{
				$line = fgets($this->_file);
				if ($line !== false && !empty($line))  // check for premature EOF and empty lines
				{
					$parts = explode(": ", $line);
					if ($parts[0] != $line)  // skip empty lines and ones without key: value format
						$this->_header[$parts[0]] = trim($parts[1]);
				}
				else
				{
					$this->_status = "Failed to read FZP header";
					$ok = false;
					break;
				}
			}
			
			if ($ok)
			{
				// DB table header fields
				$line = fgets($this->_file);
				if ($line !== false && !empty($line))  // check for premature EOF and empty lines
				{
					$parts = explode(";", $line);
					for ($i=0; $i<count($parts)-1; $i++)
					{
						$colName = trim($parts[$i]);
						$this->_header["DB"][] = $colName;
					}
					$this->_status = "OK";
				}
				else
					$this->_status = "Failed to read FZP DB header";
			}
		}
		else
			$this->_status = "File is not Open";
		
		return !empty($this->_header);
	}
	
	public function readData($nLines)
	{
		$doc = array(); 
		if ($this->_file)
		{
			if (!empty($this->header()))
			{
				$data_keys = $this->header()["DB"]; 
				for ($i=0; $i<count($data_keys); $i++)
					$doc[$data_keys[$i]] = array();
				for ($i=0; !feof($this->_file) && $i<$nLines; ++$i)
				{
					$line = fgets($this->_file);
					$val = explode(";", $line);
					for ($k=0; $k<count($val)-1; $k++)
					{
						$doc[$data_keys[$k]][] = trim($val[$k]);
					}
				}
			}
		}
		else
			$this->_status = "File is not Open";
		return $doc;
	}
}

/**
 * Handles insertion and merging of trajectories data to the trajaim table
 */
class FileManager
{
	private $dbinfo;
	private $logger;
	
	public function __construct($dbi, $logger)
	{
		$this->dbinfo = $dbi;
		$this->logger = $logger;
	}
	
	public function log($msg)
	{
		if ($this->logger)
			$this->logger->log($msg);
	}
	
	public function browse($dir)
	{
		$ret = '{"error":"Browse Failed to scan '.$dir.'"}';
		$files = @scandir($dir);
		if ($files)
		{
			array_splice($files, 0, 2);
			$ret = json_encode($files);
		}
		else
		{
			$this->log("*** ".__CLASS__."::".__FUNCTION__.": Failed to scan $dir\n");
		}
		return $ret;
	}
	
	public function InsertFzpFile($file, $advise_on)
	{
		$ff = new FzpFile();
		if ($ff->open($file))
		{
			if ($ff->readHeader())
			{
				$this->log("Header is:\n".print_r($ff->header(), true)."\n");
				$nLines = 0;
				while (!$ff->eof())
				{
					$doc = $ff->readData(1000);
					if (!empty($doc))
					{
						$ret = $this->insertToDB($doc, $advise_on);
						$nLines+=count($doc['VehNr']);
						$this->log(" Added $nLines lines: ".$ret."\n");
					}
				}
				$this->log("==== END at $nLines lines ====\n");
			}
			else
				$this->log("*****".$ff->status()."\n");
		}
		else
			$this->log("*****".$ff->status()."\n");
		
		return $ff->status();
	}
	
	public function insertToDB($doc, $advise_on)
	{
		$dbi = $this->dbinfo;
		$ret = "OK";
		$advise_on = ($advise_on == 'yes' ? 1 : 0);
		$db = new MySQLI2($dbi["hostname"], $dbi["username"], $dbi["passwd"], $dbi["dbname"]);
		if ($db->status() == "OK" )
		{
			$keys = array_keys($doc);
			$nRecs  = count($doc[$keys[0]]);
			$nrows =500;
			for ($r =0; $r<$nRecs; $r += $nrows)
			{
				$q = "INSERT INTO veh_traj (EEIS_Enabled,";
				foreach ($keys as $key)
				{
					$q .= $key.",";
				}
				$q = substr($q, 0, strlen($q)-1);
				$q .= ") VALUES \n";

				for ($i=$r; $i< $r+$nrows && $i<$nRecs; $i++)
				{
					$q .= "($advise_on,";
					foreach ($keys as $key)
					{
						$q .= "'".$doc[$key][$i]."',";
					}
					$q = substr($q, 0, strlen($q)-1);
					$q .= ")\n,";
				}
				$q = substr($q, 0, strlen($q)-1);

				$res = $db->query($q);
				if (isset($res["error"]))
				{
					$ret = $res["error"]." QUERY=[$q]";
					break;
				}
			}
		}
		else
		{
			$ret = $db->status();
		}

		return $ret;
	}

	public function useFile()
	{
		$ret_json = '{"error" : "Filemanager::useFile incorrect input"}';
		$file = GetR('file');
		$advise = GetR('adv', 'no');
		if ($file)
		{
			// drop and then rebuild empty table (much faster that delete query)
			$ret = ExecSQL("DROP TABLE veh_traj", $this->dbinfo);
			$ret = ExecSQL($this->dbinfo['tabledef'], $this->dbinfo);
			if (isset($ret["error"]))
				$ret_json = '{"error" : "Filemanager: Failed to clear table['.$ret["error"].']"}';
			else
			{
				$ret = $this->InsertFzpFile($file, $advise);
				if ($ret == "OK")
				{
					$ret_json = '{"msg" : "OK"}';
				}
				else
					$ret_json = '{"error" : "Filemanager: Failed to add data to table['.$ret.']"}';
			}
		}
		return $ret_json;
	}
	
	public function mergeFile()
	{
		$ret_json = '{"error" : "Filemanager::useFile incorrect input"}';
		$file = GetR('file');
		$advise = GetR('adv', 'no');
		if ($file)
		{
			$ret = $this->insertFzpFile($file, $advise);
			if ($ret == "OK")
			{
				$ret_json = '{"msg" : "OK"}';
			}
			else
				$ret_json = '{"error" : "Filemanager: Failed to add data to table['.$ret.']"}';
		}
		return $ret_json;
	}

}


/**
 * Calculates:
 * 1. single vehicle trajectory over time (x field)
 * 1.1. single vehicle speed over time (v field)
 * 1.2. calculate 1. and 2. for the same vehicle with and without speed advise
 * 2. total single vehicle emissions estimation for a given trajectory (scalar) with and withour speed advise
 * 
 */
class Analyzer
{
	private $dbinfo;
	private $logger;
	private $export_folder=null;
	
	public function __construct($dbi, $logger)
	{
		$this->dbinfo = $dbi;
		$this->logger = $logger;
	}
	
	public function setExportFolder($fld)
	{
		$this->export_folder = $fld;
	}
	
	public function log($msg)
	{
		if ($this->logger)
			$this->logger->log($msg);
	}
	
	public function getVehTypes()
	{
		// TODO: check possibility of counting veh.types separately per loaded traj. file (.fzp)
		$ret = null;
		$ret = ExecSQL("SELECT VehTypeName, count(VehTypeName) as `Count`
						FROM 
						(
							SELECT DISTINCT VehNr, EEIS_Enabled, VehTypeName FROM veh_traj
						) dv
						GROUP BY VehTypeName", $this->dbinfo);
		if (!isset($ret["error"]))
		{
			$rt = array();
			$vtypes = $ret['VehTypeName'];
			for ($i=0; $i<count($vtypes); $i++)
			{
				$rt[$vtypes[$i]] = $ret['Count'][$i];
			}
			$ret_json = json_encode($rt);
		}
		else
			$ret_json = '{"error" : "Analyser: ['.$ret["error"].']"}';
		
		return $ret_json;
	}
	
	public function getVehicleNumbers()
	{
		$ret_json = '{"error" : "Analyzer::getVehicleNumbers incorrect input"}';
		$veh_no = GetR('vehno', '-1'); // beginning of vehicle number
		$veh_type = GetR('vehtype'); // vehicle type
		$this->log("Analyzer::getVehicleNumbers: [$veh_no, $veh_type ]\n");
		if ($veh_type !== '' && $veh_no !== '-1')
		{
			$ret = ExecSQL("SELECT DISTINCT VehNr FROM veh_traj WHERE VehNr LIKE '$veh_no%' AND VehTypeName = '$veh_type' LIMIT 100", $this->dbinfo);
			$this->log('Analyzer::getVehicleNumbers:'. json_encode($ret)."\n");
			if (!isset($ret["error"]))
			{
				if (!empty($ret))
					$ret_json = json_encode($ret);
				else
					$ret_json = '{"VehNr": []}';
			}
			else
				$ret_json = '{"error" : "Analyser: ['.$ret["error"].']"}';
		}
		return $ret_json;
	}

	/**
	 * expects: &ns=<num series>&s0=<vehtype name>,<vehno>&s1=<vehtype name>,<vehno>,...
	 * optionally, expects: &export=1 or 0
	 * @return string json 
	 */
	public function getSeries()
	{
		$ret_json = '{"error" : "Analyzer::getSeries incorrect input"}';
		$export_enabled = GetR('export', 0);
		$type = GetR('type');
		$num_series = GetR('ns', 0); // number of series following
		$data = false;
		$ic = 0;
		for ($i=0; $i<$num_series; $i++)
		{
			$series = GetR('s'.$i, '');
			if ($series === '')
			{
				$data = array("error" => "Analyzer::getSeries series requested without vehicle type and number");
				break;
			}
			
			// formalize graph attributes (title, labels, query valyes, etc) and use them in a generic way
			// so that appropriate labels and units appear next to the axes for each different graph type.
			$series = explode(',', $series);
			$veh_type = $series[0];
			$veh_no = $series[1];
			if ($type == 'speedOverTime')
			{
				$graph = array("sql" => "SELECT v as yval,t as xval,LVeh FROM veh_traj WHERE VehNr = '$veh_no' AND VehTypeName = '$veh_type'",
						"title" => "Speed over Time",
						"x_label" =>  "Time (sec)",
						"x_msg" => "time: ",
						"y_label" => "Speed (km/h)",
						"y_msg" => "speed: ",
						"filter" => function($v) { return $v; }
					);
			}
			elseif ($type == 'speedOverDistance')
			{
				$graph = array("sql" => "SELECT v as yval, x as xval,LVeh FROM veh_traj WHERE VehNr = '$veh_no' AND VehTypeName = '$veh_type'",
						"title" => "Speed over Distance",
						"x_label" =>  "Distance (m)",
						"x_msg" => "distance: ",
						"y_label" => "Speed (km/h)",
						"y_msg" => "speed: ",
						"filter" => function($v) 
						{	
							$dist = 0.0;
							$last_dist = 0.0;
							for ($i=0; $i< count($v['xval']); $i++)
							{
								$cur_dist = floatval($v['xval'][$i]) + $last_dist;
								if ( $cur_dist < $dist) 
								{
									$last_dist = $dist;
									$cur_dist = floatval($v['xval'][$i]) + $last_dist;
								}
								$dist = $cur_dist;

								$v['xval'][$i] = $cur_dist;
							}
							return $v;
						}
					);
			}
			elseif ($type == 'accelerationVsSpeed')
			{
				$graph = array("sql" => "SELECT a as yval, v as xval,LVeh,EEIS_Enabled FROM veh_traj WHERE VehNr = '$veh_no' AND VehTypeName = '$veh_type'",
						"title" => "Acceleration vs Speed",
						"x_label" =>  "Speed (km/h)",
						"x_msg" => "speed: ",
						"y_label" => "Acceleration (m/sec^2)",
						"y_msg" => "acceleration: ",
						"filter" => function($v) { return $v; }
					);
			}
			elseif ($type == 'accelerationOverTime')
			{
				$graph = array("sql" => "SELECT a as yval,t as xval,LVeh,EEIS_Enabled FROM veh_traj WHERE VehNr = '$veh_no' AND VehTypeName = '$veh_type'",
						"title" => "Acceleration over Time",
						"x_label" =>  "Time (sec)",
						"x_msg" => "time: ",
						"y_label" => "Acceleration (m/sec^2)",
						"y_msg" => "acceleration: ",
						"filter" => function($v) { return $v; }
					);
			}
			elseif ($type == 'accelerationOverDistance')
			{
				$graph = array("sql" => "SELECT a as yval, x as xval,LVeh,EEIS_Enabled FROM veh_traj WHERE VehNr = '$veh_no' AND VehTypeName = '$veh_type'",
						"title" => "Acceleration over Distance",
						"x_label" =>  "Distance (m)",
						"x_msg" => "distance: ",
						"y_label" => "Acceleration (m/sec^2)",
						"y_msg" => "acceleration: ",
						"filter" => function($v) 
						{	
							$dist = 0.0;
							$last_dist = 0.0;
							for ($i=0; $i< count($v['xval']); $i++)
							{
								$cur_dist = floatval($v['xval'][$i]) + $last_dist;
								if ( $cur_dist < $dist) 
								{
									$last_dist = $dist;
									$cur_dist = floatval($v['xval'][$i]) + $last_dist;
								}
								$dist = $cur_dist;

								$v['xval'][$i] = $cur_dist;
							}
							return $v;
						}
					);
			}
			elseif ($type == 'distanceOverTime')
			{
				$graph = array("sql" => "SELECT x as yval, t as xval,LVeh,EEIS_Enabled FROM veh_traj WHERE VehNr = '$veh_no' AND VehTypeName = '$veh_type'",
						"title" => "Distance over Time",
						"x_label" =>  "Time (sec)",
						"x_msg" => "time: ",
						"y_label" => "Distance (m)",
						"y_msg" => "distance: ",
						"filter" => function($v)
						{	
							$dist = 0.0;
							$last_dist = 0.0;
							for ($i=0; $i< count($v['yval']); $i++)
							{
								$cur_dist = floatval($v['yval'][$i]) + $last_dist;
								if ( $cur_dist < $dist) 
								{
									$last_dist = $dist;
									$cur_dist = floatval($v['yval'][$i]) + $last_dist;
								}
								$dist = $cur_dist;

								$v['yval'][$i] = $cur_dist;
							}
							return $v;
						}
					);
			}
			else
			{
				$data = array("error" => "Analyzer::getSeries series requested for unknown graph type: $type");
				break;
			}
			$ret = array(ExecSQL($graph['sql']." AND EEIS_Enabled = 0", $this->dbinfo), 
						 ExecSQL($graph['sql']." AND EEIS_Enabled = 1", $this->dbinfo));
			//error_log("==== $type: BEFORE ====".print_r($ret, true));
		
			// use the filter implemented per graph type that should perform some 
			// custom calculation on results
			if (!empty($ret[0])) $ret[0] = $graph['filter']($ret[0] ); 
			if (!empty($ret[1])) $ret[1] = $graph['filter']($ret[1] ); 
			//error_log("==== $type: AFTER(0) ====".print_r($ret, true));

			$colors = array(
				array( "c1" => "#cc3300", "c2" => "rgba(255,121,77,0.4)"),
				array( "c1" => "#0047b3", "c2" => "rgba(102,163,255,0.4)"),
				array( "c1" => "#006600", "c2" => "rgba(0,204,0,0.4)"),
				array( "c1" => "#ae831e", "c2" => "rgba(225,182,81,0.4)"),
				array( "c1" => "#595959", "c2" => "rgba(153,153,153,0.4)"),
			);

			$quit = false;
			for ($ii=0; !$quit && $ii<2; $ii++)
			{
				if (!isset($ret[$ii]["error"]))
				{
					if (!empty($ret[$ii]))
					{
						if (!$data)
							$data = array(
							"title" => $graph['title'],
							"type" => "lines,scatter,trend",  // lines,bars,scatter,trend
							"orient" => "horizontal", // | "vertical",
							"axis_color" => "#0000ff",
							"legend" => array( "position" => "right:90px;top:3px;"),
							"x_label" =>  $graph['x_label'],
							"y_label" => $graph['y_label'],
							"x_msg" => $graph['x_msg'],
							"values" => array()
							);

						for ($lv=0; $lv<count($ret[$ii]['LVeh']); $lv++) 
							if (intval($ret[$ii]['LVeh'][$lv]) == 0) $ret[$ii]['LVeh'][$lv] = 'None';
						$data['values'][]=	array(
								"y_msg" => $graph['y_msg'],
								"y_label" => $veh_type.($ii==1 ? "-Adv" : "")." ".$veh_no,
								"user" => array("Leading Veh: " => $ret[$ii]['LVeh']),
								"color" => $colors[$ic]['c1'],
								"bar_color" => $colors[$ic]['c2'],
								"x" => $ret[$ii]['xval'] ,
								"y" => $ret[$ii]['yval']
							);
						if ($export_enabled)
							$this->createCSV($data["title"], $data["values"]);
						$ic = ($ic+1)%count($colors);
					}
					else
						$this->log("***WARNING: Analyser: No data found for Vehicle $veh_no, type $veh_type\n");
				}
				else
				{
					$this->log("***ERROR: Analyser: ".$ret[$ii]["error"]."\n");
					$ret_json = '{"error" : "Analyser: ['.$ret[$ii]["error"].']"}';
					$quit = True;
				}
			}
		}
		if (!$data)
			$ret_json = '{"error": "No Data found"}';
		else
			$ret_json = json_encode($data);
		return $ret_json;
	}
	
	public function createCSV($title, $series)
	{
		$fname = $this->export_folder."/".$title.".csv";
		$file = fopen($fname, "w");
		if ($file)
		{
			// veh-type-number(y_label), x, y, 
			for ($s=0; $s<count($series); $s++)
			{
				$xv = $series[$s]["x"];
				$yv = $series[$s]["y"];
				$y_label = $series[$s]["y_label"];
				for ($i=0; $i<count($xv); $i++)
				{
					fwrite($file, $y_label.",".$xv[$i].",".$yv[$i]."\n");
				}
				//fwrite($file, "\n");
			}
			fclose($file);
		}
		else
			$this->log("***ERROR: Analyser: Cannot open CSV file [$fname] for writing\n");
	}

}
