<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Frans van der Veen [netcreators] <extensions@netcreators.com>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

$currentDir = dirname(__FILE__) . '/';
require_once($currentDir . '../includes.php');

class tx_ncgovpermits_config_model extends tx_nclib_config_model {
	function initialize(&$controller, $typoScript) {
		parent::initialize($controller, $typoScript);

		// Local configuration defaults for this plugin
		// Note: ALL the required keys have to be defined here, otherwise the plugin will give an error
		// Error checking and feedback will save time when installing a TypoScript template, but something is wrong
		// This array is also a reminder (for the dev) for which vars can be set
		$defaults = array (
			'templates.' => array(
				// the exceptionview has the path hardcoded, since an exception can occur in the config model
				'permitView' => 'EXT:' . $controller->extKey . '/templates/permit_view.html',
			),
			// should wrap content in baseclass?
			'wrapInBaseClass' => '0',
			// default viewmode
			'pluginMode' => false,
			// should all remaining markers be removed?
			'cleanRemainingMarkers' => '0',
			// Cache links?
			'cacheLinks' => false,
			// page where the reminder log messages are stored
			'logFolder' => false,
			// where are the permits shown?
			'displayPage' => false,
			// what pages to get the records from
			'storageFolder' => false,
			// where are the publications shown? (separate since required by permit XML)
			'publicationsDisplayPage' => false,
			// what pages to get the publication records from (separate since required by permit XML) 
			'publicationsStorageFolder' => false,
			// recursive yes or no? If yes, the recursive level (250 for 'infinite')
			'recurseDepth' => false,
			// debug mode on?
			'debugMode' => false,
			// base images folder
			'imagesBasePath' => t3lib_extMgm::siteRelPath($this->controller->extKey) . 'res/images/',
			// the css file to use
			'includeCssFile' => 'res/css/style.css',
			// is the cssFile relative to the extension?
			'includeCssFilePathIsRelative' => true,
			// the js file to use
			'includeJsFile' => 'res/js/library.js',
			// is the js file relative to the extension?
			'includeJsFilePathIsRelative' => true,
			// show current week if nothing was selected
			'defaultShowCurrentWeek' => true,
			// don't show records with publishdate after now()
			'dontShowRecordsPublishedInTheFuture' => false,
			// the amount of time to look back
			'latestDateOffset' => false,
			// default charset for htmlentities for sites that do not use UTF-8 by default
			'htmlEntitiesCharset' => 'UTF-8',
			// default setting for making it possible to use region search, should not be turned on without key
			'regionSearch' => 0,
			// default setting for making it possible to use region search, should not be turned on without key
			'regionStringSearch' => 0,
			// default authorization key for pro6pp, empty
			'pro6ppAuthKey' => '',
			// default radius options for region search 
			'searchRadiusOptions' => '5,10,20,50,100',
			// special hardened version to convert latin characters to UTF-8
			'convertLatinToUtf8' => false,
			// shows fields on detail screen
			'showDetailsFieldList' => false,
			// default DC settings
			'owmsDefaults.' => array(
				'municipality' => false,
				'creator' => false,
				'creatorType' => false,
				'informationType' => false,
				'organisationType' => false,
				// date-time format for owms
				'dateFormat' => 'Y-m-d',
				// default city
				'city' => false,
				// default province
				'province' => false,
			),
			// Google Maps settings
			'googleMaps.' => array(
				'enabled' => false,
				'width' => false,
				'height' => false,
				'apiKey' => false,
				'type' => false,
				'controls' => false,
				'radius' => false,
				'preventJsLinkDeformationByAddingCommentToJavascript' => true
			),
			// config settings
			'config.' => array(
				// preview e-mails and smses?
				'previewOnly' => false,
				// date-time format
				'dateFormat' => 'd-m-Y',
				// where to put the xml files
				'xmlFilePublishPath' => 'fileadmin/permitsxml/',
			),
			// help icon stuff
			'help.' => array(
				'icon' => 'typo3conf/ext/ncgov_permits/res/icons/icon_help.gif',
				'tstamp' => false,
				'crdate' => false,
				'producttype' => false,
				'productactivities' => false,
				'description' => false,
				'documents' => false,
				'casereference' => false,
				'validity_start' => false,
				'validity_end' => false,
				'phase' => false,
				'termtype' => false,
				'termtype_start' => false,
				'termtype_end' => false,
				'company' => false,
				'companynumber' => false,
				'companyaddress' => false,
				'companyaddressnumber' => false,
				'objectreference' => false,
				'companyzipcode' => false,
				'publishdate' => false,
				'title' => false,
				'zipcode' => false,
				'address' => false,
				'addressnumber' => false,
				'addressnumberadditional' => false,
				'municipality' => false,
				'link' => false,
			),
			'viewPublicationDetails.' => array(),
			'viewPermitDetails.' => array(),
		);

				// remember, don't forget
		$dontCheck = array(
			'help.',
			'viewPublicationDetails.',
			'viewPermitDetails.',
		);
		$this->setTSDefaults($defaults, $dontCheck);
		$this->initializeTSConfigAndFFConfig();
		$this->searchAndReplaceConfigurationReferences();
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ncgov_permits/model/class.tx_ncgovpermits_config_model.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ncgov_permits/model/class.tx_ncgovpermits_config_model.php']);
}
?>
