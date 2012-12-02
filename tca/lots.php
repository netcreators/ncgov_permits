<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$_EXTKEY = 'ncgov_permits';
$_EXTKEYSHORT = 'tx_ncgovpermits';
$_TABLENAME = $_EXTKEYSHORT . '_lots';
$_MAX_ALLOWED_FILESIZE = 51200;
$_ALLOWED_FILETYPES = 'doc,pdf,ppt,xls,zip';

require_once(t3lib_extMgm::extPath($_EXTKEY).'controller/class.'.$_EXTKEYSHORT.'_be_controller.php');

$TCA[$_TABLENAME] = array (
	'ctrl' => $TCA[$_TABLENAME]['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'hidden,message,logtype'
	),
	'feInterface' => $TCA[$_TABLENAME]['feInterface'],
	'columns' => array (
		'cadastremunicipality' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/lang/locallang_tca.xml:' . $_TABLENAME . '.cadastremunicipality',
			'config' => array (
				'type' => 'select',
				'items' => array (
					array('LLL:EXT:' . $_EXTKEY . '/lang/locallang_tca.xml:' . $_TABLENAME . '.cadastremunicipality.I.0', ''),
				),
				'size' => 1,
				'maxitems' => 1,
				'itemsProcFunc' => 'tx_ncgovpermits_be_controller->user_getCadastreMunicipalities',
			)
		),
		'section' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/lang/locallang_tca.xml:' . $_TABLENAME . '.section',
			'config' => array (
				'type' => 'input',
				'size' => '4',
				'max' => '2',
			)
		),
		'number' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/lang/locallang_tca.xml:' . $_TABLENAME . '.number',
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