<?php

require_once "../common/Utilities.php";

function getR($what, $default="")
{
	 return isset($_REQUEST[$what]) ? $_REQUEST[$what] : $default;	
}


$cmd = getR('cmd');
if ($cmd !== '')
{
	$file = file_get_contents("C:/Appserv/www/temp/Latencies_AVG_log.txt");
	$latencies = json_decode($file);
}

$route = '';
$esme = '';
if ($cmd == 'ajax')
{
	$route = getR('route');
	$esme = getR('esme');
}
elseif ($cmd === 'getNames')
{
	$routes = array_keys((array)$latencies);
	$ret = array();
	foreach ($routes as $route)
	{
		$ret[$route] = array();
		foreach ($latencies->{$route}->esme as $esme => $edata)
			$ret[$route][] = $esme;
	}
	echo json_encode($ret);
	exit();
}
elseif ($cmd === 'get')
{
	$route = getR('route');
	$esme = getR('esme');
	if ($route !== '' && $esme !== '')
	{
		$esmes = explode(",",$esme);
		
		$colors = array(
			array( "c1" => "#cc3300", "c2" => "rgba(51,153,255,0.3)"),
			array( "c1" => "#22ff33", "c2" => "rgba(51,21,153,0.4)"),
			array( "c1" => "#ffdd22", "c2" => "rgba(51,121,123,0.4)"),
			array( "c1" => "#cc9922", "c2" => "rgba(180,200,95,0.4)"),
			array( "c1" => "#1a75ff", "c2" => "rgba(255,102,255,0.4)"),
		);
		if (property_exists($latencies, $route))
		{
			$data = false;
			$i = 0;
			foreach($esmes as $esme)
			{
				if (property_exists($latencies->{$route}->esme, $esme) )
				{
					if (!$data)
						$data = array(
						"type" => "lines,scatter,trend",  // lines,bars,scatter,trend
						"orient" => "horizontal", // | "vertical",
						"axis_color" => "#0000ff",
						"legend" => array( "position" => "right:90px;top:5px;"),
						"x_label" =>  "Timestamps",
						"y_label" => "Latencies in seconds",
						"x_msg" => "time: ",
						"values" => array()
						);
						
					$data['values'][]=	array(
							"y_msg" => "latency: ",
							"y_label" => $esme." (".count($latencies->{$route}->esme->{$esme}->latencies)." sms)",
							"manifold" => $latencies->{$route}->esme->{$esme}->manifold,
							"color" => $colors[$i]['c1'],
							"bar_color" => $colors[$i]['c2'],
							"x" =>  $latencies->{$route}->esme->{$esme}->timestamps,
							"y" => $latencies->{$route}->esme->{$esme}->latencies
						);
					$i = ($i+1)%count($colors);
				}
			}
			if (!$data) 
				echo '{"error": "Esme not found at this route"}';
			else
				echo json_encode($data);
			exit();
		}
		else
			echo '{"error": "Route/Esme not found"}';
	}
	else
	{
		echo '{"error": "Invalid route,esme"}';
		exit();
	}
}
elseif ($cmd != '')
{
	echo '{"error": "Unknown Command"}';
	exit();
}

?>

<html>
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Expires" content="0" />
<head>
<title>Test Data</title>
<style>
body {
	font-family: Verdana;
	font-size: 12px;
}

input[type=button], input[type=text], select {
	border: 1px solid lightgrey;
	border-radius:3px;
	padding:2px 2px 2px 2px;
}

select {
	max-height: 80px;
}

.route {
	margin: 10px 0px 0px 0px;
}
.graph {
	display:inline-block;
	border: 1px solid lightgrey;
	border-radius: 3px;
	width: 90%;
	height: 80%;
	box-shadow: 5px 5px 20px -5px rgba(0,0,0,0.45);
	margin: 0px 5px 0px auto;
	vertical-align:top;
}
.center {
	text-align:center;
	width:100%;
}
</style>
<script src="/amd_proj/common/Grapher.js"></script>

<script>

function ge(id)
{
	return document.getElementById(id);
}

var data = {
 	type: "scatter,lines", // "scatter" | lines" | "bars" | "pie"
	orient: "horizontal", // | "vertical",
	axis_color: "#000000",
	legend : {
		position: "right:3px;bottom:3px;",
	},
	x_label: "Time",
	y_label: "Latencies in seconds",
	x_msg: "time: ",
	values: [
		{
			y_label : "Latency (sec)",
			y_msg : "value: ",
			color: "#cc3300",
			bar_color: "rgb(80,130,255)",
			manifold: { min_value: 75, max_value:140, bias: 0 },
			x: ["1478801907",
				"1478801899",
				"1478801897",
				"1478801896",
				"1478801886",
				"1478801868",
				"1478801841",
				"1478801824",
				"1478801818",
				"1478801797",
				"1478801787",
				"1478801769",
				"1478801765",
				"1478801752",
				"1478801731",
				"1478801724",
				"1478801709",
				"1478801707",
				"1478801705",
				"1478801697",
				"1478801688",
				"1478801671",
				"1478801649",
				"1478801617",
				"1478801608",
				"1478801606",
				"1478801593",
				"1478801580",
				"1478801566",
				"1478801557",
				"1478801556",
				"1478801554",
				"1478801525",
				"1478801509",
				"1478801507",
				"1478801503",
				"1478801491",
				"1478801491",
				"1478801485",
				"1478801482",
				"1478801461",
				"1478801459",
				"1478801441",
				"1478801432",
				"1478801410",
				"1478801406",
				"1478801376",
				"1478801370",
				"1478801368",
				"1478801363",
				"1478801361",
				"1478801337",
				"1478801332",
				"1478801325",
				"1478801319",
				"1478801318",
				"1478801316",
				"1478801315",
				"1478801304",
				"1478801297",
				"1478801280",
				"1478801263",
				"1478801245",
				"1478801244",
				"1478801243",
				"1478801240",
				"1478801230",
				"1478801228",
				"1478801226",
				"1478801211",
				"1478801208",
				"1478801208",
				"1478801185",
				"1478801181",
				"1478801159",
				"1478801155",
				"1478801148",
				"1478801144",
				"1478801139",
				"1478801126",
				"1478801118",
				"1478801115",
				"1478801110",
				"1478801102",
				"1478801097",
				"1478801097",
				"1478801095",
				"1478801091",
				"1478801085",
				"1478801082",
				"1478801077",
				"1478801076",
				"1478801064",
				"1478801056",
				"1478801035",
				"1478801033",
				"1478801028",
				"1478800994",
				"1478800994",
				"1478800988",
				"1478800979",
				"1478800977",
				"1478800957",
				"1478800945",
				"1478800925",
				"1478800919",
				"1478800908",
				"1478800900",
				"1478800889",
				"1478800889",
				"1478800874",
				"1478800837",
				"1478800816",
				"1478800793",
				"1478800792",
				"1478800790",
				"1478800779",
				"1478800762",
				"1478800746",
				"1478800737",
				"1478800734"],
			y:[	"8",
				"17",
				"11",
				"40",
				"8",
				"8",
				"7",
				"4",
				"14",
				"7",
				"8",
				"23",
				"8",
				"23",
				"9",
				"18",
				"4",
				"10",
				"12",
				"6",
				"8",
				"16",
				"5",
				"24",
				"7",
				"17",
				"15",
				"21",
				"21",
				"11",
				"8",
				"23",
				"4",
				"36",
				"8",
				"13",
				"8",
				"18",
				"17",
				"12",
				"8",
				"13",
				"8",
				"42",
				"12",
				"8",
				"10",
				"12",
				"6",
				"8",
				"10",
				"7",
				"30",
				"10",
				"32",
				"6",
				"8",
				"32",
				"5",
				"8",
				"17",
				"8",
				"32",
				"7",
				"29",
				"9",
				"8",
				"24",
				"18",
				"148",
				"19",
				"6",
				"37",
				"9",
				"19",
				"14",
				"9",
				"5",
				"10",
				"15",
				"10",
				"30",
				"8",
				"9",
				"7",
				"8",
				"23",
				"18",
				"10",
				"16",
				"7",
				"34",
				"11",
				"9",
				"4",
				"5",
				"10",
				"9",
				"5",
				"19",
				"5",
				"34",
				"9",
				"22",
				"8",
				"8",
				"19",
				"8",
				"16",
				"18",
				"4",
				"34",
				"8",
				"19",
				"11",
				"9",
				"24",
				"6",
				"11",
				"10",
				"5"]
		 }
		]
	};
	
	
var data1 = 
{
 	type: "lines,bars,scatter", //| "bar" | "pie"
	orient: "horizontal", // | "vertical",
	axis_color: "#0000ff",
	legend : {
		position: "right:90px;top:20px;",
	},
	x_label: "X-Axis",
	y_label: "Y-Axis Multiple Data",
	x_msg: "pos:",
//	x: [32, 44, 52, 67,82, 122,132,245, 333, 344],
	values: [
		{
			y_msg : "series1: ",
			y_label : "Test Data Series 1",
			color: "#cc3300",
			bar_color: "rgba(51,153,255,0.3)",
			manifold: {min_value:30, max_value:60, bias:0 },
			x: [12, 74, 79, 82, 123, 139, 241, 287, 363, 370, 388, 412],
			y : [19, 14, 38, 43, 96, 118, 45, 53, 12,44, 25, 93]
		},
		{
			y_msg : "extra1: ",
			y_label : "Y-Axis More Data",
			color: "#22ff33",
			bar_color: "rgba(51,21,153,0.4)",
			manifold: {min_value:30, max_value:60, bias:0 },
			x: [32, 44, 52, 67,82, 122,132,245, 333, 344],
			y : [20, 25, 30, 33, 15, 22, 57, 28, 39, 19]
		},
		{
			y_msg : "extra2: ",
			y_label : "Y-Axis Extra Data",
			color: "#ffdd22",
			bar_color: "rgba(51,121,123,0.4)",
			manifold: {min_value:30, max_value:60, bias:0 },
			x : [23, 56, 67, 69, 72, 140, 180, 205, 255, 280],
			y : [60, 35, 72, 39, 28, 42, 54, 21, 29, 17]
		}
	]
 };
 
graph = null;
latencies = [];
function getlatencyData(list_id)
{
	var req = new XMLHttpRequest();
	req.onload = function()
	{
		data = JSON.parse(this.responseText);
		if (data.hasOwnProperty('error'))
		{
			alert(data.error);
		}
		else
		{
			latencies = data;
			s = '';
			for (var route in latencies)
				s += '<option value="' + route + '">';
			document.getElementById(list_id).innerHTML = s;
		}
	};
	req.onerror = function()
	{
		alert("***Error: "+this.responseText);
	};
	
	req.open("GET", "testGrapher.php?cmd=getNames", true);
	req.send();
}

function fillEsme(route, list_id)
{
	var s = '';
	for (var esme in latencies[route])
	{
		s += '<option value="' + latencies[route][esme] + '">'+latencies[route][esme]+'</option>';
	}
	document.getElementById(list_id).innerHTML = s;
	document.getElementById('inp_esme').value = '';
}

function add(list_id, inp_id)
{
	var list = document.getElementById(list_id);
	var input = document.getElementById(inp_id);
	var v = input.value.trim();
	if (v == '')
		input.value += list[list.selectedIndex].value;
	else
		input.value += ','+list[list.selectedIndex].value;
}

function setType(which, value)
{
	graph.enable(which, value);
//	graph.Show();
	document.getElementById('en_'+which).checked = value;
}

function show(id, graph_id)
{
	var route = document.getElementById(id.route).value;
	var esme = document.getElementById(id.esme).value;

	var req = new XMLHttpRequest();
	req.onload = function()
	{
		data = JSON.parse(this.responseText);
		if (data.hasOwnProperty('error'))
		{
			alert(data.error);
		}
		else
		{
			graph = new Grapher(data, "Latencies - "+route, "grph");
			graph.onFormat = g_format;
			graph.onCalc = g_calc;
			graph.Init();
			graph.enable('lines', document.getElementById('en_lines').checked);
			graph.enable('bars', document.getElementById('en_bars').checked);
			graph.enable('scatter', document.getElementById('en_scatter').checked);
			graph.enable('trend', document.getElementById('en_trend').checked);
			graph.enable('hlines', document.getElementById('en_scatter').checked);
//			graph.set('databox', 'ymin', 0);
//			graph.set('databox', 'ymax', 300);
			graph.Show();
		}
	};
	req.onerror = function()
	{
		alert("***Error: "+this.responseText);
	};
	
	req.open("GET", "testGrapher.php?cmd=get&route="+route+"&esme="+esme, true);
	req.send();
}	

function dateFormat(dt)
{
	var days = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
	var months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
	var s = "Invalid Timestamp";
	if (!isNaN(dt.getHours()))
	{
		var hr = dt.getHours();  if (hr < 10) hr = "0"+hr;
		var mn = dt.getMinutes(); if (mn < 10) mn = "0"+mn;
		var sc = dt.getSeconds(); if (sc < 10) sc = "0"+sc;
		if (arguments.length > 1 && arguments[1] === 'short')
		{
			var day = dt.getDate(); if (day < 10) day = "0"+day;
			var month = dt.getMonth() + 1; if (month < 10) month = "0"+month;
			s = dt.getFullYear()+'-'+month+'-'+day+' '+hr+':'+mn+':'+sc+'';
		}
		else
		if (arguments.length > 1 && arguments[1] === 'short_notime')
		{
			var day = dt.getDate(); if (day < 10) day = "0"+day;
			var month = dt.getMonth() + 1; if (month < 10) month = "0"+month;
			s = dt.getFullYear()+'-'+month+'-'+day;
		}
		else
			s = days[dt.getDay()] + ' ' + dt.getDate() + ' ' + months[dt.getMonth()] + ' ' + dt.getFullYear()+ ' '+ hr +':'+ mn +':'+ sc;
	}
	return s;
}

function g_format(label, value)
{
	if (label.indexOf('y-axis')>=0)
	{
		if (value.indexOf('.')>=0)
			value = parseFloat(Math.round(value * 100) / 100).toFixed(1);
	}
	else
	if (label.indexOf('x-axis')>=0)
	{
		var dt = new Date(value * 1000);
		value = dateFormat(dt, 'short');
	}
	else
	if (label.indexOf('time')>=0)
		value = dateFormat(new Date(value * 1000), 'short');
	else
	if (label.indexOf('latency') >=0)
		value = value+' sec';
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
	if (addon_name === 'hlines')
	{
		addon.set('ymarks', [data_y.manifold.min_value, data_y.manifold.max_value, data_y.manifold.bias]);
		
		// modify world coordinate extents based on manifold line coordinates
		if (graph.get('databox', 'ymin') === false || data_y.manifold.min_value < graph.get('databox', 'ymin')) graph.set('databox', 'ymin', data_y.manifold.min_value);
		else
		if (graph.get('databox', 'ymax') === false || data_y.manifold.min_value > graph.get('databox', 'ymax')) graph.set('databox', 'ymax', data_y.manifold.min_value);
	
		if (graph.get('databox', 'ymin') === false || data_y.manifold.max_value < graph.get('databox', 'ymin')) graph.set('databox', 'ymin', data_y.manifold.max_value);
		else
		if (graph.get('databox', 'ymax') === false || data_y.manifold.max_value > graph.get('databox', 'ymax')) graph.set('databox', 'ymax', data_y.manifold.max_value);
	
		if (graph.get('databox', 'ymin') === false || data_y.manifold.bias < graph.get('databox', 'ymin')) graph.set('databox', 'ymin', data_y.manifold.bias);
		else
		if (graph.get('databox', 'ymax') === false || data_y.manifold.bias > graph.get('databox', 'ymax')) graph.set('databox', 'ymax', data_y.manifold.bias);
	}
}

function init()
{
	getlatencyData('route_lst');
	
	// remote ajax call for specific route/esme
	var route = '<?php echo $route; ?>';
	var esme = '<?php echo $esme; ?>';
	if ( route !== '')
	{
		ge('inp_route').value = route;
		setTimeout( function() { 
			fillEsme(route, 'esme_lst');
			if (esme === '')
				for (var es in latencies[route]) { esme = latencies[route][es]; break; }
			ge('inp_esme').value = esme;
			show({route:'inp_route', esme:'inp_esme'}, 'grph');
/*			setTimeout( function() {
				setType('lines', true);
				setType('bars', false);
				setType('scatter', true);
				setType('trend', true);
				setType('hlines', true);
				graph.Show();
				}, 500);*/
			}, 500);
	}
	else // 
	{
		graph = new Grapher(data, "Latencies - montya2p@41702 10-Nov-2016 20:19:50", "grph");
		graph.Init();
		graph.onFormat = g_format;
		graph.onCalc = g_calc;
		setType('lines', data.type.indexOf('lines') >=0);
		setType('bars', data.type.indexOf('bars') >=0);
		setType('scatter', data.type.indexOf('scatter') >=0);
		setType('trend', data.type.indexOf('trend') >=0);
		setType('hlines', data.type.indexOf('hlines') >=0);
		graph.Show();
	}
//	var graph1 = new Grapher(data1, "Test Data", "grph1");
//	graph1.Init();  // to display immediately
//	graph1.Show();
}

</script>
</head>
<body onload="init()">
<div class="center">
	<div class="graph">
		<div id="grph"></div>
		<div class="route">
			<table style="font-size:12px;margin:0 auto">
			<tr><td>Route:</td><td><input type="text" id="inp_route" list="route_lst" onblur="fillEsme(this.value, 'esme_lst')"><datalist id="route_lst"></datalist></td>
				<td><input type="button" value="Show" onclick="show({route:'inp_route', esme:'inp_esme'}, 'grph')"></td>
			</tr>
			<tr><td>Esme:</td><td><input type="text" id="inp_esme"></td><td></td>
			<tr><td></td><td><select style="width:100%;" id="esme_lst"></select></td><td><input type="button" value="+" onclick="add('esme_lst', 'inp_esme')"></td>
			</tr>
			<tr><td colspan="3">Lines<input id="en_lines" type="checkbox" checked onclick="setType('lines', this.checked); graph.Show();">  
								Bars<input id="en_bars" type="checkbox"  onclick="setType('bars', this.checked); graph.Show();">  
								Scatter<input id="en_scatter" type="checkbox" checked onclick="setType('scatter', this.checked);graph.Show();">
								Trend<input id="en_trend" type="checkbox" checked onclick="setType('trend', this.checked);graph.Show();">
								HLines<input id="en_hlines" type="checkbox" checked onclick="setType('hlines', this.checked);graph.Show();">
				</td></tr>
			</table>
		</div>
	</div>
<!--	<div class="graph" style="width:25%;height:30%;">
		<div id="grph1"></div>
	</div> -->
</div>

</body>
</html>