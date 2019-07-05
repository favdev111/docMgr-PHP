<?php

/******************************************************
  FILENAME:	constants.php
  PURPOSE:	a central place to define constants for
            our app
******************************************************/

//seconds in specified period
define("MIN_SEC","60");
define("HOUR_SEC","3600");
define("DAY_SEC","86400");
define("WEEK_SEC","604800");
define("MONTH_SEC","2678400");
define("YEAR_SEC","31536000");

//contact status constants
define("CONTACT_ONLY","0");
define("CONTACT_ACTIVEPROSPECT","1");
define("CONTACT_INACTIVEPROSPECT","2");
define("CONTACT_UNDERCONTRACT","3");
define("CONTACT_RESIDENT","4");
define("CONTACT_OWNER","5");
define("CONTACT_OWNERRESIDENT","6");
define("CONTACT_SELLER","7");
define("CONTACT_SOLD","8");

//contract status constants
define("CONTRACT_LISTED","1");
define("CONTRACT_SOLD","2");
define("CONTRACT_UNDERCONTRACT","3");
define("CONTRACT_RENEWAL","4");
define("CONTRACT_HOLD","5");
define("CONTRACT_CANCELLED","6");
define("CONTRACT_RELEASE","7");
define("CONTRACT_RATIFIED","8");
define("CONTRACT_COMMISSIONPAID","9");
define("CONTRACT_EXPIRED","12");
define("CONTRACT_CANCELLEDLISTING","13");
define("CONTRACT_PENDING","14");

//some correspondence types
define("ACTIVITY_OFFICE_VISIT","1");
define("ACTIVITY_PHONE_CALL","2");
define("ACTIVITY_EMAIL","3");
define("ACTIVITY_LETTER","4");
define("ACTIVITY_POSTCARD","5");
define("ACTIVITY_GIFTCARD","6");
define("ACTIVITY_THANKYOU","7");
define("ACTIVITY_MASS_EMAIL","8");
define("ACTIVITY_MASS_LETTER","9");
define("ACTIVITY_OTHER","10");
define("ACTIVITY_WEB","11");
define("ACTIVITY_LABEL","12");
define("ACTIVITY_ENVELOPE","13");

define("SOURCE_OFFICE","1");
define("SOURCE_WEB","2");
define("SOURCE_PHONE","3");
define("SOURCE_EMAIL","4");
define("SOURCE_BDX","5");
define("SOURCE_TRULIA","6");
define("SOURCE_ZILLOW","7");

/******************* table defines **************************/

  //CrossApp Table Defines
  define("CONTACT_DBTABLE","public.contact");
  define("COMPY_DBTABLE","public.company");
  define("STATE_DBTABLE","state");
  define("ZIPCODE_DBTABLE","zipcodes");
  define("COUNTRY_DBTABLE","country");

  // Contract Schema Defines
  define("C_DBTABLE","contract.contracts");
  define("CDATES_DBTABLE","contract.contract_dates");
  define("VIEWDATES_DBTABLE","contract.view_contracts_with_dates");
  define("VIEWCDS_DBTABLE","contract.view_contracts_dates_status");
  define("VIEWCUR_DBTABLE","contract.view_contracts_current_status");
  define("SP_DBTABLE","contract.sellers_purchasers");
  define("SP_VIEW","contract.view_sellers_purchasers");
  define("VNRLOPT_DBTABLE","contract.view_nrl_option");
  define("NRLOPT_DBTABLE","contract.nrl_option");
  define("NRLLOCA_DBTABLE","contract.nrl_location");
  define("STATUS_DBTABLE","contract.status_option");
  define("NGHDOPT_DBTABLE","contract.neighborhood_option");
  define("CONTACT_VIEW","contract.view_contacts");
  define("COMPY_VIEW","contract.view_company");
  define("CAGENTS_DBTABLE","contract.contract_agents");
  define("COMMCONFIG_DBTABLE","contract.community_config");
  define("LDESCRIP_DBTABLE","contract.legal_description_types");
  define("VIEWLDESCRIP_DBTABLE","contract.view_legaldescrip_types");

  //Lot Tables defines
  define("L_DBTABLE","lots.lots_table");
  define("VIEWLEGAL_DBTABLE","lots.view_lots_legal_fields");
  define("VIEWLOTS_DBTABLE","lots.view_lots");
  define("LOTS_VIEW","lots.view_lots_legal");
  define("LOTTYPES_DBTABLE","lots.lot_types"); 
  define("LOTSTATUS_DBTABLE","lots.lot_status"); 

  //home Showcase Defines
  define("HOMES_DBTABLE","lots.homes");
  define("HOMEAGENTS_DBTABLE","lots.home_agents");
  define("AADAMS_DBTABLE","lots.pictures");
  define("VIEWHOMESTAT_DBTABLE","lots.view_homes_buildstatus");
  define("BUILDSTATUS_DBTABLE","lots.build_status");
  define("BUILDDATES_DBTABLE","lots.build_dates");

  //Home Builders and Floorplans Defines
  define("BUILDER_DBTABLE","lots.builder");
  define("VIEWBUILDER_DBTABLE","lots.view_builder");
  define("BUILDERMODEL_DBTABLE","lots.builder_floorplan");
  define("VIEWBUILDERMODEL_DBTABLE","lots.view_builder_floorplan");
  define("MODEL_DBTABLE","lots.floorplan");
  define("MODELPICS_DBTABLE","lots.floorplan_pictures");
  define("MODELLOCALE_DBTABLE","lots.floorplan_locations");
  define("VIEWMODELLID_DBTABLE","lots.view_floorplan_locationid");

  // OpenReports path define
  //define("OREPORTS_URL","http://contracts.eastwestpartners.net:8080/openreports");
  //define("OREPORTS_IMAGES","contracts.eastwestpartners.net:8080/openreports/images");
 
  // Presentation Schema (Flash Maps and Map Reports) defines 
  define("MAPLOTS","presentation.map_lots");
  define("MAPBUILDERS","presentation.map_builders");
  define("MAPFLOORPLANS","presentation.map_floorplans");
  define("MAPPICTURES","presentation.map_pictures");
  define("MAPACCNT","lots.view_map_accounting");
  define("OUIJACAT","lots.ouija_category");
  define("OUIJALOTS","lots.ouija_lots");

  //CM_SessionCapture and User table
  define("SNCAP_DBTABLE","user_session_info");
  define("UQUERY_DBTABLE","user_saved_queries");

  define("AMENITIES_DBTABLE","lots.amenities");   
    
  define("VIEWLOTSBLDR","lots.view_lots_builder");


/*************************************************************
  reports.  ids of global reports used by other components
*************************************************************/
define("SOLD_REPORT","1");
define("UNDERCONTRACT_REPORT","2");
define("LISTING_REPORT","3");
define("PROSPECTLEAD_REPORT","11");
define("BEBACK_REPORT","15");
define("AGENTWALK_REPORT","13");
define("HOME_SOLD_REPORT","19");
define("HOME_UNDERCONTRACT_REPORT","21");
define("HOME_LISTING_REPORT","23");
define("LOT_SOLD_REPORT","20");
define("LOT_UNDERCONTRACT_REPORT","22");

