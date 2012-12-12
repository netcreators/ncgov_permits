<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Frans van der Veen [netcreators] <extensions@netcreators.com>
*  (c) 2010 Klaus Bitto [netcreators]
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

if($errorMode) {
	class tx_ncgovpermits_controller extends tslib_pibase {
		// Same as class name
		public $prefixId      = 'tx_ncgovpermits_controller';

		// NOTE the false path helps the pibase class find the locallang.xml
		// Not used elsewhere
		// Path to this script relative to the extension dir.
		public $scriptRelPath = 'lang/class.tx_ncgovpermits_controller.php';

		// The extension key.
		// same as $_EXTKEY and $_EXTKEYSHORT
		public $extKey      = 'ncgov_permits';
		public $extKeyShort = 'tx_ncgovpermits';
		public $pi_checkCHash = true;
		function getContent($content, $configuration) {
			return 'Error: NCLIB was not loaded';
		}
	}
	return;
}

/**
 * Plugin 'Template' for the 'ncgov_permits' extension.
 *
 * @author	Frans van der Veen <extensions@netcreators.com>
 * @package	TYPO3
 * @subpackage	tx_ncgovpermits
 */
class tx_ncgovpermits_controller extends tx_ncgovpermits_base_controller {
	// Same as class name
	public $prefixId      = 'tx_ncgovpermits_controller';

	// NOTE the false path helps the pibase class find the locallang.xml
	// Not used elsewhere
	// Path to this script relative to the extension dir.
	public $scriptRelPath = 'lang/class.tx_ncgovpermits_controller.php';

	// The extension key.
	// same as $_EXTKEY and $_EXTKEYSHORT
	public $extKey      = 'ncgov_permits';
	public $extKeyShort = 'tx_ncgovpermits';
	public $pi_checkCHash = true;

	// models
	public $configModel, $logModel, $permitsModel;

	public $dateFilter, $productTypeFilter, $phaseFilter, $termTypeFilter;

	private $knownParams = array(
		'mode' => 'string',
		'zipcode' => 'string',
		'address' => 'string',
		'place' => 'string',
		'id' => 'int',
		'doc' => 'string',
		'activeMonth' => 'int',
		'activeYear' => 'int',
		'activeWeek' => 'int',
		'productType' => 'string',
		'phase' => 'arrayint',
		'termType' => 'string',
		'fulltext' => 'string',
		'postcodeNum' => 'string', # better suited than int, for sake of searching
		'postcodeAlpha' => 'string',
		'radius' => 'int',
	);

	/**
	 * Initializes the class (all the models, input variables, etc...)
	 *
	 * @param array		$configuration	typoscript configuration
	 */
	function initialize($configuration) {
		// call parent
		parent::initialize($configuration);
		// for configuration values, see the config model for this plugin
		$this->configModel = $this->makeInstance($this->extKeyShort . '_config_model');
		$this->configModel->initialize($this, $configuration);

		$this->logModel = $this->makeInstance($this->extKeyShort . '_log_model');
		$this->logModel->initialize($this);

		$this->xmlModel = $this->makeInstance($this->extKeyShort . '_xml_model');
		$this->xmlModel->initialize($this);

		$this->permitsModel = $this->makeInstance($this->extKeyShort . '_permits_model');
		$this->permitsModel->initialize($this);
		if(strpos($this->getPluginMode(), 'permit') !== FALSE) {
			$this->permitsModel->setModelType(tx_ncgovpermits_permits_model::TYPE_PERMIT);
		} else {
			$this->permitsModel->setModelType(tx_ncgovpermits_permits_model::TYPE_PUBLICATION);
		}

		$this->dateFilter = array();
		$this->productTypeFilter = array();
		$this->phaseFilter = array();
		$this->termTypeFilter = array();

		$this->tableData = array();
		t3lib_div::loadTCA($this->permitsModel->getTableName());

		$fields = array('documents');
		foreach($fields as $field) {
			$this->tableData[$this->permitsModel->getTableName()][$field]['uploadfolder'] =
				$GLOBALS['TCA'][$this->permitsModel->getTableName()]['columns'][$field]['config']['uploadfolder'];
		}

		if(TYPO3_MODE !== 'BE') {
			$this->checkOwmsSettings();
			$this->checkGoogleMapsSettings();
		}
	}

	public function checkOwmsSettings() {
		if($this->configModel->get('owmsDefaults.municipality') === false) {
			throw new tx_nclib_exception('label_error_owmsdefaults_municipality_not_set', $this);
		}
		if($this->configModel->get('owmsDefaults.city') === false) {
			throw new tx_nclib_exception('label_error_owmsdefaults_city_not_set', $this);
		}
		if($this->configModel->get('owmsDefaults.province') === false) {
			throw new tx_nclib_exception('label_error_owmsdefaults_province_not_set', $this);
		}
	}

	public function checkGoogleMapsSettings() {
		// if enabled, check if set correctly
		if($this->configModel->get('googleMaps.enabled') !== false) {
			if(!t3lib_extMgm::isLoaded('wec_map')) {
				throw new tx_nclib_exception('label_error_googlemaps_wecmap_not_loaded', $this);
			}
			if($this->configModel->get('googleMaps.apiKey') === false) {
				throw new tx_nclib_exception('label_error_googlemaps_apikey_not_set', $this);
			}
			if($this->configModel->get('googleMaps.width') === false) {
				throw new tx_nclib_exception('label_error_googlemaps_width_not_set', $this);
			}
			if($this->configModel->get('googleMaps.height') === false) {
				throw new tx_nclib_exception('label_error_googlemaps_height_not_set', $this);
			}
		}
	}

	/**
	 * The main method of the Plugin
	 *
	 * @param	string		$content: The Plugin content
	 * @param	array		$conf: The Plugin configuration
	 * @return	The content that is displayed on the website
	 */
	function getContent($content, $configuration) {
		try {
			$this->initialize($configuration);
			// determine what view to render
			switch($this->getPluginMode()) {
				case 'latest_permits':
				case 'latest_publications':
					$content = $this->getLatestView();
					break;
				case 'permits':
				case 'publications':
					$content = $this->getView();
					break;
				case 'permitsall':
                                case 'publicationsall':
					$content = $this->getViewAll();
					break;                                    
				case 'publish_permits':
					$content = $this->getPublishPermits();
					break;
				case 'publish_publications':
					$content = $this->getPublishPublications();
					break;
				default:
					throw new tx_nclib_exception(
						'label_exception_invalid_mode',
						$this,
						array('mode'=>$this->getPluginMode())
					);
			}

			if($this->configModel->get('wrapInBaseClass')) {
				$content = $this->pi_wrapInBaseClass($content);
			}
		} catch(Exception $exception) {
			$content = $this->getException($exception);
		}
		return $content;
	}

	public function getView() {
		$this->setIncomingVariablesSanitization($this->knownParams);
		$this->sanitizePiVars();
		
		if(count($_POST)) {
			# We requested fulltext or postcode filter.
			# So let's forget about no_cache, add a nice valid cHash to make the page cachable and redirect using GET.
			# Also, this prevents old values sitting in GET (e.g. old "fulltext" values) after a new one was sent by POST.
			$newUrl = $this->getURLToFilteredResult();
			header(sprintf('Location: %s', $newUrl[0] == '/' ? $newUrl : '/'.$newUrl));
			exit();
		}

		$id = $this->getPiVar('id');
		$doc = $this->getPiVar('doc');
		$mode = $this->getPiVar('mode');
		if(empty($id) || $mode === 'list') {
			$content = $this->getList();
		} else {
			if(!empty($id)) {
				$mode = 'details';
			}
			if(!empty($doc)) {
				$mode = 'document';
			}
			switch($mode) {
				case 'details':
					$content = $this->getDetails();
					break;
				case 'document':
					$content = $this->getDocument();
					break;
				case 'search':
					break;
				default:
					throw new tx_nclib_exception('label_error_unknown_submode', $this);
			}
		}
		return $content;
	}

        // New Interface and search all months & all years
        public function getViewAll() {
		$this->setIncomingVariablesSanitization($this->knownParams);
		$this->sanitizePiVars();
		
		if(count($_POST)) {
			# We requested fulltext or postcode filter.
			# So let's forget about no_cache, add a nice valid cHash to make the page cachable and redirect using GET.
			# Also, this prevents old values sitting in GET (e.g. old "fulltext" values) after a new one was sent by POST.
			$newUrl = $this->getURLToFilteredResult();
			header(sprintf('Location: %s', $newUrl[0] == '/' ? $newUrl : '/'.$newUrl));
			exit();
		}

		$id = $this->getPiVar('id');
		$doc = $this->getPiVar('doc');
		$mode = $this->getPiVar('mode');
		if(empty($id) || $mode === 'list') {
			$content = $this->getListAll();
		} else {
			if(!empty($id)) {
				$mode = 'details';
			}
			if(!empty($doc)) {
				$mode = 'document';
			}
			switch($mode) {
				case 'details':
					$content = $this->getDetailsAll();
					break;
				case 'document':
					$content = $this->getDocumentAll();
					break;
				case 'search':
					break;
				default:
					throw new tx_nclib_exception('label_error_unknown_submode', $this);
			}
		}
		return $content;
	}        
        
        
	/**
	 * Returns the latest items view.
	 */
	public function getLatestView() {
		return $this->getLatestList();
	}

	/**
	 * Returns a list containing latest permits / publications.
	 * @return string
	 */
	public function getLatestList() {
		$view = $this->makeInstance($this->extKeyShort . '_permit_view');
		$view->initialize($this, 'latest_list');
		$end = time();
		$start = $end - $this->configModel->get('latestDateOffset');
		$this->permitsModel->loadPermitsForRange($start, $end);
		return $view->getLatestList();
	}

	/**
	 * Returns a list of permits.
	 * @return string
	 */
	public function getList() {
		$view = $this->makeInstance($this->extKeyShort . '_permit_view');
		$view->initialize($this, 'list');
		$this->prepareDateFilter();
		$this->prepareProductTypeFilter();
		$this->preparePhaseFilter();
		$this->prepareTermTypeFilter();
		// filter the permits by the selected year/month, productType and phase
		$this->permitsModel->loadPermitsFiltered();
		$content = $view->getList();
		return $content;
	}
        
	/**
	 * Returns a list of permits all months/years - new interface
	 * @return string
	 */
	public function getListAll() {
		$view = $this->makeInstance($this->extKeyShort . '_permit_view');
		$view->initialize($this, 'list');
		$this->prepareDateFilterAll();
		$this->prepareProductTypeFilter();
		$this->preparePhaseFilter();
		$this->prepareTermTypeFilter();
		// filter the permits by the selected year/month, productType and phase
		$this->permitsModel->loadPermitsFiltered();
		$content = $view->getListAll();
		return $content;
	}        

	/**
	 * Returns details of a permit.
	 * @return string
	 */
	public function getDetails() {
		$view = $this->makeInstance($this->extKeyShort . '_permit_view');
		$view->initialize($this, 'details');
		$this->permitsModel->loadRecordById(
			$this->getPiVar('id')
		);
		if($this->getPluginMode() == 'permits' && !$this->permitsModel->isPermit()
			|| $this->getPluginMode() == 'publications' && $this->permitsModel->isPermit()
		) {
			$this->permitsModel->setRecord(false, true);
		}
		$content = $view->getDetails();
		return $content;
	}
        
	/**
	 * Returns details of a permit for the new interface - all months/years.
	 * @return string
	 */
	public function getDetailsAll() {
		$view = $this->makeInstance($this->extKeyShort . '_permit_view');
		$view->initialize($this, 'details');
		$this->permitsModel->loadRecordById(
			$this->getPiVar('id')
		);
		if($this->getPluginMode() == 'permitsall' && !$this->permitsModel->isPermit()
                        || $this->getPluginMode() == 'publicationsall' && $this->permitsModel->isPermit()
			
		) {
			$this->permitsModel->setRecord(false, true);
		}
		$content = $view->getDetailsAll();
		return $content;
	}        

	/**
	 * Returns details of a permit.
	 * @return string
	 */
	public function getDocument() {
		$view = $this->makeInstance($this->extKeyShort . '_permit_view');
		$view->initialize($this, 'document');
		$this->permitsModel->loadRecordById(
			$this->getPiVar('id')
		);
		$content = $view->getDocument();
		return $content;
	}
        
	/**
	 * Returns details of a permit for the new interface - all months/years.
         * Show filenames
	 * @return string
	 */
	public function getDocumentAll() {
		$view = $this->makeInstance($this->extKeyShort . '_permit_view');
		$view->initialize($this, 'document');
		$this->permitsModel->loadRecordById(
			$this->getPiVar('id')
		);
		$content = $view->getDocumentAll();
		return $content;
	}        

	/**
	 * Creates list of permits, readable for the overheid.nl spider
	 * @return log
	 */
	public function getPublishPermits() {
		$view = $this->makeInstance($this->extKeyShort . '_permit_view');
		$view->initialize($this, 'publish');
		$log = array();
		$log[] = 'Publishing records ' . date($this->configModel->get('config.dateFormat') . ' H:i:s');
		$log[] = '______________________________________' . chr(10);
		if($this->permitsModel->loadPublishablePermits()) {
			while($this->permitsModel->hasNextRecord()) {
				$this->cleanOldDocuments();
				if($this->permitsModel->skipThisUpdate()) {
					$this->permitsModel->moveToNextRecord();
					continue;
				}
				$successful = false;
				$xmlData = $view->getPermitXmls();
				foreach($xmlData as $file => $xml) {
					$file = $this->configModel->get('config.xmlFilePublishPath') . $file;
					$absFile = t3lib_div::getFileAbsFileName($file);
					if ($this->createXmlFile($absFile, $xml)) {
						$successful = true;
					} else {
						$successful = false;
						$this->logModel->log('Error publishing record: ' . $this->permitsModel->getId() . ' file ' . $absFile);
						$log[] = 'Error publishing record: ' . $this->permitsModel->getId() . ' file ' . $absFile;
					}
				}
				// everything ok?
				if($successful) {
					// let's update the record so it does not get published over and over again
					$this->permitsModel->setField('lastpublished', time());
					$this->permitsModel->saveRecord();
					$log[] = 'Published record: ' . $this->permitsModel->getId();
				}
				$this->permitsModel->moveToNextRecord();
			}
		} else {
			$log[] = 'Nothing to publish';
			$log[] = '';
		}
		$log[] = '(end of log)';
		$this->logModel->log(implode(chr(10), $log));
		$content = nl2br(implode(chr(10), $log));
		return $content;
	}

	public function getPublishPublications() {
		$view = $this->makeInstance($this->extKeyShort . '_permit_view');
		$view->initialize($this, 'publish');
		$this->permitsModel->loadPublishablePublications();
		$content = $view->getPublicationList();
		return $content;
	}

	/**
	 * CleanOldDocuments ...
	 * @return boolean
	 */
	public function cleanOldDocuments() {
		$path = $this->configModel->get('config.xmlFilePublishPath');
		$dir = @dir($path);
		$documentFilePart = 'xml_permit_' . $this->permitsModel->getId() . '_document_';
		$continue = $dir !== false;
		while($continue) {
			$entry = $dir->read();
			if($entry !== false) {
				if(t3lib_div::isFirstPartOfStr($entry, $documentFilePart)) {
					$result = unlink($path . $entry);
				}
			} else {
				$continue = false;
			}
		}
		return true;
	}

	/**
	 * Writes file to given path.
	 * @param $filePath	the file to write
	 * @param $data	the data to save
	 * @return boolean	true if succesful, false otherwise
	 */
	public function createXmlFile($filePath, $data) {
		$handle = @fopen($filePath, 'w');
		if($handle === false) {
			$result = false;
		} else {
			$written = fwrite($handle, $data);
			if($written === false || $written < strlen($data)) {
				$result = false;
			} else {
				$result = true;
			}
		}
		fclose($handle);
		return $result;
	}

	/**
	 * Prepares the filter date list.
	 * @return void
	 */
	public function prepareDateFilter() {
		// determine min, max time for the selected pages
		$aTimeRange = $this->permitsModel->getTimeRange();
		// determine min, max years
		$iStartYear = date('Y', $aTimeRange['startTime']);
		$iEndYear = date('Y', $aTimeRange['endTime']);
		// set currents
		$iCurrentYear = date('Y');
		$iCurrentMonth = date('m');
		$iCurrentWeek = date('W');
		// determine if there are incoming vars
		if($this->getPiVar('activeMonth') != '') {
			$iActiveMonth = $this->getPiVar('activeMonth');
		}
		if($this->getPiVar('activeYear') != '') {
			$iActiveYear = $this->getPiVar('activeYear');
		}
		/*if($this->getPiVar('activeWeek') != '') {
			$iActiveWeek = $this->piVars['activeWeek'];
		}*/
		// if some vars were not set as incoming, define the defaults
		$bMonthWasNotActive = false;
		if(!isset($iActiveMonth)) {
			$iActiveMonth = 1;	// default to first month of year
			$bMonthWasNotActive = true;
		}
		if(!isset($iActiveYear)) {
			$iActiveYear = (int)date('Y');	// default to current year
			$iActiveMonth = (int)date('m'); 	// default to current month
			if($this->configModel->get('defaultShowCurrentWeek')) {
				if($bMonthWasNotActive) {
					$iActiveWeek = $iCurrentWeek;	// default to current week when nothing was set
				}
			}
		}
		// check the values and correct if nessecary
		$bCurrentYearActive = false;
		if($iActiveYear == $iCurrentYear) {
			$bCurrentYearActive = true;
		}
		// determine week timestamps
		$iStartWeek = date('W', mktime(0, 0, 0, $iActiveMonth, 1, $iActiveYear));
		$iEndWeek = date('W', mktime(0, 0, 0, $iActiveMonth+1, 1, $iActiveYear));
		if($iEndWeek < $iStartWeek) {
			$iEndWeek = 52;
		}
		// check if the week was set correctly
		if($iActiveWeek < $iStartWeek || $iActiveWeek > $iEndWeek) {
			// make sure it is empty
			unset($iActiveWeek);
		}
		// month stars from 1
		$iStartMonth = 1;
		$iEndMonth = $iActiveMonth;
		// determine visibility
		if(isset($iActiveWeek)) {
			// filter on active week
			$iWeek = 60*60*24*7;
			$iStartDate = mktime(0, 0, 0, $iActiveMonth, 1, $iActiveYear) + ($iActiveWeek - $iStartWeek) * $iWeek;
			$iDayOfWeek = date('N', $iStartDate);
			$iStartDate -= ($iDayOfWeek-1) * 60*60*24;
			$iEndDate = $iStartDate + $iWeek;
		} else {
			// filter on active month, year
			$iStartDate = mktime(0, 0, 0, $iActiveMonth, 1, $iActiveYear);
			$iEndDate = mktime(0, 0, 0, $iActiveMonth+1, 1, $iActiveYear);
		}
		// Location filtering
		$sZipcode = $this->getPiVar('zipcode');
		$iRadius = $this->getPiVar('radius');
		
		// create data for view
		$this->dateFilter = array(
			'iStartYear' => $iStartYear,
			'iEndYear' => $iEndYear,
			'iStartWeek' => $iStartWeek,
			'iEndWeek' => $iEndWeek,
			'iActiveYear' => $iActiveYear,
			'iActiveMonth' => $iActiveMonth,
			'iActiveWeek' => $iActiveWeek,
			'iCurrentYear' => $iCurrentYear,
			'iCurrentMonth' => $iCurrentMonth,
			'iCurrentWeek' => $iCurrentWeek,
			'iStartTime' => $iStartDate,
			'iEndTime' => $iEndDate,
			'bCurrentYearActive' => $bCurrentYearActive,
			'sZipcode' => $sZipcode,
			'iRadius' => $iRadius
		);
	}
        
        
	/**
	 * Prepares the filter date list.
	 * @return void
	 */
	public function prepareDateFilterAll() {
		// determine min, max time for the selected pages
		$aTimeRange = $this->permitsModel->getTimeRange();
		// determine min, max years
		$iStartYear = date('Y', $aTimeRange['startTime']);
		$iEndYear = date('Y', $aTimeRange['endTime']);
		// set currents
		$iCurrentYear = date('Y');
		$iCurrentMonth = date('m');
		$iCurrentWeek = date('W');
		// determine if there are incoming vars
                $showallrecordsoftheactiveyear = false;
		if($this->getPiVar('activeMonth') != '') {
			$iActiveMonth = $this->getPiVar('activeMonth');
                        if ($iActiveMonth == 13)
                        {
                            $showallrecordsoftheactiveyear = true;
                        }
		}
		if($this->getPiVar('activeYear') != ''){
			$iActiveYear = $this->getPiVar('activeYear');
                        if ($iActiveYear == 9999)
                        {
                            $showallrecordsofallyears = true;
                        }                        
		}
		/*if($this->getPiVar('activeWeek') != '') {
			$iActiveWeek = $this->piVars['activeWeek'];
		}*/
		// if some vars were not set as incoming, define the defaults
		$bMonthWasNotActive = false;
		if(!isset($iActiveMonth)) {
			$iActiveMonth = 1;	// default to first month of year
			$bMonthWasNotActive = true;
		}
		if(!isset($iActiveYear)) {
			$iActiveYear = (int)date('Y');	// default to current year
			$iActiveMonth = (int)date('m'); 	// default to current month
			if($this->configModel->get('defaultShowCurrentWeek')) {
				if($bMonthWasNotActive) {
					$iActiveWeek = $iCurrentWeek;	// default to current week when nothing was set
				}
			}
		}
		// check the values and correct if nessecary
		$bCurrentYearActive = false;
		if($iActiveYear == $iCurrentYear) {
			$bCurrentYearActive = true;
		}
		// determine week timestamps
                if ($showallrecordsofallyears == false) {
                    if ($showallrecordsoftheactiveyear == false) {
                        $iStartWeek = date('W', mktime(0, 0, 0, $iActiveMonth, 1, $iActiveYear));
                        $iEndWeek = date('W', mktime(0, 0, 0, $iActiveMonth+1, 1, $iActiveYear));
                    }else
                    {
                        $iStartWeek = date('W', mktime(0, 0, 0, 1, 1, $iActiveYear));
                        $iEndWeek = date('W', mktime(0, 0, 0, 12, 1, $iActiveYear));                    
                    }
                }else{
                        $iStartWeek = date('W', mktime(0, 0, 0, 1, 1, 1900));
                        $iEndWeek = date('W', mktime(0, 0, 0, 12, 1, $iActiveYear));                     
                }
  
		if($iEndWeek < $iStartWeek) {
			$iEndWeek = 52;
		}
		// check if the week was set correctly
		if($iActiveWeek < $iStartWeek || $iActiveWeek > $iEndWeek) {
			// make sure it is empty
			unset($iActiveWeek);
		}
		// month stars from 1
		$iStartMonth = 1;
                if ($showallrecordsoftheactiveyear == false) {                
                    $iEndMonth = $iActiveMonth;
                }else
                {
                    $iEndMonth = 12;
                }
		// determine visibility
		if(isset($iActiveWeek)) {
			// filter on active week
			$iWeek = 60*60*24*7;
                        if ($showallrecordsofallyears == false) {
                            if ($showallrecordsoftheactiveyear == false) {   
                                $iStartDate = mktime(0, 0, 0, $iActiveMonth, 1, $iActiveYear) + ($iActiveWeek - $iStartWeek) * $iWeek;          
                            }
                            else {
                                $iStartDate = mktime(0, 0, 0,1, 1, $iActiveYear) + ($iActiveWeek - $iStartWeek) * $iWeek;                                      
                            }
                        }else{
                            $iStartDate = mktime(0, 0, 0,1, 1, 1900) + ($iActiveWeek - $iStartWeek) * $iWeek;  
                        }
                        
                        $iDayOfWeek = date('N', $iStartDate);
			$iStartDate -= ($iDayOfWeek-1) * 60*60*24;
			$iEndDate = $iStartDate + $iWeek;
		} else {
			// filter on active month, year
                        if ($showallrecordsofallyears == false) {                    
                            if ($showallrecordsoftheactiveyear == false) {                       
                                $iStartDate = mktime(0, 0, 0, $iActiveMonth, 1, $iActiveYear);
                                $iEndDate = mktime(0, 0, 0, $iActiveMonth+1, 1, $iActiveYear);
                            }else {
                                $iStartDate = mktime(0, 0, 0, 1, 1, $iActiveYear);
                                $iEndDate = mktime(0, 0, 0, 12, 1, $iActiveYear);                            
                            }
                        }else {
                                $iStartDate = mktime(0, 0, 0, 1, 1, 1900);
                                $iEndDate = mktime(0, 0, 0, 12, 1, $iActiveYear);                            
                        }
                        
		}
                
		// Location filtering
		$sZipcode = $this->getPiVar('zipcode');
		$iRadius = $this->getPiVar('radius');
		
		// create data for view
		$this->dateFilter = array(
			'iStartYear' => $iStartYear,
			'iEndYear' => $iEndYear,
			'iStartWeek' => $iStartWeek,
			'iEndWeek' => $iEndWeek,
			'iActiveYear' => $iActiveYear,
			'iActiveMonth' => $iActiveMonth,
			'iActiveWeek' => $iActiveWeek,
			'iCurrentYear' => $iCurrentYear,
			'iCurrentMonth' => $iCurrentMonth,
			'iCurrentWeek' => $iCurrentWeek,
			'iStartTime' => $iStartDate,
			'iEndTime' => $iEndDate,
			'bCurrentYearActive' => $bCurrentYearActive,
			'sZipcode' => $sZipcode,
			'iRadius' => $iRadius
		);
	}

	/**
	 * Prepares the filter product type list.
	 * @return void
	 */
	public function prepareProductTypeFilter() {
		$this->productTypeFilter = $this->permitsModel->getProductTypes();
	}

	/**
	 * Prepares the filter phase list.
	 * @return void
	 */
	public function preparePhaseFilter() {
		$this->phaseFilter = $this->permitsModel->getPhases();
	}

	/**
	 * Prepares the filter phase list.
	 * @return void
	 */
	public function prepareTermTypeFilter() {
		$this->termTypeFilter = $this->permitsModel->getTermTypes();
	}
	
	/**
	 * Returns filename identifying the current case record / document
	 * @param $documentIndex	the current
	 * @return string	filename
	 */
	public function getCaseFileName($documentIndex=false) {
		if($documentIndex === false) {
			$result = 'xml_permit_' . $this->permitsModel->getId() . '.xml';
		} else {
			$result = 'xml_permit_' . $this->permitsModel->getId() . '_document_' . $documentIndex . '.xml';
		}
		return $result;
	}


	/**
	 * Returns a
	 * @return unknown_type
	 */
/*	public function getPermitAttachment() {

	}*/

	/**
	 * Renders the exception view with details from the exception, or just shows exception information if an exception
	 * occurs in the exception view rendering.
	 *
	 * @param object 	$exception	exception object
	 * @return string	the content
	 */
	function getException(&$exception) {
		try {
			$exceptionView = $this->makeInstance($this->extKeyShort . '_exception_view');
			$exceptionView->initialize($this);
			$content = $exceptionView->getContent($exception);
		} catch(Exception $critical) {
			$content = '<strong>Critical: Exception in exception handler:</strong>';
			$content .= $critical->getMessage();
			$content .= nl2br($critical->getTraceAsString());
			$content .= '<br /><hr /><br />';
			$content .= '<strong>Original exception:</strong>';
			$content .= $exception->getMessage();
			$content .= nl2br($exception->getTraceAsString());
		}
		return $content;
	}

	/**
	 *
	 * @param $params
	 * @return string
	 */
	function fillPiVarParams($params = array()) {
		$knownParams = array_keys($this->knownParams);
		$linkParams = array();
		foreach($knownParams as $paramName) {
			if(in_array($paramName, array_keys($params))) {
				$linkParams[$paramName] = $params[$paramName];
			} elseif($this->getPiVar($paramName) !== NULL) {
				$linkParams[$paramName] = $this->getPiVar($paramName);
			}
		}
		return $linkParams;
	}

	/**
	 *
	 * @param $sLinkText
	 * @param $iActiveYear
	 * @param $iActiveMonth
	 * @param $iActiveWeek
	 * @return string
	 */
	function getLinkToFilteredResult($linkText, $params) {
		$linkParams = $this->fillPiVarParams($params);
		return $this->getLinkToController($linkText, false, $linkParams);
	}
	
	/**
	 *
	 * @param $params
	 * @return string
	 */
	function getURLToFilteredResult($params = array()) {
		$linkParams = $this->fillPiVarParams($params);
		return $this->getLinkToController(false, false, $linkParams);
	}

	/**
	 *
	 * @param $sFieldName
	 * @param $sFile
	 * @return string
	 */
	function getLinkToItemFileUrl($fieldName, $file) {
		if(t3lib_div::isFirstPartOfStr($file, 'http://')) {
			$path = $file;
		} else {
			$path = $this->tableData[$this->permitsModel->getTableName()][$fieldName]['uploadfolder'];
			$path = $path.'/'.$file;
		}
		return $path;
	}


	/**
	 * Returns the view mode of the plugin.
	 *
	 * @return string	the view mode.
	 */
	function getPluginMode() {
		return $this->configModel->get('pluginMode');
	}

	function doStuff() { //
		$this->getMunicipalities();
		return 'Hello!';
	}

	public function loadXMLFromFile($file) {
		$absFile = t3lib_div::getFileAbsFileName($file);

		return simplexml_load_file($absFile);
	}

	/**
	 * Returns a list of municipalities
	 * @return SimpleXMLElement
	 */
	public function getMunicipalities() {
		$file = 'EXT:ncgov_permits/res/xml/waardelijsten/overheid.Gemeente.xml';
		$this->loadXMLFromFile($file);
	}

	public function getCadastreMunicipalities() {

	}

	public function htmlEntitiesForQuotes($str) {
		$find = array("\"","'");
		$replace = array("&quot;","&#039;");
		return str_replace($find, $replace, $str);
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ncgov_permits/controller/class.tx_ncgovpermits_controller.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ncgov_permits/controller/class.tx_ncgovpermits_controller.php']);
}

?>