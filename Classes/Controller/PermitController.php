<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008 Frans van der Veen [netcreators] <extensions@netcreators.com>
 *  (c) 2010 Leonie Philine Bitto [netcreators]
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

namespace Netcreators\NcgovPermits\Controller;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Plugin 'Template' for the 'ncgov_permits' extension.
 *
 * @author    Frans van der Veen <extensions@netcreators.com>
 * @package    TYPO3
 * @subpackage    tx_ncgovpermits
 */
class PermitController extends BaseController
{
    // Plugin HTTP Request Parameter namespace
    public $prefixId = 'tx_ncgovpermits_controller';

    // The extension key.
    // same as $_EXTKEY and $_EXTKEYSHORT
    public $extKey = 'ncgov_permits';
    public $extKeyShort = 'tx_ncgovpermits';


    // Cache control for USER plugin (@see Configuration/TypoScript/setup.txt):

    // If set, then links are 1) not using cHash and 2) not allowing pages to be cached. (Set this for all USER_INT plugins!)
    public $pi_USER_INT_obj = false;

    // If set, then caching is disabled if piVars are incoming while no cHash was set (Set this for all USER plugins!)
    public $pi_checkCHash = true;



    /**
     * @var \Netcreators\NcgovPermits\Domain\Model\Config
     */
    public $configModel;

    /**
     * @var \Netcreators\NcgovPermits\Domain\Model\Log
     */
    public $logModel;

    /**
     * @var \Netcreators\NcgovPermits\Domain\Model\Permit
     */
    public $permitsModel;

    /**
     * @var \Netcreators\NcgovPermits\Domain\Model\Xml
     */
    public $xmlModel;

    /**
     * @var array
     */
    public $dateFilter;

    /**
     * @var array
     */
    protected $tableData;

    public $productTypeFilter, $phaseFilter, $termTypeFilter;


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
     * @param array $configuration typoscript configuration
     */
    function initialize($configuration)
    {
        // call parent
        parent::initialize($configuration);
        // for configuration values, see the config model for this plugin
        $this->configModel = GeneralUtility::makeInstance('Netcreators\\NcgovPermits\\Domain\\Model\\Config');
        $this->configModel->initialize($this, $configuration);

        $this->logModel = GeneralUtility::makeInstance('Netcreators\\NcgovPermits\\Domain\\Model\\Log');
        $this->logModel->initialize($this);

        $this->xmlModel = GeneralUtility::makeInstance('Netcreators\\NcgovPermits\\Domain\\Model\\Xml');
        $this->xmlModel->initialize($this);

        $this->permitsModel = GeneralUtility::makeInstance('Netcreators\\NcgovPermits\\Domain\\Model\\Permit');
        $this->permitsModel->initialize($this);
        if (strpos($this->getPluginMode(), 'permit') !== false) {
            $this->permitsModel->setModelType(\Netcreators\NcgovPermits\Domain\Model\Permit::TYPE_PERMIT);
        } else {
            $this->permitsModel->setModelType(\Netcreators\NcgovPermits\Domain\Model\Permit::TYPE_PUBLICATION);
        }

        $this->dateFilter = array();
        $this->productTypeFilter = array();
        $this->phaseFilter = array();
        $this->termTypeFilter = array();

        $this->tableData = array();

        $fields = array('documents');
        foreach ($fields as $field) {
            $this->tableData[$this->permitsModel->getTableName()][$field]['uploadfolder'] =
                $GLOBALS['TCA'][$this->permitsModel->getTableName()]['columns'][$field]['config']['uploadfolder'];
        }

        if (TYPO3_MODE !== 'BE') {
            $this->checkOwmsSettings();
            $this->checkGoogleMapsSettings();
        }
    }

    public function checkOwmsSettings()
    {
        if ($this->configModel->get('owmsDefaults.municipality') === false) {
            throw new \tx_nclib_exception('label_error_owmsdefaults_municipality_not_set', $this);
        }
        if ($this->configModel->get('owmsDefaults.city') === false) {
            throw new \tx_nclib_exception('label_error_owmsdefaults_city_not_set', $this);
        }
        if ($this->configModel->get('owmsDefaults.province') === false) {
            throw new \tx_nclib_exception('label_error_owmsdefaults_province_not_set', $this);
        }
    }

    public function checkGoogleMapsSettings()
    {
        // if enabled, check if set correctly
        if ($this->configModel->get('googleMaps.enabled') !== false) {
            if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('wec_map')) {
                throw new \tx_nclib_exception('label_error_googlemaps_wecmap_not_loaded', $this);
            }
            if ($this->configModel->get('googleMaps.apiKey') === false) {
                throw new \tx_nclib_exception('label_error_googlemaps_apikey_not_set', $this);
            }
            if ($this->configModel->get('googleMaps.width') === false) {
                throw new \tx_nclib_exception('label_error_googlemaps_width_not_set', $this);
            }
            if ($this->configModel->get('googleMaps.height') === false) {
                throw new \tx_nclib_exception('label_error_googlemaps_height_not_set', $this);
            }
        }
    }

    /**
     * The main method of the Plugin
     *
     * @param    string $content : The Plugin content
     * @param    array $conf : The Plugin configuration
     * @return    string The content that is displayed on the website
     */
    function getContent($content, $configuration)
    {
        try {
            $this->initialize($configuration);
            // determine what view to render
            switch ($this->getPluginMode()) {
                case 'latest_permits':
                case 'latest_publications':
                    $content = $this->getLatestView();
                    break;

                case 'permitsall':
                case 'publicationsall':
                    $content = $this->getViewAll();
                    break;

                case 'publish_permits':
                    $content = $this->getPublishPermits();
                    break;

                default:
                    throw new \tx_nclib_exception(
                        'label_exception_invalid_mode',
                        $this,
                        array('mode' => $this->getPluginMode())
                    );
            }

            if ($this->configModel->get('wrapInBaseClass')) {
                $content = $this->pi_wrapInBaseClass($content);
            }
        } catch (\Exception $exception) {
            $content = $this->getException($exception);
        }
        return $content;
    }


    // New Interface and search all months & all years
    public function getViewAll()
    {
        $this->setIncomingVariablesSanitization($this->knownParams);
        $this->sanitizePiVars();

        $content = '';

        if (count($_POST)) {
            # We requested fulltext or postcode filter.
            # So let's forget about no_cache, add a nice valid cHash to make the page cachable and redirect using GET.
            # Also, this prevents old values sitting in GET (e.g. old "fulltext" values) after a new one was sent by POST.
            $newUrl = $this->getURLToFilteredResult();
            header(sprintf('Location: %s', $newUrl[0] == '/' ? $newUrl : '/' . $newUrl));
            exit();
        }

        $id = $this->getPiVar('id');
        $doc = $this->getPiVar('doc');
        $mode = $this->getPiVar('mode');
        if (empty($id) || $mode === 'list') {
            $content = $this->getListAll();
        } else {
            if (!empty($id)) {
                $mode = 'details';
            }
            if (!empty($doc)) {
                $mode = 'document';
            }
            switch ($mode) {
                case 'details':
                    $content = $this->getDetailsAll();
                    break;
                case 'document':
                    $content = $this->getDocumentAll();
                    break;
                case 'search':
                    break;
                default:
                    throw new \tx_nclib_exception('label_error_unknown_submode', $this);
            }
        }
        return $content;
    }


    /**
     * Returns the latest items view.
     */
    public function getLatestView()
    {
        return $this->getLatestList();
    }

    /**
     * Returns a list containing latest permits / publications.
     * @return string
     */
    public function getLatestList()
    {
        /** @var \Netcreators\NcgovPermits\View\PermitView $view */
        $view = GeneralUtility::makeInstance('Netcreators\\NcgovPermits\\View\\PermitView');
        $view->initialize($this, 'latest_list');
        $end = time();
        $start = $end - $this->configModel->get('latestDateOffset');
        $this->permitsModel->loadPermitsForRange($start, $end);
        return $view->getLatestList();
    }


    /**
     * Returns a list of permits all months/years - new interface
     * @return string
     */
    public function getListAll()
    {
        /** @var \Netcreators\NcgovPermits\View\PermitView $view */
        $view = GeneralUtility::makeInstance('Netcreators\\NcgovPermits\\View\\PermitView');
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
     * Returns details of a permit for the new interface - all months/years.
     * @return string
     */
    public function getDetailsAll()
    {
        /** @var \Netcreators\NcgovPermits\View\PermitView $view */
        $view = GeneralUtility::makeInstance('Netcreators\\NcgovPermits\\View\\PermitView');
        $view->initialize($this, 'details');
        $this->permitsModel->loadRecordById(
            $this->getPiVar('id')
        );
        if ($this->getPluginMode() == 'permitsall' && !$this->permitsModel->isPermit()
            || $this->getPluginMode() == 'publicationsall' && $this->permitsModel->isPermit()

        ) {
            $this->permitsModel->setRecord(false, true);
        }
        $content = $view->getDetailsAll();
        return $content;
    }


    /**
     * Returns details of a permit for the new interface - all months/years.
     * Show filenames
     * @return string
     */
    public function getDocumentAll()
    {
        $view = GeneralUtility::makeInstance('Netcreators\\NcgovPermits\\View\\PermitView');
        $view->initialize($this, 'document');
        $this->permitsModel->loadRecordById(
            $this->getPiVar('id')
        );
        $content = $view->getDocumentAll();
        return $content;
    }

    /**
     * Creates list of permits, readable for the overheid.nl spider
     * @return string log
     */
    public function getPublishPermits()
    {
        $view = GeneralUtility::makeInstance('Netcreators\\NcgovPermits\\View\\PermitView');
        $view->initialize($this, 'publish');
        $log = array();
        $log[] = 'Publishing and depublishing records ' . date($this->configModel->get('config.dateFormat') . ' H:i:s');
        $log[] = '______________________________________' . chr(10);

        // Publish new permits (modified later than last publish date, or never published before).
        $publishedCount = 0;
        $publishErrorCount = 0;
        $skippedPublishingCount = 0;
        if ($this->permitsModel->loadPublishablePermits()) {
            $log[] = '';
            $log[] = 'Loaded ' . $this->permitsModel->getCount(
                ) . ' publishable permits. (Limit: ' . $this->configModel->get('latestlimit') . ')';
            while ($this->permitsModel->hasNextRecord()) {
                $this->cleanOldDocuments();
                if ($this->permitsModel->skipThisUpdate()) {
                    $log[] = '[-] Skipping publication of permit [' . $this->permitsModel->getId(
                        ) . ']: ' . $this->permitsModel->getField('title');
                    $skippedPublishingCount++;
                    $this->permitsModel->moveToNextRecord();
                    continue;
                }

                $log[] = '[+] Processing publication of permit [' . $this->permitsModel->getId(
                    ) . ']: ' . $this->permitsModel->getField('title');
                $successful = false;
                $xmlData = $view->getPermitXmls();
                foreach ($xmlData as $file => $xml) {
                    $file = $this->configModel->get('config.xmlFilePublishPath') . $file;
                    $absFile = GeneralUtility::getFileAbsFileName($file);
                    if ($this->createXmlFile($absFile, $xml)) {
                        $successful = true;
                        $publishedCount++;
                    } else {
                        $successful = false;
                        $publishErrorCount++;
                        $this->logModel->log(
                            'Error publishing record: ' . $this->permitsModel->getId() . ' file ' . $absFile
                        );
                        $log[] = 'Error publishing record: ' . $this->permitsModel->getId() . ' file ' . $absFile;
                    }
                }
                // everything ok?
                if ($successful) {
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

        $log[] = '';
        $log[] = 'Publication Summary:';
        $log[] = 'Published ' . $publishedCount . ' permits in ' . $this->configModel->get(
                'config.xmlFilePublishPath'
            ) . '.';
        $log[] = 'Publication of ' . $publishErrorCount . ' permits failed with errors.';
        $log[] = $skippedPublishingCount . ' permits were skipped during publication.';


        // Depublish older permits (published longer than 30 days ago).
        $depublishedCount = 0;
        $depublishedDocumentsCount = 0;
        $didNotExistWhileDepublishingCount = 0;
        if ($this->permitsModel->loadDepublishablePermits()) {
            $log[] = '';
            $log[] = 'Loaded ' . $this->permitsModel->getCount(
                ) . ' de-publishable permits. (Limit: ' . $this->configModel->get('latestlimit') . ')';
            while ($this->permitsModel->hasNextRecord()) {

                $file = GeneralUtility::getFileAbsFileName(
                    $this->configModel->get('config.xmlFilePublishPath') . $this->getCaseFileName()
                );
                if (is_file($file)) {

                    unlink($file);
                    $log[] = '[+] Depublished permit [' . $this->permitsModel->getId(
                        ) . ' / ' . $file . ']: ' . $this->permitsModel->getField('title');
                    $depublishedCount++;
                    // All good.

                } else {

                    $log[] = '[-] XML file ' . $file . ' did not exist while depublishing permit [' . $this->permitsModel->getId(
                        ) . ']: ' . $this->permitsModel->getField('title');
                    $didNotExistWhileDepublishingCount++;
                    // Note: We set the 'lastpublished' timestamp anyway, to avoid this record to show up again and again.

                }

                $removedDocumentFiles = $this->cleanOldDocuments();
                if ($removedDocumentFiles) {
                    $depublishedDocumentsCount += count($removedDocumentFiles);
                    $log[] = '[+] Depublished documents of permit [' . $this->permitsModel->getId(
                        ) . ']: ' . $this->permitsModel->getField('title');
                    $log[] = implode(', ' . $removedDocumentFiles);
                }

                // let's update the record so it does not get de-published over and over again
                $this->permitsModel->setField('lastdepublished', time());
                $this->permitsModel->saveRecord();

                $this->permitsModel->moveToNextRecord();
            }
        }

        $log[] = '';
        $log[] = 'Depublication Summary:';
        $log[] = 'Depublished ' . $depublishedCount . ' permits from ' . $this->configModel->get(
                'config.xmlFilePublishPath'
            ) . '.';
        $log[] = 'Depublished ' . $depublishedDocumentsCount . ' permit attachments/documents from ' . $this->configModel->get(
                'config.xmlFilePublishPath'
            ) . '.';
        $log[] = $didNotExistWhileDepublishingCount . ' permits had no corresponding depublishable XML files in ' . $this->configModel->get(
                'config.xmlFilePublishPath'
            ) . '.';
        $log[] = $skippedPublishingCount . ' permits were skipped during publication.';


        // Remove orphan XML files
        $allPublishedPermitXmlFiles = glob($this->configModel->get('config.xmlFilePublishPath') . 'xml_permit_*.xml');
        $log[] = '';
        $log[] = 'Found ' . count(
                $allPublishedPermitXmlFiles
            ) . ' xml_permit_*.xml files in ' . $this->configModel->get('config.xmlFilePublishPath') . '.';
        $removedOrphanPermitCount = 0;
        $removedOrphanDocumentCount = 0;
        foreach ($allPublishedPermitXmlFiles as $file) {
            $matches = array();
            $matchResult = preg_match('/\/xml_permit_([0-9]*).xml$/', $file, $matches);

            if ($matchResult === 0 /* nothing found */ || $matchResult === false /* match error */) {
                $log[] = '[-] A file - ' . $file . ' - did not match the expected pattern.';
                continue;
            }
            $permitId = (int)$matches[1];

            if (!$this->permitsModel->loadRecordById($permitId)) {
                $file = GeneralUtility::getFileAbsFileName(
                    $this->configModel->get('config.xmlFilePublishPath') . 'xml_permit_' . $permitId . '.xml'
                );
                unlink($file);
                $removedOrphanPermitCount++;
                $log[] = '[+] No longer in database: Depublished orphan permit XML file [' . $permitId . '; ' . $file . '].';
                $removedDocumentFiles = $this->cleanOldDocuments($permitId);
                if ($removedDocumentFiles) {
                    $removedOrphanDocumentCount += count($removedDocumentFiles);
                    $log[] = '[+] No longer in database: Depublished documents of orphan permit XML file [' . $permitId . '; ' . $file . ']: ' . implode(
                            ', ' . $removedDocumentFiles
                        );
                }
            }
        }

        $log[] = 'Clean-up Summary:';
        $log[] = 'Removed ' . $removedOrphanPermitCount . ' orphaned XML permit files from ' . $this->configModel->get(
                'config.xmlFilePublishPath'
            ) . ' during clean-up.';
        $log[] = 'Removed ' . $removedOrphanDocumentCount . ' orphaned XML permit attachment/document files from ' . $this->configModel->get(
                'config.xmlFilePublishPath'
            ) . ' during clean-up.';


        $log[] = '(end of log)';
        $this->logModel->log(implode(chr(10), $log));
        $content = nl2br(implode(chr(10), $log));
        return $content;
    }

    /**
     * CleanOldDocuments ...
     * @param integer $permitId Optional - usually, the currently loaded permitsModel is used.
     * @return array
     */
    public function cleanOldDocuments($permitId = 0)
    {
        if (!$permitId) {
            $permitId = $this->permitsModel->getId();
        }

        $path = $this->configModel->get('config.xmlFilePublishPath');
        $documentFilePart = 'xml_permit_' . $permitId . '_document_';
        $files = glob($path . $documentFilePart . '*');
        foreach ($files as &$file) {
            $file = GeneralUtility::getFileAbsFileName($file);
            if (is_file($file)) {
                unlink($file);
            }
        }

        return $files;
    }

    /**
     * Writes file to given path.
     * @param string $filePath the file to write
     * @param string $data the data to save
     * @return boolean    true if successful, false otherwise
     */
    public function createXmlFile($filePath, $data)
    {
        $handle = @fopen($filePath, 'w');
        if ($handle === false) {
            $result = false;
        } else {
            $written = fwrite($handle, $data);
            if ($written === false || $written < strlen($data)) {
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
    public function prepareDateFilter()
    {
        // determine min, max time for the selected pages
        $timeRange = $this->permitsModel->getTimeRange();
        // determine min, max years
        $startYear = date('Y', $timeRange['startTime']);
        $endYear = date('Y', $timeRange['endTime']);
        // set currents
        $currentYear = date('Y');
        $currentMonth = date('m');
        $currentWeek = date('W');
        // determine if there are incoming vars
        if ($this->getPiVar('activeMonth') != '') {
            $activeMonth = $this->getPiVar('activeMonth');
        }
        if ($this->getPiVar('activeYear') != '') {
            $activeYear = $this->getPiVar('activeYear');
        }
        /*if($this->getPiVar('activeWeek') != '') {
            $iActiveWeek = $this->piVars['activeWeek'];
        }*/
        // if some vars were not set as incoming, define the defaults
        $monthWasNotActive = false;
        if (!isset($activeMonth)) {
            $activeMonth = 1; // default to first month of year
            $monthWasNotActive = true;
        }
        if (!isset($activeYear)) {
            $activeYear = (int)date('Y'); // default to current year
            $activeMonth = (int)date('m'); // default to current month
            if ($this->configModel->get('defaultShowCurrentWeek')) {
                if ($monthWasNotActive) {
                    $activeWeek = $currentWeek; // default to current week when nothing was set
                }
            }
        }
        // check the values and correct if nessecary
        $currentYearActive = false;
        if ($activeYear == $currentYear) {
            $currentYearActive = true;
        }
        // determine week timestamps
        $startWeek = date('W', mktime(0, 0, 0, $activeMonth, 1, $activeYear));
        $endWeek = date('W', mktime(0, 0, 0, $activeMonth + 1, 1, $activeYear));
        if ($endWeek < $startWeek) {
            $endWeek = 52;
        }
        // check if the week was set correctly
        if ($activeWeek < $startWeek || $activeWeek > $endWeek) {
            // make sure it is empty
            unset($activeWeek);
        }
        // determine visibility
        if (isset($activeWeek)) {
            // filter on active week
            $week = 60 * 60 * 24 * 7;
            $startDate = mktime(0, 0, 0, $activeMonth, 1, $activeYear) + ($activeWeek - $startWeek) * $week;
            $dayOfWeek = date('N', $startDate);
            $startDate -= ($dayOfWeek - 1) * 60 * 60 * 24;
            $endDate = $startDate + $week;
        } else {
            // filter on active month, year
            $startDate = mktime(0, 0, 0, $activeMonth, 1, $activeYear);
            $endDate = mktime(0, 0, 0, $activeMonth + 1, 1, $activeYear);
        }
        // Location filtering
        $zipCode = $this->getPiVar('zipcode');
        $radius = $this->getPiVar('radius');

        // create data for view
        $this->dateFilter = array(
            'iStartYear' => $startYear,
            'iEndYear' => $endYear,
            'iStartWeek' => $startWeek,
            'iEndWeek' => $endWeek,
            'iActiveYear' => $activeYear,
            'iActiveMonth' => $activeMonth,
            'iActiveWeek' => $activeWeek,
            'iCurrentYear' => $currentYear,
            'iCurrentMonth' => $currentMonth,
            'iCurrentWeek' => $currentWeek,
            'iStartTime' => $startDate,
            'iEndTime' => $endDate,
            'bCurrentYearActive' => $currentYearActive,
            'sZipcode' => $zipCode,
            'iRadius' => $radius
        );
    }


    /**
     * Prepares the filter date list.
     * @return void
     */
    public function prepareDateFilterAll()
    {
        // determine min, max time for the selected pages
        $timeRange = $this->permitsModel->getTimeRange();
        // determine min, max years
        $startYear = date('Y', $timeRange['startTime']);
        $endYear = date('Y', $timeRange['endTime']);
        // set currents
        $currentYear = date('Y');
        $currentMonth = date('m');
        $currentWeek = date('W');
        // determine if there are incoming vars
        $showAllRecordsOfTheActiveYear = false;
        $showAllRecordsOfAllYears = false;
        if ($this->getPiVar('activeMonth') != '') {
            $activeMonth = $this->getPiVar('activeMonth');
            if ($activeMonth == 13) {
                $showAllRecordsOfTheActiveYear = true;
            }
        }
        if ($this->getPiVar('activeYear') != '') {
            $activeYear = $this->getPiVar('activeYear');
            if ($activeYear == 9999) {
                $showAllRecordsOfAllYears = true;
            }
        }
        /*if($this->getPiVar('activeWeek') != '') {
            $iActiveWeek = $this->piVars['activeWeek'];
        }*/
        // if some vars were not set as incoming, define the defaults
        $monthWasNotActive = false;
        if (!isset($activeMonth)) {
            $activeMonth = 1; // default to first month of year
            $monthWasNotActive = true;
        }
        if (!isset($activeYear)) {
            $activeYear = (int)date('Y'); // default to current year
            $activeMonth = (int)date('m'); // default to current month
            if ($this->configModel->get('defaultShowCurrentWeek')) {
                if ($monthWasNotActive) {
                    $activeWeek = $currentWeek; // default to current week when nothing was set
                }
            }
        }
        // check the values and correct if nessecary
        $currentYearActive = false;
        if ($activeYear == $currentYear) {
            $currentYearActive = true;
        }
        // determine week timestamps
        $startWeek = date('W', mktime(0, 0, 0, $activeMonth, 1, $activeYear));
        $endWeek = date('W', mktime(0, 0, 0, $activeMonth + 1, 1, $activeYear));

        if (($showAllRecordsOfTheActiveYear) || ($showAllRecordsOfAllYears)) {
            $startWeek = 1;
            $endWeek = 52;
        }

        if ($endWeek < $startWeek) {
            $endWeek = 52;
        }
        // check if the week was set correctly
        if ($activeWeek < $startWeek || $activeWeek > $endWeek) {
            // make sure it is empty
            unset($activeWeek);
        }
        // month stars from 1
        if ($showAllRecordsOfTheActiveYear == false) {
            $endMonth = $activeMonth;
        } else {
            $endMonth = 12;
        }
        // determine visibility
        if (isset($activeWeek)) {
            // filter on active week
            $week = 60 * 60 * 24 * 7;
            $startDate = mktime(0, 0, 0, $activeMonth, 1, $activeYear) + ($activeWeek - $startWeek) * $week;
            $dayOfWeek = date('N', $startDate);
            $startDate -= ($dayOfWeek - 1) * 60 * 60 * 24;
            $endDate = $startDate + $week;
        } else {
            // filter on active month, year
            $startDate = mktime(0, 0, 0, $activeMonth, 1, $activeYear);
            $endDate = mktime(0, 0, 0, $activeMonth + 1, 1, $activeYear);

            if ($showAllRecordsOfTheActiveYear) {
                $startDate = mktime(0, 0, 0, 1, 1, $activeYear);
                $endDate = mktime(0, 0, 0, 1, 1, $activeYear + 1);
            }

            if ($showAllRecordsOfAllYears) {
                $startDate = mktime(0, 0, 0, 1, 1, 1920);
                $endDate = mktime(0, 0, 0, 1, 1, ($currentYear + 1));
            }
        }

        // Location filtering
        $zipCode = $this->getPiVar('zipcode');
        $radius = $this->getPiVar('radius');

        // create data for view
        $this->dateFilter = array(
            'iStartYear' => $startYear,
            'iEndYear' => $endYear,
            'iStartWeek' => $startWeek,
            'iEndWeek' => $endWeek,
            'iActiveYear' => $activeYear,
            'iActiveMonth' => $activeMonth,
            'iActiveWeek' => $activeWeek,
            'iCurrentYear' => $currentYear,
            'iCurrentMonth' => $currentMonth,
            'iCurrentWeek' => $currentWeek,
            'iStartTime' => $startDate,
            'iEndTime' => $endDate,
            'bCurrentYearActive' => $currentYearActive,
            'sZipcode' => $zipCode,
            'iRadius' => $radius
        );
    }

    /**
     * Prepares the filter product type list.
     * @return void
     */
    public function prepareProductTypeFilter()
    {
        $this->productTypeFilter = $this->permitsModel->getProductTypes();
    }

    /**
     * Prepares the filter phase list.
     * @return void
     */
    public function preparePhaseFilter()
    {
        $this->phaseFilter = $this->permitsModel->getPhases();
    }

    /**
     * Prepares the filter phase list.
     * @return void
     */
    public function prepareTermTypeFilter()
    {
        $this->termTypeFilter = $this->permitsModel->getTermTypes();
    }

    /**
     * Returns filename identifying the current case record / document
     * @param string|bool $documentIndex the current
     * @return string    filename
     */
    public function getCaseFileName($documentIndex = false)
    {
        if ($documentIndex === false) {
            $result = 'xml_permit_' . $this->permitsModel->getId() . '.xml';
        } else {
            $result = 'xml_permit_' . $this->permitsModel->getId() . '_document_' . $documentIndex . '.xml';
        }
        return $result;
    }


    /**
     * Renders the exception view with details from the exception, or just shows exception information if an exception
     * occurs in the exception view rendering.
     *
     * @param \Exception $exception exception object
     * @return string    the content
     */
    function getException(\Exception &$exception)
    {
        try {
            $exceptionView = GeneralUtility::makeInstance('Netcreators\\NcgovPermits\\View\\ExceptionView');
            $exceptionView->initialize($this);
            $content = $exceptionView->getContent($exception);
        } catch (\Exception $critical) {
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
    function fillPiVarParams($params = array())
    {
        $knownParams = array_keys($this->knownParams);
        $linkParams = array();
        foreach ($knownParams as $paramName) {
            if (in_array($paramName, array_keys($params))) {
                $linkParams[$paramName] = $params[$paramName];
            } elseif ($this->getPiVar($paramName) !== null) {
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
    function getLinkToFilteredResult($linkText, $params)
    {
        $linkParams = $this->fillPiVarParams($params);
        return $this->getLinkToController($linkText, false, $linkParams);
    }

    /**
     *
     * @param $params
     * @return string
     */
    function getURLToFilteredResult($params = array())
    {
        $linkParams = $this->fillPiVarParams($params);
        return $this->getLinkToController(false, false, $linkParams, '', '', 1);
    }

    /**
     *
     * @param $params
     * @return string
     */
    function getURLToFilteredResultPublication($params = array())
    {
        $permitPage = $this->configModel->get('permitPage');
        $linkParams = $this->fillPiVarParams($params);
        return $this->getLinkToController(false, $permitPage, $linkParams);
    }

    /**
     *
     * @param $sFieldName
     * @param $sFile
     * @return string
     */
    function getLinkToItemFileUrl($fieldName, $file)
    {
        if (GeneralUtility::isFirstPartOfStr($file, 'http://')) {
            $path = $file;
        } else {
            $path = $this->tableData[$this->permitsModel->getTableName()][$fieldName]['uploadfolder'];
            $path = $path . '/' . $file;
        }
        return $path;
    }


    /**
     * Returns the view mode of the plugin.
     *
     * @return string    the view mode.
     */
    function getPluginMode()
    {
        return $this->configModel->get('pluginMode');
    }

    function doStuff()
    { //
        $this->getMunicipalities();
        return 'Hello!';
    }

    public function loadXMLFromFile($file)
    {
        $absFile = GeneralUtility::getFileAbsFileName($file);

        return simplexml_load_file($absFile);
    }

    /**
     * Returns a list of municipalities
     * @return \SimpleXMLElement
     */
    public function getMunicipalities()
    {
        $file = 'EXT:ncgov_permits/res/xml/waardelijsten/overheid.Gemeente.xml';
        $this->loadXMLFromFile($file);
    }

    public function getCadastreMunicipalities()
    {

    }

    public function htmlEntitiesForQuotes($str)
    {
        $find = array("\"", "'");
        $replace = array("&quot;", "&#039;");
        return str_replace($find, $replace, $str);
    }

}

