var RECIPIENTS = new DOCMGR_RECIPIENTS();

function DOCMGR_RECIPIENTS()
{

	this.stageData = new Array();
	this.stagearr = new Array();
	this.popupref;
	this.stage;
	this.taskrecip;
	this.id;
	this.routeData;
	this.templatelist;
	this.workflow;

	this.load = function()
	{

		MODAL.open(800,500,_I18N_EDITRECIPS);

		//replace the close button w/ a back button
		clearElement(MODAL.navbarRight);
		MODAL.addNavbarButtonRight(_I18N_BACK,"WORKFLOW.get()");

		MODAL.addToolbarButtonLeft(_I18N_TEMPLATES,"RECIPIENTS.loadTemplates()");
		MODAL.addToolbarButtonLeft(_I18N_SAVE_TEMPLATE,"RECIPIENTS.saveTemplate()");
		MODAL.addToolbarButtonRight(_I18N_NEWSTAGE,"RECIPIENTS.newStage()");

		RECIPIENTS.loadStages();

	};

	/**
		stage management
		*/

	this.loadStages = function()
	{	
		var p = new PROTO();
	  p.add("command","docmgr_workflow_get");
		p.add("workflow_id",WORKFLOW.id);
		p.post(API_URL,"RECIPIENTS.writeStages");
	};
	
	this.writeStages = function(data) 
	{
	
		clearSiteStatus();
	
		if (data.error) alert(data.error);
		else 
		{
	
			RECIPIENTS.stageData = new Array();
			RECIPIENTS.workflow = data.record[0];
	
			if (!RECIPIENTS.workflow.recipient) 
			{
				RECIPIENTS.routeData = new Array();
				RECIPIENTS.stageData.push(RECIPIENTS.routeData);
			} 
			else 
			{
	
				RECIPIENTS.routeData = RECIPIENTS.workflow.recipient;
	
				for (var i=0;i<RECIPIENTS.routeData.length;i++) 
				{
	
					var key = RECIPIENTS.routeData[i].sort_order;
	
					//store our recipient in our master stage array
					if (RECIPIENTS.stageData[key]) RECIPIENTS.stageData[key].push(RECIPIENTS.routeData[i]);
					else RECIPIENTS.stageData[key] = new Array(RECIPIENTS.routeData[i]);
				
				}
	
			}
	
			//now update the display
			RECIPIENTS.stageDisplay();
	
		}
	
	};
	
	this.stageDisplay = function()
	{
	
			var cont = MODAL.container;
			clearElement(cont);
	
			for (var i=0;i<RECIPIENTS.stageData.length;i++)
			{
	
				//create the stage container
				var sc = RECIPIENTS.stageContainer(i);
				var recip = RECIPIENTS.stageData[i];
	
				//append recipients to the stage
				for (var c=0;c<recip.length;c++)
				{
					sc.appendChild(RECIPIENTS.stageRecipient(recip[c]));
				}
	
				sc.appendChild(createCleaner());
	
				//append master container
				cont.appendChild(sc);
	
			}
	
	};
	
	this.stageContainer = function(key)
	{
	
		var stage = ce("div","stageContainer");
		stage.setAttribute("stage",key);
	
		var header = ce("div","stageHeader");
	
		//add recip link
		header.appendChild(createLink("[" + _I18N_ADDRECIP + "]","javascript:RECIPIENTS.editRecipient('" + (key) + "')"));
		header.appendChild(ctnode(_I18N_STAGE + " " + ( parseInt(key)+1 )));
	
		stage.appendChild(header);
		stage.appendChild(createCleaner());
	
		return stage;
	
	};
	
	//add a recipient to a stage
	this.stageRecipient = function(r)
	{
	
		var cont = ce("div","recipContainer");
	
		//add account and date due
		cont.appendChild(ce("div","","",r.account_name));
		if (isData(r.date_due)) cont.appendChild(ce("div","","",dateOnlyView(r.date_due)));
		cont.appendChild(ce("div","","","" + _I18N_TASKTYPE + ": " + ucfirst(r.task_type)));
	
		var links = ce("div");
		links.appendChild(createLink("[" + _I18N_EDIT + "]","javascript:RECIPIENTS.editRecipient('" + r.sort_order + "','" + r.id + "')"));
		links.appendChild(createLink("[" + _I18N_DELETE + "]","javascript:RECIPIENTS.remove('" + r.sort_order + "','" + r.id + "')"));
		cont.appendChild(links);
	
		return cont;
	
	};

	this.newStage = function()
	{
	
		//if no key, figure out how many stages their are
		var arr = MODAL.container.getElementsByTagName("div");
	
		var num = 0;
		for (var i=0;i<arr.length;i++) 
		{
			if (arr[i].getAttribute("stage")) num++;
		}
	
		MODAL.container.appendChild(RECIPIENTS.stageContainer(num));
	
	};
	
	this.editRecipient = function(key,id) 
	{

		RECIPIENTS.id = id;
		RECIPIENTS.stage = key;

		MODAL.open(800,500,_I18N_EDITRECIP);
		clearElement(MODAL.navbarRight);

		MODAL.addNavbarButtonRight(_I18N_BACK,"RECIPIENTS.load()");
		MODAL.addToolbarButtonRight(_I18N_SAVE,"RECIPIENTS.save()");
		MODAL.addForm("config/forms/workflow/recipient.xml","RECIPIENTS.recipData");
	
	}

	this.recipData = function()
	{

		var arr = new Array();

		//get our data for the current route
		for (var i=0;i<RECIPIENTS.routeData.length;i++) 
		{
	
			if (RECIPIENTS.routeData[i].id==RECIPIENTS.id) 
			{
				arr = RECIPIENTS.routeData[i];

				//so the checkbox form will be populated correctly.  it expects an array
				arr.account_id = new Array(arr.account_id);

				break;

			}
		}

		return arr;

	};
	
	this.save = function()
	{
	
		var p = new PROTO();
	  p.add("command","docmgr_workflow_saverecipient");
		p.add("workflow_id",WORKFLOW.id);
		p.add("route_id",RECIPIENTS.id);
		p.add("stage",RECIPIENTS.stage);
		p.addDOM(MODAL.container);
	
		var d = p.getData();
	
		//make sure there's an account id set
		if (!isData(d.account_id))
		{
			alert(_I18N_ACOUNT_ASSIGNTASK_ERROR);
		}
		else
		{	
			updateSiteStatus(_I18N_SAVING);
			p.post(API_URL,"RECIPIENTS.writeSave");
		}
	
	};
	
	this.writeSave = function(data)
	{
	
		clearSiteStatus();
	
		if (data.error) alert(data.error);
		else 
		{
			RECIPIENTS.load();
		}
	
	};
	
	this.remove = function(stageid,id)
	{
	
		if (confirm(_I18N_DELETE_RECIP_CONFIRM)) 
		{
	
			updateSiteStatus(_I18N_PLEASEWAIT);
	
			var p = new PROTO();
		  p.add("command","docmgr_workflow_deleterecipient");
			p.add("workflow_id",WORKFLOW.id);
			p.add("route_id",id);
			p.post(API_URL,"RECIPIENTS.writeRemove");
	
		}
	
	};
	
	this.writeRemove = function(data)
	{
		clearSiteStatus();

		if (data.error) alert(data.error);
		else RECIPIENTS.load();
	};



	/**
		template management
		*/
	this.loadTemplates = function()
	{

		updateSiteStatus(_I18N_LOADING);
	
		MODAL.open(800,500,_I18N_AVAIL_WF_TEMPLATES);

		//replace the close button w/ a back button
		clearElement(MODAL.navbarRight);
		MODAL.addNavbarButtonRight(_I18N_BACK,"RECIPIENTS.load()");
		
		var p = new PROTO();
		p.add("command","docmgr_workflow_gettemplates");
		p.add("workflow_id",WORKFLOW.id);
		p.post(API_URL,"RECIPIENTS.writeTemplates");
	
	};
	
	this.writeTemplates = function(data)
	{
		 
		clearSiteStatus();

		MODAL.openRecords();
	
		if (data.error) alert(data.error);
		else if (!data.record) 
		{
			MODAL.openRecordRow();
			MODAL.addRecordCell(_I18N_NORESULTS_FOUND,"one");
			MODAL.closeRecordRow();
		}
		else 
		{
	
			for (var i=0;i<data.record.length;i++) 
			{

				MODAL.openRecordRow("RECIPIENTS.loadTemplate('" + data.record[i].id + "')");

				MODAL.addRecordCell(data.record[i].name,"two");

				var cell = MODAL.addRecordCell("[" + _I18N_DELETE + "]","two");
				setClick(cell,"RECIPIENTS.removeTemplate(event,'" + data.record[i].id + "')");

				MODAL.closeRecordRow();	

			}
	
		}

		MODAL.closeRecords();	

	};

	this.loadTemplate = function(id)
	{
		if (confirm(_I18N_CONFIRM_USE_WORKFLOW_TEMPLATE))
		{
			updateSiteStatus(_I18N_PLEASEWAIT);
			var p = new PROTO();
			p.add("command","docmgr_workflow_getfromtemplate");
			p.add("workflow_id",WORKFLOW.id);
			p.add("template_id",id);
			p.post(API_URL,"RECIPIENTS.writeLoadTemplate");
		}
	};	

	this.writeLoadTemplate = function(data)
	{
		if (data.error) alert(data.error);
		else RECIPIENTS.load();
	}

	this.saveTemplate = function()
	{

		var txt = prompt(_I18N_TEMPLATE_NAME);

		if (txt.length > 0)
		{
			//fetch  the template list
			updateSiteStatus(_I18N_SAVING);
	
			var p = new PROTO();
			p.add("command","docmgr_workflow_savetemplate");
			p.add("workflow_id",WORKFLOW.id);
			p.add("template_name",txt);
			p.post(API_URL,"RECIPIENTS.writeSaveTemplate");

		}

	};

	this.writeSaveTemplate = function(data)
	{

		clearSiteStatus();

		if (data.error) alert(data.error);

	};

	
	this.removeTemplate = function(e,id)
	{
	
	  e.cancelBubble = true;
	
	  if (confirm("Are you sure you want to delete this template?"))
	  {
	
	    var ref = getEventSrc(e);
	
	    //fetch  the template list
	    updateSiteStatus(_I18N_PLEASEWAIT);
	
	    var p = new PROTO();
	    p.add("command","docmgr_workflow_deletetemplate");
	    p.add("template_id",id);
	    p.post(API_URL,"RECIPIENTS.writeRemoveTemplate");
	
	  }
	
	};
	
	this.writeRemoveTemplate = function(data)
	{  
	
	  if (data.error) alert(data.error);
	  else
	  {
			RECIPIENTS.loadTemplates();
	  }

	};
	

}

