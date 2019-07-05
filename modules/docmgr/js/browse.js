
var BROWSE = new OBJECT_BROWSE();

function OBJECT_BROWSE()
{

	this.path = "/Users/" + USER_LOGIN;
	this.ceiling = "/Users/" + USER_LOGIN;
	this.id = "0";
  this.searchLimit = RESULTS_PER_PAGE;
  this.searchOffset = 0;
	this.sortField = "name";
	this.sortDir = "ASC";
	this.results;
	this.obj;
	this.mode;
	this.searchMode;
	this.view = "list";
	this.initted = false;

	this.load = function()
	{

	 	RECORDS.load(ge("container"),"listDetailPagerView","active");
		BROWSE.searchMode = "browse";

		//add the thumbnail class to change it up
		if (BROWSE.view=="thumbnail") RECORDS.listContainer.className += " thumbnail";

		RECORDS.hideDetail();

		PAGER.load(BROWSE);

		var loaded = false;

		//if passed an object id, show it in the properties window
		if (ge("objectId").value.length > 0)
		{
			BROWSE.objectDetail();
			loaded = true;
		}

		//if passed an object path, browse to it
		if (ge("objectPath").value.length > 0)
		{
			BROWSE.browsePath(ge("objectPath").value);
			loaded = true;
		}

		//we haven't done anything yet
		if (loaded==false)
		{
			RECORDS.retrieveFilters("search","config/filters/search.xml",BROWSE.init);
		}
	
	};

	this.init = function()
	{

    //if we have no filters left, and the search field is empty, go back to browsing
    if (RECORDS.filterContainer.childNodes.length==0 && ge("siteToolbarSearch").value.length==0)
    {
      if (BROWSE.initted==false)
      {
        BROWSE.initted = true;

				BROWSE.getDefaultPath();
        //BROWSE.browsePath();
      }   
      else
      {
        BROWSE.browseId();
      } 
    }   
		else
		{
			SEARCH.search();
		}

	};

	this.getDefaultPath = function()
	{
		updateSiteStatus(_I18N_PLEASEWAIT);
		var p = new PROTO();
		p.add("command","docmgr_bookmark_getdefaultpath");
		p.post(API_URL,"BROWSE.writeDefaultPath");		
	};

	this.writeDefaultPath = function(data)
	{

		if (data.error) alert(data.error);
		else if (!data.default_path) BROWSE.browsePath();
		else 
		{
			BROWSE.setCeiling(data.default_path);
			NAV.selectBookmark(data.default_path);
		}

	};

	/**
		refresh the current screen whether we are in search mode or browse mode
		*/
	this.refresh = function()
	{
		if (BROWSE.searchMode=="search") SEARCH.search();
		else if (BROWSE.searchMode=="savedsearch") SAVEDSEARCHES.search();
		else BROWSE.browseId();
	}

  /**
    loads the header for the record list
    */
  this.loadHeader = function()
  { 
    RECORDS.openHeaderRow();
    RECORDS.addHeaderCell(_I18N_NAME,"nameCell","BROWSE.changeSort('name')");

		if (BROWSE.searchMode=="search") RECORDS.addHeaderCell(_I18N_RANK,"rankCell","BROWSE.changeSort('rank')");

    RECORDS.addHeaderCell(_I18N_SIZE,"sizeCell","BROWSE.changeSort('size')");
    RECORDS.addHeaderCell(_I18N_EDITED,"editedCell","BROWSE.changeSort('edit')");
    RECORDS.addHeaderCell(_I18N_OPTIONS,"optionsCell");
    RECORDS.closeHeaderRow();
  };

	this.getObject = function(id)
	{

    //get our object info from the results
    for (var i=0;i<BROWSE.results.length;i++)
    {
      if (BROWSE.results[i].id==id)
      {
				return BROWSE.results[i];
      }
    }

		return false;

	};

	this.setCeiling = function(ceil)
	{
  	BROWSE.searchLimit = RESULTS_PER_PAGE;
  	BROWSE.searchOffset = 0;
		BROWSE.ceiling = ceil;
		BROWSE.path = ceil;
		BROWSE.browsePath();
	};


	this.changeSort = function(sort)
	{

		if (BROWSE.searchMode=="search")
		{

			if (SEARCH.sortField==sort)
			{
				if (SEARCH.sortDir=="ASC") SEARCH.sortDir = "DESC";
				else SEARCH.sortDir = "ASC";
			}
			//rank always defaults to DESC, everything else should default to DESC
			else if (sort=="rank")
			{
				SEARCH.sortDir = "DESC";
			}
			else if (SEARCH.sortField=="rank")
			{
				SEARCH.sortDir = "ASC";
			}

			SEARCH.sortField = sort;
			SEARCH.search();

		}
		else
		{

			if (BROWSE.sortField==sort)
			{
				if (BROWSE.sortDir=="ASC") BROWSE.sortDir = "DESC";
				else BROWSE.sortDir = "ASC";
			}

			BROWSE.sortField = sort;
			BROWSE.browseId();
		}

	};

	/**
		used only by the pager class during forward/backward motions
		*/
	this.search = function()
	{
		BROWSE.refresh();
	};

	this.browsePath = function(path)
	{

		if (path) BROWSE.path = path;

		BROWSE.searchMode = "browse";

		updateSiteStatus(_I18N_PLEASEWAIT);
		var p = new PROTO();
		p.add("command","docmgr_query_browse");
		p.add("path",BROWSE.path);
		p.add("search_limit",BROWSE.searchLimit);
		p.add("search_offset",BROWSE.searchOffset);
		p.add("sort_field",BROWSE.sortField);
		p.add("sort_dir",BROWSE.sortDir);
		p.post(API_URL,"BROWSE.writeBrowse");
		SEARCH.clear();

	};

	this.browseId = function(id)
	{

		if (id!=null) 
		{
			BROWSE.id = id;

			//hide our detail container if visible and we aren't doing a simple refresh
			if (RECORDS.detailContainer!="none") RECORDS.hideDetail();

		}

		BROWSE.searchMode = "browse";
	
		updateSiteStatus(_I18N_PLEASEWAIT);
		var p = new PROTO();
		p.add("command","docmgr_query_browse");
		p.add("object_id",BROWSE.id);
		p.add("search_limit",BROWSE.searchLimit);
		p.add("search_offset",BROWSE.searchOffset);
		p.add("sort_field",BROWSE.sortField);
		p.add("sort_dir",BROWSE.sortDir);
		p.post(API_URL,"BROWSE.writeBrowse");

		SEARCH.clear();

	};

	this.writeBrowse = function(data)
	{

    clearSiteStatus();

		RECORDS.hideDetail();
		BROWSE.loadHeader();

		if (BROWSE.searchMode=="browse")
		{
			//store the id of what we are currently browsing just in case we were spawned by browsePath
			BROWSE.id = data.current_object_id;   

			if (!BROWSE.id) BROWSE.path = "/";
			else BROWSE.path = data.current_object_path;

			BROWSE.breadcrumbs();

		}
		else
		{
			RECORDS.hideBreadcrumbs();
		}

    RECORDS.openRecords();
		
		if (data.error) 
		{
			alert(data.error);
		}
		else if (!data.record)
		{
			BROWSE.results = new Array();
			PAGER.update(0,0);
      RECORDS.openRecordRow();
      RECORDS.addRecordCell(_I18N_NORESULTS_FOUND,"one");
      RECORDS.closeRecordRow();
		}
		else
		{

			PAGER.update(data.total_count,data.current_count);

			BROWSE.results = data.record;

      for (var i=0;i<data.record.length;i++)
      {
				if (BROWSE.view=="thumbnail")
				{
					BROWSE.thumbRecord(data.record[i]);
				}
				else
				{
					BROWSE.listRecord(data.record[i]);
				}
      }

		}

		RECORDS.closeRecords();

	};

	this.listRecord = function(rec)
	{

		var row = RECORDS.openRecordRow("BROWSE.select(event,'" + rec.id + "')");
		row.setAttribute("record_id",rec.id);
		row.setAttribute("object_type",rec.object_type);
		row.setAttribute("object_name",rec.name);

		if (!rec.last_modified_view) rec.last_modified_view = "Unknown";

		//add the icon
		var img = createImg(THEME_PATH + "/images/object-icons/" + rec.icon);
		RECORDS.addRecordImage(img); 

		var nameCell = RECORDS.addRecordCell(rec.name,"nameCell");

		//show the rank if in search mode
		if (BROWSE.searchMode=="search")
		{
			RECORDS.addRecordCell(number_format(rec.rank,2,".") + "%","rankCell");
		}

		RECORDS.addRecordCell(size_format(rec.size),"sizeCell");
		RECORDS.addRecordCell(rec.last_modified_view,"editedCell");
		BROWSE.options(rec);

  	//handle descriptions
  	if (rec.summary)   
  	{
    	var summary = ce("div","browseSummary","",rec.summary);
    	nameCell.appendChild(summary);
  	}
   
  	//handle paths
  	if (rec.object_path && BROWSE.searchMode=="search")
  	{
    	nameCell.appendChild(BROWSE.showPath(rec.object_path));
  	}

		RECORDS.closeRecordRow();

	};

	this.thumbRecord = function(rec)
	{

		var ts = new Date().getTime();

		if (rec.object_type=="file" || rec.object_type=="document") 
		{
		  var url = SITE_URL + "app/showthumb.php?sessionId=" + SESSION_ID + "&objectId=" + rec.id + "&objDir=" + rec.object_directory + "&timestamp=" + ts;
		} 
		else if (rec.object_path=="/Users/" + USER_LOGIN + "/Trash") 
		{
		  var url = THEME_PATH + "/images/thumbnails/trash.png";
		} 
		else if (rec.object_type=="collection") 
		{
		  var url = THEME_PATH + "/images/thumbnails/folder.png";
		} 
		else if (rec.object_type=="search") 
		{
		  var url = THEME_PATH + "/images/thumbnails/search_folder.png";
		} 
		else if (rec.object_type=="url") 
		{   
		  var url = THEME_PATH + "/images/thumbnails/url.png";
		}

    var row = RECORDS.openRecordRow("BROWSE.select(event,'" + rec.id + "')");
    row.setAttribute("record_id",rec.id);
		row.setAttribute("object_type",rec.object_type);
		row.setAttribute("object_name",rec.name);

		//add the icon
		var img = createImg(url);
		img.className = "objectIcon";
		RECORDS.addRecordImage(img); 

    RECORDS.addRecordCell(rec.name,"nameCell");

    RECORDS.closeRecordRow();

	};

	this.select = function(e,id)
	{

		//proceed no further if there's a pulldown up
		if (PULLDOWN.visible==true) return false;

		var ref = getEventSrc(e);

		//proceed no further if we accidently clicked on the options cell
		if (ref.className=="recordListRowCell optionsCell") return false;

		if (BROWSE.mode!="edit")
		{

			var data;
	
			//get our info from the results
			for (var i=0;i<BROWSE.results.length;i++)
			{
				if (BROWSE.results[i].id==id)
				{
					data = BROWSE.results[i];
					break;
				}
			}		
	
			//now handle accordingly
			if (data.object_type=="collection")
			{

			  BROWSE.searchLimit = RESULTS_PER_PAGE;
  			BROWSE.searchOffset = 0;

				BROWSE.browseId(id);
			}
			else 
			{
				VIEW.load(data);
			}

		}


	};

	/***********************************
		object option icons
	***********************************/

	/**
		shortcut for creating a link with the appropriate icon and function
		*/
	this.optLink = function(imgsrc,title,clickval)
	{
		var img = createImg(THEME_PATH + "/images/browse-icons2/" + imgsrc,clickval);
  	img.setAttribute("title",title);
  	return ce("span","","",img);
	};

	/**
		displays all possible options for the object based on its type
		*/
	this.options = function(entry)
	{

		var div = RECORDS.addRecordCell("","optionsCell");
  	div.appendChild(BROWSE.optLink("properties.png",_I18N_EDIT_PROP,"PROPERTIES.load(event,'" + entry.id + "')"));

  	var ref = BROWSE.optLink("action.png",_I18N_ACTIONS);
		var menu = PULLDOWN.create(ref);		
		ref.appendChild(menu);
		div.appendChild(ref);
  	PULLDOWN.add(_I18N_MOVE,"ACTIONS.move('" + entry.id + "')");

		if (isData(entry.openoffice))
		{
	  	PULLDOWN.add(_I18N_CONVERT,"CONVERT.load(event,'" + entry.id + "')");
		}

  	PULLDOWN.add(_I18N_SUBSCRIPTIONS,"SUBSCRIPTIONS.load(event,'" + entry.id + "')");
  	PULLDOWN.add(_I18N_WORKFLOW,"ACTIONS.createWorkflow('" + entry.id + "')");

	  //convert to pdf link
	  var ext = fileExtension(entry.name);

		//edit/admin perms only here
	  if ((entry.bitmask_text == "admin" || entry.bitmask_text=="edit") && entry.locked == "f" && ext=="pdf") 
		{
			PULLDOWN.add(_I18N_ADV_EDIT,"PDFEDIT.edit(event,'" + entry.id + "')");
			PULLDOWN.add(_I18N_OPTIMIZE,"PDFEDIT.optimize('" + entry.id + "')");
		}

  	var ref = BROWSE.optLink("trash.png",_I18N_TRASH);
		var menu = PULLDOWN.create(ref);		
		ref.appendChild(menu);
		div.appendChild(ref);

		//sticking this here for now.  Only show for the trash folder
		if (entry.object_path=="/Users/" + USER_LOGIN + "/Trash")
		{
	  	PULLDOWN.add(_I18N_EMPTY_TRASH,"ACTIONS.emptyTrash()");
		}
		//otherwise show our delete options
		else
		{
	  	PULLDOWN.add(_I18N_TRASH_SELECTED,"ACTIONS.trash('" + entry.id + "')");
	  	PULLDOWN.add(_I18N_DELETE_PERMANENT,"ACTIONS.remove('" + entry.id + "')");
		}

  	if (entry.discussion) 
		{
			div.appendChild(BROWSE.optLink("discussion.png",_I18N_DISCUSSION,"DISCUSSION.loadCold(event,'" + entry.id + "')"));
		}

		if (entry.object_type=="collection") BROWSE.collectionOptions(entry,div);
		else if (entry.object_type=="file") BROWSE.fileOptions(entry,div);
		else if (entry.object_type=="document") BROWSE.documentOptions(entry,div);
		
	};

	/**
		displays collection options
		*/
	this.collectionOptions = function(entry,div)
	{
	  div.appendChild(BROWSE.optLink("bookmark.png",_I18N_BOOKMARK_COLLECTION,"BOOKMARKS.add(event,'" + entry.id + "')"));
	  div.appendChild(BROWSE.optLink("zip.png",_I18N_ZIP_COLLECTION ,"OBJECT.zip(event,'" + entry.id + "')"));
	};

	/**
		displays file options
		*/
	this.fileOptions = function(entry,div)
	{

		var ref = BROWSE.optLink("share.png",_I18N_SHARE);
		var menu = PULLDOWN.create(ref);		
		ref.appendChild(menu);
		div.appendChild(ref);

  	PULLDOWN.add(_I18N_SHARING_SETTINGS,"SHARE.load('" + entry.id + "')");
  	PULLDOWN.add(_I18N_EMAIL_ATTACH,"ACTIONS.email('" + entry.id + "')");
  	PULLDOWN.add(_I18N_EMAIL_TIME_LINK,"ACTIONS.viewLink('" + entry.id + "')");
  	PULLDOWN.add(_I18N_EMAIL_DM_LINK,"ACTIONS.propLink(event,'" + entry.id + "')");

	  //convert to pdf link
	  var ext = fileExtension(entry.name);

		//edit/admin perms only here
	  if ((entry.bitmask_text == "admin" || entry.bitmask_text=="edit")) 
		{

	    //pdf editing/reordering
	    if (entry.locked == "f") 
			{
		  	var ref = BROWSE.optLink("lock.png","Lock/Unlock For Editing");
				var menu = PULLDOWN.create(ref);		
				ref.appendChild(menu);
				div.appendChild(ref);
  			PULLDOWN.add("Lock For Editing","ACTIONS.lock(event,'" + entry.id + "')");
  			PULLDOWN.add("Upload New Revision","CHECKIN.load(event,'" + entry.id + "')");
	    } 
			else 
			{

	      if (BROWSE.isLockOwner(entry)) 
				{
			  	var ref = BROWSE.optLink("lock.png","Lock/Unlock For Editing");
					var menu = PULLDOWN.create(ref);		
					ref.appendChild(menu);
					div.appendChild(ref);
	  			PULLDOWN.add("Unlock","ACTIONS.unlock(event,'" + entry.id + "','" + entry.bitmask_text + "')");
	  			PULLDOWN.add("Upload New Revision","CHECKIN.load(event,'" + entry.id + "')");
	      }

	    }

	  }

	};

	/**
		displays DocMGR document options
		*/
	this.documentOptions = function(entry,div)
	{
	   div.appendChild(BROWSE.optLink("convert.png",_I18N_CONVERT_TO_FORMAT,"CONVERT.load(event,'" + entry.id + "')"));

		var ref = BROWSE.optLink("share.png",_I18N_SHARE);
		var menu = PULLDOWN.create(ref);		
		ref.appendChild(menu);
		div.appendChild(ref);

  	PULLDOWN.add(_I18N_SHARING_SETTINGS,"SHARE.load('" + entry.id + "')");
  	PULLDOWN.add(_I18N_EMAIL_TIME_LINK,"ACTIONS.viewLink('" + entry.id + "')");
  	PULLDOWN.add(_I18N_EMAIL_DM_LINK,"ACTIONS.propLink(event,'" + entry.id + "')");

		//edit/admin perms only here
	  if ((entry.bitmask_text == "admin" || entry.bitmask_text=="edit")) 
		{

	    //pdf editing/reordering
	    if (entry.locked == "f") 
			{
		  	var ref = BROWSE.optLink("lock.png","Lock/Unlock For Editing");
				var menu = PULLDOWN.create(ref);		
				ref.appendChild(menu);
				div.appendChild(ref);
  			PULLDOWN.add("Lock For Editing","ACTIONS.lock(event,'" + entry.id + "')");
	    } 
			else 
			{

	      if (BROWSE.isLockOwner(entry)) 
				{
			  	var ref = BROWSE.optLink("lock.png","Lock/Unlock For Editing");
					var menu = PULLDOWN.create(ref);		
					ref.appendChild(menu);
					div.appendChild(ref);
	  			PULLDOWN.add("Unlock","ACTIONS.unlock(event,'" + entry.id + "','" + entry.bitmask_text + "')");
	      }

	    }

	  }

	};

	/**
		determines if the passed locked object is owned by the current user
		*/
	this.isLockOwner = function(obj)
	{

		//admins can always unlock

	  var ret = false;
	
	  for (var i=0;i<obj.lock_owner.length;i++)
	  {
	    if (obj.lock_owner[i]==USER_ID)
	    {
	      ret = true;
	      break;
	    }
	  }
	
	  return ret;
	
	};

  this.cycleMode = function()
  {

    if (BROWSE.mode=="edit")
    {
      BROWSE.mode = "view";
      RECORDS.setRowMode("select");
      editBtn.innerHTML = _I18N_EDIT;
  		trashBtn.style.display = "none";
  		actionBtn.style.display = "none";
  		shareBtn.style.display = "none";
    }
    else
    {
      BROWSE.mode = "edit";
      RECORDS.setRowMode("multiselect");
      editBtn.innerHTML = _I18N_CANCEL;
  		trashBtn.style.display = "";
  		actionBtn.style.display = "";
  		shareBtn.style.display = "";
    }

		if (BROWSE.view=="thumbnail") RECORDS.listContainer.className += " thumbnail";

  };

	this.getSelected = function()
	{

    var ids = new Array();
   
    for (var i=0;i<RECORDS.selected.length;i++)
    {
       ids.push(RECORDS.selected[i].getAttribute("record_id"));
    }

    return ids;
   
  };

	this.switchView = function(view)
	{
		BROWSE.view = view;
		BROWSE.load();
	};


	this.thumbnail = function(entry)
	{

		var cont = ce("div","browseThumb");
		var ts = new Date().getTime();

		if (entry.object_type=="file" || entry.object_type=="document") 
		{
		  var url = SITE_URL + "app/showthumb.php?sessionId=" + SESSION_ID + "&objectId=" + entry.id + "&objDir=" + 
			entry.object_directory + "&timestamp=" + ts;
		} 
		else if (entry.object_path=="/Users/" + USER_LOGIN + "/Trash") 
		{
		  var url = THEME_PATH + "/images/thumbnails/trash.png";
		} 
		else if (entry.object_type=="collection") 
		{
		  var url = THEME_PATH + "/images/thumbnails/folder.png";
		} 
		else if (entry.object_type=="search") 
		{
		  var url = THEME_PATH + "/images/thumbnails/search_folder.png";
		} 
		else if (entry.object_type=="url") 
		{   
		  var url = THEME_PATH + "/images/thumbnails/url.png";
		}

		var thumbcont = ce("div");

		//set our object attributes on our row for access later
		thumbcont.setAttribute("record_id",entry.id);

		//setup the thumbnail image
		var img = ce("img");
		img.setAttribute("src",url);
		thumbcont.appendChild(img); 

		var n = ce("div","","",entry.name);
		thumbcont.appendChild(n);

		//store some object info on the checkbox
		var cb = createCheckbox("objectId[]",entry.id);
		cb.setAttribute("object_path",path);
		cb.setAttribute("object_type",entry.object_type);
		cb.setAttribute("object_name",entry.name);
		cb.setAttribute("object_perm",entry.bitmask_text);
		cb.setAttribute("object_share",entry.share);

		if (isData(entry.open_with)) cb.setAttribute("open_with",entry.open_with);
		if (isData(entry.extension)) cb.setAttribute("extension",entry.extension);

		//wrap in a div container so our getChecked function works with references
		thumbcont.appendChild(ce("div","","",cb));

		//handle the person clicking on the thumbnail
		setClick(thumbcont,"handleResultClick(event)");

		cont.appendChild(thumbcont);

		//run if available
		var func = eval(entry.object_type + "Options");
		if (entry.bitmask_text=="admin") {
		  if (window.func) {
		    var opts = func(entry);
		    cont.appendChild(opts);
		  }
		}  
		   
		return cont;

	};

	this.breadcrumbs = function()
	{

		//do nothing if searching
		if (BROWSE.searchMode!="browse") return false;

		RECORDS.showBreadcrumbs();

		var cont = ce("div","breadcrumbs");

		var arr = BROWSE.path.split("/");

  	if (BROWSE.path==BROWSE.ceiling)
  	{

    	//if in the BROWSE.ceiling, create a link.  Otherwise just show text 
    	if (BROWSE.ceiling=="/") var txt = ROOT_NAME;
    	else var txt = arr[arr.length-1];

    	var link = ce("a","","",txt);
    	setClick(link,"BROWSE.browsePath(\"" + BROWSE.ceiling + "\")");
    	cont.appendChild(link);

  	} 
		else
  	{

			var showpath;

	    for (var i=0;i<arr.length;i++)
	    {
	
	      if (!arr[i] && i==0)
	      {
	        showpath = "/";                     //starting at toplevel
	        arr[i] = ROOT_NAME;
	      }
	      else if (showpath=="/") showpath += arr[i];       //previous was toplevel, just add directory name
	      else showpath += "/" + arr[i];                    //add directory marker and name
	
				//don't show anything before our BROWSE.ceiling
	      if (BROWSE.ceiling!="/" && showpath.indexOf(BROWSE.ceiling)==-1) continue;
	
	      //setup the link
	      var link = ce("a","","",arr[i]);
	      setClick(link,"BROWSE.browsePath(\"" + showpath + "\")");
	      cont.appendChild(link);
	
	      /******************************
	        add image delimiter
	      ******************************/
	      //add an arrow if we're not at the last one
	      if (i!=(arr.length-1) && arr[arr.length-1]) 
	      {
	
	        var img = createImg(THEME_PATH + "/images/icons/navarrow.gif");
	        cont.appendChild(img);
	
	      }
	
	    }

	  }

		clearElement(RECORDS.breadcrumbs);
		RECORDS.breadcrumbs.appendChild(cont);

	};	   

	this.objectDetail = function()
	{
		updateSiteStatus(_I18N_PLEASEWAIT);
		var p = new PROTO();
		p.add("command","docmgr_object_getinfo");
		p.add("object_id",ge("objectId").value);
		p.post(API_URL,"BROWSE.writeObjectDetail");
	};

	this.writeObjectDetail = function(data)
	{
		clearSiteStatus();

		if (data.error) alert(data.error);
		{

			//drop the name off the path
			var arr = data.current_object_path.split("/");
			arr.pop();

			var path = arr.join("/");
			BROWSE.browsePath(path);
			PROPERTIES.loadFromData(data.record[0]);

		}

	};

	this.showPath = function(op)
	{
 
	  var dp = op;
	
	  var fullpath = "";
	  var objpath = ""; 
	
	  //show the path to the object
	  var parr = op.split("/");
	
	  //remove the object itself
	  parr.pop();
	
	  var cell = ce("div","searchObjectPath");
	
	  for (var i=1;i<parr.length;i++)
	  {
	   
	    fullpath += "/" + parr[i];
	
	    //don't go any further, we haven't reached our display ceiling yet
	    if (fullpath.length < BROWSE.ceiling.length) continue;
	
	    var link = ce("a","","",parr[i]);
			setClick(link,"BROWSE.browseParentPath(event)");
	    link.setAttribute("href","javascript:void(0)");
	    link.setAttribute("object_path",fullpath);
	    
	    cell.appendChild(link);
	    if (i!=(parr.length-1)) cell.appendChild(ctnode(" -> "));
	
	  }
	   
	  return cell;
	
	};		

	this.browseParentPath = function(e)
	{

		e.cancelBubble = true;
		var ref = getEventSrc(e);

		var path = ref.getAttribute("object_path");

		//reset the ceiling since we have no idea where we were coming from
		if (path.indexOf(BROWSE.ceiling)==-1) BROWSE.ceiling = "/";

		BROWSE.browsePath(path);

	};

}
	
	