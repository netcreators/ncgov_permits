<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

// change it once per file at most
$_EXTKEY = 'ncgov_permits';
$_EXTKEYSHORT = 'tx_ncgovpermits';

// add flexform
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY . '_controller']='layout,select_key,pages,recursive';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY . '_controller']='pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($_EXTKEY . '_controller', 'FILE:EXT:' . $_EXTKEY . '/Configuration/FlexForms/flexform_ds.xml');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array('LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_be.xml:controller_title', $_EXTKEY . '_controller'), 'list_type');

// Add TypoScript resource
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript/','Permit publication plugin ts (ncgov_permits)');


$_tableName = $_EXTKEYSHORT . '_permits';
$TCA[$_tableName] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_tableName,
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
		'dynamicConfigFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY).'Configuration/TCA/Permit.php',
		'iconfile'          => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY).'Resources/Public/Icons/icon_' . $_tableName . '.gif',
	),
	'feInterface' => array (
		'fe_admin_fieldList' => 'hidden,name',
	)
);

$_tableName = $_EXTKEYSHORT . '_addresses';
$TCA[$_tableName] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_tableName,
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
		'dynamicConfigFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY).'Configuration/TCA/Address.php',
		'iconfile'          => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY).'Resources/Public/Icons/icon_' . $_tableName . '.gif',
	),
	'feInterface' => array (
		'fe_admin_fieldList' => 'hidden,name',
	)
);

$_tableName = $_EXTKEYSHORT . '_lots';
$TCA[$_tableName] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_tableName,
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
		'dynamicConfigFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY).'Configuration/TCA/Lot.php',
		'iconfile'          => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY).'Resources/Public/Icons/icon_' . $_tableName . '.gif',
	),
	'feInterface' => array (
		'fe_admin_fieldList' => 'hidden,name',
	)
);

$_tableName = $_EXTKEYSHORT . '_coordinates';
$TCA[$_tableName] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_tableName,
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
		'dynamicConfigFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY).'Configuration/TCA/Coordinate.php',
		'iconfile'          => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY).'Resources/Public/Icons/icon_' . $_tableName . '.gif',
	),
	'feInterface' => array (
		'fe_admin_fieldList' => 'hidden,name',
	)
);

$_tableName = $_EXTKEYSHORT . '_log';
$TCA[$_tableName] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_tableName,
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
		'dynamicConfigFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY).'Configuration/TCA/Log.php',
		'iconfile'          => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY).'Resources/Public/Icons/icon_' . $_tableName . '.gif',
	),
	'feInterface' => array (
		'fe_admin_fieldList' => 'hidden,name',
	)
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages($_tableName);
?>