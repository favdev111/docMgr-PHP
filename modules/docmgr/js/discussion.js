
var DISCUSSION = new DOCMGR_DISCUSSION();

function DOCMGR_DISCUSSION()
{

	this.threadList;
	this.postList;
	this.threadId;
	this.threads;
	this.post;

	this.load = function()
	{

		MODAL.open(800,500,_I18N_DISCUSSION);

		this.threadList = ce("div","","threadList");
		this.postList = ce("div","","postList");

		MODAL.add(this.threadList);
		MODAL.add(this.postList);

		DISCUSSION.search();

	};

	this.loadCold = function(e,id)
	{
    e.cancelBubble = true;

    updateSiteStatus(_I18N_PLEASEWAIT);
    var p = new PROTO();
    p.add("command","docmgr_object_getinfo");
    p.add("object_id",id);
    p.post(API_URL,"DISCUSSION.writeColdLoad");
	};

	this.writeColdLoad = function(data)
	{
		clearSiteStatus();
		if (data.error) alert(data.error);
		else
		{
			PROPERTIES.obj = data.record[0];		
			DISCUSSION.load();
		}
	};

	this.toolbar = function()
	{

		MODAL.clearToolbarRight();
		MODAL.clearToolbarLeft();

		//always show this
		MODAL.addToolbarButtonLeft(_I18N_NEW_THREAD,"DISCUSSION.addThread()");

		if (DISCUSSION.threadId)
		{
			MODAL.addToolbarButtonLeft(_I18N_REMOVE_THREAD,"DISCUSSION.remove('" + DISCUSSION.threadId + "','1')");
			MODAL.addToolbarButtonRight(_I18N_NEW_POST,"DISCUSSION.addPost()");
		}

	};

	this.search = function()
	{

	  updateSiteStatus(_I18N_LOADING);

		DISCUSSION.thread = new Array();
		DISCUSSION.toolbar();

  	//load our logs
		var p = new PROTO();
 	 	p.add("command","docmgr_discussion_search");
 	 	p.add("object_id",PROPERTIES.obj.id);
		p.add("owner","0");
		p.post(API_URL,"DISCUSSION.writeThreadSearch");

	};

	this.writeThreadSearch = function(data)
	{
	
		clearSiteStatus();
		clearElement(DISCUSSION.threadList);

		if (data.error) alert(data.error);
		else if (!data.record) DISCUSSION.threadList.appendChild(ce("div","errorMessage","",_I18N_NORESULTS_FOUND));
		else 
		{

			for (var i=0;i<data.record.length;i++) 
			{
	
				var t = data.record[i];
	
				var mydiv = ce("div","threadRow");
				mydiv.setAttribute("thread_id",t.id);
				setClick(mydiv,"DISCUSSION.viewThread('" + t.id + "')");
	
				mydiv.appendChild(ce("div","threadHeader","",t.header));
	
				var topstat = ce("div","threadStats");
				mydiv.appendChild(topstat);
				topstat.appendChild(ce("div","","",_I18N_CREATED + ": " + t.time_stamp_view));
				topstat.appendChild(ce("div","","",_I18N_CREATEDBY + ": " + t.account_name));
				if (isData(t.reply_time_stamp)) topstat.appendChild(ce("div","","",_I18N_LAST_REPLY + ": " + t.reply_time_stamp_view));
	
				DISCUSSION.threadList.appendChild(mydiv);
	
			}

			if (DISCUSSION.threadId) DISCUSSION.viewThread(DISCUSSION.threadId);
	
		}

	};

	this.showSelected = function()
	{

		var arr = DISCUSSION.threadList.getElementsByTagName("div");

		for (var i=0;i<arr.length;i++)
		{
			if (arr[i].hasAttribute("thread_id"))
			{
				if (arr[i].getAttribute("thread_id")==DISCUSSION.threadId)
				{
					arr[i].className = "threadRow selected";
				}
				else
				{
					arr[i].className = "threadRow";
				}
			}
		}

	};

	this.viewThread = function(id)
	{

		DISCUSSION.threadId = id;
		DISCUSSION.postId = "";

		DISCUSSION.toolbar();
		DISCUSSION.showSelected();

	  updateSiteStatus(_I18N_LOADING);

	  //load our logs
		var p = new PROTO();
	  p.add("command","docmgr_discussion_search");
	  p.add("object_id",PROPERTIES.obj.id);
		p.add("owner",id);
		p.post(API_URL,"DISCUSSION.writeViewThread");

	};

	this.writeViewThread = function(data)
	{

		clearSiteStatus();
		clearElement(DISCUSSION.postList);
	
		if (data.error) alert(data.error);
		else if (!data.record) DISCUSSION.postList.appendChild(ce("div","errorMessage","",_I18N_NORESULTS_FOUND));
		else 
		{

			DISCUSSION.threads = data.record;
	
			//store for later
			var tbl = createTable("postTable","","100%");
			tbl.setAttribute("cellspacing","0");
			tbl.setAttribute("cellpadding","0");
			var tbd = ce("tbody");
			tbl.appendChild(tbd);
			DISCUSSION.postList.appendChild(tbl);
	
			for (var i=0;i<data.record.length;i++) 
			{
	
				var t = data.record[i];
	
				var row = ce("tr","postRow");

				//show who and when	
				var authorCell = ce("td","authorCell");
				authorCell.appendChild(ce("div","","",t.account_name));
				authorCell.appendChild(ce("div","","",t.time_stamp_view));
				authorCell.setAttribute("valign","top");
	
				//show content
				var dataCell = ce("td","postCell");
				dataCell.setAttribute("valign","top");
	
				var con = ce("div","postContent");
				con.innerHTML = t.content;
				dataCell.appendChild(con);
	
				//post actions
				var actions = ce("div","postActions");
	
				if (t.account_id==USER_ID) 
				{
					var editlink = ce("a","","","[" + _I18N_EDIT_POST + "]");
					editlink.setAttribute("href","javascript:DISCUSSION.edit('" + t.id + "')");	
					actions.appendChild(editlink);
				}
	
				if (t.account_id==USER_ID || perm_check(ADMIN)) 
				{
					var dellink = ce("a","","","[" + _I18N_DELETE_POST + "]");
					dellink.setAttribute("href","javascript:DISCUSSION.remove('" + t.id + "')");	
					actions.appendChild(dellink);
				}

				dataCell.appendChild(actions);
				
				row.appendChild(authorCell);
				row.appendChild(dataCell);

				tbd.appendChild(row);
	
			}
	
		}

	};

	this.addThread = function()
	{
		DISCUSSION.post = "";
		DISCUSSION.threadId = "";
		DISCUSSION.postId = "";
		DISCUSSION.loadForm();
	};

	this.addPost = function()
	{
		DISCUSSION.post = "";
		DISCUSSION.postId = "";
		DISCUSSION.loadForm();
	};

	this.edit = function(id)
	{

		DISCUSSION.postId = id;

		for (var i=0;i<DISCUSSION.threads.length;i++)
		{
			if (DISCUSSION.threads[i].id==id)
			{
				DISCUSSION.post = DISCUSSION.threads[i];
				break;
			}
		}

		DISCUSSION.loadForm();


	};

	this.loadForm = function()
	{

		MODAL.open(800,500,"New Thread");
		MODAL.addToolbarButtonRight(_I18N_SAVE,"DISCUSSION.save()");

		MODAL.clearNavbarRight();
		MODAL.addNavbarButtonRight(_I18N_BACK,"DISCUSSION.load()");

		//only show a subject on a new thread
		if (!DISCUSSION.threadId)
		{
			var cell = ce("div","threadCell");
			cell.appendChild(ce("span","threadCellHeader","",_I18N_SUBJECT + " "));
			cell.appendChild(createTextbox("message_subject"));
			MODAL.add(cell);		
		}

		//populate our textarea if we are editing something
		if (DISCUSSION.post) var content = DISCUSSION.post.content;
		else var content = "";

		//show the message content
		var cell = ce("div","threadCell");
		cell.appendChild(createTextarea("editor_content",content));
		MODAL.add(cell);		

		DISCUSSION.loadEditor();

	};

	this.loadEditor = function()
	{

		var ed = ge("editor_content");

		if (CKEDITOR.instances.editor_content) 
		{
			CKEDITOR.remove(CKEDITOR.instances.editor_content);
		}

    //create a new one
    var f = CKEDITOR.replace('editor_content',
                  {
                    toolbar: 'Basic',
                    on:
                    {     
                      instanceReady: function (ev) { DISCUSSION.setFrameSize();}
                    }
  
                  });
	
	};

  this.setFrameSize = function()
  {
     
    var ref = ge("cke_contents_editor_content");

		if (ref) ref.style.height = "330px";

  };

	this.save = function()
	{

		CKEDITOR.instances.editor_content.updateElement();

		updateSiteStatus(_I18N_PLEASEWAIT);

	  //load our logs
		var p = new PROTO();
	  p.add("command","docmgr_discussion_save");
	  p.add("object_id",PROPERTIES.obj.id);
		 

		//if editing a post, make sure we update it
		if (DISCUSSION.postId) p.add("record_id",DISCUSSION.postId);

		//post as a child of the current thread if one's selected
		if (DISCUSSION.threadId) p.add("owner",DISCUSSION.threadId);
		else p.add("owner","0");

		p.addDOM(MODAL.container);

		p.post(API_URL,"DISCUSSION.writeSave");

	};

	this.writeSave = function(data)
	{

		if (data.error) alert(data.error);
		else
		{
			DISCUSSION.load();
		}

	};

	this.remove = function(id,del)
	{

		if (confirm(_I18N_DELETE_POST_CONFIRM)) 
		{

			updateSiteStatus(_I18N_PLEASEWAIT);

		  //load our logs
			var p = new PROTO();
		  p.add("command","docmgr_discussion_delete");
			p.add("record_id",id);
			p.post(API_URL,"DISCUSSION.writeSave");

		}

	};

}

