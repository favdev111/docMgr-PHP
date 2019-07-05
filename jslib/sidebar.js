
var SIDEBAR = new SITE_SIDEBAR();
var SIDEBARSCROLL;

if (window.addEventListener)
	window.addEventListener("resize",SIDEBAR.setSizes);
else
	window.attachEvent("onresize",SIDEBAR.setSizes);

function SITE_SIDEBAR()
{

	this.bar;									//maintains a reference to the element the bar lives in
	this.groupHeader;						//a reference to the current header of the group we are working in
	this.group;								//the current group we are working in
	this.groups;							//an array of all the group references
	this.groupModes;					//an array of all the selection modes of our groups
	this.groupMode;						//the mode of the current group we are working in

	/**
		clears our element and preps everything for building the sidebar
		*/
	this.open = function()
	{

		SIDEBAR.bar = ge("siteSidebar");

		SIDEBAR.group = "";
		SIDEBAR.groupMode = "";

		SIDEBAR.groups = new Array();
		SIDEBAR.groupModes = new Array();

		clearElement(SIDEBAR.bar);
		return SIDEBAR.bar;

	}	

	/**
		shows index of the passed element as selected
		*/
	this.showCurrent = function(idx)
	{

		var arr = SIDEBAR.getAllRows();

		for (var i=0;i<arr.length;i++) 
		{
			arr[i].className = "siteSidebarRow";
		}

		setClass(arr[idx],"siteSidebarRow siteSidebarRowSelected");

	};

	/**
		add a new sidebar group with a specified mode.  all rows added
		will belong to this group.  Modes are as follows:

			unique => only one row in the entire sidebar can show as selected.  You can mix "unique" and "multiselect" or "cycle" in the same 
								sidebar.  Clicking a row in a multiselect group will not change the selected row display
			groupunique => only one row within each group can show as selected.  You can have multiple groups each maintaining
										 their own selection
			cycle => group row will change color to show it was clicked, but return back to original color
			multiselect => behaves like "cycle".  But a check icon is displayed in row for using row as filter selection (like a row of checkboxes)

		*/
	this.addGroup = function(header,mode)
	{

		if (!mode) mode = "unique";

		SIDEBAR.group = ce("div","siteSidebarGroup");
		SIDEBAR.groups.push(SIDEBAR.group);
		SIDEBAR.groupMode = mode;
		SIDEBAR.groupModes.push(mode);

		//add the group to the main nav container
		SIDEBAR.bar.appendChild(SIDEBAR.group);

		//now add the header for our group
		var mydiv = ce("div","siteSidebarHeader","",header);
		SIDEBAR.group.appendChild(mydiv);
		SIDEBAR.groupHeader = mydiv;

	}

	/**
		sets the selection mode for the current group
		*/
	this.setGroupMode = function(mode)
	{
			var key = arraySearch(SIDEBAR.group,SIDEBAR.groups);
			SIDEBAR.groupModes[key] = mode;
			SIDEBAR.groupMode = mode;
	};

	/**
		returns the selection mode for the passed group
		*/
	this.getGroupMode = function(group)
	{
			var key = arraySearch(group,SIDEBAR.groups);
			return SIDEBAR.groupModes[key];
	};

	/**
		returns a reference to the row a click event was created in
		*/
	this.getRow = function(e)
	{

		var ref = getEventSrc(e);

		while (ref.className != "siteSidebarRow" && ref.className != "siteSidebarRow siteSidebarRowSelected" && ref!=null)
		{
			ref = ref.parentNode;
		}

		return ref;

	};

	/**
		returns a reference to the group a click event was created in
		*/
	this.getGroup = function(e,ref)
	{
		if (!ref) ref = getEventSrc(e);

		while (ref.className != "siteSidebarGroup" && ref!=null)
		{
			ref = ref.parentNode;
		}

		return ref;

	};

	/**
		returns all rows in a group
		*/
	this.getGroupRows = function(group)
	{

		var rows = new Array();

		if (document.getElementsByClassName) rows = group.getElementsByClassName("siteSidebarRow");
		else
		{
			var arr = group.getElementsByTagName("div");
	
			for (var i=0;i<arr.length;i++)
			{
				if (arr[i].hasAttribute("level")) rows.push(arr[i]);
			}

		}

		return rows;

	};

	/**
		get all rows in our setup
		*/
	this.getAllRows = function()
	{

		var rows = new Array();

		if (document.getElementsByClassName) rows = SIDEBAR.bar.getElementsByClassName("siteSidebarRow");
		else
		{
			var arr = SIDEBAR.bar.getElementsByTagName("div");
	
			for (var i=0;i<arr.length;i++)
			{
				if (arr[i].className=="siteSidebarRow" || arr[i].className=="siteSidebarRow siteSidebarRowSelected") rows.push(arr[i]);
			}

		}

		return rows;

	};

	/**
		highlights the row(s) that should be selected based on our current group mode
		*/
	this.updateRowSelection = function(group,row,mode)
	{

		//unique -> only one link in the entire row list may be selected at a time
		if (mode=="unique")
		{
	
			var rows = SIDEBAR.getAllRows();

			for (var i=0;i<rows.length;i++) 
			{
				rows[i].className = "siteSidebarRow";
			}

  		row.className = "siteSidebarRow siteSidebarRowSelected";   

		}
		//only one link in the group may be selected at a time
		else if (mode=="groupunique")
		{

			var rows = SIDEBAR.getGroupRows(group);

			for (var i=0;i<rows.length;i++) 
			{
				rows[i].className = "siteSidebarRow";
			}

  		row.className = "siteSidebarRow siteSidebarRowSelected";   

		}
		//multiselect -> if you click an element, it's multiselectd selected.  click again and it's multiselectd off
		else if (mode=="multiselect")
		{

			var img = row.getElementsByTagName("img")[1];

			if (!img) return false;

			//hasn't been selected yet
			if (img.src.indexOf("empty_circle")==-1)
			{
				img.src = THEME_PATH + "/images/icons/empty_circle.png";
			}			
			else
			{
				img.src = THEME_PATH + "/images/icons/green_checked.png";
			}

			row.className = "siteSidebarRow";
			
		}
		else
		{
			row.className = "siteSidebarRow";
		}

	};

	this.select = function(row)
	{
		var group = SIDEBAR.getGroup("",row);
		var mode = SIDEBAR.getGroupMode(group);

		SIDEBAR.updateRowSelection(group,row,mode);

	}

	/**
		handles a click event in a group.  Generally this just means we show
		the row as selected if the mode is correct, or expand if there are sub-elements
		*/
	this.click = function(e)
	{

		//make sure no modal window is showing
		MODAL.hide();

  	var row = SIDEBAR.getRow(e);
		var group = SIDEBAR.getGroup(e);
		var mode = SIDEBAR.getGroupMode(group);

		SIDEBAR.updateRowSelection(group,row,mode);

	};

	/**
		adds a row to the sidebar.  if passed a level, it belongs to the preceeding element
		*/
	this.add = function(title,func,img,level)
	{

		if (!level) level = 0;

		if (level==0) addref = SIDEBAR.group;
		else
		{		

			//get the last entry
			//var arr = SIDEBAR.bar.getElementsByTagName("div");
			var arr = SIDEBAR.getGroupRows(SIDEBAR.group);

			var last = arr[arr.length-1];

			//it'll either be a root div, a subcontainer, or another sublevel
			//it's an empty subcontainer
			if (last.className=="siteSidebarSub")
			{
				var addref = last;
			}
			//it's a subrow of this level
			else if (last.getAttribute("level")==level)
			{
				var addref = last.parentNode;
			}
			//it's another row of a higher level
			else if (last.getAttribute("level") > level)
			{

				var ref = last;
				var addref;

				while (1)
				{
					if (ref.parentNode.getAttribute("level")==level)
					{
						addref = ref.parentNode;
						break;
					}

					//set so we try the next level up next time
					ref = ref.parentNode;

				}

			}
			else
			{

				//add a subcontainer
				var addref = ce("div","siteSidebarSub");
				addref.setAttribute("level",level);

				last.parentNode.appendChild(addref);

				//add an arrow in front of the parent div
				var navimg = last.getElementsByTagName("img")[0];
				navimg.src = THEME_PATH + "/images/nav/closed.png";
				setClick(navimg,"SIDEBAR.expandSub(event)");

				//click handler.  replace the normal handler with our expandsub,
				//and tack on any additional methods to the end
				var arr = last.getAttribute("onclick").split(";");
				arr.shift();
				arr.unshift("SIDEBAR.expandSub(event)");
				setClick(last,arr.join(";"));

			}			

		}

		var mydiv = ce("div","siteSidebarRow");
		mydiv.setAttribute("level",level);

		//add spacer img
		var navimg = createImg(THEME_PATH + "/images/nav/blank.png");
		setClass(navimg,"siteSidebarRowExpandIcon");
		mydiv.appendChild(navimg);

		//setup an image for multiselect mode
		if (SIDEBAR.groupMode=="multiselect")
		{
			img = THEME_PATH + "/images/icons/empty_circle.png";
		}

		//add icon
		if (img)
		{
			var icon = createImg(img);
			setClass(icon,"siteSidebarRowIcon");
			mydiv.appendChild(icon);
		}
		
		mydiv.appendChild(ce("div","siteSidebarRowLabel","",title));

		//click handler
		var clickfunc = "SIDEBAR.click(event);";

		//user defined click function
		if (func) clickfunc += func + ";";

		//go for it
		if (clickfunc.length > 0) setClick(mydiv,clickfunc);

		var pl = (10 + (level * 10)) + "px";
		mydiv.style.paddingLeft = pl;

		addref.appendChild(mydiv);

		return mydiv;

	};

	this.expandSub = function(e,img)
	{

		var group = SIDEBAR.getGroup(e);
		var row = SIDEBAR.getRow(e);
		var mode = SIDEBAR.getGroupMode(group);
		var img = row.getElementsByTagName("img")[0];
		var subref = row.nextSibling;

		if (img.src.indexOf("open.png")!=-1)
		{

			//first point the image down
			img.src = THEME_PATH + "/images/nav/closed.png";
			subref.style.display = "none";

		}
		else
		{

			var newdisplay;

			//first point the image down
			img.src = THEME_PATH + "/images/nav/open.png";
			subref.style.display = "block";

		}

		SIDEBAR.updateRowSelection(group,row,mode);

		SIDEBAR.refresh();

		if (e) e.cancelBubble = true;

	};

	this.expandRow = function(row)
	{

		var img = row.getElementsByTagName("img")[0];
		var subref = row.nextSibling;

		if (img.src.indexOf("open.png")!=-1)
		{

			//first point the image down
			img.src = THEME_PATH + "/images/nav/closed.png";
			subref.style.display = "none";

		}
		else
		{

			var newdisplay;

			//first point the image down
			img.src = THEME_PATH + "/images/nav/open.png";
			subref.style.display = "block";

		}

		//SIDEBAR.updateRowSelection(group,row,mode);

		SIDEBAR.refresh();

	};

	this.refresh = function()
	{

		if (BROWSERMOBILE==true) 
		{
			setTimeout("SIDEBARSCROLL.refresh()", 0);
		}

	}

	/**
		closes out our sidebar 
		*/
	this.close = function()
	{

		if (BROWSER=="ie" && BROWSERVERSION < 10) SIDEBAR.bar.appendChild(createCleaner());
		SIDEBAR.setSizes();

	}

	/**
		adds an image to the right side of our current header
		*/
	this.addHeaderImage = function(img,func,title)
	{

    setClass(img,"siteSidebarHeaderImage");

    if (title) img.setAttribute("title",title);

    if (func) setClick(img,func);

    SIDEBAR.groupHeader.appendChild(img);

	};

  /**
    adds a submenu which can be displayed by clicking on the header image
  	*/
  this.addHeaderSubmenu = function(title,func)
  {

    //see if we already have a submenu for this button
    var arr = SIDEBAR.groupHeader.getElementsByTagName("div");

    if (arr.length > 0)
    {
      var subref = arr[0];
    }
    else
    {

			var img = SIDEBAR.groupHeader.getElementsByTagName("img")[0];
      var subref = PULLDOWN.create(img);
			SIDEBAR.groupHeader.appendChild(subref);
			
			//subref.style.marginLeft = (img.getPosition().x - 20) + "px";

    }

		if (func) PULLDOWN.add(title,func);
		else PULLDOWN.add(title);

  };

	this.setSizes = function()
	{

		var bar = ge("siteSidebar");
		if (!bar) return false;

		var tbheight = ge("siteHeader").getSize().y;
    var screenheight = window.getSize().y;
		bar.style.height = (screenheight - tbheight) + "px"; 
		
	};

}
