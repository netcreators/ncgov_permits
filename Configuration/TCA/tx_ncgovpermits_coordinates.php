<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$_EXTKEY = 'ncgov_permits';
$_EXTKEYSHORT = 'tx_ncgovpermits';
$_TABLENAME = $_EXTKEYSHORT . '_coordinates';

$tableDefinition = array (
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
		'iconfile'          => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY).'Resources/Public/Icons/icon_' . $_tableName . '.gif',
	),
	'interface' => array (
		'showRecordFieldList' => 'hidden,message,logtype'
	),
	'feInterface' => array (
		'fe_admin_fieldList' => 'hidden,name',
	),
	'columns' => array (
		'coordinatex' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.coordinatex',
			'config' => array (
				'type' => 'input',
				'size' => '12',
				'max' => '255',
			)
		),
		'coordinatey' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.coordinatey',
			'config' => array (
				'type' => 'input',
				'size' => '12',
				'max' => '255',
			)
		),
		'coordinatez' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.coordinatez',
			'config' => array (
				'type' => 'input',
				'size' => '12',
				'max' => '255',
			)
		),
	),
	'types' => array (
		'0' => array('showitem' => ''),
	),
	'palettes' => array (
		'1' => array('showitem' => '')
	)
);
$items = array();
foreach($tableDefinition['columns'] as $name => $data) {
//	if(array_search($name, array('publishdate', 'title', 'link'))) {
//		continue;
//	}
	$items[] = $name;
}
$tableDefinition['types']['0']['showitem'] = implode(', ', $items);

return $tableDefinition;

