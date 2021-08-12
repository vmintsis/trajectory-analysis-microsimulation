/**
 *	Grapher
 *	@class Grapher
 */

/**
 *	Grapher Class
 *	
 *	This object can display a series of values in a graph using
 *	multiple series of points, lines or bars.
 *	Supports display of values with mouse over in a hoverbox.
 *	Each series may contain different number of values for X and Y axes.
 *	The common X-axis valeus will be the results of mergin all series X-axis values and
 *	the graphs will be plotted based on the resulting common X-axis values
 *	and their own Y-axis values.
 *	
 *	Example:
 *~~~{.php}
 *		var data1 = 
 *		{
 *			type: "lines,bars,scatter", // These can be specified in any combination or each one alone
 *			orient: "horizontal", // | "vertical",
 *			axis_color: "#0000ff",
 *			legend : {
 *				position: "right:90px;top:20px;",
 *			},
 *			x_label: "X-Axis",		// X-axis label
 *			y_label: "Y-Axis Multiple Data",	// Y-axis label
 *			x_msg: "time:",		// indicator shown for X-axis values in the hoverbox
 *			values: [
 *				{
 *					y_msg : "series1: ",	// indicator shown for this series' y values in the hoverbox
 *					y_label : "Test Data Series 1",	// hoverbox title for this series
 *					color: "#cc3300",	// css scatter dot color
 *					bar_color: "rgba(51,153,255,0.3)",	// css bar color 
 *					x: [12, 74, 79, 82, 123, 139, 241, 287, 363, 370, 388, 412],	// x-axis values for this series
 *					y : [19, 14, 38, 43, 96, 118, 45, 53, 12,44, 25, 93]	// y-axis values for this series (MUST be the same number as x values)
 *				},
 *				{
 *					y_msg : "extra1: ",
 *					y_label : "Y-Axis More Data",
 *					color: "#22ff33",
 *					bar_color: "rgba(51,21,153,0.4)",
 *					x: [32, 44, 52, 67,82, 122,132,245, 333, 344],
 *					y : [20, 25, 30, 33, 15, 22, 57, 28, 39, 19]
 *				},
 *				{
 *					y_msg : "extra2: ",
 *					y_label : "Y-Axis Extra Data",
 *					color: "#ffdd22",
 *					bar_color: "rgba(51,121,123,0.4)",
 *					x : [23, 56, 67, 69, 72, 140, 180, 205, 255, 280],
 *					y : [60, 35, 72, 39, 28, 42, 54, 21, 29, 17]
 *				}
 *			]
 *		 };
 *            
 *		var graph = new Grapher(data, "Graph Title", "element_id");
 *		graph.Init();  // to display immediately
 *~~~		
 *
 *  Data Values displayed on the axes will be computed at equally spaced intervals based on extrapolations
 *  derived from the input data values for each axis, so that the demarcations are visible.
 *  The actual values will be displayed on the hover div for the point closest to the mouse pointer which will also
 *  be highlighted in the color specified for each series. An indicator of the same color will be displayed in the
 *  hover div.
 *  Grapher hooks the browser window resize event and if placed in an appropriate container element that resizes with the
 *  browser, then Grapher will also redraw its data in its resized canvas area.
 *
 *  The orientation specification rotates the graph by 90 degrees effectively as if y-values  and x-values had been swapped
 *  and x and y labels given for the opposite axes (tbd).
 *  
 *  PLugins are supported by using the addOn() method to register a plugin. Two are included, linearRegression_addOn
 *  and division_addOn.
 *  Each plugin must have the structure indicated by the included plugins. 
 *  
 *  Callbacks are provided to format various parts of the display and provide extra data to a plugin that requires them.
 *  Extra data fields can be added to any of the y-axis series in the data above, under the values array.
 *  onCalc:
 *		At coordinate calculation time, onCalc will be called for each registered plugin, with parameters indicating
 *		which data series is being calculated, the data series itself, the plugin registered name and the plugin itself.
 *		A user-override of this callback can set any parameter the plugin requires, either by obtaining it from the 
 *		series data provided in the parameter, or otherwise.
 *	onFormat:
 *		This callback is called when rendering various parts of the graph to allow the user to format the specified value
 *		according to the specified key passes as parameter. Keys and values passed are the ones specified in the data.
 *		Keys passed currently are the following, each with the corresponding value speficied in the data provided: 
 *				x_msg value, y_msg value, 'x-axis', 'y-axis', 
 *				'draw_params' - value for this is an object like {key: <addon_name>, value: <draw params obj>} 
 *								so  the format callback can modify any of the drawing parameters for the addon. 
 *								See addons below for draw_param syntax.
 *		
 *	Grapher allows the vertical and horizontal limits to be adjusted at minimum and maximum levels beyond the data series
 *	provided. A property called 'databox' is provided for this with members 'ymin', 'xmin', 'ymax', 'xmax' that can be used
 *	to set the minimum and maximum extra values of the data ranges. This will affect the display so that the x-y value
 *	pairs will be the minimum and maximum values of the x and y axes respectively, as their names suggest.
 *	Methods set() and get() can be used to set and access these values. The defaults are null and if no values are externally set
 *	the return value of get() will be false.
 *	
 *	Methods get() and set() can also be used to set and get parameters for plugins. Please see the documentation near these 
 *	methods for details.
 *  
 *	@param data (array) 		A description of the data to be plotted horizontally, vertically or as a pie
 *	@param label (string)		The title of the graphic
 *	@param div_id (string)		The ID of the html element to be used as container for this control
 *
 *	@author Dimitrios Tsobanopoulos, Nov-2016 (adapted from earlier work)
 */

// --------------------------------
//  Built-in Grapher add-ons
// --------------------------------

// Linear regression (trendline)
var linearRegression_addon = 
{
	// set externally to indicate current state as enabled or disabled
	enabled: false,

	set: function(what, data)
	{
		if (what === 'enabled')
			this.enabled = data;
	},
	
	get: function(what)
	{
		if (what === 'enabled')
			return this.enabled;
		else
		if (what.key && what.key  === 'coords')
			return what.value.coords;
		return false;
	},

	//	Calculates the world-space coordinates of the data passed to this addon
	//	Grapher is expected to map them to its view coordinates, store them, and then pass them to our draw()
	//	Note: used the same algorithm as in Utilities.php
	calc: function(x, y)
	{
		var lr = {};
		var n = y.length;
		var sum_x = x.reduce(function(a,b) { return a+b;});
		var sum_y = y.reduce(function(a,b) { return a+b;});
		var avgX = sum_x / n;
		var avgY = sum_y / n;

		var numerator = 0.0;
		var denominator = 0.0;

		for(var i=0; i<n; i++)
		{
			numerator += (x[i] - avgX) * (y[i] - avgY);
			denominator += (x[i] - avgX) * (x[i] - avgX);
		}
		lr.slope = Math.atan2(numerator, denominator);
		lr.intercept = (sum_y - lr.slope * sum_x)/n;

		lr.coords = {x: [], y: []};
		for (var i = 0; i < x.length; i++) 
		{
			lr.coords.x.push(x[i]);
			lr.coords.y.push(x[i] * lr.slope + lr.intercept);
		}
		return lr;
	},
	
	//	Draws the linear regression curve in the spcified context with the indicated parameters
	//	Draw data (lr) are:
	//	{
	//		coords: [{x:<xvalue>, y:<yvalue>},... ]  - the calculated view coordinates
	//		data: <all data the plugin returned from its calc method>
	//	}
	//	Draw params (dp) are:
	//	{
	//		color: <css color style>, 
	//		lineJoin: <canvas line join spec>, 
	//		lineDash: <e.g. [5,3] - 5pixels drawn, 3 pixels blank>,
	//		fillColor: <css fill color style>, 
	//		font: <font name to use>, 
	//		fontSize: <font size in pixels>
	//	}
	draw: function(ctx, lr, dp)
	{
		if (lr.coords.length <=0) return;
		ctx.save();
		ctx.beginPath();
		ctx.font = "bold " + (dp.fontSize ) + "px Helvetica"; //+ dp.font;
		ctx.setLineDash(dp.lineDash);
		ctx.strokeStyle = dp.color;
		ctx.lineJoin=dp.lineJoin;
		if (lr.coords[lr.coords.length-1].x < lr.coords[0].x)
			lr.coords.reverse();
		for (var i=0; i<lr.coords.length; i++)
		{
			var px = lr.coords[i].x;
			var py = lr.coords[i].y;

			if (i==0)
			{
				ctx.moveTo(px, py);
				ctx.fillStyle="#00ff00";
				ctx.fillRect(px-2, py-2, 4, 4);
			}
			else
			{
				ctx.lineTo(px, py);
				ctx.fillStyle="#0000ff";
				ctx.fillRect(px-2, py-2, 4, 4);
			}
		}
		var txt = (lr.data.slope < 0.0 ? "Dropping " : "Increasing ")+String((lr.data.slope * 180.0 / Math.PI).toFixed(2))+' deg';
		var sz = ctx.measureText(txt);
		ctx.fillStyle = '#ddd';
		var x = lr.coords[0].x + (lr.coords[lr.coords.length-1].x - lr.coords[0].x)/2;
		var y = lr.coords[0].y + (lr.coords[lr.coords.length-1].y - lr.coords[0].y)/2;
		var calc_slope = Math.atan2(lr.coords[lr.coords.length-1].y - lr.coords[0].y, lr.coords[lr.coords.length-1].x - lr.coords[0].x);
		ctx.stroke();
		ctx.save();
		ctx.translate(x, y-3);
		ctx.rotate(calc_slope);
		ctx.fillRect(-2,-dp.fontSize+1, sz.width+4, dp.fontSize+4);
		ctx.restore();
		ctx.fillStyle = '#000';
		ctx.translate(x, y-2);
		ctx.rotate(calc_slope);
		ctx.imageSmoothing = true;
		ctx.fillText(txt, 0, 0); 
		ctx.restore();
	},
	
};

// adds one more lines or rectangles across the graph to indicate specific areas
var division_addon = 
{
	// set externally to indicate current state as enabeld or disabled
	enabled: false,

	set: function(what, value)
	{
		if (what == 'enabled')
			this.enabled = value;
		else
		if (what == 'ymarks')
			this.yLines = value;
	},
	
	get: function(what)
	{
		if (what === 'enabled')
			return this.enabled;
		else
		if (what === 'ymarks')
			return this.yLines;
		else
		if (what.key && what.key  === 'coords')
			return what.value.coords;
		return false;
	},
	
	//	Calculates the world-space coordinates of the addon
	//	Grapher is expected to map them to its view coordinates and store them in ourt coords array
	calc: function(x, y)
	{
		var xmin = Math.min.apply(null, x), xmax = Math.max.apply(null, x);
		var ymin = Math.min.apply(null, y), ymax = Math.max.apply(null, y);
		var crd = { x:[], y:[] };
		for (var i=0; i<this.yLines.length; ++i)
		{
			crd.x.push(xmin);
			crd.y.push(this.yLines[i]);
			crd.x.push(xmax);
			crd.y.push(this.yLines[i]);
		}
		return {coords: crd, yLines: this.yLines};
	},
	
	//	Draws the linear regression curve in the spcified context with the indicated parameters
	//	Draw data (dt) are:
	//	{
	//		coords: [{x:<xvalue>, y:<yvalue>},... ]  - the calculated view coordinates
	//		data: <all data the plugin returned from its calc method>
	//	}
	//	Draw params (dp) are:
	//	{
	//		color: <css color style>, 
	//		lineJoin: <canvas line join spec>, 
	//		lineDash: <e.g. [5,3] - 5pixels drawn, 3 pixels blank>,
	//		fillColor: <css fill color style>, 
	//		font: <font name to use>, 
	//		fontSize: <font size in pixels>
	//	}
	draw: function(ctx, dt, dp)
	{
		if (dt.coords.length == 0) return;
		ctx.save();
		ctx.font = dp.fontSize + "px " + dp.font;
		ctx.beginPath();
		ctx.setLineDash(dp.lineDash);
		ctx.strokeStyle = dp.color;
		ctx.lineJoin=dp.lineJoin;
		for (var i=0; i<dt.coords.length-1; i+=2)
		{
			ctx.moveTo(dt.coords[i].x, dt.coords[i].y);
			ctx.lineTo(dt.coords[i+1].x, dt.coords[i+1].y);
			var txt = String(dt.data.yLines[i/2]);
			var sz = ctx.measureText(txt);
			ctx.fillStyle = '#ddd';
			ctx.fillRect(dt.coords[i].x, dt.coords[i].y-dp.fontSize+1, sz.width, dp.fontSize);
			ctx.fillStyle = '#000';
			ctx.fillText(txt, dt.coords[i].x, dt.coords[i].y-1);
		}
		ctx.stroke();
		ctx.restore();
	},

	yLines: []
};


// ----------------------------------------------
// Grapher class 
// ----------------------------------------------
(function () {
	// Constructor
	this.Grapher = function(data, title, div_id)
	{
		this._data = { x: [], y: data.values };
		for (var i=0; i<this._data.y.length; ++i)
		{
			this._data.y[i].coords = [];
			this._data.y[i].addon_data = {};
		}
		this._type = data.type;
		this._orient = data.orient;
		this._xLabel = data.x_label;
		this._yLabel = data.y_label;
		this._axis_color = data.axis_color;
		this._xMsg = data.x_msg;
		this._title = title;
		this._div_id = div_id;
		this._div_cont_id = div_id+'_cont';
		this._cnv_id = div_id+'_gfx';
		this._mouse = {x:0, y: 0};
		this._floater = document.createElement('div');
		this._legend = data.legend;
		this._legend.el = document.createElement('div');
		this._addOns = {};
		this._enable = {lines: this._type.indexOf('lines') >=0,
						bars: this._type.indexOf('bars') >=0,
						scatter: this._type.indexOf('scatter') >=0};
		this._zoom = 1.0;
		this._dataBox = {
				xmin: null, xmax: null, ymin: null, ymax: null,
				set: function(what, value) { if (this.hasOwnProperty(what)) this[what] = value; },
				get: function(what) { return (this.hasOwnProperty(what)) ? (this[what] != null ? this[what] : false ) : false; }
			};

		// callbacks
		this.onFormat = function(l, v) { return v; };
		this.onCalc = function(yi, data_y, addon_name, addon) { return false; };
		
		// ---------
		var my = this;
		var dv = this.ge(div_id);
		dv.innerHTML = '<div id="'+this._div_cont_id+'" style="position:relative;width:100%;height:100%;"></div>';  // clear all previous markup
		var dvc = this.ge(this._div_cont_id);

		document.body.appendChild(this._floater);
		dvc.appendChild(this._legend.el);
		this._floater.style.cssText = "text-align:left;font-family: Tahoma;border: 1px solid black; border-radius:3px; "+
										"font-size: 10px; padding: 3px 5px 3px 5px;"+
										"position:absolute; visibility:hidden; background-color: white; z-index:10000;"
		this._legend.el.style.cssText = "text-align:left; font-family: Tahoma;border: 1px solid lightgrey; border-radius:3px; "+
										"font-size: 10px; padding: 3px 5px 3px 5px; margin:1px 1px 1px 1px;"+
										"position:absolute;background: transparent;white-space:nowrap;"+
										this._legend.position;

		var t = '<table style="border-collapse:collapse;border:0;padding:0;border-spacing:0px;font-size:9px;">';
		for (var i=0; i<this._data.y.length; i++)
		{
			var dt = this._data.y[i];
			var style = 'display:inline-block;text-align:center;width:12px;height:12px;border:1px solid lighgrey;font-size:10px;background-color:';
			t += '<tr><td style="border:none;">'+
					'<div style="'+style+dt.color+'">L</div>'+
					'<div style="'+style+dt.bar_color+'">B</div>'+
				'</td><td style="border:none;">'+
					'<div style="white-space:nowrap;">'+dt.y_label+'<span style="margin-left:1px;" id="'+String(i)+'_y"></span></div>'+
				'</td></tr>'
		}
		t +=	'</table>';
		this._legend.el.innerHTML = t;

		this.msg = function(s, x, y)
		{
			if (s != '')
				my._floater.innerHTML = s;
			var w = window,
				e = document.documentElement,
				g = document.body,
				wx = w.innerWidth || e.clientWidth || g.clientWidth,
				wy = w.innerHeight|| e.clientHeight|| g.clientHeight;

			if (x < wx - my._floater.offsetWidth - 30)
				my._floater.style.left = (x+15)+"px";
			else
				my._floater.style.left = (x-my._floater.offsetWidth - 10)+"px";
			if (y > 20)
				my._floater.style.top = (y-15)+"px";
			else
				my._floater.style.top = (y+15)+"px";
		};

		var cnv = 0;
		for (var i=0; i< dv.childNodes.length; i++)
		{
			var el = dv.childNodes[i];
			if (el.id == this._cnv_id)
			{
				cnv = el;
				break;
			}
		}

		if (cnv == 0)
		{
			cnv = document.createElement('canvas');
			cnv.id = this._cnv_id;
			dvc.appendChild(cnv);
		}	

		var cancelBubbling = function(e)
		{
			e = window.event || e;
			if (e.stopPropagation)    e.stopPropagation();
			if (e.cancelBubble!=null) e.cancelBubble = true;
			if (e.preventDefault) e.preventDefault();
			e.returnValue = false;
			return false;
		};
		
		this.onResize = function()
		{
			var ctx = my.ge(my._cnv_id).getContext("2d");

			var center_style = window.getComputedStyle(my.ge(my._div_id), null);
			ctx.canvas.width = parseInt(center_style.getPropertyValue("width"))-2;
			ctx.canvas.height = parseInt(center_style.getPropertyValue("height"))-2;
			my.draw(); 
		}

		this.getMousePos = function(canvas, evt)
		{
			var rect = canvas.getBoundingClientRect();
			return {x: evt.clientX - rect.left,
					y: evt.clientY - rect.top };
		}

		this.onMouseWheel = function(e)
		{
			e = window.event || e;
			
			var delta = Math.max(-1, Math.min(1, (e.wheelDelta || -e.detail))) * 0.1;
			if (my._zoom + delta > 0.1)
				my._zoom += delta;
			my.draw();
			return cancelBubbling(e);
		};
		
		this.onMouseMove = function(e)
		{
			if (my._floater.style.visibility == 'visible')
			{
				var canvas = my.ge(my._cnv_id);
				var mouse = my.getMousePos(canvas, e);

				var max_dist_px = 5*5;  // we dont care about distances longer than 20 pixels
				var dist = [];
				for (var i=0; i<my._data.y.length; i++)
				{
					for (var j=0; j<my._data.y[i].coords.length; j++)
					{
						var coord = my._data.y[i].coords[j];
						var dx = mouse.x - coord.x;
						var dy = mouse.y - coord.y;
						var dst = dx*dx+dy*dy;
						if (dst <= max_dist_px)  // this will minimize number of collected points
							dist.push({ yi: i, vi: j, dist: dst });
					}
				}
				if (dist.length > 0)
				{
					dist.sort( function(a,b) { return a.dist < b.dist; });
					var point = dist[0];  // first one is the closest;
					var seriesY = my._data.y[point.yi];
                    var user = '';
                    if (seriesY.hasOwnProperty('user'))
                    {
                        for (var u in seriesY.user)  // ["key": "value",...
                            user += '<div>'+ u + my.onFormat(u, seriesY.user[u][point.vi] )+'</div>';
                    }
					my.msg(	'<div>'+seriesY.y_label+'</div>'+
							'<div>'+my._xMsg+my.onFormat(my._xMsg, String(seriesY.x[point.vi]))+'</div>'+
							'<div>'+seriesY.y_msg+my.onFormat(seriesY.y_msg, String(seriesY.y[point.vi]))+'<div>'+user, my._mouse.x, my._mouse.y);
				}
				else
					my.msg("...", my._mouse.x, my._mouse.y);
			}
//			return cancelBubbling(e);
		}

		window.addEventListener('mousemove', function(e)
		{
			e = e || window.event;
			my._mouse.x = e.pageX;
			my._mouse.y = e.pageY;	
		});
		window.addEventListener('resize', this.onResize, false);
		cnv.addEventListener('mouseout', function() { my._floater.style.visibility='hidden'; }, false);
		cnv.addEventListener('mouseover', function() { my._floater.style.visibility='visible'; }, false);
		cnv.addEventListener('mousemove', this.onMouseMove, false);
		cnv.addEventListener("mousewheel", this.onMouseWheel, false);
		cnv.addEventListener("DOMMouseScroll", this.onMouseWheel, false);
	};

	Grapher.prototype.draw = function()
	{
		var my = this;
		var drawText = function(ctx, text, font_decor, font_size, font, x, y, angle)
		{
			ctx.font = font_decor+' ' + font_size + "px " + font;
	//      var sz_txt = fillTextMultiLine(ctx, text, 0, 0, false);
			var sz_txt = ctx.measureText(text);
			var box_w = sz_txt.width;
			var box_h = font_size;
			if (angle != 0)
			{
				angle *= Math.PI/180;
				var length = Math.sqrt(font_size*font_size + sz_txt.width * sz_txt.width);
				box_w = Math.abs(Math.ceil(length * Math.cos(angle)));
				box_h = Math.abs(Math.ceil(length * Math.sin(angle)));
			}

			var margin = 16;
			if (x == 'center')  x = (ctx.canvas.width - box_w)/2;
			else
			if (x == 'right') x = ctx.canvas.width - box_w - margin;
			else
			if (x == 'left') x = margin;
			if (y == 'middle')  y = (ctx.canvas.height + box_h)/2;
			else
			if (y == 'bottom') y = ctx.canvas.height - margin;
			else
			if (y == 'top')  y = box_h + margin;
			if (angle != 0)
			{
				ctx.save();
				ctx.translate(x+box_w/2, y-box_h/2);
				ctx.rotate(angle);
				ctx.fillText(text, -sz_txt.width/2, font_size/3);
	//			fillTextMultiLine(ctx, text, -sz_txt.width/2, font_size/3, true);

	/*
				ctx.beginPath();
				ctx.moveTo(0,0);
				ctx.strokeStyle = "#ff0000";
				ctx.lineTo(sz_txt.width/2, 0);
				ctx.stroke();
				ctx.beginPath();
				ctx.strokeStyle = "#00ff00";
				ctx.moveTo(0,0);
				ctx.lineTo(0, sz_txt.width/2);
				ctx.moveTo(0,0);
				ctx.stroke();
				ctx.beginPath();
				ctx.arc(0, 0, sz_txt.width/2, 0, 2 * Math.PI, false);
				ctx.stroke();
	*/ 
				ctx.restore();
			}
			else
				ctx.fillText(text, x, y);
		};

		var fillTextMultiLine = function(ctx, text, x, y, doDraw) 
		{
			var lineHeight = ctx.measureText("M").width * 1.2;
			var lines = text.split("\n");
			var w = 0;
			var h = lines.length * lineHeight;
			for (var i = 0; i < lines.length; ++i) 
			{
				if (doDraw)
					ctx.fillText(lines[i], x, y);
				y += lineHeight;
				w = Math.max(h, ctx.measureText(lines[i]).width);
			}
			return {width : w, height : h};
		};

		var drawAxes = function(ctx, left, top, right, bottom, style, width)
		{
			ctx.save();
			ctx.strokeStyle = style;
			ctx.lineWidth = width;
			ctx.beginPath();
			ctx.moveTo(left, top);
			ctx.lineTo(left, bottom);
			ctx.lineTo(right, bottom);
			ctx.stroke(); 
			ctx.restore();
		};

		var calcDataBounds = function(mdx, mdy)
		{
			return { 
				xmax: Math.max.apply(null, mdx), xmin: Math.min.apply(null, mdx), 
				ymax: Math.max.apply(null, mdy)*my._zoom, ymin: Math.min.apply(null, mdy),
				width: function() { return this.xmax - this.xmin;},  height: function() { return this.ymax - this.ymin; }
			};
		};

		var calcCoords = function(left, bottom, width, height, mdx, mdy, ody)
		{
			var step = width/mdx.length;
			var step_width = step/2;
			var dy = ody.y;
			var dx = ody.x;

			var box = calcDataBounds(mdx, mdy);
			var xf= width / box.width();
			var yf = (height-5)/box.height();
			var coords = [];
			for (var i=0; i<dy.length; i++)
			{
				var px = left + xf * (dx[i] - box.xmin) - 1;
				var py = bottom - yf * (dy[i] - box.ymin) - 1;
				var coord = { x: px, y: py, w: Math.min(step_width-2, 8), h: py-bottom };
				coords.push( coord );

			}
			return coords;
		};

		var calcXLabels = function(left, bottom, w, dx)
		{
			var xmax =  Math.max.apply(null, dx);
			var xmin =  Math.min.apply(null, dx);
			var nTicks = 8.0;
			var step = w/nTicks;
			var coords = [];
			for (var i=0; i<=nTicks; i++)
			{
				var x = left+i*step, v = xmin+i*(xmax-xmin)/nTicks;
				coords.push({x:x , y: bottom, h: 10, v: v});
			}
			return coords;
		};

		var calcYLabels = function(left, bottom, h, dy)
		{
			var ymax =  Math.max.apply(null, dy)*my._zoom;
			var ymin =  Math.min.apply(null, dy);
			var nTicks = 8.0;
			var step = h/nTicks;
			var coords = [];
			for (var i=0; i<=nTicks; i++)
			{
				var y = bottom -i*step, v = ymin+i*(ymax-ymin)/nTicks;
				coords.push({x:left , y:y, w:10, v: v});
			}
			return coords;
		};

		var drawScatter = function(ctx, coords, fill_style, line_style)
		{
			for (var i=0; i<coords.length; i++)
			{
				var px = coords[i].x;
				var py = coords[i].y;

				ctx.save();
				ctx.beginPath();
				ctx.arc(px, py, 3, 0, 2 * Math.PI, false);
				ctx.fillStyle = fill_style;
				ctx.fill();
				ctx.lineWidth = 3;
				ctx.strokeStyle = line_style;
				ctx.stroke();
				ctx.restore();
			}
		};

		var drawLines = function(ctx, coords, style, join)
		{
			ctx.save();
			ctx.beginPath();
			ctx.strokeStyle = style;
			ctx.lineJoin=join;
			for (var i=0; i<coords.length; i++)
			{
				var px = coords[i].x;
				var py = coords[i].y;

				if (i==0)
					ctx.moveTo(px, py);
				else
					ctx.lineTo(px, py);
			}
			ctx.stroke();
			ctx.restore();
		};


		var drawBars = function(ctx, coords, style, fill_style)
		{
			ctx.save();
	//        ctx.beginPath();
			ctx.strokeStyle = style;
			ctx.fillStyle = fill_style;
			for (var i=0; i<coords.length; i++)
			{
				ctx.strokeRect(coords[i].x-coords[i].w/2,  coords[i].y-coords[i].h, coords[i].w, coords[i].h);
				ctx.fillRect(coords[i].x-coords[i].w/2,  coords[i].y-coords[i].h, coords[i].w, coords[i].h);
			}
	//        ctx.stroke();
			ctx.restore();
		};

		var drawXLabels = function(ctx, left, bottom, w, font_size, font, style, dx)
		{
			var coords = calcXLabels(left, bottom, w, dx);
			ctx.save();
			ctx.strokeStyle = style;
			ctx.font = font_size + "px " + font;
			ctx.beginPath();
			for (var i=0; i<coords.length;i++)
			{
				ctx.moveTo(coords[i].x, coords[i].y);
				ctx.lineTo(coords[i].x, coords[i].y + coords[i].h);
				var text = my.onFormat('x-axis', String(coords[i].v));
				var sz = ctx.measureText(text);
				var tx = coords[i].x-sz.width/2, ty = coords[i].y+coords[i].h+font_size+1;
				if (tx < 0) tx = 0;
				if (tx+sz.width > ctx.canvas.width-5) tx = ctx.canvas.width-sz.width-5;
				ctx.fillText(text, tx, ty);
			}
			ctx.stroke();
			ctx.restore();
		};

		var drawYLabels = function(ctx, left, bottom, h, font_size, font, style, dy)
		{
			var coords = calcYLabels(left, bottom, h, dy);
			ctx.save();
			ctx.strokeStyle = style;
			ctx.font = font_size + "px " + font;
			ctx.beginPath();
			for (var i=0; i<coords.length;i++)
			{
				ctx.moveTo(coords[i].x, coords[i].y);
				ctx.lineTo(coords[i].x-coords[i].w, coords[i].y);
				var text = my.onFormat('y-axis', String(coords[i].v));
				var sz = ctx.measureText(text);
				var tx = coords[i].x- coords[i].w - sz.width, ty = coords[i].y + font_size/3;
				if (tx < 0) tx = 0;
				ctx.fillText(text, tx, ty);
			}
			ctx.stroke();
			ctx.restore();
		};
        
		var drawYLines = function(ctx, left, bottom, w, h, font_size, font, style, dy)
		{
			var coords = calcYLabels(left, bottom, h, dy);
			ctx.save();
			ctx.strokeStyle = style;
			ctx.beginPath();
			for (var i=0; i<coords.length;i++)
			{
				ctx.moveTo(coords[i].x, coords[i].y);
				ctx.lineTo(coords[i].x+w, coords[i].y);
			}
			ctx.stroke();
			ctx.restore();
		};

		// get 2d context and find out the extent of the usable drawing area 
		var ctx = this.ge(this._cnv_id).getContext("2d");
		var lgnd_style = window.getComputedStyle(this._legend.el, null);
		ctx.save();
		ctx.fillStyle = '#fff';
		ctx.fillRect(0,0,ctx.canvas.width, ctx.canvas.height);
		ctx.restore();

		var font_size = 20;
		var font = "Tahoma";
		var top = font_size + 14;
		var left = 4*font_size + 4;
		var btm = ctx.canvas.height - 4*font_size;
		var right = ctx.canvas.width - left/3 + 1;
		var w = right - left-5;
		var h = btm - top - 5;

		// draw the title, x-y labels and the axes
		drawText(ctx, this._title, 'bold', font_size, font, 'center', font_size+3, 0);
		drawText(ctx, this._xLabel, '', font_size, font, 'center', 'bottom', 0);
		drawText(ctx, this._yLabel, '', font_size, font, 'left', 'middle', -90);
		drawAxes(ctx, left, top, right, btm, this._axis_color, 1);

		// merge X and Y values for picking, scaling and labelling
		this._data.x = [];
		var data_y = [];
		for (var i=0; i< this._data.y.length; i++)
		{
			this._data.y[i].y = this._data.y[i].y.map(Number);  // make sure no strings are given
			this._data.y[i].x = this._data.y[i].x.map(Number);
			for (var xi=0; xi<this._data.y[i].x.length; xi++)
			{
				if (this._data.x.indexOf(this._data.y[i].x[xi]) < 0)
					this._data.x.push(this._data.y[i].x[xi]);
				if (data_y.indexOf(this._data.y[i].y[xi]) < 0)
					data_y.push(this._data.y[i].y[xi]);
			}
		}

		// Give add-ons a chance to modify the databox
		for (var i=0; i< this._data.y.length; i++)
			for (var k in this._addOns)
				if (this._addOns[k].enabled)
					my.onCalc(i, this._data.y[i], k, this._addOns[k]);
		
		// incorporate any changes to world box extents by the add-ons
		if (this._dataBox.ymin != null) data_y.unshift(this._dataBox.ymin);
		if (this._dataBox.xmin != null) this._data_x.unshift(this._dataBox.xmin);
		if (this._dataBox.ymax != null) data_y.push(this._dataBox.ymax);
		if (this._dataBox.xmax != null) this._data_x.push(this._dataBox.xmax);
				
		// Calculate all series coordinates based on merged X axis values with any add-on extents incorporated
		for (var i=0; i< this._data.y.length; i++)
		{
			this._data.y[i].coords = calcCoords(left+5, btm, w-10, h-10, this._data.x, data_y, this._data.y[i] );
			for (var k in this._addOns)
			{
				if (this._addOns[k].enabled)
				{
					my.onCalc(i, this._data.y[i], k, this._addOns[k]);
					var addon_data = this._addOns[k].calc(this._data.y[i].x, this._data.y[i].y);
					this._data.y[i].addon_data[k] = {
							'data': addon_data, 
							'coords' : calcCoords(left+5, btm, w-10, h-10, this._data.x, data_y, this._addOns[k].get({key: 'coords', value: addon_data}) )
						};
				}
			}
		}

		drawXLabels(ctx, left, btm, w-10, font_size-3, font, this._axis_color, this._data.x);
		drawYLabels(ctx, left, btm-1, h-10, font_size-3, font, this._axis_color, data_y);
        drawYLines(ctx, left, btm, w-10, h-10, font_size-3, font, "#ddd", this._data.x);

		// restrict graph area
		ctx.save();
		ctx.beginPath();
		ctx.strokeStyle="rgba(1,1,1,0)";
		ctx.moveTo(left,top);
		ctx.lineTo(left+w, top);
		ctx.lineTo(left+w, btm);
		ctx.lineTo(left, btm);
		ctx.lineTo(left,top);
		ctx.stroke();
		ctx.clip();

		left += 0; //5;
		right -= 5;
		btm -= 1;
		h -= 10;
		w -= 10;

		// draw whatever is enabled
		for (var i=0; i< this._data.y.length; i++)
		{
			if (this._enable.bars)
				drawBars(ctx, this._data.y[i].coords, this._data.y[i].color, this._data.y[i].bar_color);
			if (this._enable.lines)
				drawLines(ctx, this._data.y[i].coords, this._data.y[i].color, "round");
			if (this._enable.scatter)
				drawScatter(ctx, this._data.y[i].coords, this._data.y[i].bar_color, this._data.y[i].color);
			
			var draw_params = {
				color: this._data.y[i].color, 
				lineJoin: "round", 
				lineDash: [5,3],
				fillColor: this._data.y[i].bar_color, 
				font: font, 
				fontSize: font_size-2
			};
			for (var k in this._addOns)
			{
				if (this._addOns[k].enabled)
					this._addOns[k].draw(ctx, this._data.y[i].addon_data[k], my.onFormat('draw_params', {key: k, value: draw_params}));
			}
		}
		ctx.restore();
	};

	//	Enable/disable internal, built-in and external addOns
	//	Internal addons are: 'bars', 'lines', 'scatter'
	//	Built-in addon are : 'trend', 'hlines'
	//	External add-ons must have the structure indiucated by the two built-in at the beginning of this file
	Grapher.prototype.enable = function(what, flag)
	{
		if (this._enable.hasOwnProperty(what))
			this._enable[what] = flag;
		else
			for (var k in this._addOns)
				if (k == what)
					this._addOns[k].set('enabled', flag);
	};

	Grapher.prototype.ge = function(id)
	{
		return document.getElementById(id);
	};

	// onFormat callback
	//	This callback is called when rendering various parts of the graph to allow the user to format the specified value
	//	according to the specified key passes as parameter. Keys and values passed are the ones specified in the data.
	//	Keys passed currently are the following, each with the corresponding value speficied in the data provided: 
	//			x_msg value, y_msg value, 'x-axis', 'y-axis', 
	//		'draw_params' - value for this is an object like {key: <addon_name>, value: <draw params obj>} 
	//						so  the format callback can modify any of the drawing parameters for the addon. 
	//						See addons below for draw_param syntax.
	//	If no external callback is specified the default simply returns the value passed for each label.
	Grapher.prototype.onFormat = function(label, value)
	{
		return this.onFormat(label, value);
	};

	//	At coordinate calculation time, onCalc will be called for each registered plugin, with parameters indicating
	//	which data series is being calculated, the data series itself, the plugin registered name and the plugin itself.
	//	A user-override of this callback can set any parameter the plugin requires, either by obtaining it from the 
	//	series data provided in the parameter, or otherwise.
	//	If no external callback is provided the default returns false;
	Grapher.prototype.onCalc = function(yi, data_y, addon_name, addon)
	{
		return this.onCalc(yi, data_y, addon_name, addon);
	};

	// Add-on management.
	// Installs an external add-on to Grapher.
	Grapher.prototype.addOn = function(label, addOn)
	{
		if (addOn.hasOwnProperty('enabled') && addOn.hasOwnProperty('calc'))
		{
			this._addOns[label] = addOn;
		}
		return false;
	};
	
	// Tentative: set/get for addon extra parameters
	Grapher.prototype.set = function(label, param, value)
	{
		if (label in this._addOns)
			this._addOns[label].set(param, value);
		else
		if (label === 'databox')
			this._dataBox.set(param, parseFloat(value));
	};
	Grapher.prototype.get = function(label, param)
	{
		if (label in this._addOns)
			return this._addons[label].get(param);
		else
		if (label === 'databox')
			return this._dataBox.get(param);
		return false;
	};

	//	Initialization. 
	//	Add all built-in add-ons.
	//	More can be added externally using the addOn() method.
	Grapher.prototype.Init = function()
	{
		this.addOn('trend', linearRegression_addon);
		this.addOn('hlines', division_addon);
	}
	
	// Display the graph
	Grapher.prototype.Show = function()
	{
		this.onResize();
	};

}());