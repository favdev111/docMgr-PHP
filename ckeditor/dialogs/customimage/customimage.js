

CKEDITOR.dialog.add( 'CustomImage', function( editor )
{
  return {

		title: 'Image Selector',

    minWidth : 750,
    minHeight : 400,
    contents : [

      {
        id : 'tab1',
        label : 'Select Image',
        title : 'Select Image',
        elements :
        [
          {
						type : 'html',
						html: '<div id="dialogContainer"></div>'
          }
        ]
      },
      {
        id : 'tab2',
        label : 'Upload Image',
        title : 'Upload Image',
        elements :
        [
          {
						type : 'html',
						html: '<iframe id="uploadframe" name="uploadframe" src="" style="display:none;width:200px;height:100px;"></iframe><div id="uploadContainer"></div>'
          }
        ]
      }

    ],

		onShow: function( data ) { runImageSelector(data,editor);},

		onOk: function()
					{

	  				var cursel = editor.getSelection();
  					var curelement = cursel.getSelectedElement();

						//if we are editing an element, just update it.
						if (curelement)
						{

							if (ge("txtUrl").value || curelement.hasAttribute("src")) curelement.setAttribute("src",ge("txtUrl").value);		
							if (ge("txtAlt").value || curelement.hasAttribute("alt")) curelement.setAttribute("alt",ge("txtAlt").value);		
							if (ge("txtWidth").value || curelement.hasAttribute("width")) curelement.setAttribute("width",ge("txtWidth").value + "px");		
							if (ge("txtHeight").value || curelement.hasAttribute("height")) curelement.setAttribute("height",ge("txtHeight").value + "px");		
							if (ge("txtBorder").value || curelement.hasAttribute("border")) curelement.setAttribute("border",ge("txtBorder").value + "px");		
							if (ge("cmbAlign").value || curelement.hasAttribute("align")) curelement.setAttribute("align",ge("cmbAlign").value);		
							if (ge("txtVSpace").value || curelement.hasAttribute("vspace")) curelement.setAttribute("vspace",ge("txtVSpace").value + "px");		
							if (ge("txtHSpace").value || curelement.hasAttribute("hspace")) curelement.setAttribute("hspace",ge("txtHSpace").value + "px");		
							
						//otherwise insert a new element
						} else
						{

							//create a new image
							var img = ce("img");
							if (ge("txtUrl").value) img.setAttribute("src",ge("txtUrl").value);		
							if (ge("txtAlt").value) img.setAttribute("alt",ge("txtAlt").value);		
							if (ge("txtWidth").value) img.setAttribute("width",ge("txtWidth").value + "px");		
							if (ge("txtHeight").value) img.setAttribute("height",ge("txtHeight").value + "px");		
							if (ge("txtBorder").value) img.setAttribute("border",ge("txtBorder").value + "px");		
							if (ge("cmbAlign").value) img.setAttribute("align",ge("cmbAlign").value);		
							if (ge("txtVSpace").value) img.setAttribute("vspace",ge("txtVSpace").value + "px");		
							if (ge("txtHSpace").value) img.setAttribute("hspace",ge("txtHSpace").value + "px");		

							//a hack because insertElement doesn't work for some reason
							var div = ce("div","","",img);
							editor.insertHtml(div.innerHTML);

						}

					}

	};

});


function runImageSelector(data,editor)
{

	var p = new PICKER(data,editor);
	p.run();

}

function PICKER(data,editor)
{

	var DATA = data;
	var EDITOR = editor;
	var CONTAINER = ge("dialogContainer");
	var UPLOADCONT = ge("uploadContainer");
	var FILELIST = "";
	var UPLOADSTAT = "";
	var API = "";
	var PIK = this;

  var cursel = editor.getSelection();
  var curelement = cursel.getSelectedElement();
	var curlink = curelement && curelement.getAscendant( 'a' );

	this.run = function()
	{

		//init our backend for manipulating files
		API = new this.DOCMGR();

		this.setup();
		if (curelement) this.populate();

		API.listFiles();

	};


	this.setup = function()
	{

		//image uploader
		clearElement(UPLOADCONT);
		if (document.all) UPLOADCONT.style.paddingTop = "15px";

		var udiv = ce("div","","uploadFileContainer");
		udiv.appendChild(ce("div","formHeader","","Select Files To Upload"));

		//container with our upload input form		
		var uform = ce("div","","uploadFileDiv");
		var up = createForm("file","uploadFileForm");
		setClass(up,"textboxSmall");
		up.onchange = createMethodReference(this,"addFile");
		uform.appendChild(up);

		udiv.appendChild(uform);

		//container with our upload list and the action button
		if (document.all) var form = ce("<form id=\"uploadFileText\" enctype=\"multipart/form-data\" method=\"POST\">");
		else
		{

			var form = ce("form","","uploadFileText");
			form.setAttribute("enctype","multipart/form-data");
			form.setAttribute("method","POST");

		}

		udiv.appendChild(form);

		var btn = createBtn("uploadBtn","Upload Files");
		btn.onclick = createMethodReference(this,"upload");
		udiv.appendChild(btn);
		
		UPLOADCONT.appendChild(udiv);

		//left column
		var lc = ce("div","","uploadColumn");

		var propdiv = ce("div","","imageProperties");	
	
		//image properties
		var cont = ce("div","","imgPreviewDiv");
		cont.appendChild(ce("div","formHeader","","Selected Image"));
		cont.appendChild(ce("div","","imgpreview"));
		propdiv.appendChild(cont);
	
		var cont = ce("div","","imgPropDiv");
		cont.appendChild(this.props("Image URL","txtUrl","30"));
		cont.appendChild(this.props("Alt Text","txtAlt","30"));
		cont.appendChild(this.props("Width","txtWidth","3"));
		cont.appendChild(this.props("Height","txtHeight","3"));
		cont.appendChild(this.props("Border","txtBorder","3"));
		cont.appendChild(this.props("Align","cmbAlign","5"));
		cont.appendChild(this.props("Vert. Spacing","txtVSpace","3"));
		cont.appendChild(this.props("Horiz. Spacing","txtHSpace","3"));
		propdiv.appendChild(cont);

		propdiv.appendChild(createCleaner());
		lc.appendChild(propdiv);	

		//right column
		var rc = ce("div","","browseColumn");
		var h = ce("div","browseHeader");
	
		//mode switcher	
		var sel = createSelect("displayMode");
		sel.onchange = createMethodReference(API,"listFiles");
		sel[0] = new Option("View As Thumbnail","thumbnail");
		sel[1] = new Option("View As List","list");
	
		h.appendChild(sel);
		h.appendChild(ce("div","formHeader","","Select File"));
	
		rc.appendChild(h);
		FILELIST = ce("div","","fileList");
		rc.appendChild(FILELIST);

		clearElement(CONTAINER);	
		if (document.all) CONTAINER.style.paddingTop = "15px";

		CONTAINER.appendChild(lc);
		CONTAINER.appendChild(rc);
		CONTAINER.appendChild(createCleaner());
	
	};

	this.setAttrib = function(attrib,field)
	{

		if (curelement.getAttribute(attrib)) ge(field).value = curelement.getAttribute(attrib);

	};

	this.populate = function()
	{

		this.setAttrib("src","txtUrl");
		this.setAttrib("alt","txtAlt");
		this.setAttrib("align","cmbAlign");
		this.setAttrib("width","txtWidth");
		this.setAttrib("height","txtHeight");
		this.setAttrib("border","txtBorder");
		this.setAttrib("vspace","txtVSpace");
		this.setAttrib("hspace","txtHSpace");

		this.updatePreview();

	};

	this.props = function(title,field,size)
	{
	
		var div = ce("div","imgprop");
		div.appendChild(ce("div","","",title));

		//custom for our alignment dropdown
		if (field=="cmbAlign")
		{

			var sel = createSelect(field);
			sel[0] = new Option("None","");
			sel[1] = new Option("Left","left");
			sel[2] = new Option("Center","center");
			sel[3] = new Option("Right","right");
			div.appendChild(sel);

		} else
		{
	
			var tb = createTextbox(field);
			tb.setAttribute("size",size);
			div.appendChild(tb);

		}
	
		return div;
	
	};

	this.switchMode = function(e)
	{

		var mode = ge("uploadMode").value;

		ge("uploadFileContainer").style.display = "none";		
		ge("imageProperties").style.display = "none";

		if (mode=="upload") ge("uploadFileContainer").style.display = "block";
		else ge("imageProperties").style.display = "block";

	};

	//add a file to our queue
	this.addFile = function()
	{
	
		//now create a text indicator to show the file
		var newfile = ce("li"); 
	
		//get our form containing the file we want to upload
		var uf = ge("uploadFileForm");
		var filediv = ge("uploadFileDiv");
	
		if (!uf || !uf.value) return false;
	
		//add a new hidden form element and the
		var txtdiv = ge("uploadFileText");
	
		//this is kind of awkward, but I couldn't get a file input to clone
		//properly in i.e.	Basically we move the file object used to select files
		//down and change it's name, then create a new one in it's place
	
		//copy the current one and change its name
		uf.setAttribute("name","uploadfile[]");	 
		uf.style.display="none";
		uf.style.left="0";
		uf.style.top="0"; 
		newfile.appendChild(uf);
	
		//clear the old and create a new onecreate a new one
		clearElement(filediv);
		var uploadfile = createForm("file","uploadFileForm");
		uploadfile.onchange = createMethodReference(this,"addFile");
		filediv.appendChild(uploadfile);
	
		//the link for clearing the file
		var img = createImg(THEME_PATH + "/images/icons/delete.png");
		img.onclick = createMethodReference(this,"removeUpload");
		newfile.appendChild(img);	

		//show the filename only	
		if (uf.value.indexOf("/") != -1) var stArr = uf.value.split("/");
		else var stArr = uf.value.split("\\");
		var len = stArr.length - 1;
		newfile.appendChild(ctnode(stArr[len]));
	
		//add to the parent
		txtdiv.appendChild(newfile);
	
	};

	this.removeUpload = function(e)
	{

		var row = getEventSrc(e).parentNode;
		row.parentNode.removeChild(row);

	};

	this.upload = function()
	{

		API.upload();

	};

	this.setUrl = function(e)
	{

		//get the url of the object
		var ref = getEventSrc(e);
		var url = ref.getAttribute("url");

		ge("txtUrl").value = url;		
		ge("txtWidth").value = ref.getAttribute("image_width");
		ge("txtHeight").value = ref.getAttribute("image_height");

		this.updatePreview();

	};

	this.updatePreview = function()
	{

		var ip =  ge("imgpreview");
		var url = ge("txtUrl").value;

		clearElement(ip);

		if (url.length > 0)
		{

			//preview it
			var img = createImg(url);
			ip.appendChild(img);		

		}

	};


	/**************************************************************
		CLASS:		DOCMGR
		PURPOSE:	subclass for dealing with path based operations
	**************************************************************/
	this.DOCMGR = function()
	{

		this.upload = function()
		{

			//stop any background processes from running
			siteFileUpload = 1;

			var form = ge("uploadFileText");

			//again, ie...
			if (document.all) 
			{
				window.frames["uploadframe"].document.open();
				window.frames["uploadframe"].document.write("");	
				window.frames["uploadframe"].document.close();
			} else 
			{
				clearElement(window.frames["uploadframe"].document);
			}
 
			//for safari
			closeKeepAlive();

			//this will happen n several stages.			First, we send the object info to the server, then when send the file itself
			//setup the xml
			var p = new PROTO();
			p.add("command","docmgr_file_multisave");
			p.add("parent_path",EDITOR.config.image_path);

			var url = API_URL + "?" + p.encodeData();

			//append a timestamp for ie to stop the god damn caching
			if (document.all) 
			{
				var d = new Date();
				url += "&timestamp=" + d.getTime();
			}

			form.action = url;
			form.target = "uploadframe";
			form.submit();

			updateSiteStatus(_I18N_PLEASEWAIT);

			FILELIST.innerHTML = "<div class=\"successMessage\">" + _I18N_PLEASEWAIT + "</div>\n";
			UPLOADSTAT = setInterval(createMethodReference(this,"checkUpload"),"100");

		};

		this.checkUpload = function()
		{

			//this was so much cleaner w/o ie handling
			if (document.all) 
			{

				var tmp = window.frames["uploadframe"].document;

				if (tmp.XMLDocument) 
				{
					var txt = tmp.XMLDocument.documentElement;
				}
				else return false;

			}
			else var txt = window.frames["uploadframe"].document;

			var err = txt.getElementsByTagName("error");
			var success = txt.getElementsByTagName("success");

			if (err.length > 0 || success.length > 0) 
			{

				clearSiteStatus();

				clearInterval(UPLOADSTAT);
				clearElement(ge("uploadFileText"));
				this.listFiles();

				//allow background processes
				siteFileUpload = 0;

				//again, ie...
				if (document.all) 
				{
					window.frames["uploadframe"].document.open();
					window.frames["uploadframe"].document.write("");
					window.frames["uploadframe"].document.close();
				} else 
				{
					clearElement(window.frames["uploadframe"].document);
				}

			}
 
		};


		this.listFiles = function()
		{

			FILELIST.innerHTML = "<div class=\"successMessage\">" + _I18N_PLEASEWAIT + "</div>";

			//setup our browse
			//setup the xml
			var p = new PROTO();
			p.add("command","docmgr_query_browse");
			p.add("path",EDITOR.config.image_path);
			p.add("show_image_size","1");
			p.add("mkdir","1");
			p.post(API_URL,createMethodReference(this,"writeFileList"));

		};

		this.writeFileList = function(data)
		{

			clearElement(FILELIST);

			if (data.error) alert(data.error);
			else if (!data.record) FILELIST.innerHTML = "<div class=\"errorMessage\">No Files Found</div>";
			else
			{

				var m = ge("displayMode").value;

				//skip non-web viewable images
				var imgarr = new Array("gif","png","jpg","jpeg");

				for (var i=0;i<data.record.length;i++)
				{

					if (m=="list") FILELIST.appendChild(this.displayList(data.record[i]));
					else FILELIST.appendChild(this.displayThumb(data.record[i]));

				}
	
			}

		};

		this.displayList = function(obj)
		{

			//the parent container
			var listdiv = ce("li","listcontainer");
			var fndiv = ce("div","listname");

			//setup the file url
			var fileurl = SITE_URL + "app/viewimage.php?objectId=" + obj.id + "&sessionId=" + SESSION_ID;

			var fnlink = ce("a","","",obj.name);
			fnlink.setAttribute("url",fileurl);
			fnlink.setAttribute("image_width",obj.image_width);
			fnlink.setAttribute("image_height",obj.image_height);
			fnlink.onclick = createMethodReference(PIK,"setUrl");
			fndiv.appendChild(fnlink);

			//the options
			var rlimg = createImg(THEME_PATH + "/images/icons/rotate_left.gif");
			rlimg.setAttribute("object_id",obj.id);
			rlimg.setAttribute("dir","left");
			rlimg.onclick = createMethodReference(this,"rotate");

			var rrimg = createImg(THEME_PATH + "/images/icons/rotate_right.gif");
			rrimg.setAttribute("object_id",obj.id);
			rrimg.setAttribute("dir","right");
			rrimg.onclick = createMethodReference(this,"rotate");

			var delimg = createImg(THEME_PATH + "/images/icons/trash.gif");
			delimg.setAttribute("object_id",obj.id);
			delimg.onclick = createMethodReference(this,"deleteFile");

			optdiv.appendChild(rlimg);
			optdiv.appendChild(rrimg);
			optdiv.appendChild(delimg);

			listdiv.appendChild(optdiv);
			listdiv.appendChild(fndiv); 

			return listdiv;

		};

		this.displayThumb = function(obj)
		{

			//the parent container
			var thumbdiv = ce("div","thumbcontainer");
			var filediv = ce("div","filecontainer");

			//setup our thumbnail url
  		var thumburl = SITE_URL + "app/showthumb.php?sessionId=" + SESSION_ID + "&objectId=" + obj.id;
  		thumburl += "&objDir=" + obj.level1 + "/" + obj.level2; 
  		thumburl += "&time=" + new Date().getTime();

			//setup the thumbnail
			var thumbimg = ce("img","thumbnail");
			thumbimg.setAttribute("src",thumburl);

			if (document.all) 
			{
						thumbimg.setAttribute("height","75");
						thumbimg.setAttribute("width","100");
			}
			filediv.appendChild(thumbimg);

			//setup the file url
			var d = new Date();
			var fileurl = SITE_URL + "app/viewimage.php?objectId=" + obj.id + "&sessionId=" + SESSION_ID;

			thumbimg.setAttribute("url",fileurl);
			thumbimg.setAttribute("image_width",obj.image_width);
			thumbimg.setAttribute("image_height",obj.image_height);
			thumbimg.onclick = createMethodReference(PIK,"setUrl");

			//the name
			var fndiv = ce("div","filename","",obj.name);
			var optdiv = ce("div","fileopt");

			var rlimg = createImg(THEME_PATH + "/images/icons/rotate_left.gif");
			rlimg.setAttribute("object_id",obj.id);
			rlimg.setAttribute("dir","left");
			rlimg.onclick = createMethodReference(this,"rotate");

			var rrimg = createImg(THEME_PATH + "/images/icons/rotate_right.gif");
			rrimg.setAttribute("object_id",obj.id);
			rrimg.setAttribute("dir","right");
			rrimg.onclick = createMethodReference(this,"rotate");

			var delimg = createImg(THEME_PATH + "/images/icons/trash.gif");
			delimg.setAttribute("object_id",obj.id);
			delimg.onclick = createMethodReference(this,"deleteFile");

			optdiv.appendChild(rlimg);
			optdiv.appendChild(rrimg);
			optdiv.appendChild(delimg);

			thumbdiv.appendChild(filediv);
			thumbdiv.appendChild(fndiv);			
			thumbdiv.appendChild(optdiv); 

			return thumbdiv;

		};

		this.rotate = function(e)
		{

			var ref = getEventSrc(e);
			var id = ref.getAttribute("object_id");
			var dir = ref.getAttribute("dir");

			FILELIST.innerHTML = "<div class=\"successMessage\">" + _I18N_PLEASEWAIT + "</div>";

			//setup the xml
			var p = new PROTO();
			p.add("command","docmgr_file_rotate");
			p.add("direction",dir);
			p.add("object_id",id);
			p.post(API_URL,createMethodReference(this,"writeFileEdit"));

		};

		this.deleteFile = function(e)
		{

			if (confirm("Are you sure you want to remove this file?"))
			{

				FILELIST.innerHTML = "<div class=\"successMessage\">" + _I18N_PLEASEWAIT + "</div>";

				var ref = getEventSrc(e);
				var id = ref.getAttribute("object_id");

				//setup the xml
				var p = new PROTO();
				p.add("command","docmgr_object_delete");
				p.add("object_id",id);
				p.post(API_URL,createMethodReference(this,"writeFileEdit"));

			}

		};

		this.writeFileEdit = function(data)
		{

			if (data.error) alert(data.error);
			else this.listFiles();

		};

	};
	
}
	
