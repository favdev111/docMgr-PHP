
var PERMISSIONS = new OBJECT_PERMISSIONS();

function OBJECT_PERMISSIONS()
{

	this.obj;			//for storing all data we retrieved from this object during the search
	this.searchTimer; 

	/**
		hands off viewing to appropriate method
		*/
	this.load = function()
	{

		this.obj = PROPERTIES.obj;

		MODAL.open(640,480,_I18N_PERMISSIONS);
		MODAL.addSearch("PERMISSIONS.ajaxSearch()",_I18N_SEARCH);

		if (this.obj.object_type=="collection")
		{
			MODAL.addToolbarButtonRight(_I18N_RESET_PERM_CHILDREN,"PERMISSIONS.resetChildren()");
		}

 		MODAL.openRecordHeader();		
		MODAL.addHeaderCell(_I18N_NAME,"permAccountName");
		MODAL.addHeaderCell(_I18N_TYPE,"permAccountType");
		MODAL.addHeaderCell(_I18N_ADMIN,"permAccountForm");
		MODAL.addHeaderCell(_I18N_EDIT,"permAccountForm");
		MODAL.addHeaderCell(_I18N_VIEW,"permAccountForm");
		MODAL.addHeaderCell(_I18N_CLEAR,"permAccountForm");
 		MODAL.closeRecordHeader();
		PERMISSIONS.search();

	};

  /** 
    handles typing for a search result
    */
  this.ajaxSearch = function()
  {

    updateSiteStatus(_I18N_PLEASEWAIT);

    clearTimeout(PERMISSIONS.searchTimer);
    PERMISSIONS.searchTimer = setTimeout("PERMISSIONS.search()","250");

  };

	this.search = function()
	{
	
	  updateSiteStatus(_I18N_LOADING);
	
	  //var pf = ge("permFilter").value;
	
	  var p = new PROTO();
	  p.add("command","docmgr_object_getpermissions");
	  p.add("object_id",PERMISSIONS.obj.id);
	  //p.add("perm_filter",pf);  
	  p.add("search_string",ge("siteModalSearch").value);
	  p.post(API_URL,"PERMISSIONS.writeSearch");
	
	};

	this.writeSearch = function(data)
	{

		clearSiteStatus();

		MODAL.openRecords();

		if (data.error) alert(data.error);
		else
		{

			for (var i=0;i<data.entry.length;i++)
			{

				var entry = data.entry[i];

			  //this makes it so you can only check one horizontally
			  var formname = "permData" + entry.type + entry.id;
	
	  		//populate them
	  		var aradio = createRadio(formname,"admin",entry.perm);
	  		var eradio = createRadio(formname,"edit",entry.perm); 
	  		var vradio = createRadio(formname,"view",entry.perm); 
	
				setClick(aradio,"PERMISSIONS.set(event)");
				setClick(eradio,"PERMISSIONS.set(event)");
				setClick(vradio,"PERMISSIONS.set(event)");

	  		var img = createImg(THEME_PATH + "/images/icons/delete.png","PERMISSIONS.set(event)");

				var row = MODAL.openRecordRow();
				row.setAttribute("record_id",entry.id);
				row.setAttribute("record_type",entry.type);

				MODAL.addRecordCell(entry.name,"permAccountName");

				if (entry.type=="group") MODAL.addRecordCell(_I18N_GROUP,"permAccountType");
				else MODAL.addRecordCell(_I18N_ACCOUNT,"permAccountType");

				MODAL.addRecordCell(aradio,"permAccountForm");
				MODAL.addRecordCell(eradio,"permAccountForm");
				MODAL.addRecordCell(vradio,"permAccountForm");
				MODAL.addRecordCell(img,"permAccountForm");
				MODAL.closeRecordRow();

			}

		}	

		MODAL.closeRecords();
	
	};	

	this.set = function(e)
	{

		var ref = getEventSrc(e);
		var row = ref.parentNode.parentNode;

		//if the source is an image, we are clearing this permission record for this entity
		if (ref.tagName.toLowerCase()=="img") 
		{

			var perm = "none";
			var arr = row.getElementsByTagName("input");

			//clear out all radio buttons for this row
			for (var i=0;i<arr.length;i++) arr[i].checked = false;

		}
		//otherwise set the new record
		else var perm = ref.value;

		updateSiteStatus(_I18N_SAVING);
		var p = new PROTO();
		p.add("command","docmgr_object_savepermissions");
		p.add("object_id",PERMISSIONS.obj.id);
		p.add("permission",perm);
		p.add("record_id",row.getAttribute("record_id"));
		p.add("record_type",row.getAttribute("record_type"));
		p.post(API_URL,"PERMISSIONS.writeSave");

	};

	this.writeSave = function(data)
	{
		clearSiteStatus();
		if (data.error) alert(data.error);
	};

	this.resetChildren = function()
	{

		if (confirm(_I18N_RESET_PERM_CHILDREN_WARNING))
		{

			updateSiteStatus(_I18N_PLEASEWAIT);
			var p = new PROTO();
			p.add("command","docmgr_object_resetchildrenpermissions");
			p.add("object_id",PERMISSIONS.obj.id);
			p.post(API_URL,"PERMISSIONS.writeSave");

		}

	};

}


