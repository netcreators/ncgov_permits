<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$_EXTKEY = 'ncgov_permits';
$_EXTKEYSHORT = 'tx_ncgovpermits';
$_TABLENAME = $_EXTKEYSHORT . '_addresses';

$TCA[$_TABLENAME] = array (
	'ctrl' => $TCA[$_TABLENAME]['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'hidden,message,logtype'
	),
	'feInterface' => $TCA[$_TABLENAME]['feInterface'],
	'columns' => array (
		'zipcode' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.zipcode',
			'config' => array (
				'type' => 'input',
				'size' => '12',
				'max' => '7',
				//'eval' => ''
			)
		),
		'addressnumber' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.addressnumber',
			'config' => array (
				'type' => 'input',
				'size' => '12',
				'max' => '20',
				'eval' => 'num'
			)
		),
		'addressnumberadditional' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.addressnumberadditional',
			'config' => array (
				'type' => 'input',
				'size' => '12',
				'max' => '20',
			)
		),
		'address' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.address',
			'config' => array (
				'type' => 'input',
				'size' => '40',
				'max' => '255',
			)
		),
		'city' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.city',
			'config' => array (
				'type' => 'input',
				'size' => '40',
				'max' => '255',
			)
		),
		'municipality' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.municipality',
			'config' => array (
				'type' => 'select',
				'items' => array (
					array('LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.municipality.I.0', ''),
				),
				'size' => 1,
				'maxitems' => 1,
				'itemsProcFunc' => 'Netcreators\\NcgovPermits\\Controller\\BackendController->user_getObjectMunicipalities',
			)
		),
		'province' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.province',
			'config' => array (
				'type' => 'select',
				'items' => array (
					array('LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.province.I.0', ''),
				),
				'size' => 1,
				'maxitems' => 1,
				'itemsProcFunc' => 'Netcreators\\NcgovPermits\\Controller\\BackendController->user_getProvinces',
			)
		),
	),
	'types' => array (
		'0' => array('showitem' => 'zipcode, addressnumber, addressnumberadditional, address, city, municipality, province'),
	),
	'palettes' => array (
		'1' => array('showitem' => '')
	)
);

$items = array();
/*foreach($TCA[$_TABLENAME]['columns'] as $name => $data) {
//	if(array_search($name, array('publishdate', 'title', 'link'))) {
//		continue;
//	}
	$items[] = $name;
}
$TCA[$_TABLENAME]['types']['0']['showitem'] = implode(', ', $items);*/

?>