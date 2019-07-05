/**********************************************************************************
  CLASS:  TREEFORM
  PURPOSE:  Builds an explorer-type navigational tree input form for navigating through
            collections.  the form can be checkbox or radio
**********************************************************************************/

var curtree;

function TREEFORM() 
{

	this.mode="checkbox";						//the type of form to display
	this.curval = "";								//currently selected collections (comma delimited string or array)
	this.ceiling = "0";							//id of browse ceiling
	this.ceilingname = "";					//name of browse ceiling
	this.ceilingchild = "0";				//set to force browsing of a root collection
	this.container = "";						//container we put the tree in
	this.writecont = "";						//current container to write collections to
	this.formname = "parentId[]";		//html form name 

  /***************************************************************************
    FUNCTION: load
    PURPOSE:  loads the tree using supplied parameters
    INPUTS:   opt -> associative keyed array.  Keys match descriptions above
  ***************************************************************************/
	this.load = function(opt) {

		if (opt.mode) this.mode = opt.mode;
		if (opt.ceiling) this.ceiling = opt.ceiling;
		if (opt.ceilingname) this.ceilingname = opt.ceilingname;
		if (opt.ceilingid) this.ceilingid = opt.ceilingid;
		if (opt.ceilingchild) this.ceilingchild = opt.ceilingchild;
		if (opt.formname) this.formname = opt.formname;
	
		this.container = opt.container;
		this.writecont = this.container;
		clearElement(this.container);

		//if passed current value, make sure it's an array
		if (opt.curval) 
		{
			if (typeof(opt.curval)=="object") this.curval = opt.curval;
			else this.curval = opt.curval.split(",");
		}

		//if passed a ceiling name, setup the toplevel ceiling lin
		if (this.ceilingname) {
			this.createCeilingForm();
		}

		//if passed a value, browse to it, otherwise just show the ceiling
		if (this.curval) this.browseObject(this.curval,1);
		else this.browseObject(this.ceiling,1);

	};

  /***************************************************************************
    FUNCTION: createCeilingForm
    PURPOSE:  creates a form entry for the root
  ***************************************************************************/

	this.createCeilingForm = function() {

		//setup an object to pass
		var obj = new Array();
		obj.id = this.ceiling;
		obj.name = this.ceilingname;
		obj.child_count = this.ceilingchild;

		this.container.onclick = createMethodReference(this,"setCurTree");

		var row = this.createFormRow(obj);
		this.container.appendChild(row);
		this.writecont = row.getElementsByTagName("div")[0];
		this.writecont.style.display = "block";

	};

  /***************************************************************************
    FUNCTION: setCurTree
    PURPOSE:  stores the class instance of the tree we are currently using.  
							allows outside functions to access it
  ***************************************************************************/
	this.setCurTree = function() {

		//copy the reference to the tree we are currently working with (for outside functions to use)
		curtree = this;

	};


  /***************************************************************************
    FUNCTION: writeBrowse
    PURPOSE:  handles collection browse results
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
					var row = this.createFormRow(data.collection[i]);
					this.writecont.appendChild(row);

				}

			}

		}

	};

  /***************************************************************************
    FUNCTION: browseObject
    PURPOSE:  browses all collections below the selected one.  If init is set,
							shows all collections including selected ones
  ***************************************************************************/

	this.browseObject = function(id,init) {

		updateSiteStatus(_I18N_LOADING);

    //assemble our command
    var p = new PROTO();
    p.add("command","docmgr_query_browsecol");       //browse collections only        
    p.add("ceiling",this.ceiling);              //the base of the displayed tree       
		p.add("object_id",id);
    if (init) p.add("init","1");          //initialize the tree (load from scratch)      
    p.post(API_URL,createMethodReference(this,"writeBrowse"));  

	};

	/****************************************************
		FUNCTION: createFormRow
		PURPOSE:	creates an entry for a collection with
							a link when the name is clicked on
	*****************************************************/
	this.createFormRow = function(obj) {
	
		var row = ce("div","parentsFormRow");
		row.setAttribute("objectid",obj.id);
		row.setAttribute("objectpath",obj.path);
	
		//setup some variables depending on whether or not this is an expanded row
		if (obj.collection && obj.collection.length > 0)
		{
			var imgsrc = THEME_PATH + "/images/icons/dashbox.gif";
			var subdisplay = "block";
		} else 
		{
			var imgsrc = THEME_PATH + "/images/icons/plusbox.gif";
			var subdisplay = "none";
		}

		//if children, create a link for browsing
		if (isData(obj.child_count) && obj.child_count > 0) 
		{
	
			var img = ce("img");
			img.setAttribute("src",imgsrc);
			img.style.marginRight = "3px";
			setClass(img,"parentRowImg");
			row.appendChild(img);
			img.onclick = createMethodReference(this,"cycleObject");
	
		} else {
			row.style.paddingLeft = "13px";
		}

		//create the form
		if (this.mode=="radio") var cb = createRadio(this.formname,obj.id);
		else var cb = createCheckbox(this.formname,obj.id);

		row.appendChild(cb);	
		row.appendChild(ctnode(obj.name));
	
		//is it checked
		if (this.curval) {
			var key = arraySearch(obj.id,this.curval);
			if (key!=-1) cb.checked = true;
		}		

		//create a subdiv for putting children
		var subdiv = ce("div");
		subdiv.style.display = subdisplay;
		subdiv.style.paddingLeft = "13px";
	
		//if data below here, expand and add as well
		if (obj.collection && obj.collection.length > 0)
		{

			subdiv.style.display = "block";
			for (var i=0;i<obj.collection.length;i++) 
			{
				subdiv.appendChild(this.createFormRow(obj.collection[i]));
			}
	
		}
	
		row.appendChild(subdiv);
	
		return row;
	
	};

  /***************************************************************************
    FUNCTION: cycleObject
    PURPOSE:  show or hide collections under the current one
  ***************************************************************************/
	this.cycleObject = function(e) {

		var ref = getEventSrc(e).parentNode;
		var objid = ref.getAttribute("objectid");

		//now reference the first sub div under it
		this.writecont = ref.getElementsByTagName("div")[0];

		var imgarr = ref.getElementsByTagName("img");
		var img1 = imgarr[0];

		if (!this.writecont.hasChildNodes()) {

			this.writecont.style.display = "block";

			//if it's a folder, just make it open
			if (img1.getAttribute("src").indexOf("box.gif")!=-1) 
				img1.setAttribute("src",THEME_PATH + "/images/icons/dashbox.gif");
			else
				img1.setAttribute("src",THEME_PATH + "/images/open_folder.png");
	
			//load the child collections of this folder
			this.browseObject(objid);
	
		} else {
	
			clearElement(this.writecont);
			img1.setAttribute("src",THEME_PATH + "/images/icons/plusbox.gif");
			this.writecont.style.display = "none";
		
		}
	
	};

}
	
	