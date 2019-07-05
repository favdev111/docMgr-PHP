
var TASKS = new WORKFLOW_TASKS();

function WORKFLOW_TASKS()
{

	this.results;
	this.id;
	this.record;

	this.load = function()
	{

		RECORDS.load(ge("container"),"listDetailView","active");
    RECORDS.setRowMode('select');
    TASKS.toolbar();
		TASKS.header();
    TASKS.search(); 

  };

  this.toolbar = function()
  {
    TOOLBAR.open();
    TOOLBAR.close();
  };

	this.header = function()
	{
	    //header row
			RECORDS.openHeaderRow();
	    RECORDS.addHeaderCell(_I18N_FILE,"fileCell");
	    RECORDS.addHeaderCell(_I18N_WORKFLOW_NAME,"workflowNameCell");
	    RECORDS.addHeaderCell(_I18N_SUBMITTED_BY,"submittedByCell");
			RECORDS.addHeaderCell(_I18N_TASKTYPE,"taskTypeCell");
	    RECORDS.addHeaderCell(_I18N_DUE,"taskDueCell"); 
			RECORDS.closeHeaderRow();
	};

	this.search = function()
	{
	
		var p = new PROTO();
		p.add("command","docmgr_workflow_gettasks");
		p.post(API_URL,"TASKS.writeSearch");
	
	};
	
	this.writeSearch = function(data)
	{

		clearSiteStatus();

		RECORDS.openRecords();
	
		if (data.error) alert(data.error);
		else if (!data.record)
		{
			TASKS.results = new Array();
	    RECORDS.setRowMode('active');

			RECORDS.openRecordRow();
			RECORDS.addRecordCell(_I18N_NORESULTS_FOUND,"one");
			RECORDS.closeRecordRow();
		}
		else
		{

			TASKS.results = data.record;
	    RECORDS.setRowMode('select');

	    for (var i=0;i<data.record.length;i++)
	    {
	
				var task = data.record[i];

				var row = RECORDS.openRecordRow("TASKS.view(event)");
	
				row.setAttribute("task_id",task.id);
				row.setAttribute("object_id",task.object_id);

				RECORDS.addRecordCell(task.object_path,"fileCell");
				RECORDS.addRecordCell(task.workflow_name,"workflowNameCell");
				RECORDS.addRecordCell(task.workflow_account_name,"submittedByCell");
				RECORDS.addRecordCell(task.task_type,"taskTypeCell");
				RECORDS.addRecordCell(task.date_due,"taskDueCell");

				RECORDS.closeRecordRow();
	
	    }
	
	  }

		RECORDS.closeRecords();	
	
	};

	this.view = function(e)
	{
	
		var ref = getEventSrc(e);
		if (!ref.hasAttribute("task_id")) ref = ref.parentNode;

		TASKS.id = ref.getAttribute("task_id");
		var objId = ref.getAttribute("object_id");

		for (var i=0;i<TASKS.results.length;i++)
		{
			if (TASKS.results[i].id==TASKS.id && TASKS.results[i].object_id==objId)
			{
				TASKS.record = TASKS.results[i];
				break;
			}
		}

		//clear our detail container
		RECORDS.clearDetail();

		//load the form
    var SUBTOOL = new SITE_TOOLBAR();

    SUBTOOL.open("left",RECORDS.detailHeader);
    SUBTOOL.addGroup();

    SUBTOOL.add(_I18N_VIEW_FILE,"TASKS.viewFile()");

		if (TASKS.record.task_type=="approve")
		{
	    SUBTOOL.add(_I18N_APPROVE,"TASKS.complete()");
	    SUBTOOL.add(_I18N_REJECT_APPROVAL,"TASKS.reject()");
		}
		else
		{
	    SUBTOOL.add(_I18N_MARK_COMPLETE,"TASKS.complete()");
		}

		SUBTOOL.close();

		//show something
		if (TASKS.record.task_notes.length==0) TASKS.record.task_notes = _I18N_NONE;

		//create our task notes box
		var notes = ce("div","","taskNotes",TASKS.record.task_notes);
		RECORDS.recordDetail.appendChild(EFORM.template(_I18N_TASK_NOTES,notes));

		//create our comment textarea
		var ta = createTextarea("comment");
		RECORDS.recordDetail.appendChild(EFORM.template(_I18N_COMMENTS,ta));
	
	};

	this.complete = function()
	{
	
		if (confirm(_I18N_CONTINUE_CONFIRM))
		{		
		  updateSiteStatus(_I18N_PLEASEWAIT);
	
		  var p = new PROTO();
		  p.add("command","docmgr_workflow_markcomplete");
		  p.add("route_id",TASKS.record.id);
		  p.add("object_id",TASKS.record.object_id);
		  p.add("comment",ge("comment").value);
		  p.post(API_URL,"TASKS.writeComplete");
		}
		
	};
	 
	this.writeComplete = function(data)
	{
	 
	  clearSiteStatus();
	
	  if (data.error) alert(data.error);
	  else 
	  {
			TASKS.id = "";
			TASKS.record = new Array();
			TASKS.load();
	  }
	   
	};
	
	this.reject = function()
	{
	

		if (confirm(_I18N_CONTINUE_CONFIRM))
		{		
		  updateSiteStatus(_I18N_PLEASEWAIT);

		  var p = new PROTO();
		  p.add("command","docmgr_workflow_rejectapproval");
		  p.add("route_id",TASKS.record.id);
		  p.add("comment",ge("comment").value);
		  p.post(API_URL,"TASKS.writeComplete");
		}
	
	};

	this.viewFile = function()
	{
	  updateSiteStatus(_I18N_PLEASEWAIT);
	
	  var p = new PROTO();
	  p.add("command","docmgr_object_getinfo");
	  p.add("object_id",TASKS.record.object_id);
	  p.post(API_URL,"TASKS.writeViewFile");
		
	};

	this.writeViewFile = function(data)
	{

		clearSiteStatus();

		if (data.error) alert(data.error);
		else
		{
			VIEW.load(data.record[0]);
		}
	}

}

