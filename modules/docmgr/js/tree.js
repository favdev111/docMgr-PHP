
/**********************************************************************************
	CLASS:	TREE
	PURPOSE:	Builds an explorer-type navigational tree for navigating through 
						collections
**********************************************************************************/

var curtree;

function TREE() {

	this.curval = "";									//array or comma delimited list of selected collections
	this.ceiling = "0";								//id of our browse ceiling (0 for root)
	this.ceilingname = "";						//name to call our browse ceiling
	this.ceilingchild = "0";					//force the browse ceiling to expand by seeting this to > 0
	this.container = "";							//html element we put the tree in
	this.writecont = "";							//the current container we are writing to
	this.reload = "";									//force a reload of the tree
	this.noexpand = "";								//don't expand root node by default

	/***************************************************************************
		FUNCTION:	load
		PURPOSE:	loads the tree using supplied parameters
		INPUTS:		opt -> associative keyed array.  Keys match descriptions above
	***************************************************************************/
	this.load = function(opt) {

		//set our globals
		if (opt.ceiling) this.ceiling = opt.ceiling;
		if (opt.ceilingname) this.ceilingname = opt.ceilingname;
		if (opt.ceilingpath) this.ceilingpath = opt.ceilingpath;
		if (opt.ceilingchild) this.ceilingchild = opt.ceilingchild;
		if (opt.noexpand) this.noexpand = opt.noexpand;
		if (opt.reload) this.reload = opt.reload;

		//set our container.  init writing to the root	
		this.container = opt.container;
		this.writecont = this.container;
		clearElement(this.container);

		//if passed current value, make sure it's an array
		if (opt.curval) {
			if (typeof(opt.curval)=="object") this.curval = opt.curval;
			else this.curval = opt.curval.split(",");
		}

		//if passed a ceiling name, setup the toplevel ceiling lin
		if (this.ceilingname) {
			this.createCeilingLink();
		}

		//default
		if (this.curval) this.curpath = this.curval;
		else if (this.ceiling) this.curpath = this.ceiling;

		//if noexpand isn't set, expand the root level by default
		if (!this.noexpand) 
		{

			//if passed a value, browse to it, otherwise just show the ceiling
			if (this.curval) this.browseObject(this.curval,1);
			else this.browseObject(this.ceiling,1);

		} 

	};

	/***************************************************************************
		FUNCTION:	createCeilingLink
		PURPOSE:	creates the root entry which everything else will be created under
	***************************************************************************/
	this.createCeilingLink = function() {

		//setup a dummy object to pass to create our link row
		var obj = new Array();
		obj.id = this.ceiling;
		obj.name = this.ceilingname;
		obj.path = this.ceilingpath;
		obj.child_count = this.ceilingchild;
		obj.setceiling = true;

		//create a method reference instead of a direct link to the funciton
		this.container.onclick = createMethodReference(this,"setCurTree");

		//create our link row
		var row = this.createLinkRow(obj);
		this.container.appendChild(row);

		//if reloading, setup our new work container and open the folder and image icons
		if (this.reload) {

			//find the container and show it
			this.writecont = row.getElementsByTagName("div")[0];
			this.writecont.style.display = "block";

			//setup the images
			var imgarr = row.getElementsByTagName("img");
			var img1 = imgarr[0];
			var img2 = imgarr[1];

			//if it's a folder, just make it open
			if (img1.getAttribute("src").indexOf("box.gif")!=-1) 
				img1.setAttribute("src",THEME_PATH + "/images/icons/dashbox.gif");
			else
				img1.setAttribute("src",THEME_PATH + "/images/open_folder.png");
	
			if (img2 && img2.getAttribute("src").indexOf("box.gif")==-1) {
				img2.setAttribute("src",THEME_PATH + "/images/open_folder.png");
			}
	
		}

	};

	/***************************************************************************
		FUNCTION:	setCurTree
		PURPOSE:	stores a reference to the tree we are working in.  Useful
							if we have multiple trees and they are operated on by something
							outside this class
	***************************************************************************/

	this.setCurTree = function() {

		//copy the reference to the tree we are currently working with (for outside functions to use)
		curtree = this;

	};


	/***************************************************************************
		FUNCTION:	writeBrowse
		PURPOSE:	writes the results of our collection browsing
	***************************************************************************/
	this.writeBrowse = function(data) {

		clearSiteStatus();

		clearElement(this.writecont);

		if (data.error) alert(data.error);
		else {

			//nothing found, don't show anything
			if (data.collection) { 

				//loop through our returns and keep an entry for them
				for (var i=0;i<data.collection.length;i++) {

					//get our entry depending on if we're in form mode or link mode
					var row = this.createLinkRow(data.collection[i]);
					this.writecont.appendChild(row);

				}

			}

		}

	};

	/***************************************************************************
		FUNCTION:	browseObject
		PURPOSE:	shows all collections under the passed one.  if init is passed
							it loads the entire tree to display currently set collections
	***************************************************************************/
	this.browseObject = function(id,init) {

		this.curpath = id;

		updateSiteStatus(_I18N_LOADING);

		//assemble our command
		var p = new PROTO();
		p.add("command","docmgr_query_browsecol");				//browse collections only
		p.add("ceiling",this.ceiling);							//the base of the displayed tree
		p.add("show_search","1");

    if (id)
    {
      if (id.isNumeric()) p.add("object_id",id);          //currently selected value  
      else p.add("path",id);
    }

		if (init) p.add("init","1");					//initialize the tree (load from scratch)
		p.post(API_URL,createMethodReference(this,"writeBrowse"));
	
	};

	/****************************************************
		FUNCTION: createLinkRow
		PURPOSE:	creates an entry for a collection with
							a link when the name is clicked on
	*****************************************************/
	this.createLinkRow = function(obj) 
	{

		var path = obj.path;

		var row = ce("div");
		row.setAttribute("objectid",obj.id);
		row.setAttribute("objectpath",path);
		row.setAttribute("objecttype",obj.object_type);
		row.setAttribute("objectname",obj.name);

		//setup some variables depending on whether or not this is an expanded row
		if (obj.children && obj.children.length > 0 && obj.children[0].collection) 
		{
			var imgsrc = THEME_PATH + "/images/icons/dashbox.gif";
			var foldersrc = THEME_PATH + "/images/open_folder.png";
			var subdisplay = "block";
		} 
		else 
		{
			var imgsrc = THEME_PATH + "/images/icons/plusbox.gif";

			if (obj.object_type=="search")
				var foldersrc = THEME_PATH + "/images/search_small.png";
			else 
				var foldersrc = THEME_PATH + "/images/closed_folder.png";

			var subdisplay = "none";

		}

		//if children, create a link for browsing
		if (isData(obj.child_count) && obj.child_count > 0) 
		{
	
			var img = ce("img");
			img.setAttribute("src",imgsrc);
			img.style.marginRight = "3px";
			row.appendChild(img);
			img.onclick = createMethodReference(this,"cycleObject");
	
		} 
		else 
		{
			row.style.paddingLeft = "13px";
		}
	
		var folderimg = ce("img");
		folderimg.setAttribute("src",foldersrc);
		folderimg.style.marginRight = "3px";
		folderimg.onclick = createMethodReference(this,"cycleObject");
		if (obj.object_type=="search") folderimg.style.marginBottom = "-1px";
		row.appendChild(folderimg);

		//browse path must exist as a function for this to all work
		var link = ce("a","","",obj.name);
		link.setAttribute("href","javascript:void(0)");

		//if we have a ceiling path, reset the ceiling to that path every time.  this helps when we have multiple trees with different ceilings
		if (obj.object_type=="search")
		{
			var clicker = "viewSaveSearch('" + obj.id + "',\\\"" + obj.name + "\\\")";
		}
		else
		{

			if (this.ceiling) 
			{
				var clicker = "browsePath(\\\"" + path + "\\\",\\\"" + this.ceilingpath + "\\\")";
			}
			else
			{
				var clicker = "browsePath(\\\"" + path + "\\\")";
			}

		}

		setClick(link,"setTimeout(\"" + clicker + "\",'10')");

		row.appendChild(link);
	
		//create a subdiv for putting children
		var subdiv = ce("div");
		subdiv.style.display = subdisplay;
		subdiv.style.paddingLeft = "13px";
	
		//if data below here, expand and add as well
		if (obj.children && obj.children.length > 0 && obj.children[0].collection) {
			subdiv.style.display = "block";
			for (var i=0;i<obj.children[0].collection.length;i++) {
				subdiv.appendChild(this.createLinkRow(obj.children[0].collection[i]));
			}
	
		}
	
		row.appendChild(subdiv);
	
		return row;
	
	};

	/***************************************************************************
		FUNCTION:	cycleObject
		PURPOSE:	shows or hides collections under a parent collection
	***************************************************************************/

	this.cycleObject = function(e) {

		var ref = getEventSrc(e).parentNode;
		var objpath = ref.getAttribute("objectpath");
		var objtype = ref.getAttribute("objecttype");
		var objname = ref.getAttribute("objectname");
		var objid = ref.getAttribute("objectid");

		//now reference the first sub div under it
		this.writecont = ref.getElementsByTagName("div")[0];

		var imgarr = ref.getElementsByTagName("img");
		var img1 = imgarr[0];
		var img2 = imgarr[1];

		if (!this.writecont.hasChildNodes()) {

			this.writecont.style.display = "block";

			//if it's a folder, just make it open
			if (img1.getAttribute("src").indexOf("box.gif")!=-1) 
				img1.setAttribute("src",THEME_PATH + "/images/icons/dashbox.gif");
			else
				img1.setAttribute("src",THEME_PATH + "/images/open_folder.png");
	
			if (img2 && img2.getAttribute("src").indexOf("box.gif")==-1) {
				img2.setAttribute("src",THEME_PATH + "/images/open_folder.png");
			}

			//load the child collections of this folder
			this.browseObject(objpath);
	
		} else {
	
			clearElement(this.writecont);
			img1.setAttribute("src",THEME_PATH + "/images/icons/plusbox.gif");
			this.writecont.style.display = "none";
	
			if (img2 && img2.getAttribute("src").indexOf("box.gif")==-1) {
				img2.setAttribute("src",THEME_PATH + "/images/closed_folder.png");
			}
	
		}

	};


	/***************************************************************************
		FUNCTION:	cycleObjectPath
		PURPOSE:	shows or hides collections under a parent collection.  this one
							can be referenced by outside functions to browse the tree
	***************************************************************************/
	this.cycleObjectPath = function(objpath,forceopen) 
	{
	
		var arr = this.container.getElementsByTagName("div");
		var run = 0;
	
		for (var i=0;i<arr.length;i++) 
		{

			//we found our element that made the call
			if (arr[i].getAttribute("objectpath")==objpath) 
			{

				run = 1;			//we did something
	
				//now reference the first sub div under it
				this.writecont = arr[i].getElementsByTagName("div")[0];
	
				var imgarr = arr[i].getElementsByTagName("img");
	
				if (!this.writecont.hasChildNodes() || forceopen) 
				{
	
					this.writecont.style.display = "block";
					var src = imgarr[0].getAttribute("src");
	
					//if it's a folder, just make it open
					if (src.indexOf("box.gif")!=-1) {				
						imgarr[0].setAttribute("src",THEME_PATH + "/images/icons/dashbox.gif");
					} else {
						imgarr[0].setAttribute("src",THEME_PATH + "/images/open_folder.png");
					}
	
					if (imgarr[1] && imgarr[1].getAttribute("src").indexOf("box.gif")==-1) {
						imgarr[1].setAttribute("src",THEME_PATH + "/images/open_folder.png");
					}
	
					//if it hasn't been loaded before, load it
					this.browseObject(objpath);
	
				} else {
	
					clearElement(this.writecont);
					imgarr[0].setAttribute("src",THEME_PATH + "/images/icons/plusbox.gif");
					this.writecont.style.display = "none";

					if (imgarr[1] && imgarr[1].getAttribute("src").indexOf("box.gif")==-1) {
						imgarr[1].setAttribute("src",THEME_PATH + "/images/closed_folder.png");
					}
	
				}
	
				break;
	
			}
	
		}

		//if nothing was done, reload the tree
		if (run==0) 
		{

			var opt = this;
			opt.curval = objpath;	
			opt.reload = 1;
			opt.noexpand = "";	
			this.load(opt);

		}
	
	};
		
}
	
	