
var PARENTS = new OBJECT_PARENTS();

function OBJECT_PARENTS()
{

	this.obj;			//for storing all data we retrieved from this object during the search

	/**
		hands off viewing to appropriate method
		*/
	this.load = function()
	{

		MODAL.open(640,480,_I18N_LOCATION);
		MODAL.addToolbarButtonRight(_I18N_SAVE,"PARENTS.save()");

		//get our info
		var p = new PROTO();
    p.add("command","docmgr_object_getinfo");
    p.add("object_id",PROPERTIES.obj.id);
    p.post(API_URL,"PARENTS.writeInfo");

	};

	/**
		stores our object data and loads the tree
		*/
	this.writeInfo = function(data)
	{

		clearSiteStatus();

		if (data.error) alert(data.error);
		else 
		{
			PARENTS.obj = data.record[0];
			PARENTS.loadTree();
		}

	};

	/**
		loads the actual tree
		*/
	this.loadTree = function()
	{

		//convert object parents to a string
		var valarr = new Array();

		if (PARENTS.obj.parents) valarr = PARENTS.obj.parents;

		//just some instructions
  	MODAL.add(ce("div","parentsInstructions","",_I18N_CHECK_COLLECTION_OF_OBJECT));

		//div for the tree
		var tcell = ce("div","parentsTree");
		MODAL.add(tcell);

		//create the form tree
		var opt = new Array();
		opt.container = tcell;
		opt.mode = "checkbox";
		opt.ceiling = "0";
		opt.ceilingname = ROOT_NAME;
		opt.curval = valarr;
		var t = new TREEFORM();
		t.load(opt);

	};

	this.save = function()
	{

		//check our form		
		var arr = MODAL.container.getElementsByTagName("input");
		var parr = new Array();

		for (var i=0;i<arr.length;i++) {
				if (arr[i].checked==true) parr.push(arr[i].value);
		}

		//stop if none are selected
		if (parr.length==0)
		{
				alert(_I18N_SELECT_COLLECTION_ERROR);
				return false;
		}

		updateSiteStatus(_I18N_SAVING);
 
		var p = new PROTO();
		p.add("command","docmgr_object_saveparent");
		p.add("object_id",PROPERTIES.obj.id);
		p.add("parent_id",parr);
		p.post(API_URL,"PARENTS.writeSave");

	};

	this.writeSave = function(data)
	{

		clearSiteStatus();

		if (data.error) alert(data.error);

	};

}


