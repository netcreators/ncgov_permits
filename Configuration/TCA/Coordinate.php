<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$_EXTKEY = 'ncgov_permits';
$_EXTKEYSHORT = 'tx_ncgovpermits';
$_TABLENAME = $_EXTKEYSHORT . '_coordinates';

$TCA[$_TABLENAME] = array (
	'ctrl' => $TCA[$_TABLENAME]['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'hidden,message,logtype'
	),
	'feInterface' => $TCA[$_TABLENAME]['feInterface'],
	'columns' => array (
		'coordinatex' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/lang/locallang_tca.xml:' . $_TABLENAME . '.coordinatex',
			'config' => array (
				'type' => 'input',
				'size' => '12',
				'max' => '255',
			)
		),
		'coordinatey' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/lang/locallang_tca.xml:' . $_TABLENAME . '.coordinatey',
			'config' => array (
				'type' => 'input',
				'size' => '12',
				'max' => '255',
			)
		),
		'coordinatez' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/lang/locallang_tca.xml:' . $_TABLENAME . '.coordinatez',
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
foreach($TCA[$_TABLENAME]['columns'] as $name => $data) {
//	if(array_search($name, array('publishdate', 'title', 'link'))) {
//		continue;
//	}
	$items[] = $name;
}
$TCA[$_TABLENAME]['types']['0']['showitem'] = implode(', ', $items);

?>