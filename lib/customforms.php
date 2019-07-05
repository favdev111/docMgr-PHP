<?php

/*************************************************************************	
//        FILE: customforms.php
//
// DESCRIPTION: Functions for creating Custom HTML form fields
//              for entering information.
//      
//              Inlcudes forms for entering 
//              Phone Numbers, Dates/Date Ranges, Time, Dropdown boxes
//              ,and Checkboxes for employees
//    CREATION
//        DATE: 04-19-2006
//
//
//     HISTORY:
//
//
*************************************************************************/
function phone($title,$name,$value,$format) {

	$posname = $name."_cursorpos";

	$string .= "<input type=hidden name=\"".$posname."\" id=\"".$posname."\">";
	
	if ($format=="table") $string .= "<tr><td align=right>";	
	
	$string .= "<div class=\"formHeader\">".$title."</div>";

	if ($format=="table") $string .= "</td><td>";	

	$value = trim($value);

	//tack on some spaces if there's no area code
	if (strlen($value)==7) $value = "   ".$value;

	if ($value) {
		
		/*  Split up the string form_home_phone string */
		
		$phone1_value = substr($value,0,3);
		$phone2_value = substr($value,3,3);
		$phone3_value = substr($value,6,4);

	}

	if (defined("READONLY")) {
		if ($phone3_value) $string .= "(".$phone1_value.") ".$phone2_value."-".$phone3_value;
	}
	else {	
		$string .= "	(<input  class=\"textbox\" type=text size=\"3\" MAXLENGTH=\"3\" name=\"".$name."1\" id=\"".$name."1\" \n";
		if (defined("KEYBOARD_FIRST_FIELD")) $string .= " onFocus=\"cursorStore('".$name."1');\" \n";
		$string .= " 	value=\"".$phone1_value."\" 
				onKeyDown=\"phoneJump(null,'".$name."1',null,'".$posname."','store');\"
				onKeyUp=\"phoneJump(null,'".$name."1','".$name."2','".$posname."','set');\"
				>) \n";

		$string .= "	<input  class=\"textbox\" type=text size=\"3\" MAXLENGTH=\"3\" name=\"".$name."2\" id=\"".$name."2\"";
		if (defined("KEYBOARD_FIRST_FIELD")) $string .= " onFocus=\"cursorStore('".$name."2');\"";
		$string .= " 	value=\"".$phone2_value."\" 
				onKeyDown=\"phoneJump(null,'".$name."2',null,'".$posname."','store');\"
				onKeyUp=\"phoneJump('".$name."1','".$name."2','".$name."3','".$posname."','set');\"
				>-";

		$string .= "	<input  class=\"textbox\" type=text size=\"4\" MAXLENGTH=\"4\" name=\"".$name."3\" id=\"".$name."3\"";
		if (defined("KEYBOARD_FIRST_FIELD")) $string .= " onFocus=\"cursorStore('".$name."3');\"";
 		$string .= " 	value=\"".$phone3_value."\" 
				onKeyDown=\"phoneJump(null,'".$name."3',null,'".$posname."','store');\"
				onKeyUp=\"phoneJump('".$name."2','".$name."3',null,'".$posname."','set');\"
				>";

	}

	if ($format=="table") $string .= "		</td></tr> ";
	
	return $string;
	
}
/************************************************************************
*************************************************************************/
function createDropdown($optionArray) {

	$table = $optionArray["table"];				//the table we are querying
	$fieldName = $optionArray["fieldName"];		//the field name which is display in the dropdown
	$fieldValue = $optionArray["fieldValue"];	//the value of the dropdown option if diff from fieldName
	$limitField = $optionArray["limitField"];	//record limiter for teh query
	$limitValue = $optionArray["limitValue"];	//value we limit records to
	$value = $optionArray["value"];				//value of existing entry
	$name = $optionArray["name"];				//name of the form
	$conn = $optionArray["conn"];				//sql conn resource id
	$size = $optionArray["size"];				//size of the dropdown
	$nullValue = $optionArray["nullValue"];		//0 entry for dd
	$order = $optionArray["order"];				//order by
	$change = $optionArray["change"];			//dropdown change value

	if ($change) $changeValue = "onChange=\"".$change.";\"";

	//the fields we are looking for
	$query = $fieldName;
	if ($fieldValue) $query .= ",".$fieldValue;

	if (!$fieldValue) $fieldValue = $fieldName;

	$sql = "SELECT $query FROM $table";
	if ($limitField) $sql .= " WHERE $limitField = $limitValue ";
	if ($order) $sql .= " ORDER BY $order";

	$ddInfo = total_result($conn,$sql);

	$string = "<select name=\"".$name."\" id=\"".$name."\" size=\"".$size."\" ".$changeValue.">";

	if ($nullValue) $string .= "<option value=\"0\">".$nullValue;

	for ($row=0;$row<count($ddInfo[$fieldName]);$row++) {

		if ($value==$ddInfo[$fieldValue][$row]) $selected = " SELECTED ";
		else $selected = null;

		$string .= "<option value=\"".$ddInfo[$fieldValue][$row]."\" ".$selected.">".$ddInfo[$fieldName][$row];

	}

	$string .= "</select>";

	return $string;

}
/************************************************************************
*************************************************************************/
function dropdownGenerate($formInfo) {

	$conn = $formInfo["conn"];
	
	if (!$formInfo["form_id"]) $formInfo["form_id"] = $formInfo["form_name"];
	if (!$formInfo["form_size"]) $formInfo["form_size"] = "1";	
	if (!$formInfo["table"]) $formInfo["table"] = $formInfo["form_name"];

	$sql = "SELECT * FROM ".$formInfo["table"];

	if ($formInfo["filter"]) $sql .= " WHERE ".$formInfo["filter"];

	$sql .= " ORDER BY id";

	$list = total_result($conn,$sql);

	$content .= "<div class=\"formHeader\">";
	$content .= $formInfo["name"];
	$content .= "</div>";

	$content .= "<select 	name=\"".$formInfo["form_name"]."\" 
				id=\"".$formInfo["form_id"]."\" 
				size=\"".$formInfo["form_size"]."\"";

	if ($formInfo["change"]) $content .= " onChange=\"".$formInfo["change"].";\" ";
	if ($formInfo["class"]) $content .= " class=\"".$formInfo["class"]."\" ";

	$content .= ">";

	for ($row=0;$row<count($formInfo["extraId"]);$row++) {
	
		if ($formInfo["cur_value"]==$formInfo["extraId"][$row]) $selected = " SELECTED ";
		else $selected = null;

		$content .= "<option ".$selected." value=\"".$formInfo["extraId"][$row]."\">".$formInfo["extraName"][$row];

	}

	for ($row=0;$row<count($list["id"]);$row++) {

		if ($formInfo["cur_value"]==$list["id"][$row]) $selected = " SELECTED ";
		else $selected = null;

		$content .= "<option ".$selected." value=\"".$list["id"][$row]."\">".$list["name"][$row];

	}

	$content .= "</select>";

	return $content;

}
/************************************************************************
*************************************************************************/
function dateRange($name) {

	$content .= "From&nbsp;";
	$content .= dateFormSelect(null,$name."Begin",null);
        $content .= "&nbsp;&nbsp;";
        $content .= "To&nbsp;";
	$content .= dateFormSelect(null,$name."End",null);
        	            
	return $content;

}
/************************************************************************
*************************************************************************/
function timeForm($formInfo) {

	$content = "<div class=\"formHeader\">";
	$content .= $formInfo["name"];
	$content .= "</div>";

	$time = explode(":",$formInfo["cur_value"]);

	$hour = $time[0];
	$minute = $time[1];

	if ($hour>="12") $pmSelect = " SELECTED ";
	else $amSelect = " SELECTED ";;

	if ($hour>12) $hour = $hour - 12;

	$name = $formInfo["form_name"];

	if (!$formInfo["form_id"]) $formInfo["form_id"] = $formInfo["form_name"];

	$content .= "<input 	type=text 
				size=2 
				maxlength=2 
				name=\"".$formInfo["form_name"]."Hour\" 
				id=\"".$formInfo["form_id"]."Hour\"
				value=\"".$hour."\"
				onKeyUp=\"dateJump('".$name."Hour','".$name."Minute');\"
				>";
	$content .= ":";
	$content .= "<input 	type=text 
				size=2
				maxlength=2 
				name=\"".$formInfo["form_name"]."Minute\" 
				id=\"".$formInfo["form_id"]."Minute\"
				value=\"".$minute."\"
				onKeyUp=\"dateJump('".$name."Minute','".$name."Period');\"
				>";
	$content .= "&nbsp;";
	$content .= "<select 	name=\"".$formInfo["form_name"]."Period\"
				id=\"".$formInfo["form_name"]."Period\"
				size=1>";

	$content .= "<option value=\"am\" ".$amSelect.">A.M.";
	$content .= "<option value=\"pm\" ".$pmSelect.">P.M.";
	$content .= "</select>";
	
	return $content;

}
/************************************************************************
*************************************************************************/
function stateForm($conn,$name,$curValue,$disabled = null) {

	$sql = "SELECT * FROM state ORDER BY abbr";
	$list = list_result($conn,$sql);
	
	if ($disabled) $disabled = " DISABLED ";
	
	$str = "<select name=\"".$name."\" id=\"".$name."\" size=1 $disabled>
		<option value=\"\">Select State
		";
	
	for ($i=0;$i<$list["count"];$i++) {
	
		if ($list[$i]["abbr"]==$curValue) $select = " SELECTED ";
		else $select = NULL;
	
		$str .= "<option value=\"".$list[$i]["abbr"]."\" ".$select.">".$list[$i]["abbr"]."\n";
		
	}
	
	$str .= "</select>\n";
	
	return $str;

}
/************************************************************************
*************************************************************************/
function countryForm($conn,$name,$curValue,$disabled = null) {

	$sql = "SELECT * FROM country";
	$list = list_result($conn,$sql);
	
	if ($disabled) $disabled = " DISABLED ";
	
	$str = "<select name=\"".$name."\" id=\"".$name."\" size=1 $disabled style=\"width:150px\">
		<option value=\"\">Select Country
		";
	
	for ($i=0;$i<$list["count"];$i++) {
	
		if ($list[$i]["id"]==$curValue) $select = " SELECTED ";
		else $select = NULL;
	
		$str .= "<option value=\"".$list[$i]["id"]."\" ".$select.">".$list[$i]["printable_name"]."\n";
		
	}
	
	$str .= "</select>\n";
	
	return $str;

}
/************************************************************************
*************************************************************************/
function salutationForm($conn,$name,$curValue,$disabled = null) {

	$sql = "SELECT * FROM salutation";
	$list = list_result($conn,$sql);
	
	if ($disabled) $disabled = " DISABLED ";
	
	$str = "<select name=\"".$name."\" id=\"".$name."\" size=1 $disabled style=\"width:150px\">
		<option value=\"\">Select Salutation
		";
	
	for ($i=0;$i<$list["count"];$i++) {
	
		if ($list[$i]["id"]==$curValue) $select = " SELECTED ";
		else $select = NULL;
	
		$str .= "<option value=\"".$list[$i]["id"]."\" ".$select.">".$list[$i]["name"]."\n";
		
	}
	
	$str .= "</select>\n";
	
	return $str;

}
/************************************************************************
*************************************************************************/
function dateDisplay($prefix,$header,$date) {

	if (!$date) $date = date("Y-m-d");

	$dateArray = explode("-",$date);
	$monthValue = $dateArray[1];
	$dayValue = $dateArray[2];
	$yearValue = $dateArray[0];

	$string = "<div class=\"formHeader\">".$header."</div>
			<SELECT name=\"".$prefix."_month\" id=\"".$prefix."_month\" onChange=\"fixDay('".$prefix."');\">
			";

	for ($row=1;$row<=12;$row++) {
		if ($monthValue == $row) $selected = " SELECTED ";
		else $selected = null;
		$string .= "<option value=".$row." ".$selected.">".date("M",mktime(0,0,0,$row,1,0));
	}

	$string .= "</SELECT>
				&nbsp;
				<select name=\"".$prefix."_day\" id=\"".$prefix."_day\">";

	for ($row=1;$row<=31;$row++) {
			if ($dayValue == $row) $selected = " SELECTED ";
			else $selected = null;
			$string .= "<OPTION value=\"".$row."\" ".$selected.">".$row;
	}


	$string .= "</SELECT>
				&nbsp;
				<SELECT name=\"".$prefix."_year\">
				";

	$date_loop=date(Y)-1;
	$date_loop_end=$date_loop+3;

	for ($row=$date_loop;$row<$date_loop_end;$row++) {
			if ($yearValue == $row) $selected = " SELECTED ";
			else $selected = null;
			$string .= "<OPTION value=\"".$row."\" ".$selected.">".$row;
	}

	$string .= "</select>";

	return $string;

}
/************************************************************************
*************************************************************************/
function dateForm($title,$name,$value) {

	if (defined("DATE_FORMAT")) $layout = DATE_FORMAT;
	else $layout = "mm/dd/yyyy";
	$layout = strtoupper($layout);

	if ($title) $string .= "<div class=\"formHeader\">".$title."</div>";

	$value = trim($value);

	if ($value) $value = date_view($value,"slash");

	$string .= "<input 	class=\"textbox\" 
					type=text 
					name=\"".$name."\" 
					id=\"".$name."\" 
					value=\"".$value."\"
					>";

	return $string;
	
}
/************************************************************************
//loads a date form with a popup calendar select
*************************************************************************/
function dateFormSelect($header,$formName,$formValue,$readonly = null,$event = null,$formHeader = null) {

	//assemble our form
	$str = null;
	
	if ($header) {

	    if (!$formHeader) $formHeader = "formHeader";

  	  $str = "<div class=\"formHeader\">
	  	  ".$header."
		  </div>
		  ";
  }
        
  $str .= "
		<input type=text size=10 ".$readonly." name=\"".$formName."\" id=\"".$formName."\" ".$event." value=\"".$formValue."\">

		<script type=\"text/javascript\">
    new Picker.Date(ge('".$formName."'), {
          timePicker: false,
                positionOffset: {x: 5, y: 0},   
                      pickerClass: 'datepicker_vista',
                            useFadeInOut: !Browser.ie
                                });
                                </script>
		";

	return $str;

}
/************************************************************************
*************************************************************************/
function dateTimeFormSelect($header,$formName,$formValue,$readonly="READONLY") {

	//extend the form name to the name of our button
	$btnName = $formName."Btn";

	//assemble our form
	$str = "<div class=\"formHeader\">
		".$header."
		</div>
		<input type=text size=20 ".$readonly." name=\"".$formName."\" id=\"".$formName."\" value=\"".$formValue."\">
		<input type=button name=\"".$btnName."\" id=\"".$btnName."\" value=\"...\">
		";

	//call our js function and setup loaders
	$str .= jsCalLoad();
	$str .= jsCalSetup($formName,$btnName,1);

	return $str;

}
/************************************************************************
//loads the required javascript files for our calendar to work
*************************************************************************/
function jsCalLoad() {

	//get out if this has already been loaded
	if (defined("JSCAL_LOADED")) return false;

	//make sure this doesn't get loaded again
	define("JSCAL_LOADED","1");

	$str = "
  	<!-- calendar stylesheet -->
  	<link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"javascript/calendar/skins/aqua/theme.css\" title=\"Aqua\" >

  	<!-- main calendar program -->
  	<script type=\"text/javascript\" src=\"javascript/calendar/calendar.js\"></script>

  	<!-- language for the calendar -->
  	<script type=\"text/javascript\" src=\"javascript/calendar/lang/calendar-en.js\"></script>

  	<!-- the following script defines the Calendar.setup helper function, which makes
       adding a calendar a matter of 1 or 2 lines of code. -->
  	<script type=\"text/javascript\" src=\"javascript/calendar/calendar-setup.js\"></script>

	";
	
	return $str;
	
}
/************************************************************************
//sets up the calendar to work with a specific input field or id, which
//is passed as the first parameter
*************************************************************************/
function jsCalSetup($formName,$btnName,$showTime = null) {

	//get our date format from the define if set
	if (defined("DATE_FORMAT")) $dateFormat = strtolower(DATE_FORMAT);
	else $dateFormat = "mm/dd/yyyy";

	//rewrite our php format to work with javascript
	$dateFormat = str_replace("mm","%m",$dateFormat);
	$dateFormat = str_replace("dd","%d",$dateFormat);
	$dateFormat = str_replace("yyyy","%Y",$dateFormat);

	//allow time selection as well if desired
	if ($showTime) {
		$setupStr = "ifFormat       :    \"".$dateFormat." %I:%M %p\",       // format of the input field\n";
        	$setupStr .= "showsTime      :    true,            // will display a time selector\n";
	}
	else {
		$setupStr = "ifFormat	:	\"".$dateFormat."\",\n";
	}

	//output our setup string
	$str = "

	<script type=\"text/javascript\">
    	Calendar.setup({
        	inputField     	:    \"".$formName."\",      // id of the input field
		".$setupStr."
        	button         	:    \"".$btnName."\",   // trigger for the calendar (button ID)
        	singleClick    	:    true,           // double-click mode
        	step           	:    1                // show all years in drop-down boxes (instead of every other year as default)
    	});
	</script>

	";
	
	return $str;

	/* other options for the calendar
	*/

}
/*********************************************************
*********************************************************/
function functionMenu($tableName,$nameList,$functionList) {

    if (BROWSER=="gecko") $leave = "onBlur=\"document.getElementById('".$tableName."').style.visibility='hidden';\"";
    else $leave="onMouseLeave=\"hideFunctionMenu('".$tableName."',event);\"";

    $string = "<div id=\"".$tableName."\" ".$leave." 
            style=\"visibility:hidden;position:absolute\" 
            class=\"functionMenu\">  ";

    for ($row=0;$row<count($nameList);$row++) {

        $id = $tableName."Cell".$row;

        $string .= "
            <div id=\"".$id."\" class=\"functionMenuCell\"
                    onMouseOver=\"changeStyle('".$id."','highlight');\"
                    onMouseOut=\"changeStyle('".$id."','normal');\"
                    unselectable=\"on\"
                    onclick=\"".$functionList[$row].";\"
                    >
                ".$nameList[$row]."
            </div>
            ";
    }

    $string .= "</table></div>";

    return $string;

}

/***********************************************************
  createPermCheckbox
  Displays a checkbox form with all permissions that
  can be set for a user
***********************************************************/
function createPermCheckbox($bitValue) {

  $permArray = $_SESSION["definePermArray"];

  for ($row=0;$row<count($permArray["name"]);$row++) {

    $bitSet = bitCal($permArray["bitpos"][$row]);

    //only display the permissions our user is already allowed
    if (bitset_compare(BITSET,$bitSet,ADMIN)) {

      if ($bitValue & $bitSet) $checked = " CHECKED ";
      else $checked = null;

      $permlang = "_PERM_".$permArray["define_name"][$row];

      if (defined($permlang)) $permText = constant($permlang);
      else $permText = $permArray["name"][$row];

      $permString .= "<li style=\"list-style-type:none\">";
      $permString .= "<input  type=checkbox
                              name=\"perm[]\"
                              id=\"perm[]\"
                              value=\"".bitCal($permArray["bitpos"][$row])."\"
                              ".$checked."
                              >&nbsp;";
      $permString .= $permText;
      $permString .= "</li>";

    }

  }

  return $permString;
}

//END FILE
