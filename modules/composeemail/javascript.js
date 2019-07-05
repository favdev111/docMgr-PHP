
/**************************************
	global variables
**************************************/

var attachdiv;
var attachlist;
var attachmode;
var uploaddiv;
var docattach = new Array();
var doclist;
var timer;
var templatebox;
var addrbox;
var privcontent;
var pubcontent;
var cursorfocus;
var ckeditor;
var draftuid;

window.onresize = setFrameSize;
document.onkeyup = handleKeyUp;

/***********************************************
  FUNCTION: handleKeyUp
  PURPOSE:  deactivates the control key setting
************************************************/
function handleKeyUp(evt) {

  if (!evt) evt = window.event;

  if (evt.keyCode=="9" && showsuggest=="1") 
	{
		pickFirstSuggest();
  }

} 

/*************************************************************
	FUNCTION:	loadPage
	PURPOSE:	loads our initial page view when the module loads
*************************************************************/
function loadPage() 
{

	draftuid = "";

	setupToolbar();
	setupAttachmentListener();
	showAttachments();
	//showAttachList();
	loadEditor();

	//if there's an objectPath specified, use it
	if (ge("objectId").value.length > 0) 
	{
		var res = new Array();
		res.id = ge("objectId").value;
		res.type = ge("objectType").value;
		mbSelectObject(res);
	}			

	setFrameSize();

	ge("to").focus();

}

/*******************************************************************
  FUNCTION: loadEditor
  PURPOSE:  load the actual editor.  
  INPUTS:   curval -> html we'll populate the editor with
*******************************************************************/
function loadEditor(curval) {

    var ed = ge("editor_content");

    //create a new one
    ckeditor = CKEDITOR.replace('editor_content',
                  {
                    toolbar: 'Email',   
                    fullPage: true,
                    on:
                    {
                      instanceReady: function (ev) { 

												setFrameSize();

												if (curval)
												{
													if (ckeditor) ckeditor.setData(curval);
													else ed.value = curval;
												}

											}

                    }

                  });   

  clearSiteStatus();
 
}


function setupToolbar() {

	TOOLBAR.open();
	TOOLBAR.addGroup();
  TOOLBAR.add(_I18N_SEND,"sendEmail()");
  TOOLBAR.add(_I18N_ADDRESS_BOOK,"ADDRESSBOOK.load()");

	TOOLBAR.addGroup();

	//if it's later than ie8,it most like supports the HTML 5 file api
	if (document.addEventListener)
	{
		TOOLBAR.add(_I18N_ATTACH_FILE,"attachFile()");
	}
	//provide a link to a modal uploader for older browsers
	else
	{
		TOOLBAR.add(_I18N_ATTACH_FILE,"oldAttachFile()");
	}

  TOOLBAR.add(_I18N_ATTACH_DOCMGR_FILE,"selectDocmgrAttachment()");
  TOOLBAR.add(_I18N_LOAD_TEMPLATE,"loadTemplate()");

	TOOLBAR.close();

	//add the input form for newer browsers
	if (document.addEventListener)
	{

		//add the file form to the toolbar
		var fileform = createForm("file","attach");
		fileform.setAttribute("multiple","true");
		fileform.style.position = "absolute";
		fileform.style.top = "14px";
		fileform.setAttribute("size","1");
	
		fileform.style.visibility = "hidden";
	
		//add the upload form to the toolbar
		TOOLBAR.toolbar.appendChild(fileform);

	}

}


function setAttachBtnClass(mode)
{

	var ref = ge("attachFileBtn");

	if (mode=="on") ref.setActive();
	else ref.setActive();

}


//send the email
function sendEmail() {

	updateSiteStatus(_I18N_SENDING_EMAIL);

	ge("editor_content").value = "";

	var p = new PROTO();
	p.add("command","email_send_send");
	p.addDOM(document.pageForm);
	if (docattach.length > 0) p.add("docmgr_attach",docattach);
	p.add("editor_content",ckeditor.getData());
	p.post(API_URL,"writeSendEmail");

}

function writeSendEmail(data)
{

	clearSiteStatus();

	if (data.error) alert(data.error);
	else if (window.opener)
	{
		self.close();
	}
	else
	{
		location.href = "index.php?module=docmgr";
	}

}

//send the email
function saveDraft() {

	//don't save if there is no destination
	if (ge("to").value.length==0) return false;

	//if we get to here send the message
	updateSiteStatus("Saving Draft");

	ge("editor_content").value = "";

	var p = new PROTO();
	p.add("command","email_send_save");
	p.addDOM(document.pageForm);
	p.add("docmgr_attach",docattach.join(","));	
	p.add("editor_content",ckeditor.getData());
	p.add("uid",draftuid);
	p.post(API_URL,"writeSaveEmail");

}

function writeSaveEmail(data)
{

	clearSiteStatus();

	if (data.error) alert(data.error);
	else if (!data.uid) alert("Could not get uid of saved draft");
	else draftuid = data.uid;

}

function setFocus(e) {
	cursorfocus = e;
}

function clearFrame() {

  uploadframe.document.open();
  uploadframe.document.write("");
  uploadframe.document.close();

}

/*******************************************************************
  FUNCTION: setFrameSize
  PURPOSE:  sets the fckeditor to fill the window when resized
  INPUTS:   none
*******************************************************************/
function setFrameSize()
{

    if (!ckeditor) return false;

    var base = 106;
    var width = "100%";

    //add site menu and email header
    base += TOOLBAR.toolbar.getSize().y + ge("emailHeader").getSize().y;

    var height = (getWinHeight() - base);

    ckeditor.resize(width,height,true);

}
