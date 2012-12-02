<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['getMainFieldsClass'][] = 'EXT:ncgov_permits/controller/class.tx_ncgovpermits_be_controller.php:tx_ncgovpermits_be_controller';

// RealURL autoconfiguration
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/realurl/class.tx_realurl_autoconfgen.php']['extensionConfiguration']['ncgov_permits'] = 'EXT:ncgov_permits/controller/class.tx_ncgovpermits_realurl_configuration.php:tx_ncgovpermits_realurl_configuration->addNcGovPermitsRealurlConfig';

// add plus to record
t3lib_extMgm::addUserTSConfig('options.saveDocNew.tx_ncgovpermits_permits=1');

if (TYPO3_MODE=='BE') {
 // Setting up scripts that can be run from the cli_dispatch.phpsh script.
 $TYPO3_CONF_VARS['SC_OPTIONS']['GLOBAL']['cliKeys'][$_EXTKEY] = array('EXT:'.$_EXTKEY.'/cli/class.tx_ncgovpermits_cli.php','_CLI_ncgovpermits');


// hook into tce forms for form manipulation
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['getMainFieldsClass'][] =  'EXT:'.$_EXTKEY.'/hooks/class.tx_ncgovpermits_tceforms_process.php:tx_ncgovpermits_tceforms_process';
 
 
}

// add hook
$GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'EXT:ncgov_permits/hooks/class.tx_ncgovpermits_afterinsert_hook.php:tx_ncgovpermits_afterinsert_hook';
?>