<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

t3lib_div::loadTCA('tt_content');

// change it once per file at most
$_EXTKEY = 'ncgov_permits';
$_EXTKEYSHORT = 'tx_ncgovpermits';

// add flexform
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY . '_controller']='layout,select_key,pages,recursive';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY . '_controller']='pi_flexform';
t3lib_extMgm::addPiFlexFormValue($_EXTKEY . '_controller', 'FILE:EXT:' . $_EXTKEY . '/res/flexform/flexform_ds.xml');

t3lib_extMgm::addPlugin(array('LLL:EXT:' . $_EXTKEY . '/lang/locallang_be.xml:controller_title', $_EXTKEY . '_controller'),'list_type');

// Add TypoScript resource
t3lib_extMgm::addStaticFile($_EXTKEY,'/static/ts/','Permit publication plugin ts (ncgov_permits)');


$_tableShortName = 'permits';
$_tableName = $_EXTKEYSHORT . '_' . $_tableShortName;
$TCA[$_tableName] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:' . $_EXTKEY . '/lang/locallang_tca.xml:' . $_tableName,
		'label'     => 'title',
		'label_alt'     => 'description',
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY uid',
		'delete' 	=> 'deleted',
		'type' 		=> 'type',
		'enablecolumns' => array (
			'disabled' => 'hidden',
		),
		'dividers2tabs' => false,
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca/' . $_tableShortName . '.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'res/icons/icon_' . $_tableName . '.gif',
	),
	'feInterface' => array (
		'fe_admin_fieldList' => 'hidden,name',
	)
);

$_tableShortName = 'addresses';
$_tableName = $_EXTKEYSHORT . '_' . $_tableShortName;
$TCA[$_tableName] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:' . $_EXTKEY . '/lang/locallang_tca.xml:' . $_tableName,
		'label'     => 'zipcode',
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY uid',
		'delete' 	=> 'deleted',
		'enablecolumns' => array (
			'disabled' => 'hidden',
		),
		'dividers2tabs' => false,
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca/' . $_tableShortName . '.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'res/icons/icon_' . $_tableName . '.gif',
	),
	'feInterface' => array (
		'fe_admin_fieldList' => 'hidden,name',
	)
);

$_tableShortName = 'lots';
$_tableName = $_EXTKEYSHORT . '_' . $_tableShortName;
$TCA[$_tableName] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:' . $_EXTKEY . '/lang/locallang_tca.xml:' . $_tableName,
		'label'     => 'cadastremunicipality',
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY uid',
		'delete' 	=> 'deleted',
		'enablecolumns' => array (
			'disabled' => 'hidden',
		),
		'dividers2tabs' => false,
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca/' . $_tableShortName . '.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'res/icons/icon_' . $_tableName . '.gif',
	),
	'feInterface' => array (
		'fe_admin_fieldList' => 'hidden,name',
	)
);

$_tableShortName = 'coordinates';
$_tableName = $_EXTKEYSHORT . '_' . $_tableShortName;
$TCA[$_tableName] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:' . $_EXTKEY . '/lang/locallang_tca.xml:' . $_tableName,
		'label'     => 'uid',
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY uid',
		'delete' 	=> 'deleted',
		'enablecolumns' => array (
			'disabled' => 'hidden',
		),
		'dividers2tabs' => false,
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca/' . $_tableShortName . '.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'res/icons/icon_' . $_tableName . '.gif',
	),
	'feInterface' => array (
		'fe_admin_fieldList' => 'hidden,name',
	)
);

$_tableShortName = 'log';
$_tableName = $_EXTKEYSHORT . '_' . $_tableShortName;
$TCA[$_tableName] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:' . $_EXTKEY . '/lang/locallang_tca.xml:' . $_tableName,
		'label'     => 'message',
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY uid',
		'delete' 	=> 'deleted',
		'enablecolumns' => array (
			'disabled' => 'hidden',
		),
		'dividers2tabs' => false,
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca/' . $_tableShortName . '.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'res/icons/icon_' . $_tableName . '.gif',
	),
	'feInterface' => array (
		'fe_admin_fieldList' => 'hidden,name',
	)
);
t3lib_extMgm::allowTableOnStandardPages($_tableName);
?>