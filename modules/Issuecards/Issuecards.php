<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
require_once('data/CRMEntity.php');
require_once('data/Tracker.php');
require_once('user_privileges/default_module_view.php');
require_once('modules/InventoryDetails/InventoryDetails.php');

class Issuecards extends CRMEntity {
	var $db, $log; // Used in class functions of CRMEntity

	var $table_name = 'vtiger_issuecards';
	var $table_index= 'issuecardid';
	var $column_fields = Array();

	/** Indicator if this is a custom module or standard module */
	var $IsCustomModule = true;
	var $HasDirectImageField = false;
	var $tab_name = Array('vtiger_crmentity','vtiger_issuecards','vtiger_issuecardscf');
	var $tab_name_index = Array('vtiger_crmentity'=>'crmid','vtiger_issuecards'=>'issuecardid','vtiger_issuecardscf'=>'issuecardid');
	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array('vtiger_issuecardscf', 'issuecardid');
	var $entity_table = 'vtiger_crmentity';

	var $object_name = 'Issuecards';

	var $update_product_array = Array();

	// This is the list of vtiger_fields that are in the lists.
	var $list_fields = Array (
		'Issuecards No'=> Array('project' => 'issuecards_no'),
		'ctoid' => Array('issuecards' => 'ctoid'),
		'accid' => Array('issuecards' => 'accid'),
		'fecha_pago' => Array('issuecards' => 'fecha_pago'),
		'invoicestatus' => Array('issuecards' => 'invoicestatus'),
		'Total'=>Array('issuecards'=>'hdnGrandTotal'),
		'Assigned To' => Array('crmentity' => 'smownerid')
	);
	var $list_fields_name = Array(
		'Issuecards No'=> 'issuecards_no',
		'ctoid' => 'ctoid',
		'accid' => 'accid',
		'fecha_pago' => 'fecha_pago',
		'invoicestatus' => 'invoicestatus',
		'Total'=> 'hdnGrandTotal',
		'Assigned To' => 'assigned_user_id'
	);

	// Make the field link to detail view from list view (Fieldname)
	var $list_link_field = 'issuecards_no';

	// For Popup listview and UI type support
	var $search_fields = Array(
		'Issuecards No'=> Array('issuecards' => 'issuecards_no'),
		'ctoid' => Array('issuecards' => 'ctoid'),
		'accid' => Array('issuecards' => 'accid'),
		'fecha_pago' => Array('issuecards' => 'fecha_pago'),
		'invoicestatus' => Array('issuecards' => 'invoicestatus'),
	);
	var $search_fields_name = Array(
		'Issuecards No'=> 'issuecards_no',
		'ctoid' => 'ctoid',
		'accid' => 'accid',
		'fecha_pago' => 'fecha_pago',
		'invoicestatus' => 'invoicestatus',
	);

	// For Popup window record selection
	var $popup_fields = Array('issuecards_no', 'startdate');

	var $sortby_fields = Array();

	// For Alphabetical search
	var $def_basicsearch_col = 'issuecards_no';

	// Column value to use on detail view record text display
	var $def_detailview_recname = 'issuecards_no';

	// Required Information for enabling Import feature
	var $required_fields = Array('issuecards_no'=>1);

	// Callback function list during Importing
	var $special_functions = Array('set_import_assigned_user');

	var $default_order_by = 'issuecards_no';
	var $default_sort_order='ASC';
	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vtiger_field.fieldname values.
	var $mandatory_fields = Array('createdtime', 'modifiedtime', 'issuecards_no');

	function save_module($module) {
		global $updateInventoryProductRel_deduct_stock;
		if ($this->HasDirectImageField) {
			$this->insertIntoAttachment($this->id,$module);
		}
		$updateInventoryProductRel_deduct_stock = true;

		//in ajax save we should not call this function, because this will delete all the existing product values
		if(inventoryCanSaveProductLines($_REQUEST, 'Issuecards')) {
			//Based on the total Number of rows we will save the product relationship with this entity
			saveInventoryProductDetails($this, 'Issuecards');
			if(vtlib_isModuleActive("InventoryDetails"))
				InventoryDetails::createInventoryDetails($this,'Issuecards');
		} else if($_REQUEST['action'] == 'IssuecardsAjax' || $_REQUEST['action'] == 'MassEditSave') {
			$updateInventoryProductRel_deduct_stock = false;
		}

		// Update the currency id and the conversion rate for the invoice
		$update_query = "update vtiger_issuecards set currency_id=?, conversion_rate=? where issuecardid=?";
		$update_params = array($this->column_fields['currency_id'], $this->column_fields['conversion_rate'], $this->id); 
		$this->db->pquery($update_query, $update_params);
	}

	function restore($module, $id) {
		global $current_user;
		$this->db->println("TRANS restore starts $module");
		$this->db->startTransaction();		

		$this->db->pquery('UPDATE vtiger_crmentity SET deleted=0 WHERE crmid = ?', array($id));
		//Restore related entities/records
		$this->restoreRelatedRecords($module,$id);

		$product_info = $this->db->pquery("SELECT productid, quantity, sequence_no, incrementondel from vtiger_inventoryproductrel WHERE id=?",array($id));
		$numrows = $this->db->num_rows($product_info);
		for($index = 0;$index < $numrows;$index++){
			$productid = $this->db->query_result($product_info,$index,'productid');
			$qty = $this->db->query_result($product_info,$index,'quantity');
			deductFromProductStock($productid,$qty);
		}
		
		$this->db->completeTransaction();
		$this->db->println("TRANS restore ends");
	}

	/*Function to create records in current module.
	**This function called while importing records to this module*/
	function createRecords($obj) {
		$createRecords = createRecords($obj);
		return $createRecords;
	}

	/*Function returns the record information which means whether the record is imported or not
	**This function called while importing records to this module*/
	function importRecord($obj, $inventoryFieldData, $lineItemDetails) {
		$entityInfo = importRecord($obj, $inventoryFieldData, $lineItemDetails);
		return $entityInfo;
	}

	/*Function to return the status count of imported records in current module.
	**This function called while importing records to this module*/
	function getImportStatusCount($obj) {
		$statusCount = getImportStatusCount($obj);
		return $statusCount;
	}

	function undoLastImport($obj, $user) {
		$undoLastImport = undoLastImport($obj, $user);
	}

	/** Function to export the lead records in CSV Format
	* @param reference variable - where condition is passed when the query is executed
	* Returns Export Issuecards Query.
	*/
	function create_export_query($where) {
		global $log, $current_user;
		$log->debug("Entering create_export_query(".$where.") method ...");

		include("include/utils/ExportUtils.php");

		//To get the Permitted fields query and the permitted fields list
		$sql = getPermittedFieldsQuery("Issuecards", "detail_view");
		$fields_list = getFieldsListFromQuery($sql);
		$fields_list .= getInventoryFieldsForExport($this->table_name);
		$userNameSql = getSqlForNameInDisplayFormat(array('first_name'=>'vtiger_users.first_name', 'last_name' => 'vtiger_users.last_name'), 'Users');

		$query = "SELECT $fields_list FROM ".$this->entity_table."
			INNER JOIN vtiger_issuecards ON vtiger_issuecards.issuecardid = vtiger_crmentity.crmid
			LEFT JOIN vtiger_issuecardscf ON vtiger_issuecardscf.issuecardid = vtiger_issuecards.issuecardid
			LEFT JOIN vtiger_inventoryproductrel ON vtiger_inventoryproductrel.id = vtiger_issuecards.issuecardid
			LEFT JOIN vtiger_products ON vtiger_products.productid = vtiger_inventoryproductrel.productid
			LEFT JOIN vtiger_service ON vtiger_service.serviceid = vtiger_inventoryproductrel.productid
			LEFT JOIN vtiger_contactdetails ON vtiger_contactdetails.contactid = vtiger_issuecards.ctoid
			LEFT JOIN vtiger_account ON vtiger_account.accountid = vtiger_issuecards.accid
			LEFT JOIN vtiger_currency_info ON vtiger_currency_info.id = vtiger_issuecards.currency_id
			LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid
			LEFT JOIN vtiger_users ON vtiger_users.id = vtiger_crmentity.smownerid";

		$query .= $this->getNonAdminAccessControlQuery('Issuecards',$current_user);
		$where_auto = " vtiger_crmentity.deleted=0";

		if($where != "") {
			$query .= " where ($where) AND ".$where_auto;
		} else {
			$query .= " where ".$where_auto;
		}

		$log->debug("Exiting create_export_query method ...");
		return $query;
	}

	/**
	 * Invoked when special actions are performed on the module.
	 * @param String Module name
	 * @param String Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
	 */
	function vtlib_handler($modulename, $event_type) {
		global $adb;
		require_once('include/events/include.inc');
		include_once('vtlib/Vtiger/Module.php');
		if($event_type == 'module.postinstall') {
			// TODO Handle post installation actions
			$modAccounts=Vtiger_Module::getInstance('Accounts');
			$modContacts=Vtiger_Module::getInstance('Contacts');
			$modInvD=Vtiger_Module::getInstance('InventoryDetails');
			$modIss=Vtiger_Module::getInstance('Issuecards');
			if ($modAccounts) $modAccounts->setRelatedList($modIss, 'Issuecards', Array('ADD'),'get_dependents_list');
			if ($modContacts) $modContacts->setRelatedList($modIss, 'Issuecards', Array('ADD'),'get_dependents_list');
			if ($modInvD){
				$field = Vtiger_Field::getInstance('related_to',$modInvD);
				$field->setRelatedModules(array('Issuecards'));
				$modIss->setRelatedList($modInvD, 'InventoryDetails', Array(''),'get_dependents_list');
			}
			//Add Gendoc to Issuecards
			if(vtlib_isModuleActive("evvtgendoc")){
				$modIss->addLink('LISTVIEWBASIC','Generate Document',"javascript:showgendoctemplates('\$MODULE\$');");
				$modIss->addLink('DETAILVIEWWIDGET','Generate Document',"module=evvtgendoc&action=evvtgendocAjax&file=DetailViewWidget&formodule=\$MODULE\$&forrecord=\$RECORD\$",'modules/evvtgendoc/evvtgendoc.gif');
			}

			$emm = new VTEntityMethodManager($adb);
			// Adding EntityMethod for Updating Products data after updating PurchaseOrder
			$emm->addEntityMethod("Issuecards","UpdateInventory","include/InventoryHandler.php","handleInventoryProductRel");
			// Creating Workflow for Updating Inventory Stock on Issuecards
			$vtWorkFlow = new VTWorkflowManager($adb);
			$invWorkFlow = $vtWorkFlow->newWorkFlow("Issuecards");
			$invWorkFlow->test = '[{"fieldname":"pslip_no","operation":"does not contain","value":"`!`"}]';
			$invWorkFlow->description = "UpdateInventoryProducts On Every Save";
			$invWorkFlow->defaultworkflow = 1;
			$vtWorkFlow->save($invWorkFlow);
		
			$tm = new VTTaskManager($adb);
			$task = $tm->createTask('VTEntityMethodTask', $invWorkFlow->id);
			$task->active=true;
			$task->methodName = "UpdateInventory";
			$task->summary="Update product stock";
			$tm->saveTask($task);

			$this->setModuleSeqNumber('configure', $modulename, 'pslip-', '0000001');
		} else if($event_type == 'module.disabled') {
			// TODO Handle actions when this module is disabled.
		} else if($event_type == 'module.enabled') {
			// TODO Handle actions when this module is enabled.
		} else if($event_type == 'module.preuninstall') {
			// TODO Handle actions when this module is about to be deleted.
		} else if($event_type == 'module.preupdate') {
			// TODO Handle actions before this module is updated.
		} else if($event_type == 'module.postupdate') {
			// TODO Handle actions after this module is updated.
			$modInvD=Vtiger_Module::getInstance('InventoryDetails');
			$modIss=Vtiger_Module::getInstance('Issuecards');
			//Add subject field to can import and export
			$block = Vtiger_Block::getInstance('LBL_ISSUECARDS_INFO', $modIss);
			$field = Vtiger_Field::getInstance('subject',$modIss);
			if (!$field) {
				$field1 = new Vtiger_Field();
				$field1->name = 'subject';
				$field1->label= 'subject';
				$field1->table = 'vtiger_issuecards';
				$field1->column = 'subject';
				$field1->columntype = 'VARCHAR(100)';
				$field1->sequence = 3;
				$field1->uitype = 1;
				$field1->typeofdata = 'V~O';
				$field1->displaytype = 1;
				$field1->presence = 0;
				$block->addField($field1);
			}
			if ($modInvD){
				$field = Vtiger_Field::getInstance('related_to',$modInvD);
				$field->setRelatedModules(array('Issuecards'));
				$modIss->setRelatedList($modInvD, 'InventoryDetails', Array(''),'get_dependents_list');
			}
			//Add Gendoc to Issuecards
			if(vtlib_isModuleActive("evvtgendoc")){
				$modIss->addLink('LISTVIEWBASIC','Generate Document',"javascript:showgendoctemplates('\$MODULE\$');");
				$modIss->addLink('DETAILVIEWWIDGET','Generate Document',"module=evvtgendoc&action=evvtgendocAjax&file=DetailViewWidget&formodule=\$MODULE\$&forrecord=\$RECORD\$",'modules/evvtgendoc/evvtgendoc.gif');
			}
			$emm = new VTEntityMethodManager($adb);
			// Adding EntityMethod for Updating Products data after updating Issuecards
			$emm->addEntityMethod("Issuecards","UpdateInventory","include/InventoryHandler.php","handleInventoryProductRel");
			// Creating Workflow for Updating Inventory Stock on Issuecards
			$vtWorkFlow = new VTWorkflowManager($adb);
			$invWorkFlow = $vtWorkFlow->newWorkFlow("Issuecards");
			$invWorkFlow->test = '[{"fieldname":"pslip_no","operation":"does not contain","value":"`!`"}]';
			$invWorkFlow->description = "UpdateInventoryProducts On Every Save";
			$invWorkFlow->defaultworkflow = 1;
			$vtWorkFlow->save($invWorkFlow);

			$tm = new VTTaskManager($adb);
			$task = $tm->createTask('VTEntityMethodTask', $invWorkFlow->id);
			$task->active=true;
			$task->methodName = "UpdateInventory";
			$task->summary="Update product stock";
			$tm->saveTask($task);
		}
	}

	/**
	* Function to get Issuecards related Task & Event which have activity type Held, Completed or Deferred.
	* @param  integer   $id
	* returns related Task or Event record in array format
	*/
	function get_history($id, $cur_tab_id, $rel_tab_id, $actions)
	{
			global $log;
			$log->debug("Entering get_history(".$id.") method ...");
			$query = "SELECT vtiger_activity.activityid, vtiger_activity.subject, vtiger_activity.status,
				vtiger_activity.eventstatus, vtiger_activity.activitytype,vtiger_activity.date_start,
				vtiger_activity.due_date, vtiger_activity.time_start,vtiger_activity.time_end,
				vtiger_crmentity.modifiedtime, vtiger_crmentity.createdtime, 
				vtiger_crmentity.description,case when (vtiger_users.user_name not like '') then vtiger_users.user_name else vtiger_groups.groupname end as user_name
				from vtiger_activity
				inner join vtiger_seactivityrel on vtiger_seactivityrel.activityid=vtiger_activity.activityid
				inner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_activity.activityid
				left join vtiger_groups on vtiger_groups.groupid=vtiger_crmentity.smownerid
				left join vtiger_users on vtiger_users.id=vtiger_crmentity.smownerid
				where (vtiger_activity.activitytype = 'Meeting' or vtiger_activity.activitytype='Call' or vtiger_activity.activitytype='Task')
				and (vtiger_activity.status = 'Completed' or vtiger_activity.status = 'Deferred' or (vtiger_activity.eventstatus = 'Held' and vtiger_activity.eventstatus != ''))
				and vtiger_seactivityrel.crmid=".$id." and vtiger_crmentity.deleted = 0";
		//Don't add order by, because, for security, one more condition will be added with this query in include/RelatedListView.php

		$log->debug("Exiting get_history method ...");
		return getHistory('Issucards',$query,$id);
	}

	/*
	 * Function to get the secondary query part of a report
	 * @param - $module primary module name
	 * @param - $secmodule secondary module name
	 * returns the query string formed on fetching the related data for report for secondary module
	 */
	function generateReportsSecQuery($module,$secmodule,$type = '',$where_condition = ''){
		$query = $this->getRelationQuery($module,$secmodule,"vtiger_issuecards","issuecardid");
		$query .= " left join vtiger_currency_info as vtiger_currency_info$secmodule on vtiger_currency_info$secmodule.id = vtiger_issuecards.currency_id ";
		if(($type !== 'COLUMNSTOTOTAL') || ($type == 'COLUMNSTOTOTAL' && $where_condition == 'add')) {
			$query .= "left join vtiger_inventoryproductrel as vtiger_inventoryproductrelIssuecards on vtiger_issuecards.issuecardid = vtiger_inventoryproductrelIssuecards.id
				left join vtiger_products as vtiger_productsIssuecards on vtiger_productsIssuecards.productid = vtiger_inventoryproductrelIssuecards.productid
				left join vtiger_service as vtiger_serviceIssuecards on vtiger_serviceIssuecards.serviceid = vtiger_inventoryproductrelIssuecards.productid ";
		}
		return $query;
	}
}
?>
