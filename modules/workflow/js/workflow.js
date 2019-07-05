
var WORKFLOW = new DOCMGR_WORKFLOW;

function DOCMGR_WORKFLOW()
{

	this.id;
	this.workflow;
	this.name;
	this.mode;
	this.editBtn;
	this.filter = "current";

	this.load = function(filter)
	{

		if (filter) WORKFLOW.filter = filter;

		RECORDS.load(ge("container"),"listView","active");
		RECORDS.setRowMode('select');
		WORKFLOW.toolbar();
		WORKFLOW.header();
		WORKFLOW.search();

		if (ge("workflow_id").value.length > 0 && ge("action").value=="newWorkflow")
		{
			WORKFLOW.get(ge("workflow_id").value);
		}

	};

	this.toolbar = function()
	{

	  TOOLBAR.open();

	  TOOLBAR.addGroup();
	  WORKFLOW.editBtn = TOOLBAR.add(_I18N_EDIT,"WORKFLOW.cycleMode()");

	  TOOLBAR.addGroup();
	  TOOLBAR.add(_I18N_NEW_WORKFLOW,"WORKFLOW.add()");
	  TOOLBAR.add(_I18N_DELETE,"WORKFLOW.remove()");

	  TOOLBAR.close();

	};

  this.cycleMode = function()
  {

    if (WORKFLOW.mode=="edit")
    {
      WORKFLOW.mode = "view";
      RECORDS.setRowMode("select");
      WORKFLOW.editBtn.innerHTML = _I18N_EDIT;
    }
    else
    {
      WORKFLOW.mode = "edit";
      RECORDS.setRowMode("multiselect");
      WORKFLOW.editBtn.innerHTML = _I18N_CANCEL;
    }

  };

	this.header = function()
	{

		//header row
		RECORDS.openHeaderRow();
		RECORDS.addHeaderCell(_I18N_NAME,"nameCell");
		RECORDS.addHeaderCell(_I18N_OBJECT_NAMES,"objectNamesCell");
		RECORDS.addHeaderCell(_I18N_CREATED,"createdDateCell");
		RECORDS.addHeaderCell(_I18N_CREATEDBY,"createdByCell");
		RECORDS.addHeaderCell(_I18N_STATUS,"statusCell");
		RECORDS.closeHeaderRow();

	};

	this.search = function()
	{
		updateSiteStatus(_I18N_PLEASEWAIT);
		var p = new PROTO();
		p.add("command","docmgr_workflow_search");
		p.add("filter",WORKFLOW.filter);
		p.post(API_URL,"WORKFLOW.writeSearch");
	};

	this.writeSearch = function(data)
	{
	
		clearSiteStatus();

		RECORDS.openRecords();

		if (data.error) alert(data.error);
		else if (!data.record)
		{
			RECORDS.openRecordRow();
			RECORDS.addRecordCell(_I18N_NORESULTS_FOUND,"one");
			RECORDS.closeRecordRow();
		}
		else
		{

			for (var i=0;i<data.record.length;i++)
			{

				var wf = data.record[i];

	      var objstr = "";
	      for (var c=0;c<wf.object.length;c++) objstr += wf.object[c].name + ", ";
	      if (objstr.length > 0) objstr = objstr.substr(0,objstr.length-2);

				var row = RECORDS.openRecordRow("WORKFLOW.get('" + wf.id + "')");
				row.setAttribute("record_id",wf.id)

				RECORDS.addRecordCell(wf.name,"nameCell");
				RECORDS.addRecordCell(objstr,"objectNamesCell");
				RECORDS.addRecordCell(wf.date_create_view,"createdDateCell");
				RECORDS.addRecordCell(wf.account_name,"createdByCell");
				RECORDS.addRecordCell(wf.status_view,"statusCell");
				RECORDS.closeRecordRow();

			}

		}

		RECORDS.closeRecords();

	};

	this.get = function(id)
	{

		if (id) WORKFLOW.id = id;

		if (WORKFLOW.mode!="edit")
		{
			updateSiteStatus(_I18N_PLEASEWAIT);
			var p = new PROTO();
			p.add("command","docmgr_workflow_get");
			p.add("workflow_id",WORKFLOW.id);
			p.post(API_URL,"WORKFLOW.writeGet");
		}

	};

	this.writeGet = function(data)
	{
	
		clearSiteStatus();
	
		if (data.error) alert(data.error);
		else
		{

			WORKFLOW.workflow = data.record[0];

			MODAL.open(800,500,WORKFLOW.workflow.name);

	  	if (WORKFLOW.workflow && WORKFLOW.workflow.account_id==USER_ID) 
			{

	  		if (WORKFLOW.workflow.status=="nodist") 
				{
					MODAL.addToolbarButtonLeft(_I18N_EDITRECIPS,"RECIPIENTS.load()");
				}	

				MODAL.addToolbarButtonLeft(_I18N_EDIT_NAME,"WORKFLOW.editName()");

	    	if (WORKFLOW.workflow.status=="nodist") 
				{
					MODAL.addToolbarButtonRight(_I18N_BEGIN_WORKFLOW,"WORKFLOW.begin()");
	    	} 
				else if (WORKFLOW.workflow.status=="pending") 
				{
					MODAL.addToolbarButtonRight(_I18N_FORCE_COMPLETE,"WORKFLOW.forceComplete()");
	    	}

			}

			MODAL.openRecords();
			
			MODAL.openRecordHeader();
			MODAL.addHeaderCell(_I18N_RECIPIENT,"recipientCell");
			MODAL.addHeaderCell(_I18N_STATUS,"statusCell");
			MODAL.addHeaderCell(_I18N_DUE,"dateDueCell");
			MODAL.addHeaderCell(_I18N_STAGE,"stageCell");
			MODAL.addHeaderCell(_I18N_COMMENTS,"commentsCell");
			MODAL.closeRecordHeader();

			if (isData(WORKFLOW.workflow.recipient))
			{

				for (var i=0;i<WORKFLOW.workflow.recipient.length;i++)
				{
			
					var wf = WORKFLOW.workflow.recipient[i];
	
					MODAL.openRecordRow();
					MODAL.addRecordCell(wf.account_name,"recipientCell");	
					MODAL.addRecordCell(wf.status_view,"statusCell");	
					MODAL.addRecordCell(dateOnlyView(wf.date_due),"dateDueCell");	
					MODAL.addRecordCell(parseInt(wf.sort_order)+1,"stageCell");	
					MODAL.addRecordCell(wf.comment,"commentsCell");
					MODAL.closeRecordRow();
			
				} 

			}
			else
			{
					MODAL.openRecordRow();
					MODAL.addRecordCell(_I18N_NORESULTS_FOUND,"one");	
					MODAL.closeRecordRow();
			}
	
		}
	
	};

	this.add = function()
	{
	
		WORKFLOW.name = prompt(_I18N_NEW_WORKFLOW_PROMPT);
	
		if (isData(WORKFLOW.name))
		{
			MINIB.open("open","WORKFLOW.mbSelect");
		}
	
	};

	this.editName = function()
	{
	
		WORKFLOW.name = prompt(_I18N_NEW_WORKFLOW_PROMPT,WORKFLOW.workflow.name);
	
		if (isData(WORKFLOW.name))
		{
			var p = new PROTO();
			p.add("command","docmgr_workflow_save");
			p.add("name",WORKFLOW.name);
		  p.add("workflow_id",WORKFLOW.workflow.id);
			p.post(API_URL,"WORKFLOW.writeCreate");
		}
	
	};

	this.mbSelect = function(res)
	{
	
		var objarr = new Array();
	
		for (var i=0;i<res.length;i++) objarr.push(res[i].id);
	
		updateSiteStatus(_I18N_PLEASEWAIT);
		
		var p = new PROTO();
		p.add("command","docmgr_workflow_save");
		p.add("name",WORKFLOW.name);
	  p.add("object_id",objarr);
		p.post(API_URL,"WORKFLOW.writeCreate");

	};

	this.writeCreate = function(data)
	{

		clearSiteStatus();	

		if (data.error) alert(data.error);
		else 
		{
			WORKFLOW.search();
			WORKFLOW.get(data.workflow_id);
		}

	};

	
	this.begin = function()
	{

		if (confirm(_I18N_CONTINUE_CONFIRM))
		{		
		  updateSiteStatus(_I18N_PLEASEWAIT);
	
		  var p = new PROTO();
		  p.add("command","docmgr_workflow_begin");
		  p.add("workflow_id",WORKFLOW.id);
		  p.post(API_URL,"WORKFLOW.writeBegin");
		}
	
	};
	 
	this.writeBegin = function(data)
	{
		clearSiteStatus();	
	  if (data.error) alert(data.error);
	  else 
	  {    
			MODAL.hide();
			WORKFLOW.search();
	  }
	};
	   
	this.forceComplete = function() 
	{
	
		if (confirm(_I18N_WORKFLOW_FC_CONFIRM))
		{
	
		  updateSiteStatus(_I18N_PLEASEWAIT);
	
		  var p = new PROTO();
		  p.add("command","docmgr_workflow_forcecomplete");
		  p.add("workflow_id",WORKFLOW.id);	
		  p.post(API_URL,"WORKFLOW.writeForceComplete");
	
		}
	
	};
	
	this.writeForceComplete = function(data)
	{
		clearSiteStatus();
	  if (data.error) alert(data.error);
	  else
	  {
			MODAL.hide();
			WORKFLOW.search();
	  }
	
	};
	
	this.remove = function()
	{

		var arr = new Array();
	
		for (var i=0;i<RECORDS.selected.length;i++)
		{
			arr.push(RECORDS.selected[i].getAttribute("record_id"));
		}

		if (arr.length == 0)
		{
			alert(_I18N_NO_ITEMS_SELECTED);
			return false;
		}
			
		if (confirm(_I18N_WORKFLOW_DELETE_CONFIRM))
		{
	
		  updateSiteStatus(_I18N_PLEASEWAIT);
	
		  var p = new PROTO();
		  p.add("command","docmgr_workflow_delete");
		  p.add("workflow_id",arr);	
		  p.post(API_URL,"WORKFLOW.writeRemove");

		}

	};
	
	this.writeRemove = function(data)
	{
	
	  if (data.error) alert(data.error);
	  else
	  {
			RECORDS.clearDetail();
			WORKFLOW.search();
	  }
	
	};
	
	this.editOptions = function()
	{
		var ref = MODAL.open("800","500",_I18N_EDITOPT);

		clearElement(MODAL.navbarRight);
		MODAL.addNavbarButtonRight(_I18N_BACK,"WORKFLOW.get()");

		MODAL.addToolbarButtonRight(_I18N_SAVE,"WORKFLOW.saveOptions()");
		MODAL.addForm("config/forms/workflow/options.xml","WORKFLOW.optionsData");
	};

	this.optionsData = function()
	{
		return WORKFLOW.workflow;
	};
	
	this.saveOptions = function()
	{
	
	  updateSiteStatus(_I18N_PLEASEWAIT);
	  var p = new PROTO();
	  p.add("command","docmgr_workflow_save");
	  p.add("workflow_id",WORKFLOW.id);
		p.addDOM(MODAL.container);
	  p.post(API_URL,"WORKFLOW.writeSaveOptions");
	
	};
	 
	this.writeSaveOptions = function(data) 
	{
	   
	  clearSiteStatus();
	
	  if (data.error) alert(data.error);
		else
		{
			WORKFLOW.get();
		}
	
	};
	 
	
}
	
