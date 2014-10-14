<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

// RealURL auto-configuration
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/realurl/class.tx_realurl_autoconfgen.php']['extensionConfiguration']['ncgov_permits'] = 'EXT:ncgov_permits/Classes/Service/RealUrl/RealUrlAutoConfigurationService.php:Netcreators\\NcgovPermits\\Service\\RealUrl\\RealUrlAutoConfigurationService->addNcGovPermitsRealUrlConfiguration';

// add plus to record
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('options.saveDocNew.tx_ncgovpermits_permits=1');

if (TYPO3_MODE == 'BE') {
	// Setting up scripts that can be run from the cli_dispatch.phpsh script.
	$TYPO3_CONF_VARS['SC_OPTIONS']['GLOBAL']['cliKeys'][$_EXTKEY] = array('EXT:' . $_EXTKEY . '/Classes/Controller/CommandLineController.php','_CLI_ncgovpermits');

	// Hook into Backend FormEngine for form manipulation
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['getMainFieldsClass'][] =  'EXT:' . $_EXTKEY . '/Classes/Service/BackendFormEngine/GetMainFieldsHook.php:Netcreators\\NcgovPermits\\Service\\BackendFormEngine\\GetMainFieldsHook';

	// Hook into Core DataHandler for data processing
	$GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'EXT:' . $_EXTKEY . '/Classes/Service/CoreDataHandler/ProcessDatamapHook.php:Netcreators\\NcgovPermits\\Service\\CoreDataHandler\\ProcessDatamapHook';

}

?>