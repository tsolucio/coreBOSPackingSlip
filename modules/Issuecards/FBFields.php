<?php
$moduleTitle="TSolucio::vtiger CRM Customizations";

echo '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
echo "<html><head><title>vtlib $moduleTitle</title>";
echo '<style type="text/css">@import url("themes/softed/style.css");br { display: block; margin: 2px; }</style>';
echo '</head><body class=small style="font-size: 12px; margin: 2px; padding: 2px; background-color:#f7fff3; ">';
echo '<table width=100% border=0><tr><td align=left>';
echo '<a href="index.php"><img src="themes/softed/images/vtiger-crm.gif" alt="vtiger CRM" title="vtiger CRM" border=0></a>';
echo '</td><td align=center style="background-image: url(\'vtlogowmg.png\'); background-repeat: no-repeat; background-position: center;">';
echo "<b><H1>$moduleTitle</H1></b>";
echo '</td><td align=right>';
echo '<a href="www.vtiger-spain.com"><img src="vtspain.gif" alt="vtiger-spain" title="vtiger-spain" border=0 height=100></a>';
echo '</td></tr></table>';
echo '<hr style="height: 1px">';

// Turn on debugging level
$Vtiger_Utils_Log = true;

include_once('vtlib/Vtiger/Module.php');

$module = Vtiger_Module::getInstance('Issuecards');

if($module) {
    $blockInstance = VTiger_Block::getInstance('LBL_ISSUECARDS_INFO',$module);

	if($blockInstance) {

		$field = new Vtiger_Field();
		$field->name = 'num_fb';
		$field->label= 'num_fb';
		$field->table = $module->basetable;
		$field->column = 'num_fb';
		$field->columntype = 'VARCHAR(21)';
		$field->uitype = 1;
		$field->displaytype = 1;
		$field->typeofdata = 'V~M';
		$field->presence = 2;
		$blockInstance->addField($field);

		$field = new Vtiger_Field();
		$field->name = 'ctoid';
		$field->label= 'ctoid';
		$field->table = $module->basetable;
		$field->column = 'ctoid';
		$field->columntype = 'INT(11)';
		$field->uitype = 10;
		$field->displaytype = 1;
		$field->typeofdata = 'I~O';
		$field->presence = 2;
		$blockInstance->addField($field);
		$field->setRelatedModules(Array('Contacts'));

		$field = new Vtiger_Field();
		$field->name = 'accid';
		$field->label= 'accid';
		$field->table = $module->basetable;
		$field->column = 'accid';
		$field->columntype = 'INT(11)';
		$field->uitype = 10;
		$field->displaytype = 1;
		$field->typeofdata = 'I~O';
		$field->presence = 2;
		$blockInstance->addField($field);
		$field->setRelatedModules(Array('Accounts'));

		$field = new Vtiger_Field();
		$field->name = 'fecha_pago';
		$field->label= 'fecha_pago';
		$field->table = $module->basetable;
		$field->column = 'fecha_pago';
		$field->columntype = 'DATE';
		$field->uitype = 5;
		$field->displaytype = 1;
		$field->typeofdata = 'D~O';
		$field->presence = 2;
		$blockInstance->addField($field);

		$field = new Vtiger_Field();
		$field->name = 'invoicestatus';
		$field->label= 'invoicestatus';
		$field->table = $module->basetable;
		$field->column = 'invoicestatus';
		$field->columntype = 'VARCHAR(51)';
		$field->uitype = 15;
		$field->displaytype = 1;
		$field->typeofdata = 'V~O';
		$field->presence = 2;
		$blockInstance->addField($field);
		$field->setPicklistValues(array());

		$field = new Vtiger_Field();
		$field->name = 'calle';
		$field->label= 'calle';
		$field->table = $module->basetable;
		$field->column = 'calle';
		$field->columntype = 'VARCHAR(81)';
		$field->uitype = 1;
		$field->displaytype = 1;
		$field->typeofdata = 'V~O';
		$field->presence = 2;
		$blockInstance->addField($field);
		
		$field = new Vtiger_Field();
		$field->name = 'poblacion';
		$field->label= 'poblacion';
		$field->table = $module->basetable;
		$field->column = 'poblacion';
		$field->columntype = 'VARCHAR(81)';
		$field->uitype = 1;
		$field->displaytype = 1;
		$field->typeofdata = 'V~O';
		$field->presence = 2;
		$blockInstance->addField($field);
		
		$field = new Vtiger_Field();
		$field->name = 'provincia';
		$field->label= 'provincia';
		$field->table = $module->basetable;
		$field->column = 'provincia';
		$field->columntype = 'VARCHAR(81)';
		$field->uitype = 1;
		$field->displaytype = 1;
		$field->typeofdata = 'V~O';
		$field->presence = 2;
		$blockInstance->addField($field);

		$field = new Vtiger_Field();
		$field->name = 'cpostal';
		$field->label= 'cpostal';
		$field->table = $module->basetable;
		$field->column = 'cpostal';
		$field->columntype = 'VARCHAR(8)';
		$field->uitype = 1;
		$field->displaytype = 1;
		$field->typeofdata = 'V~O';
		$field->presence = 2;
		$blockInstance->addField($field);

		$field = new Vtiger_Field();
		$field->name = 'pais';
		$field->label= 'pais';
		$field->table = $module->basetable;
		$field->column = 'pais';
		$field->columntype = 'VARCHAR(81)';
		$field->uitype = 1;
		$field->displaytype = 1;
		$field->typeofdata = 'V~O';
		$field->presence = 2;
		$blockInstance->addField($field);
		
		echo "<br><b>Added Field to ".$module->name." module.</b><br>";

	} else {
		echo "<b>Failed to find ".$module->name." block</b><br>";
	}

} else {
	echo "<b>Failed to find ".$module->name." module.</b><br>";
}

echo '</body></html>';

?>
