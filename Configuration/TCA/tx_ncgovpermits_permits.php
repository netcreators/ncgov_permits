<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$_EXTKEY = 'ncgov_permits';
$_EXTKEYSHORT = 'tx_ncgovpermits';
$_TABLENAME = $_EXTKEYSHORT . '_permits';
$_MAX_ALLOWED_FILESIZE = 51200;
$_ALLOWED_FILETYPES = 'doc,pdf,ppt,xls,zip';

$tableDefinition = array (
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
		'iconfile'          => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY).'Resources/Public/Icons/icon_' . $_tableName . '.gif',
	),
	'interface' => array (
		'showRecordFieldList' => 'hidden,message,logtype'
	),
	'feInterface' => array (
		'fe_admin_fieldList' => 'hidden,name',
	),
	'columns' => array (
		'hidden' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config' => array (
			'type' => 'check',
			'default' => '0'
			)
		),
		'type' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.type',
			'config' => array (
				'type' => 'select',
				'items' => array (
					array('LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.type.I.0', '0'),
					array('LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.type.I.1', '1'),
					array('LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.type.I.2', '2'),
					),
				'default' => '2',
				'size' => 1,
				'maxitems' => 1,
			)
		),
		/*'modified' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.modified',
			'config' => array (
				'type' => 'input',
				'size' => '12',
				'max' => '20',
				'eval' => 'date',
			)
		),*/
		'lastpublished' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.lastpublished',
			'config' => array (
				'type' => 'input',
				'size' => '12',
				'max' => '20',
				'eval' => 'date',
			)
		),
		'publishdate' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.publishdate',
			'config' => array (
				'type' => 'input',
				'size' => '12',
				'max' => '20',
				'eval' => 'date,required',
			)
		),
		'publishenddate' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.publishenddate',
			'config' => array (
				'type' => 'input',
				'size' => '12',
				'max' => '20',
				'eval' => 'date',
			)
		),
		'language' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.language',
			'config' => array (
				'type' => 'input',
				'size' => '12',
				'max' => '20',
				'default' => 'nl',
			)
		),
		'title' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.title',
			'config' => array (
				'type' => 'input',
				'size' => '40',
				'eval' => 'required',
			)
		),
		'description' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.description',
			'config' => array (
				'type' => 'text',
				'cols' => '30',
				'rows' => '5',
				'eval' => 'required',
			)
		),
		'publicationbody' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.publicationbody',
			'config' => array (
				'type' => 'text',
				'cols' => '30',
				'rows' => '5',
			)
		),
		'producttype' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.producttype',
			'config' => array (
				'type' => 'select',
				'items' => array (
					array(0, 0),
				),
				'size' => 1,
				'maxitems' => 1,
				'itemsProcFunc' => 'Netcreators\\NcgovPermits\\Controller\\BackendController->user_getProductTypes',
			)
		),
		'productactivities' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.productactivities',
			'config' => array (
				'type' => 'select',
				'items' => array (
					array(0 ,0),
				),
				'multiple' => false,
				'size' => 10,
				'maxitems' => 999,
				'itemsProcFunc' => 'Netcreators\\NcgovPermits\\Controller\\BackendController->user_getProductActivities',
			)
		),
		'publication' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.publication',
			'config' => array (
				'type' => 'input',
				'size' => '40',
			)
		),
		'documents' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.documents',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => $_ALLOWED_FILETYPES,
				'max_size' => $_MAX_ALLOWED_FILESIZE,
				'uploadfolder' => 'uploads/' . $_EXTKEYSHORT . '/documents',
				'size' => 10,
				'minitems' => 0,
				'maxitems' => 500,
			)
		),
		'documenttypes' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.documenttypes',
			'config' => array (
				'type' => 'select',
				'items' => array (
					array(0 ,0),
				),
				'multiple' => true,
				'size' => 10,
				'maxitems' => 999,
				'itemsProcFunc' => 'Netcreators\\NcgovPermits\\Controller\\BackendController->user_getDocumentTypes',
			)
		),
		'casereference' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.casereference',
			'config' => array (
				'type' => 'input',
				'size' => '12',
				'max' => '40',
				'eval' => 'required'
			)
		),
		'casereference_pub' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.casereference_pub',
			'config' => array (
				'type' => 'input',
				'size' => '12',
				'max' => '40',
			)
		),
		'validity_start' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.validity_start',
			'config' => array (
				'type' => 'input',
				'size' => '12',
				'max' => '20',
				'eval' => 'date',
			)
		),
		'validity_end' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.validity_end',
			'config' => array (
				'type' => 'input',
				'size' => '12',
				'max' => '20',
				'eval' => 'date',
			)
		),
		'phase' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.phase',
			'config' => array (
				'type' => 'select',
				'items' => array (
					array('LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.phase.I.0', ''),
				),
				'multiple' => false,
				'size' => 1,
				'minitems' => 1,
				'maxitems' => 1,
				'itemsProcFunc' => 'Netcreators\\NcgovPermits\\Controller\\BackendController->user_getPhases',
			)
		),
		'termtype' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.termtype',
			'config' => array (
				'type' => 'select',
				'items' => array (
					array('LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.termtype.I.0', ''),
				),
				'multiple' => false,
				'size' => 1,
				'maxitems' => 1,
				'itemsProcFunc' => 'Netcreators\\NcgovPermits\\Controller\\BackendController->user_getTermTypes',
			)
		),
		'termtype_start' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.termtype_start',
			'config' => array (
				'type' => 'input',
				'size' => '12',
				'max' => '20',
				'eval' => 'date',
			)
		),
		'termtype_end' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.termtype_end',
			'config' => array (
				'type' => 'input',
				'size' => '12',
				'max' => '20',
				'eval' => 'date',
			)
		),
		'company' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.company',
			'config' => array (
				'type' => 'input',
				'size' => '40',
				'max' => '255',
			)
		),
		'companynumber' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.companynumber',
			'config' => array (
				'type' => 'input',
				'size' => '40',
				'max' => '64',
			)
		),
		'companyaddress' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.companyaddress',
			'config' => array (
				'type' => 'input',
				'size' => '40',
				'max' => '255',
			)
		),
		'companyaddressnumber' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.companyaddressnumber',
			'config' => array (
				'type' => 'input',
				'size' => '12',
				'max' => '255',
			)
		),
		'companyzipcode' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.companyzipcode',
			'config' => array (
				'type' => 'input',
				'size' => '12',
				'max' => '7',
			)
		),
		'objectreference' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.objectreference',
			'config' => array (
				'type' => 'input',
				'size' => '40',
				'max' => '255',
			)
		),
		'objectaddresses' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_EXTKEYSHORT . '_addresses',
			'config' => array (
				'type' => 'inline',
				'foreign_table' => $_EXTKEYSHORT . '_addresses',
				'maxitems' => 999,
				'appearance' => array(
					'showSynchronizationLink' => 0,
					'showAllLocalizationLink' => 0,
					'showPossibleLocalizationRecords' => 0,
					'showRemovedLocalizationRecords' => 0,
				),
			)
		),
		'lots' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_EXTKEYSHORT . '_lots',
			'config' => array (
				'type' => 'inline',
				'foreign_table' => $_EXTKEYSHORT . '_lots',
				'maxitems' => 999,
				'appearance' => array(
					'showSynchronizationLink' => 0,
					'showAllLocalizationLink' => 0,
					'showPossibleLocalizationRecords' => 0,
					'showRemovedLocalizationRecords' => 0,
				),
			)
		),
		'coordinates' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_EXTKEYSHORT . '_coordinates',
			'config' => array (
				'type' => 'inline',
				'foreign_table' => $_EXTKEYSHORT . '_coordinates',
				'maxitems' => 999,
				'appearance' => array(
					'showSynchronizationLink' => 0,
					'showAllLocalizationLink' => 0,
					'showPossibleLocalizationRecords' => 0,
					'showRemovedLocalizationRecords' => 0,
				),
			)
		),
		'link' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.link',
			'config' => array (
				'type'     => 'input',
				'size'     => '15',
				'max'      => '255',
				'checkbox' => '',
				'eval'     => 'trim',
				'wizards'  => array(
					'_PADDING' => 2,
					'link'     => array(
						'type'         => 'popup',
						'title'        => 'Link',
						'icon'         => 'link_popup.gif',
						'script'       => 'browse_links.php?mode=wizard',
						'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1'
					)
				)
			)
		),
		'related' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.related',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'db',
				'prepend_tname' => 0,
				'allowed' => $_EXTKEYSHORT . '_permits',
				'maxitems' => 1,
				'wizards' => array(
					'_PADDING' => 2,
					'edit' => array(
						'type' => 'popup',
						'title' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_tca.xml:' . $_TABLENAME . '.edit_related',
						'script' => 'wizard_edit.php',
						'icon' => 'edit2.gif',
						'popup_onlyOpenIfSelected' => 1,
						'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
					),
				),
			)
		),
	),
	'types' => array (
		'0' => array('showitem' => ''),
		'1' => array('showitem' => 'type, hidden, publishdate, publishenddate, producttype, language, title, description;;9;richtext:rte_transform[flag=rte_enabled|mode=ts_css];3-3-3, link, objectaddresses, related'),
		'2' => array('showitem' => 'type'),
	),
	'palettes' => array (
		'1' => array('showitem' => '')
	)
);

$items = array();
foreach($tableDefinition['columns'] as $name => $data) {
	$name = trim($name);
	if(array_search($name, array('publishdate', 'title', 'link', 'companyaddress'))) {
		continue;
	}
	if($name == 'description') {
		$name .= ';;9;richtext:rte_transform[flag=rte_enabled|mode=ts_css];3-3-3';
	}
	$items[] = $name;
}
$tableDefinition['types']['0']['showitem'] = implode(', ', $items);

return $tableDefinition;

?>