
/****************************************************************
	FILE:			pager.js
	PURPOSE:	contains functions for displaying and manipulating
						browse/search results pager
****************************************************************/

var PAGER = new BOOKIE_PAGER();

function BOOKIE_PAGER()
{

	this.total;
	this.currentTotal;
	this.currentPage;
	this.limit = 10;
	this.offset = 0;
	this.maxPageNum;
	this.handler;
	this.active_first;
	this.active_prev;
	this.active_next;
	this.active_last;
	this.inactive_first;
	this.inactive_prev;
	this.inactive_next;
	this.inactive_last;

	this.load = function(handler)
	{

		PAGER.handler = handler;
		PAGER.page = 1;
		clearElement(RECORDS.recordPager);

		//pull our limits and offsets from the handler class
		var handlerClass = eval(PAGER.handler);

		PAGER.limit = parseInt(handlerClass.searchLimit);
		PAGER.offset = parseInt(handlerClass.searchOffset);

		PAGER.nav();

	};
	
	this.nav = function()
	{

		//setup all our images for later
		PAGER.active_first = ce("div","recordPagerActive","",_I18N_FIRST);
		setClick(PAGER.active_first,"PAGER.first()");

		PAGER.active_last = ce("div","recordPagerActive","",_I18N_LAST);
		setClick(PAGER.active_last,"PAGER.last()");

		PAGER.active_next = ce("div","recordPagerActive","",_I18N_NEXT);
		setClick(PAGER.active_next,"PAGER.next()");

		PAGER.active_prev = ce("div","recordPagerActive","",_I18N_PREV);
		setClick(PAGER.active_prev,"PAGER.prev()");

		PAGER.inactive_first = ce("div","recordPagerInactive","",_I18N_FIRST);
		PAGER.inactive_last = ce("div","recordPagerInactive","",_I18N_LAST);
		PAGER.inactive_next = ce("div","recordPagerInactive","",_I18N_NEXT);
		PAGER.inactive_prev = ce("div","recordPagerInactive","",_I18N_PREV);

	};

	this.update = function(total,current)
	{

		PAGER.total = parseInt(total);
		PAGER.currentTotal = parseInt(current);

		clearElement(RECORDS.recordPager);

		var lc = ce("div","recordPagerResults");
		var rc = ce("div","recordPagerTitle");

		RECORDS.recordPager.appendChild(rc);
		RECORDS.recordPager.appendChild(lc);

		//create our <num> out of <total> string
		var txt = ce("div","recordPagerPages");
		var max;
		var min;
	
		//set our min/max display for the user
		if (PAGER.offset==0) min = 1;
		else min = PAGER.offset+1;
	
		if (PAGER.currentTotal < PAGER.limit) max = PAGER.currentTotal + PAGER.offset;
		else max = PAGER.limit + PAGER.offset;

		//stop here if nothing to display
		if (PAGER.total > 0) rc.appendChild(ctnode(_I18N_VIEWING + " " + min + "-" + max + " " + _I18N_OF + " " + PAGER.total));
	
			//numbers
			var pagenum = PAGER.total / PAGER.limit;
			if (pagenum != parseInt(pagenum)) pagenum++;			//increment if we have a remainder
			PAGER.maxPageNum = parseInt(pagenum);
		
			//min range is always half the result limit minus current page
			if (PAGER.maxPageNum > PAGE_RESULT_LIMIT) 
			{
		
				var half = parseInt(PAGE_RESULT_LIMIT/2);
	
				if ( (PAGER.page-half) <=1) {
	
					var minrange = 1;
					var maxrange = PAGE_RESULT_LIMIT;
	
				} else if (PAGER.page==PAGER.maxPageNum) {
	
					var minrange = parseInt(PAGER.maxPageNum) - parseInt(PAGE_RESULT_LIMIT);
					var maxrange = PAGER.maxPageNum;
	
				} else {
					var minrange = parseInt(PAGER.page) - parseInt(half);
					var maxrange = parseInt(PAGER.page) + parseInt(half);				
				}
		
			} 
			else 
			{
		
				var minrange = 1;
				var maxrange = PAGER.maxPageNum;
		
			}
		
			for (var i=minrange;i<=maxrange;i++) 
			{
	
				var cell = ce("div","recordPagerActive","",i);
				setClick(cell,"PAGER.jump('" + i + "')");
	
				if (i==PAGER.page) setClass(cell,"recordPagerActive current");
				txt.appendChild(cell);
		
			}

			//on the first page
			if (PAGER.offset==0 && PAGER.total > PAGER.limit) 
			{
				lc.appendChild(PAGER.inactive_first);
				lc.appendChild(PAGER.inactive_prev);
				lc.appendChild(txt);
				lc.appendChild(PAGER.active_next);
				lc.appendChild(PAGER.active_last);			
			}
			//on the last page
			else if (PAGER.offset>0 && PAGER.offset>=(PAGER.total-PAGER.currentTotal)) 
			{
				lc.appendChild(PAGER.active_first);
				lc.appendChild(PAGER.active_prev);
				lc.appendChild(txt);
				lc.appendChild(PAGER.inactive_next);
				lc.appendChild(PAGER.inactive_last);			
			}
			else if (PAGER.offset==0 && (PAGER.total < PAGER.limit || PAGER.total==PAGER.limit)) 
			{
				lc.appendChild(PAGER.inactive_first);
				lc.appendChild(PAGER.inactive_prev);
				lc.appendChild(txt);
				lc.appendChild(PAGER.inactive_next);
				lc.appendChild(PAGER.inactive_last);			
			}
			else 
			{
				lc.appendChild(PAGER.active_first);
				lc.appendChild(PAGER.active_prev);
				lc.appendChild(txt);
				lc.appendChild(PAGER.active_next);
				lc.appendChild(PAGER.active_last);			
			}
	
		if (BROWSER=="ie" && BROWSERVERSION < 10) lc.appendChild(createCleaner());
	
	};
	
	//for changing pages
	/********************************************************
		FUNCTION:	nextPage
		PURPOSE:	cycles to the next page of msg list results
	********************************************************/
	this.next = function() 
	{

		PAGER.offset = parseInt(PAGER.offset) + parseInt(PAGER.limit);
		PAGER.page++;

		var handlerClass = eval(PAGER.handler);
		handlerClass.searchOffset = PAGER.offset;
		handlerClass.search();
	
	};
	
	/********************************************************
		FUNCTION:	prevPage
		PURPOSE:	cycles to the previous page of msg list results
	********************************************************/
	this.prev = function() 
	{
	
		PAGER.offset = parseInt(PAGER.offset) - parseInt(PAGER.limit);
		PAGER.page--;

		var handlerClass = eval(PAGER.handler);
		handlerClass.searchOffset = PAGER.offset;
		handlerClass.search();
	
	};
	
	/********************************************************
		FUNCTION:	firstPage
		PURPOSE:	change to the first page of results
	********************************************************/
	this.first = function() 
	{
	
		PAGER.offset = 0;
		PAGER.page = 1;
	
		var handlerClass = eval(PAGER.handler);
		handlerClass.searchOffset = PAGER.offset;
		handlerClass.search();
	
	};
	
	/********************************************************
		FUNCTION:	lastPage
		PURPOSE:	change to the last page of results
	********************************************************/
	this.last = function() 
	{
	
		//calculate our final offset
		var rem = PAGER.total % PAGER.limit;
	
		PAGER.page = PAGER.maxPageNum;
	
		if (rem==0) PAGER.offset = PAGER.total - PAGER.limit;
		else PAGER.offset = PAGER.total - rem;
	
		var handlerClass = eval(PAGER.handler);
		handlerClass.searchOffset = PAGER.offset;
		handlerClass.search();
			
	};
	
	/********************************************************
		FUNCTION:	jump
		PURPOSE:	change to the last page of results
	********************************************************/
	this.jump = function(num) 
	{
	
		PAGER.page = num;
		PAGER.offset = PAGER.limit * num - PAGER.limit;

		var handlerClass = eval(PAGER.handler);
		handlerClass.searchOffset = PAGER.offset;
		handlerClass.search();
	
	};
	
}

