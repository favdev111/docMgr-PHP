var content;
var curpage;

function loadPage()
{
	loadNavigation();
	WORKFLOW.load();
}

function loadNavigation()
{
	SIDEBAR.open();
	SIDEBAR.addGroup("Navigation");
	SIDEBAR.add(_I18N_CURRENT_WORKFLOWS,"WORKFLOW.load('current')");
	SIDEBAR.add(_I18N_WORKFLOW_HISTORY,"WORKFLOW.load('history')");
	SIDEBAR.add(_I18N_CURRENT_TASKS,"TASKS.load()");
	SIDEBAR.close();

	SIDEBAR.showCurrent('0');
}

