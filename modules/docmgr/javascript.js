var editBtn;
var actionBtn;
var trashBtn;
var shareBtn;
var everywhereRow;
var collectionRow;

function loadPage()
{
	loadToolbar();

	NAV.load();
	BROWSE.load();

}

function loadToolbar()
{

	TOOLBAR.open();


	TOOLBAR.addSearch("SEARCH.ajaxSearch()",_I18N_SEARCH);
  everywhereRow = TOOLBAR.addSearchSubmenu(_I18N_EVERYWHERE,"SEARCH.setSearchRange('everywhere')",1);
  collectionRow = TOOLBAR.addSearchSubmenu(_I18N_THIS_COLLECTION,"SEARCH.setSearchRange('collection')");

	//advanced search
	TOOLBAR.addGroup();
	TOOLBAR.add(_I18N_FILTER,"ACTIONS.addFilter()");

	//view submenu
	TOOLBAR.add(_I18N_VIEW);
	TOOLBAR.addSubmenu(_I18N_LIST,"BROWSE.switchView('list')",1,1);
	TOOLBAR.addSubmenu(_I18N_THUMBNAILS,"BROWSE.switchView('thumbnail')",1);

	//action and action submenu
	TOOLBAR.add(_I18N_ADD);

	TOOLBAR.addSubmenu(_I18N_ADD_FILES,"UPLOAD.load()");

	TOOLBAR.addSubmenu(_I18N_ADD_COLLECTION,"ACTIONS.addCollection()");
	TOOLBAR.addSubmenu(_I18N_ADD_DM_DOCUMENT,"ACTIONS.addDocument()");
	TOOLBAR.addSubmenu(_I18N_ADD_TEXT_FILE,"ACTIONS.addTextFile()");
	//TOOLBAR.addSubmenu(_I18N_ADD_OFFICE_DOCUMENT);
	TOOLBAR.addSubmenu(_I18N_ADD_WEBSITE,"ACTIONS.addURL()");

	editBtn = TOOLBAR.add(_I18N_EDIT,"BROWSE.cycleMode()");

	TOOLBAR.addGroup();


	trashBtn = TOOLBAR.add(_I18N_TRASH);
	TOOLBAR.addSubmenu(_I18N_TRASH_SELECTED,"ACTIONS.trash()");
	TOOLBAR.addSubmenu(_I18N_DELETE_PERMANENT,"ACTIONS.remove()");
	TOOLBAR.addSubmenu(_I18N_EMPTY_TRASH,"ACTIONS.emptyTrash()");

	actionBtn = TOOLBAR.add(_I18N_ACTIONS);
	TOOLBAR.addSubmenu(_I18N_MOVE,"ACTIONS.move()");
	TOOLBAR.addSubmenu(_I18N_CONVERT_FILES,"CONVERT.loadBatch()");
	TOOLBAR.addSubmenu(_I18N_MERGE_PDFS,"PDFEDIT.merge()");
	TOOLBAR.addSubmenu(_I18N_ADD_WORKFLOW,"ACTIONS.createWorkflow()");

	//share and share submenu
	shareBtn = TOOLBAR.add(_I18N_SHARE);
	TOOLBAR.addSubmenu(_I18N_SHARING_SETTINGS,"SHARE.load()");
	TOOLBAR.addSubmenu(_I18N_EMAIL_ATTACH,"ACTIONS.email()");
	TOOLBAR.addSubmenu(_I18N_EMAIL_TIME_LINK,"ACTIONS.viewLink()");
	TOOLBAR.addSubmenu(_I18N_EMAIL_DM_LINK,"ACTIONS.propLink()");

	trashBtn.style.display = "none";
	actionBtn.style.display = "none";
	shareBtn.style.display = "none";

	//utilities
	TOOLBAR.close();

	//set our current values if necessary
	if (isData(sessionStorage["docmgr_search_string"])) 
	{
		ge("siteToolbarSearch").value = sessionStorage["docmgr_search_string"];
	}

	if (isData(sessionStorage["docmgr_search_range"]) && sessionStorage["docmgr_search_range"]=="collection")
	{
		PULLDOWN.selectRow(collectionRow);
	}	

}

function closeWindow()
{
	//will browse or search based on our current mode
	BROWSE.refresh();
}

