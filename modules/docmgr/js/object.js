
var OBJECT = new DOCMGR_OBJECT();

function DOCMGR_OBJECT()
{

	/**
		determines if the passed locked object is owned by the current user
		*/
	this.zip = function(e,id)
	{

		e.cancelBubble = true;

    //setup the request
    var p = new PROTO();
    p.add("command","docmgr_collection_zip");
    p.add("object_id",id);
		p.redirect(API_URL);

	};
	
	/***********************************
		object option called methods
	***********************************/

	
	 
}
	
	