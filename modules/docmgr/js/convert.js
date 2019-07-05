
var CONVERT = new DOCMGR_CONVERT();

function DOCMGR_CONVERT()
{

	this.batch;			//letting us know if we are converting more than one obj at a time
	this.obj;

	this.load = function(e,id)
	{
		e.cancelBubble = true;

		CONVERT.obj = BROWSE.getObject(id);

		//get our extensions
		updateSiteStatus(_I18N_PLEASEWAIT);
		protoReq("config/extensions.xml","CONVERT.writeExtensions");

	};

	this.writeExtensions = function(data)
	{

		clearSiteStatus();

		var ext = fileExtension(CONVERT.obj.name);
		var ot = getOOType(data,ext);

		//if we found a custom type for this extension, continue
		if (ot.length > 0 || CONVERT.obj.object_type=="document")
		{
			var toext = getOODest(data,ot);
			CONVERT.loadModal(toext);
		}
		else
		{
			alert(_I18N_UNABLE_CONVERT_FILE_TYPE);
		}

	};


	/****************************************************
		batch convert setup
	****************************************************/
	
	/***********************************************************
		FUNCTION:	batchConvertWin
		PURPOSE:	sets up our convert window for batch conversion
	***********************************************************/
	this.loadBatch = function()
	{
	
		CONVERT.batch = 1;
	
		//get references to all checked rows
		var arr = RECORDS.selected;
	
		//make sure something is checked
		if (arr.length==0)
		{
			alert(_I18N_SELECT_OBJECT_CONVERT_ERROR);
			return false;
		}
	
		var ret = CONVERT.checkTypes(arr);
	
		if (!ret)
		{
			alert(_I18N_OBJECT_TYPE_CONVERT_ERROR);
			return false;
		}
		else
		{

			//use our first selected object as our source
			CONVERT.obj = BROWSE.getObject(arr[0].getAttribute("record_id"));	
	
			updateSiteStatus(_I18N_PLEASEWAIT);
			protoReq("config/extensions.xml","CONVERT.writeBatchExtensions");
	
		}
	
	}
	
	this.writeBatchExtensions = function(data)
	{
	
		clearSiteStatus();
	
		//make sure all checked fiels are of the same openoffice type
		if (!CONVERT.checkExtensions(data))
		{
			alert(_I18N_OBJECT_TYPE_CONVERT_ERROR);
			return false;
		}
	
		//use the name of our first file to figure out what types
		//we can convert to
		var ext = fileExtension(CONVERT.obj.name);
		var ot = getOOType(data,ext);
	
		//if we found a custom type for this extension, continue
		if (ot.length > 0 || CONVERT.obj.object_type=="document")
		{
	
			var toext = getOODest(data,ot);
			CONVERT.loadModal(toext);
	
		}
		else
		{
			alert(_I18N_UNABLE_CONVERT_FILE_TYPE);
		}
	
	}
	
	this.loadModal = function(toext)
	{

		MODAL.open(400,200,_I18N_CONVERT_TO_FORMAT);
		MODAL.addToolbarButtonRight(_I18N_CONVERT_FILE,"CONVERT.runConvert()");

		//dropdown for picking destination format		
		var sel = createSelect("toOption");
		setChange(sel,"CONVERT.handleToOption()");
		
		for (var i=0;i<toext.length;i++)
		{
			sel[i] = new Option(toext[i][0] + " - " + toext[i][1],toext[i][0]);
		}

		MODAL.add(EFORM.template(_I18N_CONVERT_TO,sel));

		//dropdown for picking where the converted file will land		
		var sel = createSelect("returnOption");
		sel[0] = new Option(_I18N_MY_COMPUTER,"download");
		sel[1] = new Option(_I18N_DM_THIS_COLLECTION,"docmgr");
		sel[2] = new Option(_I18N_DM_OTHER_COLLECTION,"other");

		MODAL.add(EFORM.template(_I18N_SAVE_TO,sel));
	
		CONVERT.handleToOption();
	
	}
	
	this.handleToOption = function()
	{
	
		var ro = ge("returnOption");
	
		if (ge("toOption").value == "docmgr" || CONVERT.batch)
		{
	
			while (ro.options.length > 0) ro.options.remove(0);
	
			ro[0] = new Option(_I18N_DM_THIS_COLLECTION,"docmgr");
			ro[1] = new Option(_I18N_DM_OTHER_COLLECTION,"other");
	
		}
		else
		{
			ro[0] = new Option(_I18N_MY_COMPUTER,"download");
			ro[1] = new Option(_I18N_DM_THIS_COLLECTION,"docmgr");
			ro[2] = new Option(_I18N_DM_OTHER_COLLECTION,"other");
		}
	
	}
	
	/***********************************************************
		FUNCTION:	runConvertObject
		PURPOSE:	passed convert options to the API
	***********************************************************/
	this.runConvert = function()
	{
	
		var returnOpt = ge("returnOption").value;
	
		//if the files are supposed to go somewhere else, launch special processing
		if (returnOpt=="other")
		{
	
			//launch the destination window
			mbmode = "convert";
	
	   	//launch our selector to pick where to save the file
			openMiniB("open","","collection");
	
		}
		else
		{
	
			var p = new PROTO();
	
			//batch conversion options	
			if (CONVERT.batch)
			{
		
				p.add("command","docmgr_object_batchconvert");
				p.add("object_id",BROWSE.getSelected());
		
			}
			//single conversion options
			else
			{
		
				p.add("command","docmgr_object_convert");
				p.add("object_id",CONVERT.obj.id);
		
			}
		
			//converting to docmgr documents
			if (ge("toOption").value == "docmgr")
			{
				p.add("to","html");
				p.add("convert_type","document");
				p.add("return","docmgr");
			}
			//handle normal conversion
			else
			{
				p.add("return",returnOpt);
				p.add("to",ge("toOption").value);	
			}
	
			//download result to the browser		
			if (returnOpt=="download")
			{
				p.redirect(API_URL);
			}
			//store in current docmgr collection
			else
			{
				updateSiteStatus(_I18N_PLEASEWAIT);
				p.post(API_URL,"CONVERT.writeConvert");
			}
	
		}
	
	}
	
	this.writeConvert = function(data)
	{
	
		clearSiteStatus();
		MODAL.hide();
		BROWSE.refresh();

	}
	
	/***********************************************************
		FUNCTION:	convertObjProcess
		PURPOSE:	handler of callback from the alternate destination
							popup
	***********************************************************/
	function convertObjProcess(parent_path)
	{
	
		var returnOpt = ge("returnOption").value;
	
		var p = new PROTO();
		p.add("parent_path",parent_path);
	
		if (CONVERT.batch)
		{
		
			p.add("command","docmgr_object_batchconvert");
			p.add("object_id",BROWSE.getSelected());
		
		}
		else
		{
		
			p.add("command","docmgr_object_convert");
			p.add("object_id",CONVERT.obj.id);
		
		}
	
		p.add("return","docmgr");
		
		if (ge("toOption").value == "docmgr")
		{
			p.add("to","html");
			p.add("convert_type","document");
		}
		else
		{
		
			p.add("to",ge("toOption").value);	
		
		}
			
		updateSiteStatus(_I18N_PLEASEWAIT);
		p.post(API_URL,"CONVERT.writeConvert");
	
	}
	
	
	/***********************************************************
		FUNCTION:	getOOType
		PURPOSE:	get the openoffice type of the passed extension
	***********************************************************/
	function getOOType(data,ext)
	{
	
		//make docmgr documents html
		if (!ext && CONVERT.obj.object_type=="document") ext = "html";
	
		var ot = "";
	
		for (var i=0;i<data.object.length;i++)
		{
	
			if (ext==data.object[i].extension)
			{
				ot = data.object[i].openoffice;
				break;
			}
	
		}
	
		return ot;
	
	}
	
	/***********************************************************
		FUNCTION:	getOODest
		PURPOSE:	get all extensions this file can be
							converted to
	***********************************************************/
	function getOODest(data,ot)
	{
	
		var toext = new Array();
	
			//always put pdf
			var arr = new Array();
			arr.push("pdf");
			arr.push("Adobe PDF Document");
			toext.push(arr);
	
			//if not a docmgr document, add an option to convert to one
			if (!CONVERT.obj.object_type!="document")
			{
				var arr = new Array();
				arr.push("docmgr");
				arr.push("DocMGR Document");
				toext.push(arr);
			}
		
			//now find all matches of this custom type
			for (var i=0;i<data.object.length;i++)
			{
	
				//skip disabled ones
				if (!isData(data.object[i].openoffice_convert) || data.object[i].openoffice_convert!=1)
				{
					continue;
				}
	
				if (data.object[i].openoffice==ot)
				{
					var arr = new Array();
					arr.push(data.object[i].extension);
					arr.push(data.object[i].proper_name);
	
					toext.push(arr);
	
				}
	
			}
	
		return toext;
	
	}
	
	/***********************************************************
		FUNCTION:	checkConvertTypes
		PURPOSE:	checks to make sure all objs to convert are
							of the same docmgr object_type
	***********************************************************/
	this.checkTypes = function(arr)
	{
	
		var ret = true;
		var ot = "";
	
		//make sure they all have the same object type
		for (var i=0;i<arr.length;i++)
		{
	
			//initial setup
			if (!ot) 
			{
				ot = arr[i].getAttribute("object_type");
				continue;
			}
	
			if (ot!=arr[i].getAttribute("object_type"))
			{
				ret = false;
				break;
			}
	
		}
	
		return ret;
	
	}
	
	/***********************************************************
		FUNCTION:	checkConvertExtension
		PURPOSE:	checks to make sure all objs to convert are
							of the same openoffice type (like all writer
							or calc)
	***********************************************************/
	this.checkExtensions = function(data)
	{
	
		var ret = true;
	
		//loop through and make sure all files are of either openoffice writer or cal
		//we can't mix it though
		var arr = RECORDS.selected;
	
		var oo = "";
		var ext = "";
		var ootype = "";
	
		for (var i=0;i<arr.length;i++)
		{

			var id = arr[i].getAttribute("record_id");
			var obj = BROWSE.getObject(id);
	
			ext = fileExtension(obj.name);
	
			ootype = getOOType(data,ext);
	
			//first one, just use it
			if (!oo) 
			{
				oo = ootype;
			}
			else
			{
	
				//openoffice types don't match, conversion can't continue
				if (oo!=ootype)
				{
					ret = false;
					break;
				}
	
			}				
	
		}
	
		return ret;
	
	};
	
	
}
	
	