
function RECORD_FILTERS()
{

	this.file;
	this.handler;
	this.container;
	this.records;
	this.filterData;

	/**
		*/
	this.load = function(container,xmlFile,handler)
	{

		this.container = container;
		this.file = xmlFile;	
		this.handler = handler;

		updateSiteStatus(_I18N_PLEASEWAIT);
		var p = new PROTO();
		p.add("command","eform_filters_load");
		p.add("file",this.file);
		p.post(API_URL,createMethodReference(this,"writeFilters"));

	};

	/**
		*/
	this.writeFilters = function(data)
	{

		clearSiteStatus();

		if (data.error) alert(data.error);
		else if (data.record) 
		{

			this.records = data.record;

			this.filterOption();
			this.matchOption();

			if (this.handler) 
			{
				this.handler();
			}

		}

	};

	/**
		creates our filter option dropdown for selecting what we want to filter by
		*/
	this.filterOption = function() 
	{

		clearElement(this.container);

		var sel = createSelect("filters[]");
		sel.onchange = createMethodReference(this,"matchOption");
	
		//loop through filters
		for (var i=0;i<this.records.length;i++) 
		{
			var rec = this.records[i];
			sel[i] = new Option(rec.title,rec.data);
		}

		if (this.filterData) sel.value = this.filterData.filter;

		this.container.appendChild(sel);

	};

	/**
		clears out all forms on this filter bar except the filter selector
		*/
	this.clearFilter = function()
	{

		var sels = this.container.getElementsByTagName("select");
		var tbs = this.container.getElementsByTagName("input");

		var sellen = sels.length;
		var tbslen = tbs.length;		

		//remove all forms except for the filterOption dropdown
		for (var i=(sellen-1);i>0;i--) this.container.removeChild(sels[i]);
		for (var i=(tbslen-1);i>=0;i--) this.container.removeChild(tbs[i]);

	};

	/**
		creates our match option dropdown for selecting how we want to match our data to our filter
		*/
	this.matchOption = function() 
	{

		//remove any other filters already set
		this.clearFilter();

		var form = this.container.getElementsByTagName("select")[0];

		//alert(this.container.getElementsByTagName("select").length);

		var idx = form.selectedIndex;

		//get the filter reference based on our index
		var filter = this.records[idx];

		//now create the dropdown
		var sel = createSelect("matches[]");

		//add our possible match types for this filter
		for (var i=0;i<filter.match.length;i++)
		{
			var rec = filter.match[i];
			sel[i] = new Option(rec.title,rec.data);
		}

		if (this.filterData) sel.value = this.filterData.match;

		this.container.appendChild(sel);

		//show the value field now
		this.valueOption();

		//show the data type container
		this.dataTypeOption();
	
	};

	/**
		creates our value option dropdown for what we want our filter to match against
		*/
	this.valueOption = function()
	{

		var form = this.container.getElementsByTagName("select")[0];
		var idx = form.selectedIndex;

		//get the filter reference based on our index
		var filter = this.records[idx];

		//if it's a textbox
		if (filter.type=="textbox")
		{

			var tb = createTextbox("values[]");
			tb.onkeyup = this.handler;
			this.container.appendChild(tb);

			if (this.filterData) tb.value = this.filterData.value;

		}
		else if (filter.type=="date")
		{

			var tb = createTextbox("values[]");
			//tb.onchange = this.handler;
			this.container.appendChild(tb);

			if (this.filterData) tb.value = this.filterData.value;

      new Picker.Date(tb, {
        timePicker: false, 
        positionOffset: {x: 5, y: 0},
        pickerClass: 'datepicker_vista',
        useFadeInOut: !Browser.ie,
				onClose: this.handler
      });

		}
		else
		{

			//now create the dropdown
			var sel = createSelect("values[]");
			sel[0] = new Option("Select...","-1");

			//add our possible match types for this filter
			for (var i=0;i<filter.option.length;i++)
			{
				var rec = filter.option[i];
				sel[i+1] = new Option(rec.title,rec.data);
			}

			if (this.filterData) sel.value = this.filterData.value;

			sel.onchange = this.handler;
			this.container.appendChild(sel);

		}

	};

	/**
		creates our value option dropdown for what we want our filter to match against
		*/
	this.dataTypeOption = function()
	{

		var form = this.container.getElementsByTagName("select")[0];
		var idx = form.selectedIndex;

		//get the filter reference based on our index
		var filter = this.records[idx];

		//if it's a textbox
		if (filter.data_type)
		{
			var tb = createHidden("data_types[]",filter.data_type);
			this.container.appendChild(tb);
		}

	};

}
	
