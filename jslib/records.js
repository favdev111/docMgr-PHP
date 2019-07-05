
var RECORDS = new SITE_RECORDS();

if (window.addEventListener)
  window.addEventListener("resize",RECORDS.setSizes);
else
  window.attachEvent("onresize",RECORDS.setSizes);

function SITE_RECORDS(ref)
{

	//our containers
	this.container;
	this.listContainer;
	this.detailContainer;
	this.recordHeader;
	this.recordPager;
	this.breadcrumbs;
	this.recordList;
	this.recordDetail;
	this.recordTable;
	this.recordTableHeader;
	this.recordTableBody;
	this.detailHeader;
	this.detailSeparator;
	this.recordRow;
	this.headerRow;
	this.autoResize;
	this.recordFooter;

	this.filterContainer;
	this.filterXML = "";
	this.filterHandler;

	this.viewMode;
	this.rowMode;
	this.rows = new Array();
	this.init = false;
	this.selected = new Array();

	this.load = function(ref,mode,rowMode)
	{

		RECORDS.container = ref;
		RECORDS.viewMode = mode;
		RECORDS.reset();
		RECORDS.init = false;
		RECORDS.rows = new Array();
		RECORDS.selected = new Array();

		if (RECORDS.autoResize!=false) RECORDS.autoResize = true;

		//set the interactivity of our rows
		RECORDS.setRowMode(rowMode);

		if (mode=="listView") RECORDS.buildListView();
		else if (mode=="listPagerView") RECORDS.buildListPagerView();
		else if (mode=="listDetailView") RECORDS.buildListDetailView();
		else if (mode=="listDetailPagerView") RECORDS.buildListDetailPagerView();
		else if (mode=="listTableView") RECORDS.buildListTableView();
		else if (mode=="detailView") RECORDS.buildDetailView();

	};

	this.loadForm = function(ref,form,datahandler,formdef)
	{

		RECORDS.container = ref;
		RECORDS.reset();
		RECORDS.init = false;
		RECORDS.rows = new Array();
		RECORDS.selected = new Array();

		RECORDS.buildFormView(form,datahandler,formdef);

	};

	/**
		start over
		*/
	this.reset = function()
	{
		RECORDS.recordHeader = "";
		RECORDS.recordPager = "";
		RECORDS.recordList = "";
		RECORDS.recordRow = "";
		RECORDS.headerRow = "";
	};

	/**
		sets the interactivity level of our rows
		*/
	this.setRowMode = function(mode)
	{

		if (mode=="active") RECORDS.rowClass = "recordListRow activeMode";
		else if (mode=="select") RECORDS.rowClass = "recordListRow selectMode"
		else if (mode=="select1")
		{
			RECORDS.rowClass = "recordListRow";
			mode = "select";
		}
		else if (mode=="multiselect") RECORDS.rowClass = "recordListRow selectMode"
		else RECORDS.rowClass = "recordListRow";

		//save for later
		RECORDS.rowMode = mode;

		//set the main list class so we can inherit styles later
		if (RECORDS.listContainer)
		{
			RECORDS.listContainer.className = mode;
		}

		//if we already have rows listed, update their style
		for (var i=0;i<RECORDS.rows.length;i++)
		{

			//skip if the class name contains "deleted";
			if (RECORDS.rows[i].className.indexOf("deleted")!=-1) continue;

			//reset the row class to not selected
			RECORDS.rows[i].className = RECORDS.rowClass;

			//if switching to multiselect, make sure any previous selected row is checked
			if (mode=="multiselect" && RECORDS.selected.length > 0)
			{

				var key = arraySearch(RECORDS.rows[i],RECORDS.selected);
				var img = RECORDS.rows[i].getElementsByTagName("img")[0];

				if (key!=-1)
				{
					img.src = THEME_PATH + "/images/icons/green_checked.png";
				}
				else
				{
					img.src = THEME_PATH + "/images/icons/empty_circle.png";
				}

			}
			else if (mode=="select" && RECORDS.selected.length > 0)
			{

				//if more than one is selected, only allow the first one to stay
				if (RECORDS.selected.length > 1) RECORDS.selected = new Array(RECORDS.selected[0]);

				//show the remaining row as stil selected
				if (RECORDS.rows[i]==RECORDS.selected[0]) RECORDS.rows[i].className += " selected";

			}

		}

	};

	this.buildListView = function()
	{

  	RECORDS.viewMode = "listView";

  	clearElement(RECORDS.container);

	  //list view
	  RECORDS.listContainer = ce("div","","recordListContainer");
		RECORDS.breadcrumbs = ce("div","","recordListBreadcrumbs");
	  RECORDS.recordHeader = ce("div","","recordListHeader");
		RECORDS.recordList = ce("div",RECORDS.rowMode,"recordList");

		RECORDS.breadcrumbs.style.display = "none";

	  //put the list view together
		RECORDS.listContainer.appendChild(RECORDS.breadcrumbs);
	  RECORDS.listContainer.appendChild(RECORDS.recordHeader);
	  RECORDS.listContainer.appendChild(RECORDS.recordList);

	  //assemble!
	  RECORDS.container.appendChild(RECORDS.listContainer);

		RECORDS.recordFooter = ce("div","","recordListFooter");
		RECORDS.container.appendChild(RECORDS.recordFooter);		
		RECORDS.recordFooter.style.display = "none";

  	RECORDS.setSizes();
  	RECORDS.resizer();

	};

	this.showBreadcrumbs = function()
	{
		RECORDS.breadcrumbs.style.display = "block";
		RECORDS.setSizes();
	};

	this.hideBreadcrumbs = function()
	{
		RECORDS.breadcrumbs.style.display = "";
		RECORDS.setSizes();
	};

	this.buildListPagerView = function()
	{

  	RECORDS.viewMode = "listPagerView";

  	clearElement(RECORDS.container);

	  //list view
	  RECORDS.listContainer = ce("div","","recordListContainer");
		RECORDS.breadcrumbs = ce("div","","recordListBreadcrumbs");
	  RECORDS.recordHeader = ce("div","","recordListHeader");
		RECORDS.recordList = ce("div",RECORDS.rowMode,"recordList");
		RECORDS.recordPager = ce("div","","recordListPager");
		RECORDS.filterContainer = ce("div","","recordListFilterContainer");

	  //put the list view together
	  RECORDS.listContainer.appendChild(RECORDS.breadcrumbs);
		RECORDS.listContainer.appendChild(RECORDS.filterContainer);
	  RECORDS.listContainer.appendChild(RECORDS.recordPager);
	  RECORDS.listContainer.appendChild(RECORDS.recordHeader);
	  RECORDS.listContainer.appendChild(RECORDS.recordList);

	  //assemble!
	  RECORDS.container.appendChild(RECORDS.listContainer);

		RECORDS.recordFooter = ce("div","","recordListFooter");
		RECORDS.container.appendChild(RECORDS.recordFooter);		
		RECORDS.recordFooter.style.display = "none";

  	RECORDS.setSizes();
  	RECORDS.resizer();

	};

	this.buildListTableView = function()
	{
	
		RECORDS.viewMode = "listTableView";
		
		clearElement(RECORDS.container);

		//setup the main container, the pager, and the filter container
	  RECORDS.listContainer = ce("div","","recordListContainer");
		RECORDS.recordList = ce("div","","recordList");	
		RECORDS.recordPager = ce("div","","recordListPager");
		RECORDS.filterContainer = ce("div","","recordListFilterContainer");

		//build our table components
		RECORDS.recordTable = ce("table","","recordTableContainer");
		RECORDS.recordTable.setAttribute("cellpadding","0");
		RECORDS.recordTable.setAttribute("cellspacing","0");

	  RECORDS.recordTableHeader = ce("thead","","recordTableHeader");
	  RECORDS.recordTableBody = ce("tbody","","recordTableBody");
		RECORDS.recordTable.appendChild(RECORDS.recordTableHeader);
		RECORDS.recordTable.appendChild(RECORDS.recordTableBody);

		//assemble
		RECORDS.recordList.appendChild(RECORDS.recordTable);
		RECORDS.listContainer.appendChild(RECORDS.recordList);

		RECORDS.container.appendChild(RECORDS.filterContainer);
		RECORDS.container.appendChild(RECORDS.recordPager);
		RECORDS.container.appendChild(RECORDS.listContainer);
	
		RECORDS.setSizes();
		RECORDS.resizer();
	
	};
	
	this.buildListDetailPagerView = function()
	{

	  RECORDS.viewMode = "listDetailPagerView";
	
	  clearElement(RECORDS.container);
	
	  //init our containers and scrollers
		RECORDS.recordPager = ce("div","","recordListPager");
		RECORDS.breadcrumbs = ce("div","","recordListBreadcrumbs");
	  RECORDS.listContainer = ce("div","","recordListContainer");
		RECORDS.detailContainer = ce("div","","recordDetailContainer");

		//init the data holders
	  RECORDS.recordHeader = ce("div","","recordListHeader");
		RECORDS.recordList = ce("div",RECORDS.rowMode,"recordList");

		RECORDS.detailHeader = ce("div","","recordDetailHeader");
		RECORDS.recordDetail = ce("div","","recordDetail");

	  RECORDS.detailSeparator = ce("div","","recordDetailSeparator");
		RECORDS.filterContainer = ce("div","","recordListFilterContainer");

	  //put the list view together
		RECORDS.listContainer.appendChild(RECORDS.breadcrumbs);
		RECORDS.listContainer.appendChild(RECORDS.filterContainer);
	  RECORDS.listContainer.appendChild(RECORDS.recordPager);
	  RECORDS.listContainer.appendChild(RECORDS.recordHeader);
	  RECORDS.listContainer.appendChild(RECORDS.recordList);
	
	  //put the content view together
		RECORDS.detailContainer.appendChild(RECORDS.detailHeader);
		RECORDS.detailContainer.appendChild(RECORDS.recordDetail);

	  //assemble!
	  RECORDS.container.appendChild(RECORDS.listContainer);
	  RECORDS.container.appendChild(RECORDS.detailSeparator);
	  RECORDS.container.appendChild(RECORDS.detailContainer);

		RECORDS.recordFooter = ce("div","","recordListFooter");
		RECORDS.container.appendChild(RECORDS.recordFooter);		
		RECORDS.recordFooter.style.display = "none";
	
	  RECORDS.setSizes();
	  RECORDS.resizer(); 
	
	};

	this.buildListDetailView = function()
	{

	  RECORDS.viewMode = "listDetailView";
	
	  clearElement(RECORDS.container);
	
	  //init our containers and scrollers
		RECORDS.breadcrumbs = ce("div","","recordListBreadcrumbs");
		RECORDS.filterContainer = ce("div","","recordListFilterContainer");
	  RECORDS.listContainer = ce("div","","recordListContainer");
		RECORDS.detailContainer = ce("div","","recordDetailContainer");

		//init the data holders
	  RECORDS.recordHeader = ce("div","","recordListHeader");
		RECORDS.recordList = ce("div",RECORDS.rowMode,"recordList");

		RECORDS.detailHeader = ce("div","","recordDetailHeader");
		RECORDS.recordDetail = ce("div","","recordDetail");

	  RECORDS.detailSeparator = ce("div","","recordDetailSeparator");
	
	  //put the list view together
		RECORDS.listContainer.appendChild(RECORDS.breadcrumbs);
		RECORDS.listContainer.appendChild(RECORDS.filterContainer);
	  RECORDS.listContainer.appendChild(RECORDS.recordHeader);
	  RECORDS.listContainer.appendChild(RECORDS.recordList);
	
	  //put the content view together
		RECORDS.detailContainer.appendChild(RECORDS.detailHeader);
		RECORDS.detailContainer.appendChild(RECORDS.recordDetail);

	  //assemble!
	  RECORDS.container.appendChild(RECORDS.listContainer);
	  RECORDS.container.appendChild(RECORDS.detailSeparator);
	  RECORDS.container.appendChild(RECORDS.detailContainer);

		RECORDS.recordFooter = ce("div","","recordListFooter");
		RECORDS.container.appendChild(RECORDS.recordFooter);		
		RECORDS.recordFooter.style.display = "none";
	
	  RECORDS.setSizes();
	  RECORDS.resizer(); 
	
	};

	this.setSizes = function()
	{

		//bail if disabled by the client
		if (RECORDS.autoResize==false) return false;

	  var tbheight = parseInt(ge("siteHeader").getSize().y);
	  var screenheight = parseInt(window.getSize().y);
	
	  if (RECORDS.viewMode=="listTableView")
	  {
	
	    RECORDS.recordList.style.width = "auto";
	
	    //determine our height based on the screen height, the toolbar height, and a little offset
	    var height = screenheight - tbheight - RECORDS.recordPager.getSize().y - RECORDS.filterContainer.getSize().y;
	
	    //if we have and are using search filters, add their height to the offset as well
	   	//if (RECORDS.filterContainer) height -= RECORDS.filterContainer.getSize().y;
	 	
	    RECORDS.recordList.style.height = height + "px";

	  }	
	  else if (RECORDS.viewMode=="listView")
	  {  
	    RECORDS.recordList.style.width = "auto";
	    RECORDS.recordList.style.height = (screenheight - tbheight - RECORDS.breadcrumbs.getSize().y - RECORDS.recordHeader.getSize().y - RECORDS.recordFooter.getSize().y - 1) + "px";
	  }
	  else if (RECORDS.viewMode=="listPagerView")
	  { 
	    RECORDS.recordList.style.width = "auto";
	    RECORDS.recordList.style.height = (screenheight - tbheight - RECORDS.breadcrumbs.getSize().y - RECORDS.filterContainer.getSize().y - RECORDS.recordHeader.getSize().y - RECORDS.recordPager.getSize().y - RECORDS.recordFooter.getSize().y - 1) + "px";
	  }
	  else if (RECORDS.viewMode=="listForm")
	  {                                               
	    RECORDS.recordDetail.style.width = "auto";
	    RECORDS.recordDetail.style.height = (screenheight - tbheight - RECORDS.detailHeader.getSize().y - RECORDS.recordFooter.getSize().y - 1) + "px";
	  }
	  else if (RECORDS.viewMode=="form")
	  {                                               
	    RECORDS.recordDetail.style.width = "auto";
	    RECORDS.recordDetail.style.height = (screenheight - tbheight - RECORDS.breadcrumbs.getSize().y - RECORDS.recordFooter.getSize().y - 1) + "px";
	  }
	  else if (RECORDS.viewMode=="detailView")
	  { 
	    RECORDS.recordDetail.style.width = "auto";
	    RECORDS.recordDetail.style.height = (screenheight - tbheight - RECORDS.breadcrumbs.getSize().y - RECORDS.recordFooter.getSize().y) + "px";
	  }
		else if (RECORDS.viewMode=="listDetailPagerView")
		{

	   	// RECORDS.recordList.style.width = "auto";
			var base = screenheight - tbheight - RECORDS.breadcrumbs.getSize().y - RECORDS.recordPager.getSize().y - RECORDS.filterContainer.getSize().y - RECORDS.recordHeader.getSize().y - RECORDS.recordFooter.getSize().y - RECORDS.detailHeader.getSize().y - RECORDS.detailSeparator.getSize().y;

			//if first time we've done this, split the available screen real-estate
			if (RECORDS.detailContainer.style.display=="none")
			{
				RECORDS.recordList.style.height = base + "px";
				RECORDS.init = false;
			}
			else if (RECORDS.init==false)
			{
				var newheight = parseInt(base/2);
				
				RECORDS.recordList.style.height = newheight + "px";
		    RECORDS.recordDetail.style.height = newheight + "px";

				RECORDS.init = true;

			}
			else
			{
				RECORDS.recordDetail.style.height = base - RECORDS.recordList.getSize().y + "px";
			}

		}
		else if (RECORDS.viewMode=="listDetailView")
		{

			var base = screenheight - tbheight - RECORDS.breadcrumbs.getSize().y - RECORDS.recordHeader.getSize().y - RECORDS.detailHeader.getSize().y - RECORDS.recordFooter.getSize().y - RECORDS.detailSeparator.getSize().y;

			//if first time we've done this, split the available screen real-estate
			if (RECORDS.detailContainer.style.display=="none")
			{
				RECORDS.recordList.style.height = base + "px";
				RECORDS.init = false;
			}
			else if (RECORDS.init==false)
			{
				var newheight = parseInt(base/2);
				
				RECORDS.recordList.style.height = newheight + "px";
		    RECORDS.recordDetail.style.height = newheight + "px";

				RECORDS.init = true;

			}
			else
			{
				RECORDS.recordDetail.style.height = base - RECORDS.recordList.getSize().y + "px";
			}

		}

		//see if we are scrolling.  If so, add a piece to the header to fix widths
		if (RECORDS.headerRow && RECORDS.viewMode!="listTableView")
		{
			var scrollheight = parseInt(RECORDS.recordList.getScrollSize().y);
			var viewheight = parseInt(RECORDS.recordList.getSize().y);
			var base = 0;

			if (scrollheight > viewheight) base = 14;

			//RECORDS.headerRow.parentNode.style.width = (RECORDS.recordList.getScrollSize().x - base)  + "px";

			//I think we may need this for certain browsers or operating systems
			//RECORDS.headerRow.parentNode.style.paddingRight = base + "px";

		}

	}
	
	this.resizer = function()
	{
	
	  if (BROWSERMOBILE==true) return false;

	  if (RECORDS.viewMode=="listView" || RECORDS.viewMode=="listPagerView" || RECORDS.viewMode=="reportView" || RECORDS.viewMode=="listForm" || RECORDS.viewMode=="form" || RECORDS.viewMode=="detailView") return false;
	
	  var mv = RECORDS.recordDetail;
	  var mc = RECORDS.recordList;

	  mc.makeResizable({
	   
	        limit: {x:[mc.getWidth(),mc.getWidth()]},
	
	        handle: RECORDS.detailSeparator,
	   
	        onComplete: function() 
					{
	          RECORDS.setSizes();
	          mc.style.width = "auto";
	        }
	
	      });


	
	};

	this.showFooter = function()
	{
		RECORDS.recordFooter.style.display = "block";
		RECORDS.setSizes();
	};

	/**
		dumps an xml form into the container with no header
		*/
	this.buildDetailView = function()
	{

  	RECORDS.viewMode = "detailView";

  	clearElement(RECORDS.container);

	  //list view
		RECORDS.breadcrumbs = ce("div","","recordListBreadcrumbs");
	  RECORDS.detailContainer = ce("div","","recordDetailContainer");
		RECORDS.recordDetail = ce("div","","recordDetail");

		RECORDS.breadcrumbs.style.display = "none";

	  //put the list view together
		RECORDS.detailContainer.appendChild(RECORDS.breadcrumbs);
	  RECORDS.detailContainer.appendChild(RECORDS.recordDetail);

	  //assemble!
	  RECORDS.container.appendChild(RECORDS.detailContainer);

		RECORDS.recordFooter = ce("div","","recordListFooter");
		RECORDS.container.appendChild(RECORDS.recordFooter);		
		RECORDS.recordFooter.style.display = "none";

  	RECORDS.setSizes();
  	RECORDS.resizer();

	};

	/**
		dumps an xml form into the container with no header
		*/
	this.buildFormView = function(form,datahandler,deffile,parameters)
	{

  	RECORDS.viewMode = "form";

  	clearElement(RECORDS.container);

	  //list view
		RECORDS.breadcrumbs = ce("div","","recordListBreadcrumbs");
	  RECORDS.detailContainer = ce("div","","recordDetailContainer");
		RECORDS.recordDetail = ce("div","","recordDetail");

		RECORDS.breadcrumbs.style.display = "none";

	  //put the list view together
	  RECORDS.detailContainer.appendChild(RECORDS.breadcrumbs);
	  RECORDS.detailContainer.appendChild(RECORDS.recordDetail);

	  //assemble!
	  RECORDS.container.appendChild(RECORDS.detailContainer);

		//load the long way so we can pass parameters if needed
    EFORM.formFile = form;
    EFORM.formHandler = "RECORDS.writeForm"; 
    EFORM.dataHandler = datahandler; 
		EFORM.formDefinitionFile = deffile;
    EFORM.parameters = parameters;   
    EFORM.process();

		RECORDS.recordFooter = ce("div","","recordListFooter");
		RECORDS.container.appendChild(RECORDS.recordFooter);		
		RECORDS.recordFooter.style.display = "none";

  	RECORDS.setSizes();
  	RECORDS.resizer();

	};

  /**
    writes the created eform to the modal container
    */
  this.writeForm = function(cont)
  {
		RECORDS.recordDetail.appendChild(cont);
    clearSiteStatus();
  };

	/**
		dumps an xml based form into our container
		*/
	this.addDetailForm = function(form,datahandler,formhandler,parameters)
	{

		//use the default one if not overridden
		if (!formhandler) formhandler = "RECORDS.writeDetailForm";

  	clearElement(RECORDS.recordDetail);

		//load the long way so we can pass parameters if needed
		EFORM.reset();
    EFORM.formFile = form;
    EFORM.formHandler = formhandler;
    EFORM.dataHandler = datahandler; 
    EFORM.parameters = parameters;   
    EFORM.process();

  	RECORDS.setSizes();
  	RECORDS.resizer();

	};

  /**
    writes the created eform to the modal container
    */
  this.writeDetailForm = function(cont)
  {
		RECORDS.recordDetail.appendChild(cont);
    clearSiteStatus();
  };


	/**
		fires up the header row for categorizing and sorting
		*/
	this.openHeaderRow = function()
	{

		clearElement(RECORDS.recordHeader);

		RECORDS.headerRow = ce("div","recordListHeaderRow");

		var img = createImg(THEME_PATH + "/images/icons/gray_dot.png");
		img.className = "recordHeaderRowSelectImage";

		var cell = ce("div","recordHeaderRowSelect")
		cell.appendChild(img);
		setClick(cell,"RECORDS.selectAll()");

		RECORDS.headerRow.appendChild(cell);
		return RECORDS.headerRow;

	};

  /**
    adds a header cell to the header row
    */
  this.addHeaderCell = function(text,size,clicker)
  {
   
    //setup the class and subclass
    var cn = "recordListHeaderCell";
    if (size) cn += " " + size;

    //make the cell
    var cell = ce("div",cn,"",text);

    //add a clicker
    if (clicker) setClick(cell,clicker);

		RECORDS.headerRow.appendChild(cell);

  };

  /**
    closes the header row
    */
  this.closeHeaderRow = function()
  {
    if (BROWSER=="ie" && BROWSERVERSION < 10) RECORDS.headerRow.appendChild(createCleaner());
		RECORDS.recordHeader.appendChild(RECORDS.headerRow);
  };

	this.getRowRef = function(e)
	{

		var ref = getEventSrc(e);

		while (arraySearch(ref,RECORDS.rows)==-1)
		{
			ref = ref.parentNode;
		}

		return ref;

	};

	/**
		show the current row as selected
		*/
	this.select = function(e)
	{

		var ref;

		//if passed from a click event, track down the parent row of the click
		if (e.type=="click") ref = RECORDS.getRowRef(e);
		else ref = e;

		if (RECORDS.rowMode=="select")
		{

			//deselect any currently selected one
			if (RECORDS.selected.length > 0)
			{
				setClass(RECORDS.selected[0],"recordListRow selectMode");
			}

			//save and show as selected
			RECORDS.selected = new Array(ref);

			setClass(ref,"recordListRow selectMode selected");

		}
		else if (RECORDS.rowMode=="multiselect")
		{

			var key = arraySearch(ref,RECORDS.selected);
			var img = ref.getElementsByTagName("img")[0];

			if (key==-1)
			{
				RECORDS.selected.push(ref);
				img.src = THEME_PATH + "/images/icons/green_checked.png";
				//setClass(ref,"recordListRow selectMode selected");
			}
			else
			{
				RECORDS.selected.splice(key,1);
				//setClass(ref,"recordListRow selectMode");
				img.src = THEME_PATH + "/images/icons/empty_circle.png";
			}

		}		

	};

	/**
		select all rows
		*/
	this.selectAll = function()
	{

		if (RECORDS.rowMode!="multiselect") return false;

		//make sure we are starting on even footing
		if (RECORDS.selected.length!=RECORDS.rows.length) RECORDS.selected = new Array();

		for (var i=0;i<RECORDS.rows.length;i++)
		{
			RECORDS.select(RECORDS.rows[i]);
		}

	};

  /**
    creates a new list row, and container if necessary) for a modal list record
    */
  this.openRecordRow = function(clicker)
  {

		var click = "RECORDS.select(event);";
		if (clicker) click += clicker;

    RECORDS.recordRow = ce("div",RECORDS.rowClass);
		setClick(RECORDS.recordRow,click);

		RECORDS.rows.push(RECORDS.recordRow);

		var img = createImg(THEME_PATH + "/images/icons/empty_circle.png");
		img.className = "recordListRowSelectImage";
		RECORDS.recordRow.appendChild(ce("div","recordListRowSelect","",img));

		return RECORDS.recordRow;

  };

	this.addRecordImage = function(img,ref)
	{
		if (img.className.length==0) img.className = "recordListRowImage";
		else img.className += " recordListRowImage";

		if (!ref) ref = RECORDS.recordRow;
		ref.getElementsByTagName("div")[0].appendChild(img);

	};

  /**
    adds a record cell to the current list row
    */
  this.addRecordCell = function(text,size,clicker)
  {
   
    //setup the class and subclass
    var cn = "recordListRowCell";
    if (size) cn += " " + size;   

    //make the cell
    var cell = ce("div",cn,"",text);

    //add a clicker
    if (clicker) setClick(cell,clicker);

    RECORDS.recordRow.appendChild(cell);

    return cell;

  };

  /**
    closes the current list row and adds to the list
    */
  this.closeRecordRow = function()
  {
    if (BROWSER=="ie" && BROWSERVERSION < 10) RECORDS.recordRow.appendChild(createCleaner());
    RECORDS.recordList.appendChild(RECORDS.recordRow);

    return RECORDS.recordRow;

  };

  /**
    clears all rows in the record list
    */
  this.openRecords = function()
  {
		RECORDS.rows = new Array();
		RECORDS.selected = new Array();
    clearElement(RECORDS.recordList);
  };

	this.closeRecords = function()
	{

		RECORDS.setSizes();
		RECORDS.resizer();

	};

  /**
    clears out the list header of its cells
    */
  this.clearHeader = function()
  {
    clearElement(RECORDS.recordList);
  };

  /**
    clears a list of its rows and header
    */
  this.clear = function()
  {
		clearElement(RECORDS.container);
  };

	this.clearDetail = function()
	{
		clearElement(RECORDS.detailHeader);
		clearElement(RECORDS.recordDetail);
	};


	/**********************************************
		table mode building methods
	**********************************************/

  /**
    clears all rows in the record list
    */
  this.openTableRecords = function()
  {
		RECORDS.rows = new Array();
		RECORDS.selected = new Array();
    clearElement(RECORDS.recordTableBody);
  };

	this.closeTableRecords = function()
	{

		RECORDS.setSizes();
		RECORDS.resizer();

	};

	/**
		fires up the header row for categorizing and sorting
		*/
	this.openTableHeaderRow = function()
	{

		clearElement(RECORDS.recordTableHeader);
		RECORDS.headerRow = ce("tr","recordTableHeader");
		return RECORDS.headerRow;

	};

  /**
    adds a header cell to the header row
    */
  this.addTableHeaderCell = function(text,size,clicker)
  {
   
    //setup the class and subclass
    var cn = "recordTableHeaderCell";
    if (size) cn += " " + size;

    //make the cell
    var cell = ce("th",cn,"",text);

    //add a clicker
    if (clicker) setClick(cell,clicker);

		RECORDS.headerRow.appendChild(cell);

  };

  /**
    closes the header row
    */
  this.closeTableHeaderRow = function()
  {
		RECORDS.recordTableHeader.appendChild(RECORDS.headerRow);
  };

  /**
    creates a new list row, and container if necessary) for a modal list record
    */
  this.openTableRecordRow = function(clicker)
  {

		//var click = "RECORDS.select(event);";
		//if (clicker) click += clicker;

    RECORDS.recordRow = ce("tr","recordTableRow");
		//setClick(RECORDS.recordRow,click);
		RECORDS.rows.push(RECORDS.recordRow);

		return RECORDS.recordRow;

  };

  /**
    adds a record cell to the current list row
    */
  this.addTableRecordCell = function(text,size,clicker)
  {
   
    //setup the class and subclass
    var cn = "recordTableRowCell";
    if (size) cn += " " + size;   

    //make the cell
    var cell = ce("td",cn,"",text);

    //add a clicker
    if (clicker) setClick(cell,clicker);

    RECORDS.recordRow.appendChild(cell);

    return cell;

  };

  /**
    closes the current list row and adds to the list
    */
  this.closeTableRecordRow = function()
  {
    RECORDS.recordTableBody.appendChild(RECORDS.recordRow);

		//update our scrollbar

    return RECORDS.recordRow;

  };

	/**
		*/
	this.openFilters = function(xmlFile,handler)
	{

		RECORDS.filterXML = xmlFile;
		RECORDS.filterHandler = handler;
		RECORDS.addFilter();

	};

	this.closeFilters = function()
	{
		RECORDS.filterXML = null;
		RECORDS.filterHandler = null;
		clearElement(RECORDS.filterContainer);
	};

	/**
		adds a new filter row to our record list
		*/
	this.addFilter = function(filterData)
	{

		//create our filter row and add to the container
		var filterBar = ce("div","recordListFilter");
		RECORDS.filterContainer.appendChild(filterBar);

    //add/remove images
    var addimg = createImg(THEME_PATH + "/images/icons/add-criteria.png");
    var remimg = createImg(THEME_PATH + "/images/icons/remove-criteria.png");

    setClick(addimg,"RECORDS.addFilter()");
    setClick(remimg,"RECORDS.removeFilter(event)");

		//container for our filter
		var cont = ce("div");
		filterBar.appendChild(cont);

		var f = new RECORD_FILTERS()

		//populate the filters with existing information
		if (filterData) f.filterData = filterData;

		f.load(cont,RECORDS.filterXML,RECORDS.filterHandler);

    //spacer
    filterBar.appendChild(ce("div","recordListFilterSpacer"));
		
		var imgdiv = ce("div","recordListFilterOptions");
    imgdiv.appendChild(addimg);
    imgdiv.appendChild(remimg);
		filterBar.appendChild(imgdiv);

		//reset our overflows
		RECORDS.setSizes();

		return filterBar;

	};

	/**
		removes selected filter row from the container
		*/
	this.removeFilter = function(e)
	{

    var ref = getEventSrc(e).parentNode.parentNode;
    ref.parentNode.removeChild(ref);

		//reset our overflows
		RECORDS.setSizes();

		if (RECORDS.filterHandler) RECORDS.filterHandler();

	};

	/**
		stores where our filters are currently set so we can 
		repopulate them later
		*/
	this.storeFilters = function(key)
	{

		//the name we'll store this at
		var sessionKey = "RECORDS_FILTER_" + key;

		var p = new PROTO();
		var data = p.traverse(RECORDS.filterContainer);

		sessionStorage[sessionKey] = JSON.encode(data);

	};

	/**
		repopulates our filter bars based on a prior session
		*/
	this.retrieveFilters = function(key,xmlFile,handler)
	{

		//setup our filter variables
		RECORDS.filterXML = xmlFile;
		RECORDS.filterHandler = handler;

		//the name we'll store this at
		var sessionKey = "RECORDS_FILTER_" + key;

		if (isData(sessionStorage[sessionKey]))
		{

			var data = JSON.decode(sessionStorage[sessionKey]);

			if (data.filters)
			{

				for (var i=0;i<data.filters.length;i++)
				{
					var filterData = new Array();
					filterData["filter"] = data.filters[i];
					filterData["match"] = data.matches[i];
					filterData["value"] = data.values[i];
	
					this.addFilter(filterData);
	
				}

			}
			else
			{
				handler();
			}

		}
		else
		{
			handler();
		}

	};

	this.hideDetail = function()
	{
		RECORDS.detailSeparator.style.display = "none";	
		RECORDS.detailContainer.style.display = "none";	
		RECORDS.setSizes();
	};

	this.showDetail = function()
	{
		RECORDS.detailSeparator.style.display = "";	
		RECORDS.detailContainer.style.display = "";	
		RECORDS.setSizes();
	};

}
		