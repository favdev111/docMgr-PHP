
//for compatibility
function loadForms(file,deffile,fhandler,dhandler)
{
	EFORM.loadDefinitions(file,deffile,fhandler,dhandler);
}

var EFORM = new SITE_EFORM();

function SITE_EFORM()
{

	this.data;
	this.formFile;										//xml file that contains our form definitions
	this.formDefinitionFile;					//when formFile contains a layout only, our form definitions live here.  Only used with prospects right now
	this.formHandler;									//function we pass the completed form to
	this.dataHandler;									//function we call to get data for our form
	this.parameters;

	/**
		convenience method that we can call and create a form with our 
		most commonly used options
		*/
	this.load = function(file,fhandler,dhandler)
	{

		this.reset();

		//always required
		this.formFile = file;

		//optional
		if (fhandler) this.formHandler = fhandler;
		if (dhandler) this.dataHandler = dhandler;

		//all setup, load the form
		this.process();

	};

	this.reset = function()
	{
		this.data = null;
		this.formFile = null;
		this.formDefinitionFile = null;
		this.formHandler = null;
		this.dataHandler = null;
		this.parameters = null;
	};

	this.loadDefinitions = function(file,deffile,fhandler,dhandler)
	{
		this.reset();

		//always required
		this.formFile = file;
		this.formDefinitionFile = deffile;

		//optional
		if (fhandler) this.formHandler = fhandler;
		if (dhandler) this.dataHandler = dhandler;

		//all setup, load the form
		this.process();

	};

	/**
		actually loads the form.  We can call this directly if we need to set
		a bunch of custom parameters
		*/
	this.process = function()
	{

		var p = new PROTO();
		p.add("command","eform_forms_load");
		p.add("file",this.formFile);
	
		//pass any additional parameters to the api during api calls
		if (this.parameters) p.add("api_parameters",this.parameters);

		//use a form definition file
		if (this.formDefinitionFile) 
		{
			p.add("definition_file",this.formDefinitionFile);
		}

		//return xml response
		p.setDecode(false);

		//kick it
		p.post(API_URL,createMethodReference(this,"writeProcess"));

	};

	/**
		takes our populated response from eform api, turns it into DOM objects
		and passes it to our form handler
		*/
	this.writeProcess = function(xml)
	{

		//something bad happened
		if (typeof(xml)=="string")
		{
			var arr = JSON.decode(xml);
			alert(arr.body.error);
			return false;
		}

		//get our data first from our data handler function.  we will
		//then use this data to populate the forms we create
		if (this.dataHandler)
		{
			//ret = this.dataHandler();
			var func = eval(this.dataHandler);
			var ret = func();

			if (ret) this.data = ret;
			else this.data = null;

		}

		//proccess our response
		var root = ce("div","eForm");
		this.processElements(root,xml);

		//call the handler function for our resulting div
		this.formHandler = eval(this.formHandler);
		this.formHandler(root);

	};

	/****************************************************************
		FUNCTION: processElements
		PURPOSE:  processes all elements from our div xml file
		INPUT:	  dataNode -> the firstChild of the data xmlf ile
		RETURN:		html element that can be appended to another element
	*****************************************************************/	

	this.processElements = function(mydiv,dataNode) 
	{

		var i = 0;

		while (dataNode.childNodes[i]) 
		{
		
	    var objNode = dataNode.childNodes[i];

	    if (objNode.nodeType!=1) 
			{
				i++;
				continue;
			}

			//process div container
			if (objNode.nodeName=="div") 
			{

				var div = ce("div");
	
				//transfer id and class elements over to our new div
				var objid = objNode.getAttribute("id");
				var objclass = objNode.getAttribute("class");
				var objclick = objNode.getAttribute("onclick");

				if (objid) div.setAttribute("id",objid);						
				if (objclass) setClass(div,objclass);
				if (objclick) setClick(div,objclick);

				//if there are more elements under this one, process them
				//if (XML.isArray(objNode)) 
				//if (USER_ID==1000) alert(objNode.hasChildNodes() + ":" + objNode.tagName + ":" + objNode.childNodes.length);

				if (objNode.hasChildNodes())
				{
					//it's a div w/ text in it
					if (objNode.childNodes.length==1 && (BROWSER!="ie" || (BROWSER=="ie"  && BROWSERVERSION >= 10)))
					{
						div.appendChild(ctnode(objNode.firstChild.nodeValue));
					}
					else
					{
						var ret = this.processElements(div,objNode);
						if (ret) div.appendChild(ret);
					}
				} 
				else if (objNode.firstChild && objNode.firstChild.nodeValue) 
				{
					div.appendChild(ctnode(objNode.firstChild.nodeValue));
				}
	
				mydiv.appendChild(div);
	
			//process form
			} 
			else if (objNode.nodeName=="form") 
			{
	
				//convert the form data into an array
				var curform = XML.decode(objNode);
				var curdiv;
	
				//call the appropriate form creator based on the returned type
				//the function should be called "_<formname>Form"
		
				if (curform.type) 
				{
					//overwrite the title with a foreign language replacement if possible
					if ( typeof(window[curform.lang]) != 'undefined' ) curform.title = window[curform.lang];

					curdiv = this[curform.type](curform);
				}
	
				if (curdiv) mydiv.appendChild(curdiv);
	
			}
	
			i++;
	
		}
	
	};
	
	
	/****************************************************************
		FUNCTION: _textboxForm
		PURPOSE:  creates a text input form
		INPUT:	  curform -> xml data in array form from the
												 xml config file
		RETURNS:	div containing the created div
	*****************************************************************/	
	
	this.textbox = function(curform)
	{
	
		//use a multiform class if set
		if (curform.display && curform.display=="multiform") var cn = "eformInputMultiTitleCell";
		else var cn = "eformInputTitleCell";
	
		//load the main cell and the header
		var mydiv = ce("div","eformInputRow",curform.name + "FormDiv");
		var header = ce("div",cn,curform.name + "FormDivTitle",curform.title);
	
		var size;
		if (curform.size) size = curform.size;
	
		//see if there's data to populate this form
		var curval;
		if (this.data && isData(this.data[curform.data])) curval = this.data[curform.data];
		else if (isData(curform.defaultval)) curval = curform.defaultval;
	
		var form = createTextbox(curform.name,curval,size);
	
		//optional settings, set by corresponding xml tags for the form
		if (curform.disabled) form.disabled = true;
		if (curform.readonly) {
			form.readonly = true;
			form.setAttribute("readonly","true");
		}

		if (curform.onkeyup) setKeyUp(form,curform.onkeyup);
		if (curform.onkeydown) setKeyUp(form,curform.onkeydown);
		if (curform.onchange) setChange(form,curform.onchange);
		if (curform.onclick) setClick(form,curform.onclick);
		if (curform.autocomplete) form.setAttribute("autocomplete",curform.autocomplete);
		if (curform.onfocus) setFocus(form,curform.onfocus);
		if (curform.onblur) setBlur(form,curform.onblur);
		if (curform.maxlength) form.setAttribute("maxlength",curform.maxlength);
		if (curform.tabindex) form.setAttribute("tabindex",curform.tabindex);
		
		//put it together
		mydiv.appendChild(header);
		mydiv.appendChild(ce("div","eformInputFormCell","",form));
		mydiv.appendChild(createCleaner());
	
		return mydiv;
	
	};
	
	/****************************************************************
		FUNCTION: _hiddenForm
		PURPOSE:  creates a text input form
		INPUT:	  curform -> xml data in array form from the
												 xml config file
		RETURNS:	div containing the created div
	*****************************************************************/	
	
	this.hidden = function(curform)
	{
	
		//see if there's data to populate this form
		var curval;
		if (this.data && isData(this.data[curform.data])) curval = this.data[curform.data];
		else if (isData(curform.defaultval)) curval = curform.defaultval;
	
		var form = createHidden(curform.name,curval);
	
		return form;
	
	};
	
	/****************************************************************
		FUNCTION: _passwordForm
		PURPOSE:  creates a text input form
		INPUT:	  curform -> xml data in array form from the
												 xml config file
		RETURNS:	div containing the created div
	*****************************************************************/	
	
	this.password = function(curform)
	{
	
		//use a multiform class if set
		if (curform.display && curform.display=="multiform") var cn = "eformInputMultiTitleCell";
		else var cn = "eformInputTitleCell";
	
		//load the main cell and the header
		var mydiv = ce("div","eformInputRow",curform.name + "FormDiv");
		var header = ce("div",cn,curform.name + "FormDivTitle",curform.title);
	
		var size;
		if (curform.size) size = curform.size;
	
		//see if there's data to populate this form
		var curval;
		if (this.data && isData(this.data[curform.data])) curval = this.data[curform.data];
		else if (isData(curform.defaultval)) curval = curform.defaultval;
	
		var form = createPassword(curform.name,curval,size);
	
		//optional settings, set by corresponding xml tags for the form
		if (curform.disabled) form.disabled = true;
		if (curform.readonly) {
			form.readonly = true;
			form.setAttribute("readonly","true");
		}
		if (curform.onkeyup) setKeyUp(form,curform.onkeyup);
		if (curform.onchange) setChange(form,curform.onchange);
		if (curform.onclick) setClick(form,curform.onclick);
		if (curform.autocomplete) form.setAttribute("autocomplete",curform.autocomplete);
		if (curform.onfocus) setFocus(form,curform.onfocus);
		if (curform.onblur) setBlur(form,curform.onblur);
		if (curform.maxlength) form.setAttribute("maxlength",curform.maxlength);
		if (curform.tabindex) form.setAttribute("tabindex",curform.tabindex);
		
		//put it together
		mydiv.appendChild(header);
		mydiv.appendChild(ce("div","eformInputFormCell","",form));
		mydiv.appendChild(createCleaner());
	
		return mydiv;
	
	};
	
	/****************************************************************
		FUNCTION: _fileForm
		PURPOSE:  creates a text input form
		INPUT:	  curform -> xml data in array form from the
												 xml config file
		RETURNS:	div containing the created div
	*****************************************************************/	

	this.file = function(curform)
	{
	
		//use a multiform class if set
		if (curform.display && curform.display=="multiform") var cn = "eformInputMultiTitleCell";
		else var cn = "eformInputTitleCell";
	
		//load the main cell and the header
		var mydiv = ce("div","eformInputRow",curform.name + "FormDiv");
		var header = ce("div",cn,curform.name + "FormDivTitle",curform.title);
	
		var size;
		if (curform.size) size = curform.size;
	
		//see if there's data to populate this form
		var curval;
		if (this.data && isData(this.data[curform.data])) curval = this.data[curform.data];
		else if (isData(curform.defaultval)) curval = curform.defaultval;
	
		var form = createTextbox(curform.name,curval,size);
	
		//create the base form
		var form = createForm("file",curform.name);
		if (curval) form.setAttribute("value",curval); 
		if (size) curform.setAttribute("size",size);
	
		//optional settings, set by corresponding xml tags for the form
		if (curform.disabled) form.disabled = true;
		if (curform.readonly) {
			form.readonly = true;
			form.setAttribute("readonly","true");
		}
		if (curform.onkeyup) setKeyUp(form,curform.onkeyup);
		if (curform.onchange) setChange(form,curform.onchange);
		if (curform.onclick) setClick(form,curform.onclick);
		if (curform.autocomplete) form.setAttribute("autocomplete",curform.autocomplete);
		if (curform.onfocus) setFocus(form,curform.onfocus);
		if (curform.onblur) setBlur(form,curform.onblur);
		if (curform.maxlength) form.setAttribute("maxlength",curform.maxlength);
		if (curform.tabindex) form.setAttribute("tabindex",curform.tabindex);
		
		//put it together
		mydiv.appendChild(header);
		mydiv.appendChild(ce("div","eformInputFormCell","",form));
		mydiv.appendChild(createCleaner());
	
		return mydiv;
	
	};
	
	/****************************************************************
		FUNCTION: _textareaForm
		PURPOSE:  creates a text input form in disabled mode
		INPUT:	  curform -> xml data in array form from the
												 xml config file
		RETURNS:	div containing the created div
	*****************************************************************/	
	
	this.textarea = function(curform)
	{

		//use a multiform class if set
		if (curform.display && curform.display=="multiform") var cn = "eformInputMultiTitleCell";
		else var cn = "eformInputTitleCell";
	
		//load the main cell and the header
		var mydiv = ce("div","eformInputRow textarea",curform.name + "FormDiv");
		var header = ce("div",cn,curform.name + "FormDivTitle",curform.title);
	
		var size;
		if (curform.size) size = curform.size;
	
		//see if there's data to populate this form
		var curval;
		if (this.data && isData(this.data[curform.data])) curval = this.data[curform.data];
		else if (isData(curform.defaultval)) curval = curform.defaultval;
	
		var form = createTextarea(curform.name,curval,size);
	
		//optional settings, set by corresponding xml tags for the form
		if (curform.disabled) form.disabled = true;
		if (curform.readonly) 
		{
			form.readonly = true;
			form.setAttribute("readonly","true");
		}
		if (curform.onkeyup) setKeyUp(form,curform.onkeyup);
		if (curform.onchange) setChange(form,curform.onchange);
		if (curform.onclick) setClick(form,curform.onclick);
		if (curform.autocomplete) form.setAttribute("autocomplete",curform.autocomplete);
		if (curform.onfocus) setFocus(form,curform.onfocus);
		if (curform.onblur) setBlur(form,curform.onblur);
		if (curform.maxlength) form.setAttribute("maxlength",curform.maxlength);
		if (curform.tabindex) form.setAttribute("tabindex",curform.tabindex);
		
		//put it together
		mydiv.appendChild(header);
		mydiv.appendChild(ce("div","eformInputFormCell","",form));
	
		mydiv.appendChild(createCleaner());
	
		return mydiv;
	
	};
	
	
	/****************************************************************
		FUNCTION: _selectForm
		PURPOSE:  creates a text input form in disabled mode
		INPUT:	  curform -> xml data in array form from the
												 xml config file
		RETURNS:	div containing the created div
	*****************************************************************/	

	this.select = function(curform)
	{
	
		var i;
	
		//use a multiform class if set
		if (curform.display && curform.display=="multiform") var cn = "eformInputMultiTitleCell";
		else var cn = "eformInputTitleCell";
	
		//load the main cell and the header
		var mydiv = ce("div","eformInputRow",curform.name + "FormDiv");
		var header = ce("div",cn,curform.name + "FormDivTitle",curform.title);
	
		var sel = createSelect(curform.name);
		if (curform.disabled) sel.disabled = true;
		if (curform.onchange) setChange(sel,curform.onchange);
		if (curform.onclick) setClick(sel,curform.onclick);
		if (curform.onfocus) setFocus(sel,curform.onfocus);
		if (curform.onblur) setBlur(sel,curform.onblur);
	  if (curform.multiple) sel.multiple = true;
	  if (curform.size) sel.setAttribute("size",curform.size);
	
		//if we returned records, insert them into the form
		if (curform.option) 
		{
			for (i=0;i<curform.option.length;i++)
				sel[i] = new Option(curform.option[i].title,curform.option[i].data);
		}
	
		//see if there's data to populate this form
		var curval;
		if (this.data && isData(this.data[curform.data])) curval = this.data[curform.data];
		else if (isData(curform.defaultval)) curval = curform.defaultval;
	
		if (curval) sel.value = curval;
	
		mydiv.appendChild(header);
		mydiv.appendChild(ce("div","eformInputFormCell","",sel));	
		mydiv.appendChild(createCleaner());
	
		return mydiv;
	
	};
	
	
	/****************************************************************
		FUNCTION: _checkboxForm
		PURPOSE:  creates a checkbox form
		INPUT:	  curform -> xml data in array form from the
												 xml config file
		RETURNS:	div containing the created div
	*****************************************************************/	
	
	this.checkbox = function(curform,radio)
	{
	
		var i;
	
		//use a multiform class if set
		if (curform.display && curform.display=="multiform") var cn = "eformInputMultiTitleCell";
		else var cn = "eformInputTitleCell";
	
		//load the main cell and the header
		var mydiv = ce("div","eformInputRow",curform.name + "FormDiv");
		var header = ce("div",cn,curform.name + "FormDivTitle",curform.title);
	
		//see if there's data to populate this form.  this should always be an array for checkboxes
		var curval;
		if (this.data && isData(this.data[curform.data])) curval = this.data[curform.data];
		else if (isData(curform.defaultval)) curval = curform.defaultval;
	
		//container for all the boxes
		var cont = ce("div","eformInputFormCell")
	
		if (curform.option)
		{
	
			for (var i=0;i<curform.option.length;i++)
			{
				//a row for each
				var row = ce("div","eformInputCheckboxRow");
	
				if (radio) var cb = createRadio(curform.name,curform.option[i].data);
				else var cb = createCheckbox(curform.name,curform.option[i].data);		
	
				if (curform.onclick) setClick(cb,curform.onclick);
		
				row.appendChild(cb);
				row.appendChild(ctnode(curform.option[i].title));
		
				cont.appendChild(row);
		
				if (curval)
				{
					//see if this has already been selected
					if (arraySearch(curform.option[i].data,curval)!=-1) cb.checked = true;
				}
		
			}	
	
		}
	
		mydiv.appendChild(header);
		mydiv.appendChild(cont);
		mydiv.appendChild(createCleaner());
	
	
		return mydiv;
	
	};
	
	/****************************************************************
		FUNCTION: _radioForm
		PURPOSE:  creates a radio form.  basically a pointer to the
							_checkboxForm
		INPUT:	  curform -> xml data in array form from the
												 xml config file
		RETURNS:	div containing the created div
	*****************************************************************/	
	
	this.radio = function(data)
	{
		return this.checkbox(data,1);
	};
	
	/****************************************************************
		FUNCTION: _yesnoForm
		PURPOSE:  creates the yesno select form
		INPUT:	  data -> data for the current prospect
	*****************************************************************/	
	
	this.yesno = function(curform)
	{
	
		var option = new Array();
	
		var opt1 = new Array();
		opt1.title = "Yes";
		opt1.data = "t";
	
		var opt2 = new Array();
		opt2.title = "No";
		opt2.data = "f";
	
		option.push(opt1);
		option.push(opt2);
	
		curform.option = option;
	
		return this.select(curform);
		
	};
	
	
	/****************************************************************
	  FUNCTION: _dateForm
	  PURPOSE:  creates a date  form
	  INPUT:    curform -> xml data in array form from the
	                       xml config file
	  RETURNS:  html object containing the created div
	*****************************************************************/
	
	this.date = function(curform)
	{
	
		//just inherit from the textbox form
		var mydiv = this.textbox(curform);
	
		//add the image to the end of the form
		var tb = mydiv.getElementsByTagName("input")[0];
	
		if (isData(this.data))
		{
			if (isData(this.data[curform.data])) 
			{
				tb.value = dateView(this.data[curform.data],1);
			}
		}
		else if (curform.defaultval=="NOW")
		{
			tb.value = dateView(new Date().toISOString(),1);
		}

		if (!curform.readonly)
		{
	    new Picker.Date(tb, {
	      timePicker: false,
	      positionOffset: {x: 5, y: 0},
	      pickerClass: 'datepicker_vista',
	      useFadeInOut: !Browser.ie
	    });
		}

	  return mydiv;
	  
	}; 
	
	this.datetime = function(curform)
	{

		var curDate;
	
		//just inherit from the textbox form
		var mydiv = this.textbox(curform);
		mydiv.style.whiteSpace = "nowrap";	
		var tb = mydiv.getElementsByTagName("input")[0];
	
		if (isData(this.data))
		{
			if (isData(this.data[curform.data])) 
			{
				//var str = dateNormalize(this.data[curform.data]);
				curDate = new XDate(this.data[curform.data]);
			}
		}
		else if (curform.defaultval=="NOW")
		{
			curDate = new XDate();
		}

    new Picker.Date(tb, {
      timePicker: false,
      positionOffset: {x: 5, y: 0},
      pickerClass: 'datepicker_vista',
      useFadeInOut: !Browser.ie
    });


		var ref = mydiv.getElementsByTagName("div")[1];

		var hform = createTextbox(curform.name + "_hour");
		var mform = createTextbox(curform.name + "_minute");
		hform.size = 2;
		hform.setAttribute("maxlength","2");
		mform.size = 2;
		mform.setAttribute("maxlength","2");
	
		var period = createSelect(curform.name + "_period");
		period[0] = new Option("am","am");
		period[1] = new Option("pm","pm");

		tb.style.width = "70px";
		tb.style.marginRight = "5px";

		hform.style.width = "16px";
		hform.style.marginLeft = "5px";
		mform.style.marginLeft = "0px";
		mform.style.width = "16px";
		period.style.marginLeft = "5px";
		ref.appendChild(ce("span","timeSep",""," at "));
		ref.appendChild(hform);
		ref.appendChild(ce("span","timeSep","",":"));
		ref.appendChild(mform);
		ref.appendChild(period);
	
		//figure out the values
		if (curDate)
		{

			//set the value for our textbox
			tb.value = dateOnlyView(curDate.toISOString(),1);

			//convert to a local string so we know what the local time is
			var time = curDate.toLocaleTimeString();

			//get our hours, minutes and period of the day
			var components = time.split(" ");
			var timeComponents = components[0].split(":");
			var hour = timeComponents[0];
			var min = timeComponents[1];

			//24 hour mode
			if (components.length==1)
			{
				var pval = "am";

				if (parseInt(hour) > 12)
				{
					pval = "pm";
					hour = String(parseInt(hour) - 12);					
				}
				else if (parseInt(hour)==0)
				{
					hour = "12";
				}
			}
			else
			{
				var pval = components[1].toLowerCase();
			}

			if (hour.length < 2) hour = "0" + hour;			
			if (min.length < 2) min = "0" + min;			

			//put our values 
			hform.value = hour;
			mform.value = min;
			period.value = pval;
	
		}


	  return mydiv;
	  
	}; 
	
	/****************************************************************
		FUNCTION: _pickerForm
		PURPOSE:  creates a text input form
		INPUT:	  curform -> xml data in array form from the
												 xml config file
		RETURNS:	div containing the created div
	*****************************************************************/	
	
	this.picker = function(curform)
	{
	
		//load the main cell and the header
		var mydiv = ce("div","eformInputRow",curform.name + "FormDiv");
		var header = ce("div","eformInputTitleCell",curform.name + "FormDivTitle",curform.title);
		var formcell = ce("div","eformInputFormCell");
	
		var size;
		if (curform.size) size = curform.size;
	
		//see if there's data to populate this form
		var hideval = "";
		var showval = "";
	
		if (this.data)
		{
	
			if (isData(this.data[curform.data_field])) hideval = this.data[curform.data_field];
			if (isData(this.data[curform.title_field])) showval = this.data[curform.title_field];
	
		}
	
		var form = createTextbox(curform.title_field,showval,size);
		form.readonly = true;
		form.disabled = true;
	
	  //the hidden box
	  var input = createHidden(curform.data_field,hideval);
	
	  var img = createImg(THEME_PATH + "/images/icons/browse.png");
		if (curform.picker_descripiton) img.setAttribute("title",curform.picker_description);
		if (curform.picker) setClick(img,curform.picker);
	
		if (curform.onchange) setChange(form,curform.onchange);
		if (curform.onclick) setClick(form,curform.onclick);
		if (curform.onfocus) setFocus(form,curform.onfocus);
		if (curform.onblur) setBlur(form,curform.onblur);
		if (curform.tabindex) form.setAttribute("tabindex",curform.tabindex);
	
		formcell.appendChild(form);
		formcell.appendChild(input);
		formcell.appendChild(img);
		
		//put it together
		mydiv.appendChild(header);
		mydiv.appendChild(formcell);
		mydiv.appendChild(createCleaner());
		
		return mydiv;
	
	};

	/****************************************************************
		FUNCTION: _timeForm
		PURPOSE:  creates a date select form
		INPUT:	  curform -> xml data in array form from the
												 xml config file
		RETURNS:	html object containing the created div
	*****************************************************************/	
	
	this.time = function(curform)
	{
	
		//load the main cell and the header
		var mydiv = ce("div","eformInputRow",curform.name + "FormDiv");
		var header = ce("div","eformInputTitleCell",curform.name + "FormDivTitle",curform.title);
	
		var curval = "";
		if (this.data && isData(this.data[curform.data])) curval = this.data[curform.data];
		else if (isData(curform.defaultval)) 
		{
			if (curform.defaultval=="NOW") 
			{
				var d = new Date();
				curval = d.getHours() + ":" + d.getMinutes();
			} 
			else 
			{
				curval = curform.defaultval;
			}
		}
	
		//create our hours and minutes.  the forms will have the form name
		//+ "Hour" or + "Minute"
	
		var hform = createTextbox(curform.name + "Hour");
		var mform = createTextbox(curform.name + "Minute");
		hform.size = 2;
		hform.setAttribute("maxlength","2");
		mform.size = 2;
		mform.setAttribute("maxlength","2");
	
		var period = createSelect(curform.name + "Period");
		period[0] = new Option("am","am");
		period[1] = new Option("pm","pm");
	
		//figure out the values
		if (curval) 
		{
	
			var valarr = curval.split(":");
			var hour = parseInt(valarr[0]);
			var min = parseInt(valarr[1]);
	
			//reformat our time to normalcy
			if (hour==0) {
				hour = 12;
				pval = "am";
			} else if (hour==12) {
				pval = "pm";
			} else if (hour>12) {
				hour = hour - 12;
				pval = "pm";
			} else {
				pval = "am";
			}
	
			//put our values 
			hform.value = hour;
			mform.value = min;
			period.value = pval;
	
		}
	
		mydiv.appendChild(header);
		mydiv.appendChild(hform);
		mydiv.appendChild(ce("div","timeSep","",":"));
		mydiv.appendChild(mform);
		mydiv.appendChild(period);
		mydiv.style.whiteSpace = "nowrap";	
		mydiv.appendChild(createCleaner());
		return mydiv;
		
	
	};
	
	
	/****************************************************************
		FUNCTION: _pricerangeForm
		PURPOSE:  creates a checkbox form
		INPUT:	  curform -> xml data in array form from the
												 xml config file
		RETURNS:	div containing the created div
	*****************************************************************/	
	
	this.pricerange = function(curform)
	{
	
		var i;
	
		var range = "";
		if (this.data && isData(this.data["price_range"])) range = this.data["price_range"].toString();
		var min;
		var max;
	
		if (isData(range) && range.length > 0) {
			var arr = range.split(",");
			min = arr[0];
			max = arr[1];
		}
	
		//use a multiform class if set
		if (curform.display && curform.display=="multiform") var cn = "eformInputMultiTitleCell";
		else var cn = "eformInputTitleCell";
	
		//load the main cell and the header
		var mydiv = ce("div","eformInputRow",curform.name + "FormDiv");
		var header = ce("div",cn,curform.name + "FormDivTitle",curform.title);
	
		//see if there's data to populate this form.  this should always be an array for checkboxes
		var curval;
		if (this.data && isData(this.data[curform.data])) curval = this.data[curform.data];
		else if (isData(curform.defaultval)) curval = curform.defaultval;
	
		//container for all the boxes
		var cont = ce("div","eformInputFormCell")
	
		if (curform.option)
		{
	
			for (var i=0;i<curform.option.length;i++)
			{
				//a row for each
				var row = ce("div","eformInputCheckboxRow");
	
				var cb = createCheckbox(curform.name,curform.option[i].data);		
	
				if (curform.onclick) setClick(cb,curform.onclick);
		
				row.appendChild(cb);
				row.appendChild(ctnode(curform.option[i].title));
		
				cont.appendChild(row);

        if (min && max) 
				{
          if (parseInt(curform.option[i].data)>=parseInt(min) && parseInt(curform.option[i].data)<=parseInt(max)) 
					{
            cb.checked = true;
          }
        }  
		
			}	
	
		}
	
		mydiv.appendChild(header);
		mydiv.appendChild(cont);
		mydiv.appendChild(createCleaner());
	
	
		return mydiv;
	
		return mydiv;
	
	};
	
	/****************************************************************
		FUNCTION: _pricerangeEntry
		PURPOSE:  creates a checkbox entry for _checkboxForm
		INPUT:	  curform -> xml data in array form from the
							key -> key in curform we're on
							min -> min selected range
							max -> max in selected range
		RETURNS:	div containing the created div
	*****************************************************************/	

	//not externally called
	this.pricerangeEntry = function(curform,key,min,max) 
	{
	
				var div = ce("div","multiformInputCell");
	
				var cb = createForm("checkbox",curform.name);
				cb.value = curform.option[key].data;
	
				div.appendChild(cb);
				div.appendChild(ctnode(curform.option[key].title));
				div.appendChild(createCleaner());
	
				//check it if selected
				if (min && max) {
					if (parseInt(curform.option[key].data)>=parseInt(min) && parseInt(curform.option[key].data)<=parseInt(max)) {
						cb.checked = true;
					}
				}
	
				//optional actions
				if (curform.disabled) cb.disabled = true;
				if (curform.onclick) setClick(cb,curform.onclick);
	
				return div;
	
	};
	
	
	/****************************************************************
		FUNCTION: _ageForm
		PURPOSE:  creates a checkbox form
		INPUT:	  curform -> xml data in array form from the
												 xml config file
		RETURNS:	div containing the created div
	*****************************************************************/	
	
	this.age = function(curform)
	{
	
		var i;
	
		//load the main cell and the header
	  var mydiv = ce("div","eformInputRow",curform.name + "FormDiv"); 
	  var header = ce("div","eformInputTitleCell",curform.name + "FormDivTitle",curform.title);
	
	  //put it together
	  mydiv.appendChild(header);
	
		var cont = ce("div","eformInputFormCell");
		cont.style.paddingTop = "5px";
	
		//if we returned records, insert them into the form
		if (curform.option) 
		{
	
			if (this.data && isData(this.data[curform.data])) var curval = this.data[curform.data];
			else var curval = new Array();
	
			for (i=0;i<curform.option.length;i++) 
			{
				cont.appendChild(this.ageEntry(curform,i,curval));
			}
	
		}
	
		mydiv.appendChild(cont);
		mydiv.appendChild(createCleaner());
	
	  return mydiv;
	
	};
	
	/****************************************************************
		FUNCTION: _ageEntry
		PURPOSE:  creates a checkbox entry for _checkboxForm
		INPUT:	  curform -> xml data in array form from the
							key -> key in curform we're on
							curdata -> data string of selected data
		RETURNS:	div containing the created div
	*****************************************************************/	
	this.ageEntry = function(curform,key,curdata) 
	{
	
				var div = ce("div","multiformInputCell");
	
				//the record option id we are editing
				var optId = curform.option[key].data;
				var optName = curform.name + optId;
	
				//create form and add options
				var sel = createSelect(optName);
				for (var c=0;c<=5;c++) sel[c] = new Option(c,c);
	
				//find this option in the curdata to see if it's checked
				if (curdata) 
				{
					sel.value = curdata[optName];
				}	
	
				div.appendChild(sel);
				div.appendChild(ctnode(curform.option[key].title));
	
	
				return div;
	
	};
	
	this.template = function(title,curform)
	{
	
	  //load the main cell and the header
	  var mydiv = ce("div","eformInputRow",curform.name + "FormDiv"); 
	  var header = ce("div","eformInputTitleCell",curform.name + "FormDivTitle",title);
	
	  //put it together
	  mydiv.appendChild(header);
	  mydiv.appendChild(ce("div","eformInputFormCell","",curform));
	
		mydiv.appendChild(createCleaner());
	
	  return mydiv;
	
	};

	this.label = function(curform)
	{

		var curval;
		if (this.data && isData(this.data[curform.data])) curval = this.data[curform.data];
		else if (isData(curform.defaultval)) curval = curform.defaultval;
	
	  //load the main cell and the header
	  var mydiv = ce("div","eformInputRow",curform.name + "FormDiv"); 
	  var header = ce("div","eformInputTitleCell",curform.name + "FormDivTitle",curform.title);
	
	  //put it together
	  mydiv.appendChild(header);
	  mydiv.appendChild(ce("div","eformInputLabelCell","",curval));
	
		mydiv.appendChild(createCleaner());
	
	  return mydiv;
	
	};



  this.processDateTime = function(formName)
  {
 
   	var d = new Date(ge(formName).value);
    var h = parseInt(ge(formName + "_hour").value);
    var m = parseInt(ge(formName + "_minute").value);
    var p = ge(formName + "_period").value;

    if (p=="pm")
    {
      if (h < 12) h += 12;
    }  
    else
    {   
      if (h==12) h = "00";
    }

    d.setHours(h);
    d.setMinutes(m);

    return d.toISOString();

  };
	
}
	
	