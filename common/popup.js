
// popup.js  V1.0
//
// Displays a formatted (html) message in a floating div at screen coordinates (x,y)
// if 't' is provided then the message disappears after 't' seconds
// If an element with a 'hdr-move' attribute is found in the content, it is considered a grabbable element
// that can initiate dragging of this window and the appropriate handlers are added
//
// author: Dimitrios Tsobanopoulos, 2012
//
function popup(s, x, y, t)
{
	var my=this;
	
	if (my._info_tm && my._info_tm !== 0) { clearTimeout(my._info_tm); my._info_tm = 0; }

	if (!my._infoPopup)
	{
		my._infoPopup = document.createElement('div');
		document.body.appendChild(my._infoPopup);
		my._infoPopup.style.cssText =	"border: 1px solid black; border-radius:3px; "+
						"font-size: 10px; text-align: center; padding: 3px 5px 3px 5px;"+
						"position:absolute; z-index=1000; visibility:hidden; background-color: white;";
	}

	if (s == '')
	{
		my._infoPopup.style.visibility = 'hidden';
		document.removeEventListener( 'mousemove', _mouseMove);
		return;
	}
	
	my._infoPopup.style.visibility = 'visible';
	my._infoPopup.innerHTML = s;
	
	var findHDR = function(el)
	{
		if (el.hasAttribute('hdr-move')) return el;
		for (var i=0; i< el.children.length; i++)
		{
			var ch = findHDR(el.children[i]);
			if (ch) return ch;
		}		
		return null;
	};

	my._infoPopup_hdr_el = findHDR(my._infoPopup);
	my._infoPopup_wnd = { 
				drag : false,
				mouse : { x: 0, y: 0},
				pos : { x: 0, y: 0},
				offset : { dx : 0, dy : 0 },
				startDrag : function() 
					{ 
						this.drag = true;
						this.offset.dx = this.mouse.x - this.pos.x;
						this.offset.dy = this.mouse.y - this.pos.y;
					},
				endDrag : function() { this.drag = false; },
				newpos : function() { return {x: this.mouse.x - this.offset.dx, y : this.mouse.y - this.offset.dy }; }
			};
					
	var _startDrag = function(e) 
	{
		my._infoPopup_wnd.startDrag();
		return _pauseEvent(e);
	};
	var _endDrag = function(e) 
	{ 
		my._infoPopup_wnd.endDrag();
	};
	var _mouseMove = function(e) 
	{
		my._infoPopup_wnd.mouse.x = e.clientX; 
		my._infoPopup_wnd.mouse.y = e.clientY; 
		if (my._infoPopup_wnd.drag)
		{
			move_to( my._infoPopup_wnd.newpos().x , my._infoPopup_wnd.newpos().y);
			return _pauseEvent(e);
		}
	};
	var _pauseEvent = function(e) // cvall to stop auto-selection everywhere during dragging
	{
		if(e.stopPropagation) e.stopPropagation();
		if(e.preventDefault) e.preventDefault();
		e.cancelBubble=true;
		e.returnValue=false;
		return false;
	};
	
	if (my._infoPopup_hdr_el)
	{
		my._infoPopup_hdr_el.addEventListener( 'mousedown', _startDrag, true);
		my._infoPopup_hdr_el.addEventListener( 'mouseup', _endDrag, true);
		document.addEventListener( 'mousemove', _mouseMove, true);
	}

	var w = window,
		e = document.documentElement,
		g = document.body,
		wx = w.innerWidth || e.clientWidth || g.clientWidth,
		wy = w.innerHeight|| e.clientHeight|| g.clientHeight,
		clTop = e.clientTop|| g.clientTop || 0,
		clLeft= e.clientLeft|| g.clientLeft || 0;

	if (x - clLeft + my._infoPopup.offsetWidth < wx - 20)
		x += 1;
	else
	if (x - clLeft - my._infoPopup.offsetWidth > 5)
		x = x - clLeft - my._infoPopup.offsetWidth - 5;
	else
		x = 5;
	if (y - clTop + my._infoPopup.offsetHeight < wy - 20)
		y += 1; 
	else
	if (y - clTop - my._infoPopup.offsetHeight > 5)
		y = y - clTop - my._infoPopup.offsetHeight - 5;
	else
		y = 5;

	var move_to = function(x,y)
	{
//		msg("MOVE x="+x+" y="+y);
		my._infoPopup_wnd.pos.x= x;
		my._infoPopup_wnd.pos.y= y;
		my._infoPopup.style.left = x + "px";
		my._infoPopup.style.top = y + "px";
	};
		
	move_to(x,y);
	
	if (t && t>0)
		my._info_tm = setTimeout(function () 
				{
					my._infoPopup_wnd.endDrag();
					my._infoPopup.style.visibility = 'hidden'; 
				}, t);
}
