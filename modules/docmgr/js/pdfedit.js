
var PDFEDIT = new DOCMGR_PDFEDIT();

function DOCMGR_PDFEDIT()
{

	this.orderchanged = 0;
	this.id;
	this.obj;
	
	this.merge = function()
	{
	
		var arr = RECORDS.selected;
		var pdfarr = new Array();
		
		for (var i=0;i<arr.length;i++) 
		{
	
			var objId = arr[i].getAttribute("record_id");
			var obj = BROWSE.getObject(objId);

			if (fileExtension(obj.name)!="pdf")
			{
				alert("You can only merge pdf files");
				return false;
			}

			pdfarr.push(objId);
	
		}
		
		if (pdfarr.length==0) {
			alert(_I18N_NOMERGEFILE_ERROR);
			return false;
		}
		if (pdfarr.length==1) {
			alert(_I18N_ONEMERGEFILE_ERROR);
			return false;
		}
	
		//setup the xml
		updateSiteStatus(_I18N_PLEASEWAIT);
		var p = new PROTO();
		p.add("command","docmgr_pdf_merge");
	
		for (var i=0;i<pdfarr.length;i++) p.add("object_id",pdfarr[i]);

		p.post(API_URL,"PDFEDIT.writeMerge");
	
	};
	
	this.writeMerge = function(data)
	{
		clearSiteStatus();
		BROWSE.refresh();
	};
	
	this.edit = function(e,id)
	{

		e.cancelBubble = true;
		PDFEDIT.obj = BROWSE.getObject(id);
	
		//setup the xml
		updateSiteStatus(_I18N_PLEASEWAIT);
		var p = new PROTO();
		p.add("command","docmgr_pdf_advedit");
		p.add("object_id",id);
		p.post(API_URL,"PDFEDIT.writeEdit");
	
	};
	
	this.writeEdit = function(data)
	{

		clearSiteStatus();

		MODAL.open(800,500,_I18N_ADV_PDF_EDITING);

		//selection tool
		var sel = createSelect("fileSelector");
		setChange(sel,"PDFEDIT.selectFiles()");
		sel[0] = new Option(_I18N_SELECT + "...","0");
		sel[1] = new Option(_I18N_ALL_FILES,"all");
		sel[2] = new Option(_I18N_EVERY_OTHER_FIRST,"alternatefirst");
		sel[3] = new Option(_I18N_EVERY_OTHER_SECOND,"alternatesecond");

		MODAL.toolbarLeft.appendChild(sel);
		MODAL.addToolbarButtonLeft(_I18N_DELETE,"PDFEDIT.remove()");
		MODAL.addToolbarButtonLeft(_I18N_ROTATE_LEFT,"PDFEDIT.rotate('left')");
		MODAL.addToolbarButtonLeft(_I18N_ROTATE_RIGHT,"PDFEDIT.rotate('right')");
		MODAL.addToolbarButtonLeft(_I18N_FLIP,"PDFEDIT.rotate('flip')");
		MODAL.addToolbarButtonRight(_I18N_SAVE,"PDFEDIT.save()");
	

		var lc = ce("div","leftColumn","advLeft");
		var rc = ce("div","rightColumn","advRight");
		MODAL.container.appendChild(lc);
		MODAL.container.appendChild(rc);
		MODAL.container.appendChild(createCleaner());

		//list containing the files we will edit
		editlist = ce("div","","fileEditList");
		lc.appendChild(editlist);
		
		//start getting our files
		for (var i=0;i<data.file.length;i++) 
		{
			editlist.appendChild(editListRow(data.file[i],i+1));
			editlist.appendChild(createCleaner());
		}

		new Sortables(editlist, {

	      revert: { duration: 250, transition: 'linear' },
				opacity: .25,
				clone: true,
	      onComplete: function() {
	        PDFEDIT.orderchanged = 1;
	      }

	    });

		//setup sections for re-ordering and rotating
		rc.appendChild(ce("div","advCell","",_I18N_PDFEDIT_INSTRUCT));
		rc.appendChild(ce("div","","pdfPreview"));
	
	};
	
	function editListRow(file,idx) {
	
		var row = ce("div","editListRow");
		row.setAttribute("path",file.path);
		row.setAttribute("filename",file.name);
	
		var cb = createCheckbox("filePath[]",file.path);
		
		var img = ce("img","editListThumb");
		img.setAttribute("src",SITE_URL + "app/showpic.php?image=" + file.small_thumb + "?time=" + new Date().getTime());
		setClick(img,"PDFEDIT.preview('" + file.huge_thumb + "')");
		var name = ce("div","editListName","",_I18N_PAGE + " " + idx);
		
		var optdiv = ce("div");
	
		row.appendChild(cb);
		row.appendChild(img);
		row.appendChild(name);
		row.appendChild(optdiv);
	
		return row;
	
	};
	
	this.preview = function(imgsrc)
	{
	
		var ref = ge("pdfPreview");
		clearElement(ref);
	
		//rework src with showpic and the site url
		imgsrc = SITE_URL + "app/showpic.php?image=" + imgsrc + "?time=" + new Date().getTime();
	
		var img = ce("img");
		img.setAttribute("src",imgsrc);
		ref.appendChild(img);
	
	};
	
	
	this.rotateHandler = function(data)
	{

		clearSiteStatus();
		 
		PDFEDIT.orderchanged = 0;
		PDFEDIT.writeEdit(data);

	};
	
	this.rotate = function(dir) 
	{
	
		var arr = MODAL.container.getElementsByTagName("input");

		if (arr.length==0)
		{
			alert(_I18N_NO_FILES_SELECTED);
			return false;
		}

		var check = "";
	
		//setup the xml
		var p = new PROTO();
		p.add("command","docmgr_pdf_rotate");
		p.add("direction",dir);
		p.add("object_id",PDFEDIT.obj.id);
	
		//figure out which files to rotate
		for (var i=0;i<arr.length;i++) 
		{
			if (arr[i].type=="checkbox" && arr[i].checked) 
			{
				check = 1;
				p.add("file",arr[i].value);
			}
		}
	
		if (!check) 
		{
			alert(_I18N_ROTATE_SELECT_ERROR);
			return false;
		}
	
		//if we've changed the order, store that also
		if (PDFEDIT.orderchanged==1) 
		{
	
			p.add("saveorder","1");
	
			for (var i=0;i<arr.length;i++) 
			{
				if (arr[i].type=="checkbox") p.add("reorderfile",arr[i].value);
			}
	
		}
	
		updateSiteStatus(_I18N_PLEASEWAIT);
		p.post(API_URL,"PDFEDIT.rotateHandler");
	
	};

	this.remove = function()
	{
	
		var arr = MODAL.container.getElementsByTagName("input");

		if (arr.length==0)
		{
			alert(_I18N_NO_FILES_SELECTED);
			return false;
		}

		var check = "";
	
		//setup the xml
		var p = new PROTO();
		p.add("command","docmgr_pdf_delete");
		p.add("object_id",PDFEDIT.obj.id);
	
		//figure out which files to rotate
		for (var i=0;i<arr.length;i++) 
		{
			if (arr[i].type=="checkbox" && arr[i].checked) 
			{
				check = 1;
				p.add("file",arr[i].value);
			}
		}
	
		if (!check) 
		{
			alert(_I18N_DELETE_SELECT_ERROR);
			return false;
		}
	
		//if we've changed the order, store that also
		if (PDFEDIT.orderchanged==1) 
		{
	
			p.add("saveorder","1");
	
			for (var i=0;i<arr.length;i++) 
			{
				if (arr[i].type=="checkbox") p.add("reorderfile",arr[i].value);
			}
	
		}
	
		updateSiteStatus(_I18N_PLEASEWAIT);
		p.post(API_URL,"PDFEDIT.rotateHandler");
	
	};
	
	this.save = function()
	{
	
		//get our files
		var arr = MODAL.container.getElementsByTagName("input");
	
		//setup the xml
		var p = new PROTO();
		p.add("command","docmgr_pdf_commit");
		p.add("object_id",PDFEDIT.obj.id);
	
		//if we've changed the order, store that also
		if (PDFEDIT.orderchanged==1) 
		{
	
			p.add("saveorder","1");
	
			for (var i=0;i<arr.length;i++) 
			{
				if (arr[i].type=="checkbox") p.add("reorderfile",arr[i].value);
			}
	
		}
	
		updateSiteStatus(_I18N_SAVING);
		p.post(API_URL,"PDFEDIT.writeSave");
	
	};
	
	this.writeSave = function(data) 
	{
		clearSiteStatus();
		MODAL.hide();
		BROWSE.refresh();
	};
	
	this.selectFiles = function()
	{
	
		var ref = ge("fileSelector");
		if (ref.value=="0") return false;
	
		var arr = editlist.getElementsByTagName("input");
	
		if (ref.value=="all") 
		{
	
			for (var i=0;i<arr.length;i++) 
			{
				if (arr[i].type=="checkbox") arr[i].checked = true;
			}
	
		} else if (ref.value=="alternatefirst") 
		{
	
			for (var i=0;i<arr.length;i++) 
			{
				if (i%2==0) arr[i].checked = true;
				else arr[i].checked = false;
			}
	
		} else if (ref.value=="alternatesecond") 
		{
	
			for (var i=0;i<arr.length;i++) 
			{
				if (i%2==1) arr[i].checked = true;
				else arr[i].checked = false;
			}
			
		}
	
		//go back to the beginning
		ref.selectedIndex = 0;
	
	};

	this.optimize = function(id)
	{
		PDFEDIT.id = id;
		updateSiteStatus(_I18N_PLEASEWAIT);
		MODAL.open(400,200,_I18N_OPTIMIZE_PDF);
		MODAL.addToolbarButtonRight(_I18N_SAVE,"PDFEDIT.saveOptimize()");
		MODAL.addForm("config/forms/objects/pdf-optimize.xml");
	};	

	this.saveOptimize = function()
	{
		updateSiteStatus(_I18N_PLEASEWAIT);
		var p = new PROTO();
		p.add("command","docmgr_pdf_optimize");
		p.add("object_id",PDFEDIT.id);
		p.addDOM(MODAL.container);
		p.post(API_URL,"PDFEDIT.writeSaveOptimize");
	};

	this.writeSaveOptimize = function(data)
	{
		clearSiteStatus();

		if (data.error) alert(data.error);
		else
		{
			MODAL.hide();

			if (data.url)
			{
				var parms = centerParms(800,600,1) + ",resizable=yes,menubar=yes,toolbar=yes";
				var ref = window.open(data.url,"_blank",parms);

				if (ref) ref.focus();
				else alert(_I18N_POPUP_BLOCKER_ERROR);

			}
			else
			{
				BROWSE.refresh();
			}

		}

	};

}

