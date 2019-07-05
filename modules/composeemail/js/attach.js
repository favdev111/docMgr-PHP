var uploadArray = new Array();
var timer;

function createAttachEntry(entry,type) 
{

	var row = ce("div","attachEntry");
	row.setAttribute("attach_type",type);
	row.setAttribute("attach_name",entry.name);

	if (entry.id) row.setAttribute("attach_id",entry.id);
	

	var img = createImg(THEME_PATH + "/images/icons/attachment.png");
	row.appendChild(img);
	
	row.appendChild(ctnode(entry.name));
	
	var delimg = createImg(THEME_PATH + "/images/icons/delete_gray.png");
	setClick(delimg,"attachDelete(event)");
	row.appendChild(delimg);

	var ref = ge("attachCell");
	ref.style.display = "block";

	ref.appendChild(row);

	setFrameSize();

}

/*******************************************************
	handle file attachments
*******************************************************/

function attachFile()
{

	if (BROWSERMOBILE==true)
	{
		alert("File uploading does not work for mobile devices");
	}
	else
	{
		ge("attach").click();
	}

}


function setupAttachmentListener()
{

	var input = ge("attach");

	if (input) input.addEventListener("change",uploadFiles,false);

	//input.attachEvent("onchange","oldUploadFile()");
	//input.addEventListener("progress",progressHandler,false);

}



function uploadFiles()
{

	var files = ge("attach").files;

	var total = 0;

	for (var i=0;i<files.length;i++) 
	{

		uploadArray[i] = new Array();
		uploadArray[i]["file"] = files[i];
		uploadArray[i]["name"] = files[i].name;
		uploadArray[i]["loaded"] = "";
		uploadArray[i]["total"] = files[i].size;

		total += files[i].fileSize;

	}


	//make our upload progress window
	MODAL.open(300,120,"Uploading " + files.length + " files");

	var cell = ce("div");
	var progdiv = ce("div","","progressContainer");
	var statdiv = ce("div","","progressStatus");
	progdiv.appendChild(statdiv);
	cell.appendChild(progdiv);

	var detail = ce("div","","progressText","Uploaded ");
	detail.appendChild(ce("span","","currentUploadTotal","0"));
	detail.appendChild(ctnode(" of " + size_format(total)));
	cell.appendChild(detail);

	MODAL.addCell(cell);

	//help safari out
	closeKeepAlive();

	for (var i=0;i<files.length;i++)
	{
		//run in a separate function to prevent PROTO overlapping
		uploadFile(i);
	}

}

function uploadFile(idx)
{

	var file = ge("attach").files[idx];

	var p = new PROTO();
	p.add("command","email_attach_add");

	var data = p.upload(API_URL,file,"uploadComplete","uploadProgress");

}


function uploadProgress(evt,file) 
{

	if (evt.lengthComputable) 
	{

		var loaded = 0;
		var total = 0;

		for (var i=0;i<uploadArray.length;i++)
		{

			if (uploadArray[i]["file"]==file) 
			{
				uploadArray[i]["loaded"] = evt.loaded;
			}

			loaded += uploadArray[i]["loaded"];
			total += uploadArray[i]["total"];

		}

		ge("currentUploadTotal").innerHTML = size_format(loaded);
		var percentComplete = Math.round(loaded * 100 / total);
		ge("progressStatus").style.width = percentComplete + "%";

	}

}

function uploadComplete(data,file) 
{

	/* This event is raised when the server send back a response */
	if (data.error) alert(data.error);
	else
	{

		MODAL.hide();

		for (var i=0;i<uploadArray.length;i++)
		{

			if (uploadArray[i]["file"]==file) 
			{
				var arr = new Array();
				arr.name = uploadArray[i]["name"];
				createAttachEntry(arr,"file");
			}

		}

	}

}

function showAttachments()
{

	clearElement(ge("attachCell"));

	showFileAttachments();
	showDocmgrAttachments();

}

//load our list of current attachments
function showFileAttachments()
{

	var p = new PROTO();
	p.add("command","email_attach_get");
	p.post(API_URL,"writeFileAttachments");

}

function writeFileAttachments(data)
{

	clearSiteStatus();
	var ref = ge("attachCell");

	if (data.attach)
	{
		ref.style.display = "block";

		for (var i=0;i<data.attach.length;i++)
		{
			createAttachEntry(data.attach[i],"file");
		}
			
	}

}


function attachDelete(e) 
{

	var ref = getEventSrc(e).parentNode;

	if (ref.getAttribute("attach_type")=="docmgr")
	{
		deleteDocmgrAttachment(ref);
	}
	else
	{
		deleteFileAttachment(ref);
	}

	ref.parentNode.removeChild(ref);

	setFrameSize();

}

function deleteFileAttachment(ref)
{

	var p = new PROTO();
	p.add("command","email_attach_remove");
	p.add("filename",ref.getAttribute("attach_name"));
	p.post(API_URL,"writeAttachDelete");

}

function writeAttachDelete(data) 
{
	 
	if (data.error) 
	{
		alert(data.error);
		showAttachments();
	}

}

/**********************************************************
	handle DOCMGR attachments
**********************************************************/

function selectDocmgrAttachment() 
{
	MINIB.open("open","attachSelectObject","document,file,collection");
}


function attachSelectObject(arr) {

	var res = arr[0];
	var objType = res.type;
	var id = res.id;
	var path = res.path;

  //make sure they picked a document or file
  if (objType!="document" && objType!="file") 
	{
    alert("You must pick an DocMGR Document or File");
    return false;
  }

	//push onto our attachment array
	docattach.push(id);

	var data = new Array();
	data.id = id;
	data.name = res.name;

	createAttachEntry(data,"docmgr");

}


function deleteDocmgrAttachment(ref)
{

	var id = ref.getAttribute("attach_id");
	
	for (var i=0;i<docattach.length;i++) 
	{

		if (docattach[i]==id) docattach.splice(i,1);

	}

}


function showDocmgrAttachments() 
{

	var ids = ge("docmgrAttachments").value;

	if (ids.length > 0) 
	{

		updateSiteStatus(_I18N_PLEASEWAIT);

	  //setup the xml
		var p = new PROTO();
		p.add("command","docmgr_query_search");
		p.add("object_filter",ids);
		p.post(API_URL,"writeDocmgrAttachments");

	}

}

function writeDocmgrAttachments(data)
{

	clearSiteStatus();

	if (data.error) alert(data.error);
	else if (data.record) 
	{

		for (var i=0;i<data.record.length;i++) 
		{

			//push onto our attachment array
			docattach.push(data.record[i].id);

			createAttachEntry(data.record[i],"docmgr");

	  }

	}

}

function oldAttachFile()
{
	
	MODAL.open(300,120,"Attach File");

  var p = new PROTO();
  p.add("command","email_attach_addfile");
  var url = API_URL + "?" + p.encodeData();
  var d = new Date();
  url += "&timestamp=" + d.getTime();

	//create the form for uploading
	var uploadForm = ce("form");
	uploadForm.setAttribute("name","attachForm");
	uploadForm.setAttribute("method","post");
	uploadForm.setAttribute("enctype","multipart/form-data");
	uploadForm.setAttribute("action",url);
	uploadForm.setAttribute("target","uploadframe");

	uploadForm.appendChild(ce("div","formHeader","","Select File To Upload"));
	uploadForm.appendChild(createForm("file","attach"));

	MODAL.addCell(uploadForm);
	MODAL.addToolbarButtonRight("Upload","runOldAttachFile()");

}

function runOldAttachFile()
{

	updateSiteStatus(_I18N_PLEASEWAIT);

  window.frames["uploadframe"].document.open();
  window.frames["uploadframe"].document.write("");
  window.frames["uploadframe"].document.close();  

  //this will happen n several stages.  First, we send the object info to the server, then when send the file itself
	document.attachForm.submit();

	timer = setInterval("checkUpload()","100");

}

function checkUpload() 
{

  //this was so much cleaner w/o ie handling
  var tmp = window.frames["uploadframe"].document;

  if (tmp.XMLDocument) var txt = tmp.XMLDocument.documentElement;
  else return false;

  var err = txt.getElementsByTagName("error");
  var success = txt.getElementsByTagName("success");

  if (err.length > 0 || success.length > 0) 
	{

    clearSiteStatus();
    clearInterval(timer);
		MODAL.hide();

    //if success just refresh, otherwise show the error
    if (success.length > 0) 
		{
			showAttachments();
    } 
		else 
		{
      alert(err[0].firstChild.nodeValue);
    }

    window.frames["uploadframe"].document.open();
    window.frames["uploadframe"].document.write("");
    window.frames["uploadframe"].document.close();  

  }
   
}  


