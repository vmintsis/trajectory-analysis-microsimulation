<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "common/mysqli2.php";
require_once "common/Utilities.php";
require_once "Settings.php";
require_once "TrajaimUtils.php";
require_once "DBBuilder.php";

// NOTE: Netbeans had an issue with java jre, so installed JDK and then copied to javahome dir netbeans required.

/*
	echo '<tr class= "tbl_hdr">';
	$data_keys = array_keys($doc["data"]);
	foreach ($data_keys as $key)
		echo "<td>".$key."</td>";
	echo "</tr>";
	$nData = count($doc["data"][$data_keys[0]]);
	echo "Data Lines:".$nData."<br>";
	echo $doc["data"][$data_keys[0]][0]."  -  ".$doc["data"][$data_keys[0]][$nData - 1];
	for ($i=0; $i<$nData; $i++)
	{
		echo "<tr>";
		foreach ($data_keys as $key)
		{	
			echo "<td>".$doc["data"][$key][$i]."</td>";
		}
		echo "</tr>";
	}
*/

//------- API
//
// Populate DB:  ?cmd=init&file=<file>
// Populate DB:  ?cmd=merge&file=<file>
//

SessionInit2("TRAJAIM");


class dummyLogger
{
	public function log($msg) {	 ErrLog($msg);}
}

class Application
{
	private $_logger;
	
	public function Application($logger=null)
	{
		$this->_logger = $logger ? $logger : new dummyLogger();
	}
	
	public function logger()
	{
		return $this->_logger;
	}
	
	public function log($msg)
	{
		$this->logger()->log($msg);
	}
	
	public function run($config)
	{
		$traj_dir = $config['traj_dir'];	// create a Config class with a member get() that returns either a specific item or an array of everything
		$dbinfo = $config['dbinfo'];
		$traj_csv = $config['traj_csv'];
		
		$builder = new DBBuilder();
		$ret_json = $builder->run($dbinfo, $this->logger());
		
		if (strstr($ret_json, "error") === False)
		{
			$ret_json = '';	// empty out success string to allow HTML to be output
			$cmd = GetR('cmd');
			if ($cmd === 'init')
			{
				$mgr = new FileManager($dbinfo, $this->logger());
				$ret_json = $mgr->useFile();
			}
			elseif ($cmd === 'merge')
			{
				$mgr = new FileManager($dbinfo, $this->logger());
				$ret_json = $mgr->mergeFile();
			}
			elseif ($cmd === 'browse')
			{
				$mgr = new FileManager($dbinfo, $this->logger());
				$ret_json = $mgr->browse($traj_dir);
			}
			elseif ($cmd === 'gettypes')
			{
				$az = new Analyzer($dbinfo, $this->logger());
				$ret_json = $az->getVehTypes();
			}
			elseif ($cmd === 'get_veh_no')
			{
				$az = new Analyzer($dbinfo, $this->logger());
				$ret_json = $az->getVehicleNumbers();
			}
			elseif($cmd === 'get_series')
			{
				$az = new Analyzer($dbinfo, $this->logger());
				$az->setExportFolder($traj_csv);
				$ret_json = $az->getSeries();
			}
		}
		return $ret_json;
	}
}

$config = config();
$logger = new Logger($config['logFile']);
$app = new Application($logger);
$ret = $app->run($config);
if ($ret != '')
{
	echo $ret;
}
else
{
  // GUI HERE
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en">
<head>
<meta charset="utf-8"></meta>
<title>Trajaim</title>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css"></link>
<link rel="stylesheet" href="trajaim.css"></link>
<style>

</style>

<script type="text/javascript" src="/trajaim/common/popup.js"></script>
<script type="text/javascript" src="/trajaim/common/Grapher.js"></script>
<script>

var loading = '<i class="fa fa-spinner fa-pulse"></i>';
function ge(id) { return document.getElementById(id); }	

var ajax = function(method, url, fn_ok, fn_error)
{
	var req = new XMLHttpRequest();
	req.onload = fn_ok;
	req.onerror = fn_error;
			
	req.open(method, url, true);
	req.send();
};

// ============ Feedback GUI areas ========
var tmsg = 0;
function timed_msg(s, t)
{
	msg(s);
	if (tmsg != 0) clearTimeout(tmsg);
	tmsg = setTimeout( function() { msg(''); tmsg = 0; }, t);
}

function msg(s)
{
	ge("msg").innerHTML = s;
	if (s == '')
		ge("msg").style.visibility = 'hidden';
	else
		ge("msg").style.visibility = 'visible';
	if (tmsg != 0) clearTimeout(tmsg);
	tmsg = 0;
}


graphs = { 
	obj: {
	},
	
	enable: function(type)
	{
		if (!this.obj.hasOwnProperty(type))
		{
			this.obj[type] = null;
		}
	},
	disable: function(type)
	{
		if (this.obj.hasOwnProperty(type))
		{
			delete this.obj[type];
		}
	},
	
	add: function(type, data, gid)
	{
		if (this.obj.hasOwnProperty(type))
		{
			this.obj[type] = new Grapher(data, data.title, gid);
			this.obj[type].Init();
			this.obj[type].onFormat = g_format;
			this.obj[type].onCalc = g_calc;
			this.obj[type].enable('lines', document.getElementById('en_lines').checked);
			this.obj[type].enable('bars', document.getElementById('en_bars').checked);
			this.obj[type].enable('scatter', document.getElementById('en_scatter').checked);
			this.obj[type].enable('trend', document.getElementById('en_trend').checked);
			this.obj[type].enable('hlines', document.getElementById('en_hlines').checked);
			this.obj[type].set('databox', 'ymin', 0);
			return this.obj[type];
		}
		else
			throw "add: Undefined Graph type "+type;
	},
	
	get: function(type)
	{
		if (type==='all')
			return this.obj;
		if (this.obj.hasOwnProperty(type))
			return this.obj[type];
		throw "get: Undefined Graph type "+type;
		return null;
	},
	
	each: function( fcn )
	{
		for (var i in this.obj)
		{
			if (this.obj[i])
				fcn(this.obj[i], i);
		}
	}
};

function setType(which, value)
{
	try 
	{
		graphs.each( function(graph, i) { graph.enable(which, value); graph.Show(); });
	}
	catch(e)
	{
		msg("***"+e.message);
	}
}

function show(id_series, graph_id)
{
	// graph_id is "grph_" + name of the graph type
	var type = graph_id.substr(5);
	var list = ge(id_series).list;
	var exprt = document.getElementById('en_export').checked ? '1' : '0';
	var q='';
	// .. &s0=vt,vt&s1=vt,vn...
	for (var i=0; i<list.length; i++)
	{
		q += '&s'+String(i)+'='+list[i].vehtype+','+list[i].vehno;
	}
	
	var req = new ajax("GET", "index.php?cmd=get_series&export="+exprt+"&type="+type+"&ns="+String(list.length)+q,
		function()
		{
			var data = JSON.parse(this.responseText);
			if (data.hasOwnProperty('error'))
			{
				msg(data.error);
			}
			else
			{
				console.log(data);
				try {
					graphs.add(type, data, graph_id).Show();
				}catch(e) { msg('***'+e.message); }
			}
		},
		function()
		{
			msg("Failed to obtain data");
		});
}	

function g_format(label, value)
{
	if (label.indexOf('time')>=0)
		value += ' sec';
	else
	if (label.indexOf('speed') >=0)
		value = String(parseFloat(value*3.6).toFixed(2)) + ' km/h';
	else
	if (label.indexOf('acceleration') >=0)
		value = String(parseFloat(value).toFixed(2)) + ' m/sec<sup>2</sup>';
	else
	if (label.indexOf('y-axis') >= 0)
	{
		if (this._title.indexOf('Speed over') >= 0)
			value = String(parseFloat(value*3.6).toFixed(2));
		else
		if (this._title.indexOf('Acceleration ') >= 0)
			value = String(parseFloat(value).toFixed(2));
		else
			value = String(parseFloat(value).toFixed(0));
	}
	else
	if (label.indexOf('x-axis') >= 0)
	{
		if (this._title == 'Acceleration vs Speed')
			value = String(parseFloat(value*3.6).toFixed(2));
		else
			value = String(parseFloat(value).toFixed(0));
	}
	else
	if (label.indexOf('draw_params') >=0)
	{
		if (value.key == 'hlines') value.value.lineDash = [10,2];
		else
		if (value.key == 'trend') value.value.lineDash = [5,3];
		value = value.value;
	}
	return value;
}

function g_calc(yi, data_y, addon_name, addon)
{
	addon.set('ymarks', []);
	if (addon_name === 'hlines')
	{
		if (this._title.indexOf('Acceleration ') >= 0)
		{
			addon.set('ymarks', [0]);
		}
		// modify world coordinate extents based on manifold line coordinates
/*
		if (graph.get('databox', 'ymin') === false || data_y.manifold.min_value < graph.get('databox', 'ymin')) graph.set('databox', 'ymin', data_y.manifold.min_value);
		else
		if (graph.get('databox', 'ymax') === false || data_y.manifold.min_value > graph.get('databox', 'ymax')) graph.set('databox', 'ymax', data_y.manifold.min_value);
	
		if (graph.get('databox', 'ymin') === false || data_y.manifold.max_value < graph.get('databox', 'ymin')) graph.set('databox', 'ymin', data_y.manifold.max_value);
		else
		if (graph.get('databox', 'ymax') === false || data_y.manifold.max_value > graph.get('databox', 'ymax')) graph.set('databox', 'ymax', data_y.manifold.max_value);
	
		if (graph.get('databox', 'ymin') === false || data_y.manifold.bias < graph.get('databox', 'ymin')) graph.set('databox', 'ymin', data_y.manifold.bias);
		else
		if (graph.get('databox', 'ymax') === false || data_y.manifold.bias > graph.get('databox', 'ymax')) graph.set('databox', 'ymax', data_y.manifold.bias);
*/
	}
	
}

//
//	Object that contains all the functionalities of all tabs as function objects.
//	Uses $traj_file and $traj_dir from PHP
//
var tab_func = {
	
	// Tables tab.
	// Displays the HTML in the corresponding tab and handles all user actions.
	tables : function(tab_el)
	{
		var html = 
			'<div class="pg_info">\n\
			 1. Browse to select one of the available files on the server<br />\n\
			 2. Check \'Advise\' if the file was generated with speed advise enabled<br />\n\
			 3. Click \'Initialize\' to clear existing data and start with the file selected<br />\n\
			 4. Click \'Merge\' to add more data to the existing table on the server<br /><br />\n\
			 <input id="traj_file" type="text" value="<?php echo $config['traj_file']; ?>" style="vertical-align:middle;" />\n\
			 <input id="browse_btn" type="button" value="Browse..." style="vertical-align:middle;" title="Select a trajectory file from a list\nof available files on the server" />\n\
			 <input id="advise" type="checkbox" style="vertical-align:middle;" title="Check if the file was generated with vehicle speed advise."/>Advise\
			 <input id="merge_btn" cmd="merge" type="button" value="Merge" style="vertical-align:middle;" title="Merge data from this file with the existing data in the table."/>\n\
			 <input id="initialize_btn" cmd="init" type="button" value="Initialize" style="vertical-align:middle;" title="Clear existing data and re-initialize with the data in this file."/>\n\
			</div>';

		tab_el.innerHTML = html;

		// 'browse' command mplementation
		// expects an array of filenames:
		// ['file1', 'file2', ...]
		var browse = ge('browse_btn');
		browse.onclick = function()
		{
			var my = this;
			new ajax('GET', '/trajaim/?cmd=browse',
			function()
			{
				try {
					var files = JSON.parse(this.responseText);
					var html = '<div class="pg_block">\n\
								<div class="br_title pg_grad">Trajectory Files</div>\n\
								<select id="file_sel" class="br_list">';
					for (var f in files)
						html += '<option value="'+files[f]+'">'+files[f]+'</option>';
					html += '</select>\n\
							<div id="br_ok" class="bk_btn">OK</div>\n\
							</div>';
					var rc = my.getBoundingClientRect();
					popup(html, rc.left+10, rc.bottom+10);
					
					var ok_btn = ge('br_ok');
					ok_btn.onclick = function()
					{
						var file_sel = ge('file_sel');
						var file = file_sel[file_sel.selectedIndex].value;
						
						msg(file + " is selected");
						ge('traj_file').value = '<?php echo $config['traj_dir']; ?>'+file;
						popup('');
					};
				}
				catch(e)
				{
					msg('***ERROR:'+e.message+'<br>'+this.responseText );
				}
			},
			function()
			{
				msg('Network Failure');
			});
		};

		// 'init' and 'merge' implementations
		// These use the same handler, which uses the custom attribute 'cmd' to form the relevant ajax call
		var btn_init = ge('initialize_btn');
		var btn_merge = ge('merge_btn');
		
		btn_init.onclick = function()
		{
			var cmd = this.getAttribute('cmd');
			var traj_file = ge('traj_file');
			var advise = ge('advise');
			msg('Populating DB '+loading);
			new ajax('GET', '/trajaim/?cmd='+cmd+'&file='+ traj_file.value+'&adv='+(advise.checked ? "yes" : "no"),
			function()
			{
				if (this.status !== 200)
				{
					msg('***Invalid URL. Code:'+this.status);
					return;
				}
					
				try{
					var json = JSON.parse(this.responseText);
					if (json.hasOwnProperty('error'))
						msg('***ERROR:'+json.error);
					else
						msg('success');
				}catch(e)
				{
					msg('***ERROR:'+e.message+'<br>'+this.responseText );
				}
			},
			function()
			{
				msg('Network Failure');
			});
		};
		
		btn_merge.onclick = btn_init.onclick;
	},
	
	// Analysis tab
	// TODO: Implements statistics and graphs displays
	analysis : function (tab_el)
	{
		var my = this;
		new ajax('GET', '/trajaim/?cmd=gettypes',
		function()
		{
			var createSelection = function(el, vtypes)
			{
				var sel_el = ge('type_sel');
				html = '';
				for (var t in vtypes)
					html += '<option value="'+t+'">'+t+' : '+vtypes[t]+'</option>';
				sel_el.innerHTML = html;
			};
			
			try {
				// JSON: {"Car": 100, "EQ_Car": 200, ...}
				var vtypes = JSON.parse(this.responseText);
				
				createSelection(tab_el, vtypes);
				
				// handle vehicle typename selction
				var vehType_el = ge('type_sel');
				vehType_el.onchange = function()
				{
//					handleDropDown(ge('vehNr').value);
				};

				// handle vehicle number dropdown list
				var vehNr_el = ge('vehNr');
				vehNr_el.onkeyup = function(e)
				{
					var key = String.fromCharCode(e.keyCode);
					if ('0123456789'.indexOf(key) <0) return;
					if (vehNr_el.tm && vehNr_el.tm != 0)
						clearTimeout(vehNr_el.tm);
					vehNr_el.tm = setTimeout( function() 
					{ 
						handleDropDown(vehNr_el.value); 
						vehNr_el.tm = 0;
					}, 800);
				};
				
				var handleDropDown = function(value)
				{
					var my = this;
//					my.input = ge('vehNr');
					my.list = ge('vehNr_list');
					my.vehno = value;
					my.vehtype = ge('type_sel')[ge('type_sel').selectedIndex].value;
					new ajax('GET', '/trajaim/?cmd=get_veh_no&vehno='+my.vehno+'&vehtype='+my.vehtype,
					function()
					{
						// expecting array of numbers matching what was typed and the vehicle typename
						var o = JSON.parse(this.responseText);
						if (o.hasOwnProperty('VehNr'))
						{
							var s = '';
							var lst = o['VehNr'];
							for (var i in lst)
							{
								s += '<option value="'+lst[i]+'">';
							}
							my.list.innerHTML = s;
						}
						else
						if (o.hasOwnProperty('error'))
							msg("***ERROR: "+o.error);
					
					},
					function()
					{
						msg("Network Error");
					});
				};
				
				var ok_btn = ge('br_add');
				ok_btn.onclick = function() 
				{
					buildSelection('selected', {vehtype: ge('type_sel').value, vehno: ge('vehNr').value}, 'add');
				};
				
				var buildSelection = function(sel_id, keys, action)
				{
					var q_sel = ge(sel_id);
					if (!q_sel.hasOwnProperty('list'))
						q_sel.list = [];
					if (action === 'add')
					{
						if (keys.vehtype != '' && keys.vehno != '')
							q_sel.list.push(keys);
					}
					else
					if (action === 'del')
					{
						var list = [];
						for (var i in q_sel.list)
							if (q_sel.list[i].vehtype !== keys.vehtype || q_sel.list[i].vehno !== keys.vehno)
								list.push(q_sel.list[i]);
						q_sel.list = list;
					}
					else
					if (action === 'clear')
						q_sel.list = [];

					var s = '';
					if (q_sel.list.length > 0)
					{
						s = '<table>\n\
								<tr class="tbl_hdr">\n\
									<td>Vehicle type</td><td>Veh.#</td>\n\
									<td rowspan="'+(q_sel.list.length+1)+'">';
						s +=			'<div id="show_graph" class="bk_btn icon_btn"><i class="fa fa-line-chart fa-3x" aria-hidden="true"></i><br>Graph</div>\n\
									</td>\n\
									<td rowspan="'+(q_sel.list.length+1)+'" style="padding:0">\n\
										<table style="border:none;margin:0">\n\
											<tr>\n\
												<td style="border:none;text-align:right;">Lines<input id="en_lines" type="checkbox" checked onclick="setType(\'lines\', this.checked); graphs.each( function(graph, i) { graph.Show(); });"></td> \n\
												<td style="border:none;text-align:right;">Bars<input id="en_bars" type="checkbox"  onclick="setType(\'bars\', this.checked); graphs.each( function(graph, i) { graph.Show(); });"></td>  \n\
												<td style="border:none;text-align:right;">Scatter<input id="en_scatter" type="checkbox" checked onclick="setType(\'scatter\', this.checked); graphs.each( function(graph, i) { graph.Show(); });"></td>\n\
											</tr>\n\
											<tr>\n\
												<td style="border:none;text-align:right;">Trend<input id="en_trend" type="checkbox" onclick="setType(\'trend\', this.checked); graphs.each( function(graph, i) { graph.Show(); });"></td>\n\
												<td style="border:none;text-align:right;">HLines<input id="en_hlines" type="checkbox" checked onclick="setType(\'hlines\', this.checked); graphs.each( function(graph, i) { graph.Show(); });"></td>\n\
												<td style="border:none;text-align:right;">Export<input id="en_export" type="checkbox" ></td>\n\
											</tr>\n\
										</table>\n\
									</td>\n\
								</tr>';
						for (var i in q_sel.list)
							s += '<tr><td>' + q_sel.list[i].vehtype + '</td><td>' + q_sel.list[i].vehno + 
									' <span id="id_'+String(q_sel.list[i].vehtype) + String(q_sel.list[i].vehno)+'" class="bk_btn icon_btn"><i class="fa fa-times" aria-hidden="true"></i></span>'+
								'</td></tr>';
						s += '</table>';
					}					
					q_sel.innerHTML = s;
					if (q_sel.list.length > 0)
					{
						for (var i in q_sel.list)
						{
							var btn = ge('id_'+String(q_sel.list[i].vehtype) + String(q_sel.list[i].vehno));
							btn.onclick = function()
							{
								buildSelection('selected', {vehtype: q_sel.list[i].vehtype, vehno: q_sel.list[i].vehno}, 'del');
							};
						}
						
						// "Graph" button - displays all selected graphs
						//	TODO: get graph types from back-end with all associated params
						//		  and form corresponding GUI elements
						var show_btn = ge('show_graph');
						show_btn.onclick = function() 
						{
							var graph_area = ge('graph_area');
							if (ge('distanceOverTime').checked)
								graphs.enable('distanceOverTime', 'Distance over Time');
							else
								graphs.disable('distanceOverTime');
							if (ge('speedOverTime').checked)
								graphs.enable('speedOverTime', 'Speed over Time');
							else
								graphs.disable('speedOverTime');
							if (ge('accelerationOverTime').checked)
								graphs.enable('accelerationOverTime', 'Acceleration over Time');
							else
								graphs.disable('accelerationOverTime');
							if (ge('accelerationOverDistance').checked)
								graphs.enable('accelerationOverDistance', 'Acceleration over Distance');
							else
								graphs.disable('accelerationOverDistance');
							if (ge('speedOverDistance').checked)
								graphs.enable('speedOverDistance', 'Speed over Distance');
							else
								graphs.disable('speedOverDistance');
							if (ge('accelerationVsSpeed').checked)
								graphs.enable('accelerationVsSpeed', 'Acceleration vs Speed');
							else
								graphs.disable('accelerationVsSpeed');
							
							graph_area.innerHTML = '';
							for (var i in graphs.obj)
								graph_area.innerHTML += '<div class="graph" id="grph_'+i+'"></div>';
							adjustGraphArea();
							for (var i in graphs.obj)
								show('selected', 'grph_'+i );
							
//							console.info(graphs);
						};
					}
				};

			}
			catch(e)
			{
				msg('***ERROR:'+e.message+'<br>'+this.responseText );
			}
		},
		function()
		{
			msg('Network Failure');
		});
//		tab_el.innerHTML = '<i class="fa fa-bar-chart"></i> Analysis';

		var adjustGraphArea = function()
		{
			var graph_area = ge('graph_area');
			var cont = ge('tabs');
			var dh = cont.getBoundingClientRect().bottom - graph_area.getBoundingClientRect().bottom;
			var h = graph_area.getBoundingClientRect().height;
//			msg('h:'+h+' dh:'+dh);
			graph_area.style.height = (h+dh-5) + 'px';
		};

		window.addEventListener('resize', adjustGraphArea, false);
	}
		
};

function tab(grp_id, tab_id)
{
	var grp = ge(grp_id).getElementsByTagName('div');
	for (var i=0; i < grp.length; i++)
	{
		var item = grp[i];
		if (item.id === tab_id)
		{
			item.style.display = '';
			if (tab_func.hasOwnProperty(tab_id))
				tab_func[tab_id](item);
		}
		else
		if (item.hasAttribute('tab-item'))
			item.style.display = 'none';
	
//		console.info(item);
	}
}

function Resize()
{
}

function Init()
{
	msg('');  // hide message widget
//	ge('pg_title').innerHTML = 'V'+mon_version+' <span style="font-weight: normal; font-size: 10px;">(cvsmsc V' + version+')</span>';  // set version
	tab('tabs', 'tables');	// this will set the 'groups' tab as active and also call onActivate('groups') to populate it; 
}
</script>
</head>
<body onload="Init()" onresize="Resize()">
	<div class = "content">
		<div class="pg_hdr pg_grad">
			<div class="pg_title"><?php echo $config['app_name'] ?> <span id="pg_title">V<?php echo $config['version'] ?></span>
				<span class="pg_msg" id="pg_msg"></span>
			</div>
		</div>
		<div class="pg_tabs pg_block pg_grad">
			<ul id="tabs_list">
				<li id="groups_li"> <div onclick="javascript:tab('tabs', 'tables');" title="Tables"><i class="fa fa-database" aria-hidden="true"></i> Tables</div> </li>
				<li id="cell_li"> <div onclick="javascript:tab('tabs', 'analysis');" title="Analysis"><i class="fa fa-bar-chart"></i> Analysis</div> </li>
			</ul>
			<div class="pg_tabs pg_block" id="tabs">
				<div tab-item class="pg_data" id="tables"></div>
				<div tab-item class="pg_data pg_full" id="analysis" style="display:none;">
					<div class="pg_data">
						<div class="pg_div">
							<label for="type_sel">Vehicle Type:</label>
							<select id="type_sel" class="br_list">
							</select>
							<label for="vehNr">Veh.#:</label>
							<input class="vehnr" type="text" id="vehNr" list="vehNr_list">
							<datalist id="vehNr_list">
							</datalist>
							<div id="br_add" class="bk_btn icon_btn"><i class="fa fa-play" aria-hidden="true"></i></div>
							<div></div>
							<table style="border:none; margin:0;">
							<tr>
							<!-- 
								TODO: create these tags based on data obtained from back-end in conjunction with the 'Graph' button 
							-->
								<td style="border:none;text-align:right;">Speed over Time<input id="speedOverTime" type="checkbox" checked /></td>
								<td style="border:none;text-align:right;">Speed over Distance<input id="speedOverDistance" type="checkbox" /></td>
							</tr><tr>
								<td style="border:none;text-align:right;">Distance over Time<input id="distanceOverTime" type="checkbox" checked /></td>
								<td style="border:none;text-align:right;">Acceleration vs Speed<input id="accelerationVsSpeed" type="checkbox" /></td>
							</tr><tr>
								<td style="border:none;text-align:right;">Acceleration over Time<input id="accelerationOverTime" type="checkbox" checked/></td>
								<td style="border:none;text-align:right;">Acceleration over Distance<input id="accelerationOverDistance" type="checkbox" /></td>
							</tr>
							</table>
						</div>
						<div style="display:inline-block;vertical-align:top;" id="selected"></div>
					</div>
					<div class="graph_area" id="graph_area"></div>
				</div>
			</div>
		</div>
		<div class="pg_counter" id="msg"></div>
	</div>
</body>
</html>

<?php
}
?>