
var timer;
var content;
var extensions;

function loadPage()
{

	content = ge("container");
	getExtensions();

	updateSiteStatus(_I18N_PLEASEWAIT);

	endReq("EDITOR.load()");

}

function getExtensions()
{

	var p = new PROTO();
  p.setProtocol("XML");
  p.post("config/extensions.xml","writeGetExtensions");

}

function writeGetExtensions(data)
{
	extensions = data;
}


