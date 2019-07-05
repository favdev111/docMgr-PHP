
var PULLDOWN = new SITEPULLDOWN();

if (window.addEventListener)
  window.addEventListener("click",PULLDOWN.hideAll);
else
  window.attachEvent("onclick",PULLDOWN.hideAll);
function SITEPULLDOWN()
{

	this.menus = new Array();
	this.count = -1;
	this.menuref;
	this.openHandler;
	this.closeHandler;
	this.visble = false;

	this.hideAll = function()
	{
		for (var i=0;i<PULLDOWN.menus.length;i++)
		{
			PULLDOWN.menus[i].style.display = "none";
		}

		if (PULLDOWN.closeHandler) 
		{
			var func = eval(PULLDOWN.closeHandler);
			func();
		}

		PULLDOWN.visible = false;

	};

	//handle is the object which cycles the menu
	this.create = function(handle)
	{

		//ie hates me
		if (document.all) document.body.onclick = PULLDOWN.hideAll;

		this.count++;

		handle.setAttribute("onClick","PULLDOWN.cycle(event)");

   	this.menuref = ce("div","sitePullDownMenu");

		this.menus.push(this.menuref);

		return this.menuref;

	};

	this.add = function(title,func,img,checked)
	{

    //add submenu to the last button
    var row = ce("div","sitePullDownMenuRow");
		var cyclefunc = "";

		//prefix our row with an image if passed
		if (img)
		{

			//first element gets the checkmark
			if (this.menuref.getElementsByTagName("div").length==0 || checked)
     		var menuimg = createImg(THEME_PATH + "/images/toolbar/check.png");
      else
        var menuimg = createImg(THEME_PATH + "/images/toolbar/blank.png");

			row.appendChild(menuimg);

			cyclefunc += "PULLDOWN.cycleRow(event);";

		}

		//row title
		row.appendChild(ctnode(title));

		//onclick function
		cyclefunc += "PULLDOWN.cycle(event);";

		if (func) func = cyclefunc + func;
		else func = cyclefunc;

		row.setAttribute("onClick",func);

    this.menuref.appendChild(row);

		return row;

	};

	this.addSeparator = function()
	{

    //add submenu to the last button
    var row = ce("hr");

    this.menuref.appendChild(row);

		return row;

	};

	this.close = function(e)
	{

		var ref = getEventSrc(e);

		if (ref.tagName.toUpperCase()=="IMG") ref = ref.parentNode.parentNode;
		else ref = ref.parentNode;

		ref.style.display = "none";

	};

	this.cycleRow = function(e)
	{

		e.cancelBubble = true;

		//get a reference to the row that sent the event
		var rowref = getEventSrc(e);
		if (rowref.tagName.toLowerCase()=="img") rowref = rowref.parentNode;
		var menuref = rowref.parentNode;

		var arr = menuref.getElementsByTagName("div");
		
		for (var i=0;i<arr.length;i++)
		{
			var img = arr[i].getElementsByTagName("img")[0];

			if (arr[i]==rowref)
			{
				img.src = THEME_PATH + "/images/toolbar/check.png";
			}
			else
			{
				img.src = THEME_PATH + "/images/toolbar/blank.png";
			}

		}

	};

	this.getElementsByClassName = function(ref,cn)
	{
		var arr = new Array();

		if (document.getElementsByClassName) arr = ref.getElementsByClassName(cn);
		else
		{

			var tmp = ref.getElementsByTagName("div");

			for (var i=0;i<tmp.length;i++)
			{
				if (tmp[i].className==cn) arr.push(tmp[i]);
			}

		}

		return arr;

	}

	this.getMenu = function(ref)
	{

		var menu;

		//handle clicks from rows in a pulldown menu
		if (ref.className=="sitePullDownMenuRow" || ref.parentNode.className =="sitePullDownMenuRow")
		{
			while (ref.className!="sitePullDownMenu")
			{
				ref = ref.parentNode;
			}
			menu = ref;
		}
		//handle everything else
		else
		{

			var arr = PULLDOWN.getElementsByClassName(ref,"sitePullDownMenu");

			if (arr.length==0)
			{
				arr = PULLDOWN.getElementsByClassName(ref.parentNode,"sitePullDownMenu");
			}

			menu = arr[0];

		}

		return menu;

	}

	this.cycle = function(e)
	{

		e.cancelBubble = true;

		var src = getEventSrc(e);
		var ref = PULLDOWN.getMenu(src);

		if (ref.style.display == "block") 
		{
			ref.style.display = "none";
			PULLDOWN.visible = false;

			if (PULLDOWN.closeHandler) 
			{
				var func = eval(PULLDOWN.closeHandler);
				func(e);
			}

		}
		else 
		{

			PULLDOWN.hideAll();
			ref.style.display = "block";
			PULLDOWN.visible = true;

			//ie hack
			if (BROWSER=="ie" && src.className == "siteSidebarHeaderImage")
			{
				ref.style.marginLeft = "75px";
			}
			else
			{

		 		var rightpos = src.getLeft() + ref.getWidth();
      
	      if (rightpos > window.getSize().x)
	      {
					ref.style.left = "";
					ref.style.right = "10px";
	      }
				else if (BROWSER!="ie" || (BROWSER=="ie" && BROWSERVERSION >= 10) )
				{
					ref.style.left = src.getLeft() + "px";
					ref.style.top = (src.getTop() + src.getHeight()) + "px";
				}

			}

			if (PULLDOWN.openHandler) 
			{
				var func = eval(PULLDOWN.openHandler);
				func(e);
			}

		}

	};

	this.selectRow = function(rowref)
	{

		//get a reference to the row that sent the event
		if (rowref.tagName.toLowerCase()=="img") rowref = rowref.parentNode;

		var menuref = rowref.parentNode;
		var arr = menuref.getElementsByTagName("div");
		
		for (var i=0;i<arr.length;i++)
		{
			var img = arr[i].getElementsByTagName("img")[0];

			if (arr[i]==rowref)
			{
				img.src = THEME_PATH + "/images/toolbar/check.png";
			}
			else
			{
				img.src = THEME_PATH + "/images/toolbar/blank.png";
			}

		}

	};

}		
