/********************************************************************
	Functions for creating or manipulating DOM objects

	created: 04/20/2006

********************************************************************/

//set a floatStyle for an object
function setFloat(myvar,floatVal) {

        if (document.all) myvar.style.styleFloat = floatVal;
        else myvar.setAttribute("style","float:" + floatVal);

        return myvar;
}

//set a className value for an object
function setClass(myvar,classVal) {

        if (document.all) myvar.setAttribute("className",classVal);
        else myvar.setAttribute("class",classVal);

        return myvar;
}

//get a className value for an object
function getObjClass(myvar) {

        if (document.all) return myvar.getAttribute("className");
        else return myvar.getAttribute("class");

}

//set an onclick event for an object
function setClick(myvar,click) {

        if (document.all) myvar.onclick = new Function(" " + click + " ");
        else myvar.setAttribute("onClick",click);

        return myvar;

}

//set an onclick event for an object
function setDblClick(myvar,click) {

        if (document.all) myvar.ondblclick = new Function(" " + click + " ");
        else myvar.setAttribute("onDblClick",click);

        return myvar;

}

//set an onclick event for an object
function setMouseDown(myvar,click) {

        if (document.all) myvar.onmousedown = new Function(" " + click + " ");
        else myvar.setAttribute("onMouseDown",click);

        return myvar;

}
//set an onclick event for an object
function setMouseUp(myvar,click) {

        if (document.all) myvar.onmouseup = new Function(" " + click + " ");
        else myvar.setAttribute("onMouseUp",click);

        return myvar;

}

//set an onclick event for an object
function setMouseOver(myvar,click) {

        if (document.all) myvar.onmouseover = new Function(" " + click + " ");
        else myvar.setAttribute("onMouseOver",click);

        return myvar;

}

//set an onclick event for an object
function setMouseOut(myvar,click) {

        if (document.all) myvar.onmouseout = new Function(" " + click + " ");
        else myvar.setAttribute("onMouseOut",click);

        return myvar;

}

// IE ONLY
function setMouseEnter(myvar,click) {

        if (document.all) myvar.onmouseenter = new Function(" " + click + " ");

        return myvar;

}

// IE ONLY
function setMouseLeave(myvar,click) {

        if (document.all) myvar.onmouseleave = new Function(" " + click + " ");

        return myvar;

}

//create a new form
function createForm(formType,formName,checked) {

	var curform;

	if (checked) formCheck = " CHECKED ";
	else formCheck = "";

	//just don't ask...
	if (document.all) {
		fStr = "<input type=\"" + formType + "\" name=\"" + formName + "\" id=\"" + formName + "\" " + formCheck + ">";
		curform = document.createElement(fStr);
	}
	else {
		var curform = ce("input");
		curform.setAttribute("name",formName);
		curform.setAttribute("type",formType);
		curform.setAttribute("id",formName);
		if (checked) curform.checked = true;
	}

	return curform;

}

//create a new form
function createSelect(formName,change,dataArr,curVal) {

	var curform;

	//just don't ask...
	if (document.all) {

		if (change) onChange = "onChange=\"" + change + "\"";
		else onChange = "";

		fStr = "<select name=\"" + formName + "\" id=\"" + formName + "\" " + onChange + ">";
		curform = document.createElement(fStr);
	}
	else {
		var curform = document.createElement("select");
		curform.setAttribute("name",formName);
		curform.setAttribute("id",formName);
		if (change) curform.setAttribute("onChange",change);
	}

	//add data and a curvalue
	if (dataArr && dataArr.length > 0) {

		for (var i=0;i<dataArr.length;i++) {

			curform[i] = new Option(dataArr[i]["name"],dataArr[i]["value"]);

		}
	
	}

	if (curVal) curform.value = curVal;

	return curform;

}

//set an onclick event for an object
function setChange(myvar,click) {

        if (document.all) myvar.onchange = new Function(" " + click + " ");
        else myvar.setAttribute("onChange",click);

        return myvar;

}

//set an onclick event for an object
function setKeyUp(myvar,click) {

        if (document.all) myvar.onkeyup = new Function(" " + click + " ");
        else myvar.setAttribute("onkeyup",click);

        return myvar;

}

//set an onclick event for an object
function setFocus(myvar,click) {

        if (document.all) myvar.onfocus = new Function(" " + click + " ");
        else myvar.setAttribute("onfocus",click);

        return myvar;

}

//set an onclick event for an object
function setBlur(myvar,click) {

        if (document.all) myvar.onblur = new Function(" " + click + " ");
        else myvar.setAttribute("onblur",click);

        return myvar;

}

//shorthand for getElementbyId
function ge(element) {
	return document.getElementById(element);
}

//shorthand for creating an element
function ce(elementType,elementClass,elementId,txt) {

	var e = document.createElement(elementType);

	//add optional parameters
	if (elementId) e.setAttribute("id",elementId);
	if (elementClass) setClass(e,elementClass);

	//append extra text.  If passed an object, append with without the textnode wrapper
	if (isData(txt)) {
		if (typeof(txt)=="object") e.appendChild(txt);
		else e.appendChild(ctnode(txt));
	}

	return e;

}

function createCleaner(fill) {

	var cleaner = document.createElement("div");
	setClass(cleaner,"cleaner");

	if (fill) cleaner.appendChild(createNbsp());

	return cleaner;

}

//shorthand for creating a text node
function ctnode(str) {
	return document.createTextNode(str);
}
 
function changeClass(id,section) {
	document.getElementById(id).className = section;
}


//hide an object from view
function hideObject(obj) {

        document.getElementById(obj).style.position="absolute";
        document.getElementById(obj).style.visibility="hidden";
        document.getElementById(obj).style.zIndex="-10";
				document.getElementById(obj).style.display="none";

}

//show an object in the browser
function showObject(obj,zIndex) {

	if (!zIndex) zIndex = 1;

        document.getElementById(obj).style.position="static";
        document.getElementById(obj).style.visibility="visible";
				document.getElementById(obj).style.display="block";
        document.getElementById(obj).style.zIndex=zIndex;

}

//cycle between hide and show
function cycleObject(obj,zIndex) {

        var visib = document.getElementById(obj).style.visibility;

        if (visib=="visible") hideObject(obj);
        else showObject(obj,zIndex);

}

/******* some better versions of the above functions ********/
function showObj(obj) {
	ge(obj).style.display = "block";	
}

function hideObj(obj) {
	ge(obj).style.display = "none";	
}

function cycleObj(obj) {

	var visib = ge(obj).style.display;
	if (visib=="block") hideObj(obj);
	else showObj(obj);

}

//calculates the left offset of an object
function calculateOffsetLeft(r){
        return Ya(r,"offsetLeft");
}

//calcuates teh top offset of an object
function calculateOffsetTop(r){
        return Ya(r,"offsetTop");
}

//does the legwork on offset calcuation
function Ya(r,attr) {
        var kb=0;
        while(r){
                kb+=r[attr];
                r=r.offsetParent;
        }
        return kb;
}

//returns the value of a radio form
function getRadioValue(name,obj) {

        if (!obj) return "";

				var setval = "";

				//loop through area, get form with right name and see if it's checked at all
				var arr = obj.getElementsByTagName("input");

				for (var i=0;i<arr.length;i++) {

					if (arr[i].type=="radio" && arr[i].id==name && arr[i].checked==true) {
						setval = arr[i].value;
						break;
					}

				}

				return setval;

}

function createTextbox(formName,curVal,formLen) {

        //create the base form
        curform = createForm("text",formName);

        //set our attributes
        if (curVal) curform.setAttribute("value",curVal); 
        if (formLen) curform.setAttribute("size",formLen);

        return curform;
 
}

function createHidden(formName,curVal) {

        //create the base form
        curform = createForm("hidden",formName);

        //set our attributes
        if (curVal) curform.setAttribute("value",curVal); 

        return curform;
 
}

function createPassword(formName,curVal,formLen) {

        //create the base form
        curform = createForm("password",formName);

        //set our attributes
        if (curVal) curform.setAttribute("value",curVal); 
        if (formLen) curform.setAttribute("size",formLen);

        return curform;
 
}

function createRadio(formName,formVal,curVal,clicker) {

        var checked;

        if (curVal && curVal == formVal) checked = 1;
        else checked = null;

        curform = createForm("radio",formName,checked);
        curform.setAttribute("value",formVal);

				if (clicker) setClick(curform,clicker);

        return curform;
 
}

function createRadioDiv(formName,formVal,curVal,txt,cn,oc) {

        var checked;

				var mydiv = ce("div");
				if (cn) setClass(mydiv,cn);

        curform = createRadio(formName,formVal,curVal);
				if (oc) setClick(curform,oc);

				mydiv.appendChild(curform);
				if (txt) mydiv.appendChild(ctnode(txt));

        return mydiv;
 
}

function createCheckbox(formName,formVal,curVal,clicker) {

        var checked;

        if (curVal && curVal == formVal) checked = 1;
        else checked = null;

        curform = createForm("checkbox",formName,checked);
        curform.setAttribute("value",formVal);

				if (clicker) setClick(curform,clicker);

        return curform;

}
 
function createTextarea(formName,curVal,rows,cols) {

        //just don't ask...
        if (document.all) {
                fStr = "<textarea name=\"" + formName + "\" id=\"" + formName + "\"></textarea>";
                curform = document.createElement(fStr);
        }
        else {
                var curform = document.createElement("textarea");
                curform.setAttribute("name",formName);
                curform.setAttribute("id",formName);  
        }

        if (rows) curform.setAttribute("rows",rows);
        if (cols) curform.setAttribute("cols",cols);

        if (curVal) curform.value = curVal;

        return curform;

}

function createBtn(formName,val,oc) {

        var btn = createForm("button",formName);
        btn.setAttribute("value",val);

        if (oc) setClick(btn,oc);

        return btn;

}

function setZIndex(objName,zIndex) {

        var obj = ge(objName);
        obj.style.zIndex = zIndex;
 
}

//create a table
function createTable(tableName,className,width,border,cellpadding,cellspacing) {

	if (!border) border = "0";
	if (!cellpadding) cellpadding = "0";
	if (!cellspacing) cellspacing = "0";

	//create the element
	if (document.all) {
		var str = "<table ";
		if (tableName) str += "id=\"" + tableName + "\" ";
		if (className) str += "class=\"" + className + "\" ";
		str += "border=\"" + border + "\" cellpadding=\"" + cellpadding + "\" cellspacing=\"" + cellspacing + "\">";

		var tbl = ce(str);

	} else {

		var tbl = ce("table",className,tableName);

		//set our main attributes
		tbl.setAttribute("border",border);
		tbl.setAttribute("cellpadding",cellpadding);
		tbl.setAttribute("cellspacing",cellspacing);

	}

	if (width) tbl.setAttribute("width",width);

	return tbl;

}

function createTableCell(cellName,className,rowspan,colspan) {

	if (document.all) {

		var str = "<td ";
		if (cellName) str += "id=\"" + cellName + "\" ";
		if (className) str += "class=\"" + className + "\" ";
		if (rowspan) str += "rowspan=\"" + rowspan + "\" ";
		if (colspan) str += "colspan=\"" + colspan + "\" ";
		str += ">";
		var cell = ce(str);

	} else {

		var cell = ce("td");
		if (cellName) cell.setAttribute("id",cellName);
		if (className) setClass(cell,className);
		if (rowspan) cell.setAttribute("rowspan",rowspan);
		if (colspan) cell.setAttribute("colspan",colspan);

	}

	return cell;

}

//removes all child nodes within an element
function clearElement(el) {

		if (!el) return false;
		while (el.hasChildNodes()) {
			el.removeChild(el.firstChild);
		}

}

//replaces content of an element with passed data
function setElement(e,data) {

	//clear out the insides
	clearElement(e);

	//append extra text.  If passed an object, append with without the textnode wrapper
	if (isData(data)) {
		if (typeof(data)=="object") e.appendChild(data);
		else e.appendChild(ctnode(data));
	}

}

//css
function loadStylesheet(csspath) {
	var oLink = document.createElement("link");
	oLink.href = csspath;
	oLink.rel = "stylesheet";
	oLink.type = "text/css";
	document.getElementsByTagName("head")[0].appendChild(oLink);
}

//javascript
function loadJavascript(jspath) {
	
	var e = document.createElement("script");
	e.src = jspath;
	e.type="text/javascript";
	document.getElementsByTagName("head")[0].appendChild(e); 

}

function createDateSelect(name,title,val) {

	var divname = name + "Div";
	var cell = ce("div","popupCell",divname);
  var head = ce("div","formHeader","",title);
  var form = createTextbox(name);
  form.setAttribute("size","10");
	if (val) form.value = val;

  var btn = createBtn(name + "_btn","...");

  if (!window.Calendar) {
    alert("You must include the calendar javascript file");
  }
   
  Calendar.setup({
          inputField      :    form,
          ifFormat        :   "%m/%d/%Y",
          button          :    btn,
          singleClick     :    true,           // double-click mode
          step            :    1                // show all years in drop-down boxes (instead of every other year as default)
      });


  cell.appendChild(head);
  cell.appendChild(form);
  cell.appendChild(btn); 

  return cell;

}

function cdata(elementType,txt) {

	var xml = "<" + elementType + "><![CDATA[" + escape(txt) + "]]></" + elementType + ">";
	return xml

}

function getEventSrc(e) {
  e = e || window.event;
  return e.target || e.srcElement;
}


function createMethodReference(object, methodName) {
    return function () {
        //object[methodName](params);   
				object[methodName].apply(object, arguments);
    };
};

function getScrollTop() {

	if (document.documentElement) return document.documentElement.scrollTop;
	else return document.body.scrollTop;

}

function getScrollLeft() {

	if (document.documentElement) return document.documentElement.scrollLeft;
	else return document.body.scrollLeft;

}

function createImg(src,clicker,title) {

	var img = ce("img");
	img.setAttribute("src",src);

	if (clicker) setClick(img,clicker);
	if (title) img.setAttribute("title",title);

	return img;	

}

function createLink(txt,dest) { 

  var link = ce("a","","",txt);
  link.setAttribute("href",dest);

  return link;

}

function createNbsp()
{
	return ctnode(String.fromCharCode(160));
}
