<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
global $app_strings, $mod_strings, $current_language, $currentModule, $theme, $adb;
require_once 'Smarty_setup.php';

$focus = CRMEntity::getInstance($currentModule);

$encode_val = (!empty($_REQUEST['encode_val']) ? vtlib_purify($_REQUEST['encode_val']) : '');
$decode_val=base64_decode($encode_val);

$saveimage=isset($_REQUEST['saveimage'])?vtlib_purify($_REQUEST['saveimage']):'false';
$errormessage=isset($_REQUEST['error_msg'])?vtlib_purify($_REQUEST['error_msg']):'false';
$image_error=isset($_REQUEST['image_error'])?vtlib_purify($_REQUEST['image_error']):'false';

$smarty = new vtigerCRM_Smarty();
// Identify this module as custom module.
$smarty->assign('CUSTOM_MODULE', $focus->IsCustomModule);
$smarty->assign('CONVERT_MODE', '');

$category = getParentTab($currentModule);
$record = isset($_REQUEST['record']) ? vtlib_purify($_REQUEST['record']) : null;
$isduplicate = isset($_REQUEST['isDuplicate']) ? vtlib_purify($_REQUEST['isDuplicate']) : null;

$searchurl = getBasic_Advance_SearchURL();
$smarty->assign('SEARCH', $searchurl);

if ($record) {
	$focus->id = $record;
	$focus->mode = 'edit';
	$focus->retrieve_entity_info($record, $currentModule);
	$product_base_currency = getProductBaseCurrency($focus->id, $currentModule);
} else {
	$product_base_currency = fetchCurrency($current_user->id);
}
if ($image_error=='true') {
	$explode_decode_val=explode('&', $decode_val);
	for ($i=1; $i<count($explode_decode_val); $i++) {
		$test=$explode_decode_val[$i];
		$values=explode("=", $test);
		$field_name_val=$values[0];
		$field_value=$values[1];
		$focus->column_fields[$field_name_val]=$field_value;
	}
}

if ($isduplicate == 'true') {
	$focus->id = '';
	$focus->mode = '';
	$focus->column_fields['isduplicatedfromrecordid'] = $record; // in order to support duplicate workflows
	$smarty->assign('__cbisduplicatedfromrecordid', $record);
	$_REQUEST['cbcustominfo1'] = 'duplicatingproduct';
	$_REQUEST['cbcustominfo2'] = $record;
}
$focus->preEditCheck($_REQUEST, $smarty);
if (!empty($_REQUEST['save_error']) && $_REQUEST['save_error'] == 'true') {
	if (!empty($_REQUEST['encode_val'])) {
		global $current_user;
		$encode_val = vtlib_purify($_REQUEST['encode_val']);
		$decode_val = base64_decode($encode_val);
		$explode_decode_val = explode('&', trim($decode_val, '&'));
		$tabid = getTabid($currentModule);
		foreach ($explode_decode_val as $fieldvalue) {
			$value = explode("=", $fieldvalue);
			$field_name_val = $value[0];
			$field_value =urldecode($value[1]);
			$finfo = VTCacheUtils::lookupFieldInfo($tabid, $field_name_val);
			if ($finfo !== false) {
				switch ($finfo['uitype']) {
					case '56':
						$field_value = $field_value=='on' ? '1' : '0';
						break;
					case '7':
					case '9':
					case '72':
						$field_value = CurrencyField::convertToDBFormat($field_value, null, true);
						break;
					case '71':
						$field_value = CurrencyField::convertToDBFormat($field_value);
						break;
					case '33':
					case '3313':
					case '3314':
						if (is_array($field_value)) {
							$field_value = implode(' |##| ', $field_value);
						}
						break;
				}
			}
			$focus->column_fields[$field_name_val] = $field_value;
		}
	}
	$errormessageclass = isset($_REQUEST['error_msgclass']) ? vtlib_purify($_REQUEST['error_msgclass']) : '';
	$errormessage = isset($_REQUEST['error_msg']) ? vtlib_purify($_REQUEST['error_msg']) : '';
	$smarty->assign('ERROR_MESSAGE_CLASS', $errormessageclass);
	$smarty->assign('ERROR_MESSAGE', $errormessage);
} elseif ($focus->mode != 'edit') {
	setObjectValuesFromRequest($focus);
}
$smarty->assign('MASS_EDIT', '0');
$disp_view = getView($focus->mode);
$blocks = getBlocks($currentModule, $disp_view, $focus->mode, $focus->column_fields);
$smarty->assign('BLOCKS', $blocks);
$basblocks = getBlocks($currentModule, $disp_view, $focus->mode, $focus->column_fields, 'BAS');
$smarty->assign('BASBLOCKS', $basblocks);
$advblocks = getBlocks($currentModule, $disp_view, $focus->mode, $focus->column_fields, 'ADV');
$smarty->assign('ADVBLOCKS', $advblocks);

$custom_blocks = getCustomBlocks($currentModule, $disp_view);
$smarty->assign('CUSTOMBLOCKS', $custom_blocks);
$smarty->assign('FIELDS', $focus->column_fields);

//needed when creating a new product with a default vendor name passed
if (isset($_REQUEST['name']) && is_null($focus->name)) {
	$focus->name = $_REQUEST['name'];
}
if (isset($_REQUEST['vendorid']) && is_null($focus->vendorid)) {
	$focus->vendorid = $_REQUEST['vendorid'];
}

$smarty->assign('OP_MODE', $disp_view);
$smarty->assign('APP', $app_strings);
$smarty->assign('MOD', $mod_strings);
$smarty->assign('MODULE', $currentModule);
$smarty->assign('SINGLE_MOD', 'SINGLE_'.$currentModule);
$smarty->assign('CATEGORY', $category);
$smarty->assign("THEME", $theme);
$smarty->assign('IMAGE_PATH', "themes/$theme/images/");
$smarty->assign('ID', $focus->id);
$smarty->assign('MODE', $focus->mode);
$smarty->assign('CREATEMODE', isset($_REQUEST['createmode']) ? vtlib_purify($_REQUEST['createmode']) : '');

$smarty->assign('CHECK', Button_Check($currentModule));
$smarty->assign('DUPLICATE', $isduplicate);

if ($focus->mode == 'edit' || $isduplicate == 'true') {
	$recordName = array_values(getEntityName($currentModule, $record));
	$recordName = $recordName[0];
	$smarty->assign('NAME', $recordName);
	$smarty->assign('UPDATEINFO', updateInfo($record));
}

if (isset($_REQUEST['return_module'])) {
	$smarty->assign('RETURN_MODULE', vtlib_purify($_REQUEST['return_module']));
}
if (isset($_REQUEST['return_action'])) {
	$smarty->assign('RETURN_ACTION', vtlib_purify($_REQUEST['return_action']));
}
if (isset($_REQUEST['return_id'])) {
	$smarty->assign('RETURN_ID', vtlib_purify($_REQUEST['return_id']));
}
if (isset($_REQUEST['return_viewname'])) {
	$smarty->assign('RETURN_VIEWNAME', vtlib_purify($_REQUEST['return_viewname']));
}
$upload_maxsize = GlobalVariable::getVariable('Application_Upload_MaxSize', 3000000, $currentModule);
$smarty->assign('UPLOADSIZE', $upload_maxsize/1000000); //Convert to MB
$smarty->assign('UPLOAD_MAXSIZE', $upload_maxsize);

// Field Validation Information
$tabid = getTabid($currentModule);
$validationData = getDBValidationData($focus->tab_name, $tabid);
$validationArray = split_validationdataArray($validationData);

$smarty->assign('VALIDATION_DATA_FIELDNAME', $validationArray['fieldname']);
$smarty->assign('VALIDATION_DATA_FIELDDATATYPE', $validationArray['datatype']);
$smarty->assign('VALIDATION_DATA_FIELDLABEL', $validationArray['fieldlabel']);

// In case you have a date field
$smarty->assign('CALENDAR_LANG', $app_strings['LBL_JSCALENDAR_LANG']);
$smarty->assign('CALENDAR_DATEFORMAT', parse_calendardate($app_strings['NTC_DATE_FORMAT']));

// Module Sequence Numbering
$mod_seq_field = getModuleSequenceField($currentModule);
if ($focus->mode != 'edit' && $mod_seq_field != null) {
	$autostr = getTranslatedString('MSG_AUTO_GEN_ON_SAVE');
	list($mod_seq_string, $mod_seq_prefix, $mod_seq_no, $doNative) = cbEventHandler::do_filter('corebos.filter.ModuleSeqNumber.get', array('', '', '', true));
	if ($doNative) {
		$mod_seq_string = $adb->pquery("SELECT prefix, cur_id from vtiger_modentity_num where semodule = ? and active=1", array($currentModule));
		$mod_seq_prefix = $adb->query_result($mod_seq_string, 0, 'prefix');
		$mod_seq_no = $adb->query_result($mod_seq_string, 0, 'cur_id');
	}
	if ($adb->num_rows($mod_seq_string) == 0 || $focus->checkModuleSeqNumber($focus->table_name, $mod_seq_field['column'], $mod_seq_prefix.$mod_seq_no)) {
		$smarty->assign('ERROR_MESSAGE_CLASS', 'cb-alert-warning');
		$smarty->assign('ERROR_MESSAGE', '<b>'. getTranslatedString($mod_seq_field['label']). ' '. getTranslatedString('LBL_NOT_CONFIGURED')
			.' - '. getTranslatedString('LBL_PLEASE_CLICK') .' <a href="index.php?module=Settings&action=CustomModEntityNo&parenttab=Settings&selmodule='.$currentModule
			.'">'.getTranslatedString('LBL_HERE').'</a> '. getTranslatedString('LBL_TO_CONFIGURE'). ' '. getTranslatedString($mod_seq_field['label']) .'</b>');
	} else {
		$smarty->assign('MOD_SEQ_ID', $autostr);
	}
} else {
	$smarty->assign('MOD_SEQ_ID', $focus->column_fields[$mod_seq_field['name']]);
}

// Gather the help information associated with fields
$smarty->assign('FIELDHELPINFO', vtlib_getFieldHelpInfo($currentModule));

if ($focus->id != '') {
	$smarty->assign('ROWCOUNT', getImageCount($focus->id));
}

//Tax handling (get the available taxes only) - starts
if ($focus->mode == 'edit') {
	$retrieve_taxes = true;
	$productid = $focus->id;
	$tax_details = getTaxDetailsForProduct($productid, 'available_associated');
} elseif (isset($_REQUEST['isDuplicate']) && $_REQUEST['isDuplicate'] == 'true') {
	$retrieve_taxes = true;
	$productid = vtlib_purify($_REQUEST['record']);
	$tax_details = getTaxDetailsForProduct($productid, 'available_associated');
} else {
	$retrieve_taxes = false;
	$productid = 0;
	$tax_details = getAllTaxes('available');
}

for ($i=0; $i<count($tax_details); $i++) {
	$tax_details[$i]['check_name'] = $tax_details[$i]['taxname'].'_check';
	$tax_details[$i]['check_value'] = 0;
}

//For Edit and Duplicate we have to retrieve the product associated taxes and show them
if ($retrieve_taxes) {
	for ($i=0; $i<count($tax_details); $i++) {
		$tax_value = getProductTaxPercentage($tax_details[$i]['taxname'], $productid);
		$tax_details[$i]['percentage'] = $tax_value;
		$tax_details[$i]['check_value'] = 1;
		//if the tax is not associated with the product then we should get the default value and unchecked
		if ($tax_value == '') {
			$tax_details[$i]['check_value'] = 0;
			$tax_details[$i]['percentage'] = getTaxPercentage($tax_details[$i]['taxname']);
		}
	}
}

$smarty->assign('TAX_DETAILS', $tax_details);
//Tax handling - ends

$unit_price = $focus->column_fields['unit_price'];
$price_details = getPriceDetailsForProduct($productid, $unit_price, 'available', $currentModule);
$smarty->assign('PRICE_DETAILS', $price_details);

$base_currency = 'curname' . $product_base_currency;
$smarty->assign('BASE_CURRENCY', $base_currency);

if (isset($focus->id) && (empty($_REQUEST['isDuplicate']) || $_REQUEST['isDuplicate'] != 'true')) {
	$is_parent = $focus->isparent_check();
} else {
	$is_parent = 0;
}
$smarty->assign('IS_PARENT', $is_parent);

if (isset($_REQUEST['return_module']) && $_REQUEST['return_module']=='Products' && isset($_REQUEST['return_action']) && isset($_REQUEST['return_id'])) {
	$return_name = getProductName($_REQUEST['return_id']);
	$smarty->assign('RETURN_NAME', $return_name);
}

if ($errormessage==2) {
	$msg =$mod_strings['LBL_MAXIMUM_LIMIT_ERROR'];
	$errormessage ="<B><font color='red'>".$msg."</font></B> <br><br>";
} elseif ($errormessage==3) {
	$msg = $mod_strings['LBL_UPLOAD_ERROR'];
	$errormessage ="<B><font color='red'>".$msg."</font></B> <br><br>";
} elseif ($errormessage=="image") {
	$msg = $mod_strings['LBL_IMAGE_ERROR'];
	$errormessage ="<B><font color='red'>".$msg."</font></B> <br><br>";
} elseif ($errormessage =="invalid") {
	$msg = $mod_strings['LBL_INVALID_IMAGE'];
	$errormessage ="<B><font color='red'>".$msg."</font></B> <br><br>";
} else {
	$errormessage='';
}
if ($errormessage!='') {
	$smarty->assign('ERROR_MESSAGE', $errormessage);
}

$smarty->assign('Product_Maximum_Number_Images', GlobalVariable::getVariable('Product_Maximum_Number_Images', 6));

// Gather the help information associated with fields
$smarty->assign('FIELDHELPINFO', vtlib_getFieldHelpInfo($currentModule));

$cbMapFDEP = Vtiger_DependencyPicklist::getFieldDependencyDatasource($currentModule);
$smarty->assign('FIELD_DEPENDENCY_DATASOURCE', json_encode($cbMapFDEP));

if ($focus->mode == 'edit') {
	$smarty->display('Inventory/InventoryEditView.tpl');
} else {
	$smarty->display('Inventory/InventoryCreateView.tpl');
}
?>
