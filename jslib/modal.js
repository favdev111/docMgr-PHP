
var MODAL = new SITEMODAL();

function SITEMODAL()
{

	this.modalref = "";
	this.container = "";
	this.navbar = "";
	this.toolbar = "";
	this.navbarLeft = "";
	this.navbarRight = "";
	this.toolbarLeft = "";
	this.toolbarRight = "";
	this.closefunc = "";
	this.visible = false;
	this.recordHeader = "";
	this.recordContainer = "";
	this.recordRow = "";
	this.openHandler = "";
	this.closeHandler = "";

	/**
		sets the size of our modal window.  can be called by outside methods
		to resize w/o reopening window
		*/
	this.setSize = function(width,height)
	{

 	 	if (BROWSER=="webkit") 
		{
 	   	var sl = window.scrollX;
    	var st = window.scrollY;
  	} 
		else 
		{
    	var sl = getScrollLeft();
    	var st = getScrollTop();
  	}

  	//try to center the popup if values are nto passed
  	var xPos = (getWinWidth()/2) - (width/2) + sl;
  	var yPos = (getWinHeight()/2) - (height/2) + st;

		//don't let the top show up off screen
		if (yPos < 0) yPos = "5";

		MODAL.modalref.style.width = width + "px";
		MODAL.modalref.style.minHeight = height + "px";
		MODAL.modalref.style.left = xPos + "px";
		MODAL.modalref.style.top = yPos + "px";

	}

	this.clearAll = function()
	{
    MODAL.clearContainer();   
    MODAL.clearNavbarLeft();  
    MODAL.clearNavbarRight();  
    MODAL.clearToolbarLeft(); 
    MODAL.clearToolbarRight();
	};

	/**
		loads the modal window and makes it visible
		*/
	this.open = function(width,height,title,closefunc)
	{

		//setup our references
		MODAL.modalref = ge("siteModal");

		//set the size
		MODAL.setSize(width,height);

		//if passed a close function, set it
		if (closefunc) MODAL.closefunc = closefunc;
		else MODAL.closefunc = null;

		//store that we are visible
		MODAL.visible = true;

		//clear it out
  	clearElement(MODAL.modalref);
		MODAL.recordContainer = "";
		MODAL.recordHeader = "";

		//add our close image.  make sure it's first
		MODAL.navbar = ce("div","","siteModalNavbar");
		MODAL.container = ce("div","","siteModalContainer");
		MODAL.toolbar = ce("div","","siteModalToolbar");

		//set a min height for our container so we know the toolbar will be on the bottom
		MODAL.container.style.height = (parseInt(MODAL.modalref.style.minHeight) - 60) + "px";
		MODAL.container.style.overflow = "auto";

		//setup places for our buttons
		MODAL.navbarLeft = ce("div","","siteModalNavbarLeft");		
		MODAL.navbarRight = ce("div","","siteModalNavbarRight");		

		MODAL.navbar.appendChild(MODAL.navbarRight);
		MODAL.navbar.appendChild(MODAL.navbarLeft);

		MODAL.toolbarLeft = ce("div","","siteModalToolbarLeft");		
		MODAL.toolbarRight = ce("div","","siteModalToolbarRight");		

		MODAL.toolbar.appendChild(MODAL.toolbarRight);
		MODAL.toolbar.appendChild(MODAL.toolbarLeft);

		MODAL.modalref.appendChild(MODAL.navbar);
		MODAL.modalref.appendChild(MODAL.container);
		MODAL.modalref.appendChild(MODAL.toolbar);

		MODAL.modalref.className = "shown";

  	if (window.Drag) 
		{
    	//work with older version of mootools for editreports
    	new Drag(MODAL.modalref,{handle:MODAL.navbar});
  	}

    //create the close button
		MODAL.addNavbarButtonRight(_I18N_CLOSE,"MODAL.hide()");

		//set the title if passed
		if (title) MODAL.setTitle(title);

		//we have an externally set openHandler, call it
    if (MODAL.openHandler)
    {
      var func = eval(MODAL.openHandler);
      func();
    }

	}

	/**
		clear the left side of the navbar (top bar)
		*/
	this.clearNavbarLeft = function()
	{
		clearElement(MODAL.navbarLeft);
	};

	/**
		clear the right side of the navbar (top bar)
		*/
	this.clearNavbarRight = function()
	{
		clearElement(MODAL.navbarRight);
	};

	/**
		clear the left side of the toolbar (lower bar)
		*/
	this.clearToolbarLeft = function()
	{
		clearElement(MODAL.toolbarLeft);
	};

	/**
		clear the right side of the toolbar (lower bar)
		*/
	this.clearToolbarRight = function()
	{
		clearElement(MODAL.toolbarRight);
	};

	/**
		add an action button to the left navbar
		*/
	this.addNavbarButtonLeft = function(title,click,img)
	{
		return MODAL.addBarButton(MODAL.navbarLeft,title,click,img);
	}

	/**
		add an action button to the right navbar
		*/
	this.addNavbarButtonRight = function(title,click,img)
	{
		return MODAL.addBarButton(MODAL.navbarRight,title,click,img);
	}

	/**
		add an action button to the left toolbar
		*/
	this.addToolbarButtonLeft = function(title,click,img)
	{
		return MODAL.addBarButton(MODAL.toolbarLeft,title,click,img);
	}

	/**
		add an action button to the right toolbar
		*/
	this.addToolbarButtonRight = function(title,click,img)
	{
		return MODAL.addBarButton(MODAL.toolbarRight,title,click,img);
	}

	/**
		sets the title of the modal.  Displayed on the left navbar side
		*/
	this.setTitle = function(title)
	{
		clearElement(MODAL.navbarLeft);
		MODAL.navbarLeft.appendChild(ctnode(title));
	}

  /**
    adds a search field to left toolbar
		*/
  this.addSearch = function(func,placeholder)
  {

    //create the button
    var btn = ce("div","","siteModalSearchDiv");
    btn.setAttribute("toolbar_type","search");

    var tb = createTextbox("siteModalSearch");
    tb.setAttribute("autocomplete","off");
    tb.setAttribute("autocapitalize","off");
    btn.appendChild(tb);

		if (placeholder) tb.placeholder = placeholder;
    if (func) setKeyUp(btn,func);

		MODAL.toolbarLeft.appendChild(btn);
   
    //return the button in case user wants to set attributes on it or something
    return btn;

  };

	this.getAllButtons = function(ref)
	{

		var arr = new Array();

		if (document.getElementsByClassName) arr = ref.getElementsByClassName("siteToolbarButton");
		else
		{
			var tmp = ref.getElementsByTagName("div");

			for (var i=0;i<tmp.length;i++)
			{
				if (tmp[i].className=="siteToolbarButton") arr.push(tmp[i]);
			}
		}

		return arr;

	};

	/**
		generic class for adding a button to toolbar or navbar
		*/
	this.addBarButton = function(ref,title,click,img)
	{

    //create the button
    var btn = ce("div","siteToolbarButton");
    btn.setAttribute("toolbar_type","button");
    btn.appendChild(ce("span","","",title));

		if (click) setClick(btn,click);

		ref.appendChild(btn);

		var arr = MODAL.getAllButtons(ref);

		for (var i=0;i<arr.length;i++)
		{

			var className = "siteToolbarButton";

			if (i==0) className += " siteToolbarButtonBegin";
			if (i==(arr.length-1)) className += " siteToolbarButtonEnd";

			arr[i].className = className;

		}

		return btn;

	};

	/**
		hides the modal window
		*/
	this.hide = function()
	{
		MODAL.modalref.className = "";
		if (MODAL.closefunc) eval(MODAL.closefunc);

		MODAL.visible = false;

		//we have an externally set closeHandler, call it
    if (MODAL.closeHandler)
    {
      var func = eval(MODAL.closeHandler);
      func();
    }

		clearElement(MODAL.container);
		MODAL.closefunc = null;
		MODAL.closeHandler = null;

	}

	/**
		adds a cell to the window using the header css
		*/
	this.addHeader = function(data)
	{

		MODAL.modalref = ge("siteModal");

		var header = ce("div","siteModalHeader","",data);

		MODAL.container.appendChild(header);

	};

	/**
		drops the pass DOM object into the main container
		*/
	this.add = function(data)
	{
		MODAL.container.appendChild(data);
	};

	/**
		adds a cell using the modalCell css
		*/
	this.addCell = function(data)
	{

		MODAL.modalref = ge("siteModal");

		var cell = ce("div","siteModalCell","",data);

		MODAL.container.appendChild(cell);

	};

	/**
		adds a button to the main container with cell css
		*/
	this.addButton = function(title,func)
	{

		var btn = createBtn("",title,func);

		MODAL.addCell(btn);

	};

	/**
		clears all modal cells from the container
		*/
	this.clearCells = function()
	{

		var arr = MODAL.container.getElementsByClassName("siteModalCell");

		while (arr.length > 0)
		{
			MODAL.container.removeChild(arr[0]);
		}

	};

	/**
		clears the entire modal container
		*/
	this.clearContainer = function()
	{
		clearElement(MODAL.container);
		MODAL.recordHeader = "";
		MODAL.recordContainer = "";
		MODAL.recordRow = "";

	};

	/**
		adds an xml-driven eform to the modal window.  calls the
		data handler and form def file if passed
		*/
	this.addForm = function(form,datahandler,formhandler,parameters)
	{

		//set some handler defaults
		if (!datahandler) datahandler = "MODAL.emptyData";
		if (!formhandler) formhandler = "MODAL.writeForm";

    //load the long way so we can pass parameters if needed
		EFORM.reset();
		EFORM.formFile = form;
    EFORM.formHandler = formhandler;
    EFORM.dataHandler = datahandler;
    EFORM.parameters = parameters;
    EFORM.process();

	};

	/**
		empty data handler in case a form data func isn't specified
		*/
	this.emptyData = function()
	{
		return new Array();
	}

	/**
		writes the created eform to the modal container
		*/
	this.writeForm = function(cont)
	{
		MODAL.add(cont);
		clearSiteStatus();
	};

	/**
		starts a header row for a modal record list
		*/
	this.openRecordHeader = function()
	{
		if (!MODAL.recordHeader)
		{
			MODAL.recordHeader = ce("div","","modalRecordHeader");
			MODAL.add(MODAL.recordHeader);
		}
	};

	/**
		adds a header cell to the header row 
		*/
	this.addHeaderCell = function(text,size,clicker)
	{

		//setup the class and subclass
		var cn = "modalRecordHeaderCell";
		if (size) cn += " " + size;

		//make the cell
		var cell = ce("div",cn,"",text);

		//add a clicker
		if (clicker) setClick(cell,clicker);

		MODAL.recordHeader.appendChild(cell);

	};

	/**
		closes the header row
		*/
	this.closeRecordHeader = function()
	{
		MODAL.recordHeader.appendChild(createCleaner());
		return MODAL.recordHeader;
	};

	/**
		creates a new list row, and container if necessary) for a modal list record
		*/
	this.openRecordRow = function(clicker)
	{

		//make our container if it doesn't exist
		if (!MODAL.recordContainer) 
		{
			MODAL.recordContainer = ce("div","","modalRecordContainer");
			MODAL.add(MODAL.recordContainer);
		}

		MODAL.recordRow = ce("div","modalRecordRow");

		if (clicker)
		{
			 setClick(MODAL.recordRow,clicker);
		}

		return MODAL.recordRow;

	};

	/**
		adds a record cell to the current list row
		*/
	this.addRecordCell = function(text,size,clicker)
	{

		//setup the class and subclass
		var cn = "modalRecordRowCell";
		if (size) cn += " " + size;

		//make the cell
		var cell = ce("div",cn,"",text);

		//add a clicker
		if (clicker) setClick(cell,clicker);

		MODAL.recordRow.appendChild(cell);

		return cell;

	};

	/**
		closes the current list row and adds to the list
		*/
	this.closeRecordRow = function()
	{
		MODAL.recordContainer.appendChild(MODAL.recordRow);
		if (BROWSER=="ie" && BROWSERVERSION < 10) MODAL.recordContainer.appendChild(createCleaner());
		return MODAL.recordRow;

	};

	this.openRecords = function()
	{

		if (MODAL.recordContainer) 
		{
			MODAL.recordContainer.parentNode.removeChild(MODAL.recordContainer);
			MODAL.recordContainer = null;
		}

	};

	this.closeRecords = function()
	{

		//set the record container height to fill our form
		if (MODAL.recordHeader) 
		{
			var height = parseInt(MODAL.container.style.height) - parseInt(MODAL.recordHeader.getSize().y);
			MODAL.recordContainer.style.height = height + "px";
		}
		else
		{
			MODAL.recordContainer.style.height = MODAL.container.style.height;
		}

	};

	/**
		clears all rows in the record list
		*/
	this.clearRecords = function()
	{
		clearElement(MODAL.recordContainer);
	};

	/**
		clears out the list header of its cells
		*/
	this.clearHeader = function()
	{
		clearElement(MODAL.recordHeader);
	};

	/**
		clears a list of its rows and header
		*/
	this.clearList = function()
	{
		MODAL.clearHeader();
		MODAL.clearListRows();
	};
	
}
