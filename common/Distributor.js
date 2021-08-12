/*!
 *	Distributor
 *	\class Distributor
 */

/*!
 *	Distributor Graph Class
 *	
 *	This object can display a series of values in a graph using
 *	lines, bars or both,
 *	Supports display of values with mouse over.
 *	
 *	Usage:
 *~~~{.php}
 *		var points = [
 *			20, 35,  2,  1, 0, 0,  0, 19, 25, 99,
 *			150,  0,  0,  0, 0, 0,  0,  0,  0, 50, 
 *			32, 44, 12,  0,15,122,12,245, 33, 24];
 *		var distrib = new Distributor(points, "Points Distribution", "Y", "X", "div_id");
 *		//distrib.lines(false);
 *		//distrib.bars(false);
 *		distrib.onResize();
 *~~~		
 *	By default, both lines and bars are plotted. To disable one or the other,
 *	uncomment in your code the corresponding line above
 *
 *	@param points (array) 		An array of points to plot along the X axis as bars and optionally as connected lines
 *	@param label (string)		The title of the graphic
 *	@param count_msg (string) 	The label to display next to the number of items counter in the floating display
 *	@param pos_msg (string)		The label to display next to the position indicator in the floating display
 *	@param div_id (string)		The ID of the html div element to be used as container for this control
 *
 *	@author Dimitrios Tsobanopoulos, Sep-2014
 */
 
function Distributor(points, label, count_msg, pos_msg, div_id)
{
	this._points = { values: points, coords: [] };
	this._label = label;
	this._div_id = div_id;
	this._cnt_msg = count_msg;
	this._pos_msg = pos_msg;
	this._cnv_id = div_id+'_gfx';
	this._lines = true;
	this._bars = true;
	this._total = true;
	this._mouse = {x:0, y: 0};
	this._infoPopup = document.createElement('div');
	this._guiReversePos = false;
	
	var my = this;
	
	var dv = this.ge(div_id);
	document.body.appendChild(this._infoPopup);
//	dv.appendChild(this._infoPopup);
	this._infoPopup.style.cssText = "border: 1px solid black; border-radius:3px; "+
									"font-size: 10px; text-align: center; padding: 3px 5px 3px 5px;"+
									"position:absolute; z-index=1000; visibility:hidden; background-color: white; z-index:10000;"
	console.info("zindex="+dv.style.zIndex);
	window.addEventListener('mousemove', function(e)
	{
		e = e || window.event;
		my._mouse.x = e.pageX;
		my._mouse.y = e.pageY;	
	});
	
	this.msg = function(s, x, y)
	{
		if (s != '')
			my._infoPopup.innerHTML = s;
		var w = window,
			e = document.documentElement,
			g = document.body,
			wx = w.innerWidth || e.clientWidth || g.clientWidth,
			wy = w.innerHeight|| e.clientHeight|| g.clientHeight;

		if (x < wx - my._infoPopup.offsetWidth - 30)
			my._infoPopup.style.left = (x+15)+"px";
		else
			my._infoPopup.style.left = (x-my._infoPopup.offsetWidth - 10)+"px";
		if (y > 20)
			my._infoPopup.style.top = (y-15)+"px";
		else
			my._infoPopup.style.top = (y+15)+"px";
	}
	
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
		dv.appendChild(cnv);
	}	
	
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
	
	this.onMouseMove = function(e)
	{
		if (my._infoPopup.style.visibility == 'visible')
		{
			var canvas = my.ge(my._cnv_id);
			var mouse = my.getMousePos(canvas, e);
			var found = false;
			for (var i=0; i<my._points.coords.length; i++)
			{
				var coord = my._points.coords[i];
				if (mouse.x >= coord.x && mouse.x < coord.x+coord.w)
				{
					found = true;
					break;
				}
			}
			if (found)
			{
				var g_i = i;
				if (my._guiReversePos)
					g_i = my._points.coords.length - g_i;
				else
					g_i++;

				my.msg("<div>"+my._cnt_msg+String(my._points.values[i])+"</div><div>"+my._pos_msg+String(g_i)+"<div>", my._mouse.x, my._mouse.y);
			}
			else
				my.msg("", my._mouse.x, my._mouse.y);
		}
	}
	
	cnv.addEventListener('mouseout', function() { my._infoPopup.style.visibility='hidden'; }, false);
	cnv.addEventListener('mouseover', function() { my._infoPopup.style.visibility='visible'; }, false);
	cnv.addEventListener('mousemove', this.onMouseMove, false);
	window.addEventListener('resize', this.onResize, false);

}

/**
 *	This method specifies whether the graph will be plotted from right-to-left as opposed to the
 *	default which is Left-to-Right
 *
 *	@param f (boolean)	true: Right-to-Left, false: Left-to-Right (def.)
 */
function guiReversePos(f)
{
	this._guiReversePos = f;
}
Distributor.prototype.guiReversePos = guiReversePos;

Distributor.prototype.lines = function(f)
{
	this._lines = f;
}

Distributor.prototype.bars = function(f)
{
	this._bars = f;
}
Distributor.prototype.total = function(f)
{
	this._total = f;
}

Distributor.prototype.Init = function()
{
	this.onResize();
}

Distributor.prototype.ge = function(id)
{
	return document.getElementById(id);
}

Distributor.prototype.draw = function()
{
	
	var ctx = this.ge(this._cnv_id).getContext("2d");
	
	ctx.font = "12px Tahoma";
	var sz_txt = ctx.measureText(this._label);
	ctx.fillText(this._label,  (ctx.canvas.width - sz_txt.width)/2, ctx.canvas.height-2);
	var top = 14;
	var left = 6;
	var btm = ctx.canvas.height - 14;
	var right = ctx.canvas.width - left + 1;
	var w = right - left + 1;
	var h = btm - top + 1;

	ctx.strokeStyle = "#000000";
	ctx.beginPath();
	ctx.moveTo(left, top);
	ctx.lineTo(left, btm);
	ctx.lineTo(right, btm);
	ctx.lineTo(right, top);
	ctx.stroke(); 

	left += 5;
	right -= 5;
	w -= 10;
	
	var step = w/this._points.values.length;
	var step_width = step/2;
	var pnt_max = 0.0
	for (var i=0; i<this._points.values.length; i++)
		if (this._points.values[i] > pnt_max) pnt_max = this._points.values[i];
		
	var total = 0;
	this._points.coords = [];
	for (var i=0; i<this._points.values.length; i++)
	{
		total += this._points.values[i];
		var px = left+i*step;
		var py = btm - (h-5)/pnt_max * this._points.values[i] - 1;
		var coord = { x: px+step_width-step/3, y: btm-1, w: 2*step/3, h: py-btm };
		this._points.coords.push( coord );
	}
	

	if (this._lines || this._bars)
		ctx.beginPath();
		
	ctx.fillStyle = "#3399FF";
	if (this._bars)
	{
		ctx.fillStyle = "#3399FF";
		for (var i=0; i<this._points.values.length; i++)
		{
			ctx.fillRect(this._points.coords[i].x, this._points.coords[i].y, this._points.coords[i].w, this._points.coords[i].h);
		}
	}
	if (this._lines)
	{
		ctx.strokeStyle = "#CC3300";
		ctx.lineJoin="round";
		for (var i=0; i<this._points.values.length; i++)
		{
			var px = left+i*step;
			var py = btm - (h-5)/pnt_max * this._points.values[i] - 1;
			
			if (i==0)
				ctx.moveTo(px+step_width, py);
			else
				ctx.lineTo(px+step_width, py);
		}
	}
	if (this._lines || this._bars)
		ctx.stroke();
		
	ctx.fillStyle = "#000000";
	if (this._total)
		ctx.fillText("Total: "+String(total),  3, 13);
}
