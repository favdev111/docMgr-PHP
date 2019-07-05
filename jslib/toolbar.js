var TOOLBAR = new SITE_TOOLBAR();

function SITE_TOOLBAR()
{

	//I know we need these
	this.toolbar;
	this.mode = "left";
	this.group;
	this.button;
	this.search;
	this.groups;
	this.groupsLeft = new Array();
	this.groupsRight = new Array();
	this.buttons = new Array();

	this.open = function(mode,ref)
	{

		//if passed a reference to use
		if (ref) this.toolbar = ref;
		else this.toolbar = ge("siteToolbar");

		clearElement(this.toolbar);
		
		this.buttons = new Array();
		this.groupsLeft = new Array();
		this.groupsRight = new Array();
		this.group = "";
		this.button = "";
		this.search = "";

		//setup if we are aligning from the left or the right
		if (mode) this.mode = mode;
		else this.mode = "left";

	};


	/**********************************************************************
		FUNCTION:	addGroup
		PURPOSE:	sets the toolbar group for subsequent button or
							searchfield additions
	**********************************************************************/
	this.addGroup = function(mode)
	{

		if (mode) this.mode = mode;

		this.group = new Array();

		if (this.mode=="right") this.groupsRight.push(this.group);
		else this.groupsLeft.push(this.group);

		//this.groups.push(this.group);

	};

	/**********************************************************************
		FUNCTION:	add
		PURPOSE:	add a button to the current group
	**********************************************************************/
	this.add = function(title,func,img)
	{

		//create the button
		this.button = ce("div");
		this.button.setAttribute("toolbar_type","button");
		this.button.appendChild(ce("span","","",title));
		
		if (func) setClick(this.button,func);

		this.group.push(this.button);

		//return the button in case user wants to set attributes on it or something
		return this.button;

	};

	/**********************************************************************
		FUNCTION:	addSubmenu
		PURPOSE:	adds a submenu to the last added button
	**********************************************************************/
	this.addSubmenu = function(title,func,menuimg,checked)
	{

		//see if we already have a submenu for this button
		var arr = this.button.getElementsByTagName("div");

		if (arr.length > 0) 
		{
			var subref = arr[0];
		}
		else
		{

			//add the expand image
			var img = createImg(THEME_PATH + "/images/toolbar/down-arrow-small-white.png");
			setClass(img,"siteToolbarButtonExpandImg");
			this.button.appendChild(img);

			var subref = PULLDOWN.create(this.button);

			this.button.appendChild(subref);

		}

		return PULLDOWN.add(title,func,menuimg,checked);

	};

	this.setSubmenuSelected = function(row)
	{
		PULLDOWN.selectRow(row);
	};

	/**********************************************************************
		FUNCTION:	addSubmenuSeparator
		PURPOSE:	adds a submenu separator
	**********************************************************************/
	this.addSubmenuSeparator = function()
	{
		return PULLDOWN.addSeparator();
	};

	/**********************************************************************
		FUNCTION:	cycleMenu
		PURPOSE:	cycles a submenu of a button to displayed or hidden
							based on current visibility
	**********************************************************************/
	this.cycleMenu = function(e)
	{

		e.cancelBubble = true;
		var ref = getEventSrc(e);

		if (ref.tagName.toUpperCase()=="IMG") ref = ref.parentNode;

		var submenu = ref.getElementsByTagName("div")[0];

		if (!submenu) return false;

		if (submenu.style.display=="block") submenu.style.display = "none";
		else submenu.style.display = "block";		

	};

	/**********************************************************************
		FUNCTION:	setMenuOption
		PURPOSE:	sets the current search menu value 
	**********************************************************************/
	this.setMenuOption = function(e)
	{

		var clickref = getEventSrc(e);
		var menuref = getEventSrc(e);

		while (menuref.className!="sitePullDownMenu")
		{
			menuref = menuref.parentNode;
		}

		var arr = menuref.getElementsByTagName("div");

		for (var i=0;i<arr.length;i++)
		{

			var img = arr[i].getElementsByTagName("img")[0];

			if (arr[i]==clickref) img.src = THEME_PATH + "/images/toolbar/check.png";
			else img.src = THEME_PATH + "/images/toolbar/blank.png";

		}

		this.closeMenu(e);

	};

	/**********************************************************************
		FUNCTION:	closeMenu
		PURPOSE:	closes the current menu
	**********************************************************************/
	this.closeMenu = function(e)
	{

		var ref = getEventSrc(e);

		var submenu = ref.parentNode;
		submenu.style.display = "none";

	};


	/**********************************************************************
		FUNCTION:	addSearch
		PURPOSE:	adds a search field to the current group
	**********************************************************************/
	this.addSearch = function(func,placeholder)
	{

		//create the button
		this.search = ce("div","","siteToolbarSearchContainer");
		this.search.setAttribute("toolbar_type","search");

		var tb = createTextbox("siteToolbarSearch");
		tb.setAttribute("autocomplete","off");
		tb.setAttribute("autocapitalize","off");
		this.search.appendChild(tb);

			if (BROWSERMOBILE==true) 
			{
				tb.style.marginTop = "1px";
			}

		if (placeholder) tb.setAttribute("placeholder",placeholder);

		if (func) setKeyUp(tb,func);

		return this.search;

	};

	/**********************************************************************
		FUNCTION:	addSearchSubmenu
		PURPOSE:	adds a submenu to the last added search field
	**********************************************************************/
	this.addSearchSubmenu = function(title,func,checked)
	{

		//see if we already have a submenu for this button
		var arr = this.search.getElementsByTagName("div");

		if (arr.length > 0) 
		{
			var subref = arr[0];
		}
		else
		{

			var tb = this.search.getElementsByTagName("input")[0];

			//add the expand image
			var img = createImg(THEME_PATH + "/images/toolbar/down-arrow-small.png");
			setClass(img,"siteToolbarSearchExpandImg");

			tb.style.marginLeft = "4px";
			
			this.search.insertBefore(img,tb);

			var subref = PULLDOWN.create(img);
			subref.id = "siteToolbarSearchMenu";
			this.search.appendChild(subref);

		}

		return PULLDOWN.add(title,func,1,checked);

	};

	this.addSearchSeparator = function()
	{
		return PULLDOWN.addSeparator();
	};

	this.closeBox = function()
	{

		var spacer = ce("div","siteToolbarSpacer " + this.mode);

		//add the search last
		if (this.search)
		{
			this.toolbar.appendChild(this.search);
		}

		for (var i=0;i<this.groupsLeft.length;i++)
		{
			this.addButtonGroup(this.groupsLeft[i],"left");
		}

		if (this.groupsRight.length > 0)
		{

			this.toolbar.appendChild(spacer);

			for (var i=0;i<this.groupsRight.length;i++)
			{
				this.addButtonGroup(this.groupsRight[i],"right");
			}
				
		}

		if (this.toolbar.className.indexOf("siteToolbar")==-1)
		{
			if (this.toolbar.className.length > 0) this.toolbar.className += " siteToolbar";
			else this.toolbar.className = "siteToolbar";
		}
		
		if (BROWSER=="ie" && BROWSERVERSION < 10) this.toolbar.appendChild(createCleaner());

	};

	/**
		the messy way - close for ie using floats
		*/
	this.closeFloat = function()
	{

		var spacer = ce("div","siteToolbarSpacer " + this.mode);

		if (this.groupsRight.length > 0)
		{

			this.groupsRight.reverse();

			for (var i=0;i<this.groupsRight.length;i++)
			{
				this.addButtonGroup(this.groupsRight[i],"right");
			}

		}

		//now add the search, and the left groups
		if (this.search)
		{
			this.toolbar.appendChild(this.search);
		}

		for (var i=0;i<this.groupsLeft.length;i++)
		{
			this.addButtonGroup(this.groupsLeft[i],"left");
		}

		if (this.toolbar.className.indexOf("siteToolbar")==-1)
		{
			if (this.toolbar.className.length > 0) this.toolbar.className += " siteToolbar";
			else this.toolbar.className = "siteToolbar";
		}
		
		this.toolbar.appendChild(createCleaner());

	};

	this.close = function()
	{

		if (BROWSER=="ie" && BROWSERVERSION < 10) this.closeFloat();
		else this.closeBox();

	};


	this.addButtonGroup = function(btns,mode)
	{

			//setup the toolbar group
			var groupDiv = ce("div");	

			//set some styling based on mode
			if (mode=="right") groupDiv.className = "siteToolbarGroup right";
			else groupDiv.className = "siteToolbarGroup left";

			//add all the buttons to the group
			for (var c=0;c<btns.length;c++)
			{

				if (btns[c].getAttribute("toolbar_type")=="button")
				{

					//add aditional class
					if (c==0 && btns.length==1)  setClass(btns[c],"siteToolbarButton siteToolbarButtonBegin siteToolbarButtonEnd");
					else if (c==0) setClass(btns[c],"siteToolbarButton siteToolbarButtonBegin");
					else if (c==(btns.length-1)) setClass(btns[c],"siteToolbarButton siteToolbarButtonEnd");
					else setClass(btns[c],"siteToolbarButton");

				}

				//add to the group div
				groupDiv.appendChild(btns[c]);

			}


			//add to the toolbar
			this.toolbar.appendChild(groupDiv);

	};

}