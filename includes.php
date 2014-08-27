<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

// first include nc_lib
$errorMode = false;
if(!t3lib_extMgm::isLoaded('nc_lib')) {
	$errorMode = true;
}
if(!$errorMode) {
	require_once(t3lib_extMgm::extPath('nc_lib', 'includes.php'));
	$_EXTKEY = tx_nclib::getMyExtKey(__FILE__);
	$_EXTKEYSHORT = tx_nclib::getMyExtKeyShort(__FILE__);
}
// load xajax (not needed?!)
/*if(t3lib_extMgm::isLoaded('xajax') && !function_exists('xajaxErrorHandler')) {
	require_once (t3lib_extMgm::extPath('xajax') . 'class.tx_xajax.php');
}
if(t3lib_extMgm::isLoaded('tt_news')) {
	require_once (t3lib_extMgm::extPath('tt_news') . 'pi/class.tx_ttnews.php');
}*/
if(t3lib_extMgm::isLoaded('wec_map')) {
	include_once(t3lib_extMgm::extPath('wec_map').'map_service/google/class.tx_wecmap_map_google.php');
}

// check if tslib is included
if(!defined('PATH_tslib')) {
	if (@is_dir(PATH_site.'typo3/sysext/cms/tslib/')) {
		define('PATH_tslib', PATH_site.'typo3/sysext/cms/tslib/');	// other includes
	} else {
		print('Error: tslib not found in ' . $_EXTKEY);
	}
}
//require_once(PATH_tslib . 'class.tslib_pibase.php');

$currentPath = t3lib_extMgm::extPath($_EXTKEY);

// all included files can now safely rely on $_EXTKEY and $_EXTKEYSHORT to be set!
// exceptions to this system are:
// controller/class.$EXTKEYSHORT_wizicon.php
// ext_localconf.php
if(!$errorMode) {
	// model includes
	require_once($currentPath . 'model/class.tx_ncgovpermits_base_model.php');
	require_once($currentPath . 'model/class.tx_ncgovpermits_config_model.php');
	require_once($currentPath . 'model/class.tx_ncgovpermits_log_model.php');
	require_once($currentPath . 'model/class.tx_ncgovpermits_xml_model.php');
	require_once($currentPath . 'model/class.tx_ncgovpermits_permits_model.php');

	// view includes
	require_once($currentPath . 'view/class.tx_ncgovpermits_base_view.php');
	require_once($currentPath . 'view/class.tx_ncgovpermits_exception_view.php');
	require_once($currentPath . 'view/class.tx_ncgovpermits_log_view.php');
	require_once($currentPath . 'view/class.tx_ncgovpermits_permit_view.php');

	// controller includes
	require_once($currentPath . 'controller/class.tx_ncgovpermits_base_controller.php');
}
require_once($currentPath . 'controller/class.tx_ncgovpermits_controller.php');

?>