
var KEYWORDS = new OBJECT_KEYWORDS();

function OBJECT_KEYWORDS()
{

	this.obj;			//for storing all data we retrieved from this object during the search
	this.searchTimer; 

	/**
		hands off viewing to appropriate method
		*/
	this.load = function()
	{

		this.obj = PROPERTIES.obj;

		MODAL.open(640,480,_I18N_KEYWORDS);
		MODAL.addToolbarButtonRight(_I18N_SAVE,"KEYWORDS.save()");

		//add the header
    MODAL.openRecordHeader();   
    MODAL.addHeaderCell(_I18N_NAME,"keywordNameCell");
    MODAL.addHeaderCell(_I18N_VALUE,"keywordValueCell");
		MODAL.closeRecordHeader();

		KEYWORDS.search();

	}

	this.search = function()
	{
	
	  updateSiteStatus(_I18N_PLEASEWAIT);

		//load our logs
		var p = new PROTO();
		p.add("command","docmgr_keyword_search");
		p.add("object_id",KEYWORDS.obj.id);
		p.post(API_URL,"KEYWORDS.writeSearch");
	
	};

	this.writeSearch = function(data)
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

				var curkey = data.record[i];

				var row = MODAL.openRecordRow();
				row.appendChild(createHidden("keyword_id[]",curkey.id));
				MODAL.addRecordCell(curkey.name,"keywordNameCell");
				MODAL.addRecordCell(KEYWORDS.createForm(curkey),"keywordValueCell");
				MODAL.closeRecordRow();

			}

		}	

		MODAL.closeRecords();
	
	};	

	this.createForm = function(curkey)
	{

		var tb; 

		//display the options
		if (curkey.type=="select")
		{

		  tb = createSelect("keyword_value[]");
		  if (curkey.option)
		  {
		  	for (var c=0;c<curkey.option.length;c++)
		  	{
					tb[c] = new Option(curkey.option[c].name,curkey.option[c].id);
		  	}
			}

		} 
		else 
		{
			//search string
			tb = createTextbox("keyword_value[]");
		}

		tb.setAttribute("keyword_id",curkey.id);
		tb.setAttribute("required",curkey.required);

		if (KEYWORDS.obj.bitmask_text!="admin") tb.disabled = true;

		//if there is a value for this keyword for this object, set it
		if (isData(curkey.object_value)) tb.value = curkey.object_value;

		return tb;
	
	};

	this.save = function()
	{
		
		if (!KEYWORDS.validate())
		{
			alert(_I18N_REQUIRED_KEYWORD_ERROR);
			return false;
		}

		updateSiteStatus(_I18N_SAVING);
		var p = new PROTO();
		p.add("command","docmgr_keyword_save");
		p.add("object_id",KEYWORDS.obj.id);
		p.addDOM(MODAL.container);
		p.post(API_URL,"KEYWORDS.writeSave");

	};

	this.writeSave = function(data)
	{
		clearSiteStatus();
		if (data.error) alert(data.error);
	};

	this.validate = function()
	{

		var ret = true;

		var arr = MODAL.container.getElementsByTagName("input");

		for (var i=0;i<arr.length;i++)
		{

			var kid = arr[i].getAttribute("keyword_id");
			var req = arr[i].getAttribute("required");

			if (kid && req && req=="t" && arr[i].value.length=="0")
			{
				ret = false;
				break;
			}

		}

		return ret;

	};

}





