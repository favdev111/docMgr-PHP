
var IMPORT = new DOCMGR_IMPORT();

function DOCMGR_IMPORT()
{

	this.mode = "shared";
	this.path;
	this.importpath;
	this.toolbar;

	this.load = function()
	{

		IMPORT.path = "";

		RECORDS.load(ge("container"),"listView","active");
		RECORDS.showBreadcrumbs();

		IMPORT.loadNavbar();
		IMPORT.loadToolbar();

		IMPORT.searchHeader();
		IMPORT.search();

	};

	this.loadNavbar = function()
	{
		SIDEBAR.open();
		SIDEBAR.addGroup(_I18N_BROWSE);
	  SIDEBAR.add(_I18N_SHARED_DIR,"IMPORT.setMode('shared')");
		SIDEBAR.add(_I18N_USER_DIR,"IMPORT.setMode('user')");
		SIDEBAR.close();

		SIDEBAR.showCurrent('0');

	};

	this.loadToolbar = function()
	{
		TOOLBAR.open();
		TOOLBAR.addGroup();
		TOOLBAR.add(_I18N_IMPORT_SELECTED,"IMPORT.save()");
		TOOLBAR.add(_I18N_MERGE_FILES,"IMPORT.merge()");
		TOOLBAR.close();

	}
	
	this.setMode = function(mode)
	{
		IMPORT.mode = mode;
		IMPORT.search();
	}

	this.search = function(data)
	{

		//one of our utilities returned an error
		if (data && data.error) 
		{
			alert(data.error);	
			return false;
		}

		IMPORT.breadcrumbs();

		updateSiteStatus(_I18N_PLEASEWAIT);
	
		var p = new PROTO();
		p.add("command","docmgr_import_browse");
		p.add("mode",IMPORT.mode);
	
		if (isData(IMPORT.path.length)) p.add("browse_path",IMPORT.path.substr(1));
	
		p.post(API_URL,"IMPORT.writeSearch");

	};

	this.searchHeader = function()
	{

    //add the header for search results
    RECORDS.openHeaderRow();   
    RECORDS.addHeaderCell(createNbsp(),"imageCell");
    RECORDS.addHeaderCell(_I18N_NAME,"nameCell");
    RECORDS.addHeaderCell(_I18N_SIZE,"sizeCell");
    RECORDS.addHeaderCell(_I18N_OPTIONS,"optionsCell");
    RECORDS.closeHeaderRow();

	};

	this.backRow = function()
	{

 		var row = RECORDS.openRecordRow("IMPORT.back()");

		RECORDS.addRecordCell(img,"importImage")
    RECORDS.addRecordCell(_I18N_BACK,"importName");

    RECORDS.closeRecordRow();

	};

	this.back = function()
	{

	};

	this.writeSearch = function(data)
	{

    clearSiteStatus();

    RECORDS.openRecords();

		//create our back row

    if (!data.record)
    {
				RECORDS.setRowMode("active");
        RECORDS.openRecordRow();
    		RECORDS.addRecordCell(createNbsp(),"imageCell");
        RECORDS.addRecordCell(_I18N_NORESULTS_FOUND,"nameCell");
        RECORDS.closeRecordRow();
    }
    else
    {   

			RECORDS.setRowMode("multiselect");

      for (var i=0;i<data.record.length;i++)
      {

        var file = data.record[i];
				var clicker;

				var img = createImg(file.icon);
	
				//allow browsing if a collection
				if (file.type=="collection")
				{
					newpath = IMPORT.path + "/" + file.name;
					clicker = "IMPORT.browse(event,\"" + newpath + "\")";
				}

        var row = RECORDS.openRecordRow(clicker);
				row.setAttribute("filename",file.name);

				RECORDS.addRecordCell(img,"imageCell");
        RECORDS.addRecordCell(file.name,"nameCell");
	      RECORDS.addRecordCell(file.size,"sizeCell");
	
				var optdiv = RECORDS.addRecordCell("","optionsCell");
				optdiv.appendChild(IMPORT.editAction("IMPORT.preview(event,\"" + file.name + "\")",_I18N_PREVIEW,"preview.png"));
				optdiv.appendChild(IMPORT.editAction("IMPORT.rename(event)",_I18N_RENAME,"rename.png"));
				optdiv.appendChild(IMPORT.editAction("IMPORT.remove(event)",_I18N_DELETE,"delete.png"));

        RECORDS.closeRecordRow();

      }

    }

    RECORDS.closeRecords();

	};

	this.browse = function(e,path)
	{

		var ref = getEventSrc(e);

		if (ref.tagName.toLowerCase()!="img") 
		{
			IMPORT.path = path;
			IMPORT.search();
		}

	};
	
	this.save = function()
	{
	
		var files = IMPORT.getChecked();
	
		if (files.length==0)
		{
			alert(_I18N_ERROR_SELECT_IMPORT);
			return false;
		}
	
		MINIB.open("open","IMPORT.mbSelect","","collection");
	
	};
	
	this.mbSelect = function(arr)
	{
		var res = arr[0];
		IMPORT.importPath = res.path;
		IMPORT.runSave();
	};
	
	this.runSave = function()
	{
	
	  var del = false;
	
	  if (confirm(_I18N_CONFIRM_REMOVE_IMPORT))
	  {
	    del = true;
	  }
	
		updateSiteStatus(_I18N_PLEASEWAIT);
	
		var files = IMPORT.getChecked();
	
		if (files.length==0)
		{
			alert(_I18N_ERROR_SELECT_IMPORT);
			return false;
		}
	
	  //this will happen n several stages.  First, we send the object info to the server, then when send the file itself
	  //setup the xml
	  var p = new PROTO();
	  p.add("command","docmgr_import_run");
	  p.add("parent_path",IMPORT.importPath); 
		p.add("file",files);
		p.add("mode",IMPORT.mode);
	
		if (del==true) p.add("delete","1");
		if (IMPORT.path.length > 0) p.add("browse_path",IMPORT.path.substr(1));
	
		p.post(API_URL,"IMPORT.writeSave");
	
	};
	
	this.writeSave = function(data)
	{
	
		clearSiteStatus();
		IMPORT.search();
	
	};
	
	this.preview = function(e,file)
	{

		e.cancelBubble = true;

		var ref = getEventSrc(e);

		updateSiteStatus(_I18N_PLEASEWAIT);
	
	  //this will happen n several stages.  First, we send the object info to the server, then when send the file itself
	  //setup the xml
	  var p = new PROTO();
	  p.add("command","docmgr_import_preview");
		p.add("file_path",file);
		p.add("mode",IMPORT.mode);
		 
	
		if (IMPORT.path.length > 0) p.add("browse_path",IMPORT.path.substr(1));
	
		p.post(API_URL,"IMPORT.writePreview");
	
	
	};
	
	this.writePreview = function(data)
	{
	
		clearSiteStatus();

		if (data.error) alert(data.error);
		else
		{
			var h = getWinHeight()-50;
			MODAL.open(510,h,_I18N_PREVIEW);

			var img = ce("img","largePreviewImg");
			img.setAttribute("src",data.preview);
			img.setAttribute("align","center");
			MODAL.container.appendChild(img);
		}
	
	};
	
	
	this.editAction = function(action,title,icon) 
	{

		var img = createImg(THEME_PATH + "/images/icons/" + icon);
		setClick(img,action);
		img.setAttribute("title",title);
	
		return img;
	
	}

	this.merge = function()
	{
	
		var files = IMPORT.getChecked();
	
		if (files.length==0) 
		{
			alert(_I18N_NOMERGEFILE_ERROR);
			return false;
		}
	
		if (files.length==1) 
		{
			alert(_I18N_ONEMERGEFILE_ERROR);
			return false;
		}
	
		for (var i=0;i<files.length;i++)
		{
	
				//make sure it's a pdf that we are trying to merge
				if (fileExtension(files[i])!="pdf") 
				{
					alert(_I18N_MERGE_TYPE_ERROR);
					return false;
				}
	
		}
	
		updateSiteStatus(_I18N_PLEASEWAIT);
	
		var p = new PROTO();
		p.add("command","docmgr_import_merge");
		p.add("file",files);
		p.add("mode",IMPORT.mode);
	
		if (IMPORT.path.length > 0) p.add("browse_path",IMPORT.path.substr(1));
	
		p.post(API_URL,"IMPORT.search");
	
	}
	
	this.remove = function(e)
	{

		e.cancelBubble = true;
	
		if (confirm(_I18N_FILE_REMOVE_CONFIRM)) 
		{
	
			var ref = getEventSrc(e).parentNode.parentNode;
			var name = ref.getAttribute("filename");
	
	  	var p = new PROTO();
	  	p.add("command","docmgr_import_delete");
			p.add("file",name);
			p.add("mode",IMPORT.mode);
	
			if (IMPORT.path.length > 0) p.add("browse_path",IMPORT.path.substr(1));
	
			p.post(API_URL,"IMPORT.search");
	
		}
	
	}
	
	this.rename = function(e)
	{
	
		e.cancelBubble = true;

		var ref = getEventSrc(e).parentNode.parentNode;
		var name = ref.getAttribute("filename");
	
		var newname = prompt(_I18N_NEW_FILENAME_PROMPT,name);
		if (newname) 
		{
	
	  	var p = new PROTO();
	  	p.add("command","docmgr_import_rename");
			p.add("file",name);
			p.add("name",newname);
			p.add("mode",IMPORT.mode);

			if (IMPORT.path.length > 0) p.add("browse_path",IMPORT.path.substr(1));
	
			p.post(API_URL,"IMPORT.search");
	
		}
	
	}
	
	
	this.breadcrumbs = function()
	{
	 
	  clearElement(RECORDS.breadcrumbs);
	
	  var arr = IMPORT.path.split("/");

		var cont = ce("div","breadcrumbs");
	
	  var showpath = "";
	      
	  for (var i=0;i<arr.length;i++)
	  {
	
	    //if in the ceiling, create a link.  Otherwise just show text
	
	    if (!arr[i] && i==0)
	    {
	      showpath = "";                     //starting at toplevel
	      arr[i] = _I18N_ROOT;
	    }
	    else if (showpath=="/") showpath += arr[i];       //previous was toplevel, just add directory name
	    else showpath += "/" + arr[i];                    //add directory marker and name
	
	    //setup the link
	    var link = ce("a","","",arr[i]);
			setClick(link,"IMPORT.browse(event,\"" + showpath + "\")");
	
	    cont.appendChild(link);
	
	    /******************************
	      add image delimiter
	    ******************************/
	    //add an arrow if we're not at the last one
	    if (i!=(arr.length-1))
	    {
	      var img = createImg(THEME_PATH + "/images/icons/navarrow.gif");
	      cont.appendChild(img);
	    }
	
	  }
	
		RECORDS.breadcrumbs.appendChild(cont);
	
	};
	      
	this.getChecked = function()
	{
	
		var arr = RECORDS.selected;
		var files = new Array();
	
		for (var i=0;i<arr.length;i++)
		{
			files.push(arr[i].getAttribute("filename"));
		}
	
		return files;
	
	}
	
}

