
var CHECKIN = new OBJECT_CHECKIN();

function OBJECT_CHECKIN()
{

	this.obj;			//for storing all data we retrieved from this object during the search
	this.progressMeter;
	this.progressText;
	this.totalSize;

	/**
		hands off viewing to appropriate method
		*/
	this.load = function(e,id)
	{

		e.cancelBubble = true;

    //get our object info from the results
    for (var i=0;i<BROWSE.results.length;i++)
    {
      if (BROWSE.results[i].id==id)
      {
        this.obj = BROWSE.results[i];
        break;
      }
    }

		MODAL.open(640,350,_I18N_CHECKIN_OBJECT);
		MODAL.addToolbarButtonRight(_I18N_SAVE,"CHECKIN.save()");
		MODAL.addForm("config/forms/objects/checkin.xml");

	};

	this.save = function()
	{

		var file = ge("uploadForm").files[0];
		CHECKIN.totalSize = file.size;

		var cont = ge("checkinProgressContainer");
		cont.style.display = "block";

    CHECKIN.progressMeter = ce("div","","progressStatus");
    cont.appendChild(CHECKIN.progressMeter);
  
    var detail = ce("div","","progressText");
    CHECKIN.progressText = ce("span","","currentUploadTotal");
    CHECKIN.progressText.appendChild(ctnode(size_format(file.size))); 
    detail.appendChild(CHECKIN.progressText);
    cont.appendChild(detail);

		closeKeepAlive();

    var p = new PROTO();
    p.add("command","docmgr_file_saveinputstream");
    p.add("name",file.name);
    p.add("object_id",CHECKIN.obj.id);
		p.add("unlock","1");
    p.addDOM(MODAL.container);

    p.upload(API_URL,file,"CHECKIN.uploadComplete","CHECKIN.uploadProgress");

  
	};

  this.uploadProgress = function(evt,file)
  {
  
    if (evt.lengthComputable)
    {
     
    	var loaded = evt.loaded;
  
      var percentComplete = Math.round(loaded/CHECKIN.totalSize * 100);
  
      CHECKIN.progressMeter.style.width = percentComplete + "%";
      CHECKIN.progressText.innerHTML = _I18N_UPLOADED + " " + size_format(loaded) + " " + _I18N_OF + " " + size_format(CHECKIN.totalSize);
  
    }
  
  };

  this.uploadComplete = function(data,file)
  {

    /* This event is raised when the server send back a response */
    if (data.error) 
    {
      clearSiteStatus();
      alert(data.error);
    }
    else
    {   
			clearSiteStatus();
			MODAL.hide();
			BROWSE.refresh();  
    }

  };

}


