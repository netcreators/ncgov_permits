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

namespace Netcreators\NcgovPermits\Domain\Model;

use Netcreators\NcgovPermits\Controller\PermitController;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

class Permit extends Base {
	/**
	 * @var array
	 */
	protected $publications = array();
	private $transactionId;
	protected $addresses, $coordinates, $lots;
	protected $modelType;
	const TYPE_PERMIT = 0;
	const TYPE_PUBLICATION = 1;

	function initialize(PermitController &$controller) {
		parent::initialize($controller);
		$this->setTableName('tx_ncgovpermits_permits');
		$this->addresses = false;
		$this->lots = false;
		$this->coordinates = false;
		$this->transactionId = false;
		$this->modelType = self::TYPE_PERMIT;
		$this->setGetMethod('documents', '_getField_getDocuments');
		$this->setGetMethod('documenttypes', '_getField_getDocumentTypes');
		$this->setGetMethod('crdate', '_getField_getCreationDate');
		$this->setGetMethod('tstamp', '_getField_getTstamp');
		$this->setGetMethod('lastmodified', '_getField_getModifiedDate');
		$this->setGetMethod('objectzipcode', '_getField_getObjectZipcode');
		$this->setGetMethod('objectaddresses', '_getField_getObjectAddresses');
		$this->setGetMethod('lots', '_getField_getLots');
		$this->setGetMethod('coordinates', '_getField_getCoordinates');
		$this->setGetMethod('publishdate', '_getField_getPublishdate');
		$this->setGetMethod('validity_start', '_getField_getValidityStart');
		$this->setGetMethod('validity_end', '_getField_getValidityEnd');
		$this->setGetMethod('termtype_start', '_getField_getValidityStart');
		$this->setGetMethod('termtype_end', '_getField_getValidityEnd');
		$this->setGetMethod('publications', '_getField_getPublications');
	}

	/**
	 * Loads all permit records.
	 *
	 * @throws \tx_nclib_exception
	 * @return boolean true if successful, false otherwise.
	 */
	public function loadPermits() {
		$pageIds = $this->database->getPageIdsRecursive(
			$this->controller->configModel->get('storageFolder'),
			$this->controller->configModel->get('recurseDepth')
		);
		if($pageIds === false) {
			throw new \tx_nclib_exception('label_error_no_pages_found', $this->controller);
		}
		$fields = '*';
		$where = array(
			'hidden=0',
			'deleted=0',
			sprintf('%s.pid in (%s)', $this->getTableName(), implode(',', $pageIds)),
		);
		$where = $this->database->getWhere($where);
		$orderBy = '';
		$groupBy = '';

		$this->database->clear();
		$records = $this->database->getQueryRecords($this->getTableName(), $fields, $where, $groupBy, $orderBy);
		if(!$records || !\tx_nclib::isLoopable($records)) {
			return false;
		}
		$this->setIterationArray($records);
		return true;
	}

	/**
	 * Loads records for the specified page.
	 *
	 * @throws \tx_nclib_exception
	 * @internal param int $iPageId the parent pageid
	 * @return boolean true if successful, false otherwise.
	 */
	public function loadPublishablePermits() {
		$pageIds = $this->database->getPageIdsRecursive(
			$this->controller->configModel->get('storageFolder'),
			$this->controller->configModel->get('recurseDepth')
		);
		if($pageIds === false) {
			throw new \tx_nclib_exception('label_error_no_pages_found', $this->controller);
		}
		$fields = '*';
		$where = array(
			'(lastpublished = 0 OR lastmodified > lastpublished)',
			'publishdate <= ' . time(),
			'type = ' . self::TYPE_PERMIT,
			'hidden=0',
			'deleted=0',
			sprintf('%s.pid in (%s)', $this->getTableName(), implode(',', $pageIds)),
		);
		$where = $this->database->getWhere($where);
		$orderBy = '';
		$groupBy = '';
		$limit = $this->controller->configModel->get('latestlimit');

		$this->database->clear();
		$records = $this->database->getQueryRecords($this->getTableName(), $fields, $where, $groupBy, $orderBy, $limit);
		if(!$records || !\tx_nclib::isLoopable($records)) {
			return false;
		}
		$this->setIterationArray($records);
		return true;
	}

	/**
	 * Sets the type of records to be loaded (permits / publications)
	 * @param int $type
	 * @return void
	 */
	public function setModelType($type) {
		$this->modelType = $type;
	}

	/**
	 * Returns the record type to be loaded.
	 */
	public function getModelType() {
		return $this->modelType;
	}

	/**
	 * Loads the time range for the permits (oldest-newest)
	 * @throws \tx_nclib_exception
	 * @return array [startTime], [endTime]
	 */
	public function getTimeRange() {
		$pageIds = $this->database->getPageIdsRecursive(
			$this->controller->configModel->get('storageFolder'),
			$this->controller->configModel->get('recurseDepth')
		);
		if($pageIds === false) {
			throw new \tx_nclib_exception('label_error_no_pages_found', $this->controller);
		}
		$fields = 'MIN(publishdate) as startTime, MAX(publishdate) as endTime';
		$where = array(
			'hidden=0',
			'deleted=0',
			'type=' . $this->getModelType(),
			'publishdate < ' . time(),
			sprintf('%s.pid in (%s)', $this->getTableName(), implode(',', $pageIds)),
		);
		$where = $this->database->getWhere($where);
		$orderBy = '';
		$groupBy = '';

		$this->database->clear();

		$records = $this->database->getQueryRecords($this->getTableName(), $fields, $where, $groupBy, $orderBy);
		if(!$records || !\tx_nclib::isLoopable($records)) {
			return false;
		}
		$result = array(
			'startTime' => $records[0]['startTime'],
			'endTime' => $records[0]['endTime'],
		);
		return $result;
	}

	/**
	 * Loads items for a certain daterange
	 * @param integer $startTime
	 * @param integer $endTime
	 * @throws \tx_nclib_exception
	 * @return boolean
	 */
	public function loadPermitsForRange($startTime, $endTime) {
		$pageIds = $this->database->getPageIdsRecursive(
			$this->controller->configModel->get('storageFolder'),
			$this->controller->configModel->get('recurseDepth')
		);
		if($pageIds === false) {
			throw new \tx_nclib_exception('label_error_no_pages_found', $this->controller);
		}
		$fields = '*';
		if($endTime > time()) {
			$endTime = time();
		}
		$where = array(
			'hidden=0',
			'deleted=0',
			sprintf('%s.pid in (%s)', $this->getTableName(), implode(',', $pageIds)),
			'type=' . $this->getModelType(),
			'publishdate >= ' . $startTime,
			'publishdate <= ' . $endTime
		);
		$where = $this->database->getWhere($where);
		$orderBy = 'publishdate DESC';
		$groupBy = '';

		$this->database->clear();
		$records = $this->database->getQueryRecords($this->getTableName(), $fields, $where, $groupBy, $orderBy);
		if(!$records || !\tx_nclib::isLoopable($records)) {
			$this->setIterationArray(array());	// don't exactly know why...
			return false;
		}
		$this->setIterationArray($records);
		return true;
	}

	/**
	 * Loads items filtered by year/month, fulltext search phrase, productType and phase
	 * @throws \tx_nclib_exception
	 * @return boolean
	 */
	public function loadPermitsFiltered() {
		$startTime = $this->controller->dateFilter['iStartTime'];
		$endTime = $this->controller->dateFilter['iEndTime'];
		if($this->controller->configModel->get('dontShowRecordsPublishedInTheFuture')) {
			if($endTime > time()) {
				$endTime = time();
			}
		}
		$fulltextPhrase = explode(' ',$this->controller->getPiVar('fulltext'));
		$productType = $this->controller->getPiVar('productType');
		$termType = $this->controller->getPiVar('termType');
		$phaseList = array();
		$phases = $this->controller->getPiVar('phase');
		if(\tx_nclib::isLoopable($phases)) {
			foreach($phases as $key => $phase) {
				if($phase) {
					$phaseList[] = $this->controller->phaseFilter[$key];
				}
			}
		}
		
		$postcodeNum = $this->controller->getPiVar('postcodeNum');
		$postcodeAlpha = $this->controller->getPiVar('postcodeAlpha');
		$radius = $this->controller->getPiVar('radius');
		$zipcode = addslashes($postcodeNum . $postcodeAlpha);

		$pageIds = $this->database->getPageIdsRecursive(
			$this->controller->configModel->get('storageFolder'),
			$this->controller->configModel->get('recurseDepth')
		);
		if($pageIds === false) {
			throw new \tx_nclib_exception('label_error_no_pages_found', $this->controller);
		}
		$fields = 'permits.*';
		/*if($endTime > time()) {
			$endTime = time();
		}*/
		$where = array(
			'permits.hidden=0',
			'permits.deleted=0',
			sprintf('permits.pid IN (%s)', implode(',', $pageIds)),
			'permits.type=' . $this->getModelType(),
			'permits.publishdate >= ' . $startTime,
			'permits.publishdate <= ' . $endTime
		);
		if(count($fulltextPhrase)) {
			$textColumns = array(
				'permits.producttype',
				'permits.description',
				'permits.casereference',
				'permits.phase',
				'permits.termtype',
				'permits.company',
				'permits.companynumber',
				'permits.companyaddress',
				'permits.companyaddressnumber',
				'permits.companyzipcode',
				'permits.objectreference',
				'permits.title',
				'permits.link',
				'addresses.zipcode',
				'addresses.address',
				'addresses.city',
				'addresses.municipality',
				'addresses.province'
			);
			foreach($fulltextPhrase as $fulltextWord) {
				if(!$fulltextWord)
					continue;
				$fulltextWordWhere = array();
				foreach($textColumns as $columnName) {
					$fulltextWordWhere[] = sprintf('%s LIKE \'%%%s%%\'', $columnName, addslashes($fulltextWord));
				}
				$where[] = sprintf('(%s)', implode(' OR ', $fulltextWordWhere));
			}
		}
		if($productType) {
			$where[] = sprintf('permits.producttype = \'%s\'', addslashes($productType));
		}
		if($termType) {
			$where[] = sprintf('permits.termtype = \'%s\'', addslashes($termType));
		}
		 if(count($phaseList)) {
			$quotedPhases = $this->getFullQuotedPhaseList($phaseList);
			$where[] = sprintf('permits.phase IN (%s)', implode(',', $quotedPhases));
		 }

		// Location
		if (!empty($zipcode) &&  $this->controller->configModel->get('regionSearch')) {
			$zipcode = trim(str_replace(' ', '', $zipcode));
			
			$arrReq = array('auth_key' => $this->controller->configModel->get('pro6ppAuthKey'));
			if (strlen($zipcode) == 4) {
				$arrReq['nl_fourpp'] = $zipcode;
			} elseif (strlen($zipcode) == 6) {
				$arrReq['nl_sixpp'] = $zipcode;
			}
			
			if (count($arrReq) > 1) {
				$skipRegionSearch = false;
				try {
					$client = new \SoapClient("http://api.pro6pp.nl/v1/soap/api.wsdl");
					$result = $client->autocomplete($arrReq); 
				} catch (\Exception $e) {
					$skipRegionSearch = true;
				}
				if(!$skipRegionSearch) {
					/** @var object $result */
					$arrRes = get_object_vars($result->results->result);

					$rEarth = 6371;
					$degrees = ($radius * 180) / ($rEarth * M_PI);
					$minLat = round($arrRes['lat']-$degrees, 2);
					$maxLat = round($arrRes['lat']+$degrees, 2);
					$minLng = round($arrRes['lng']-$degrees, 2);
					$maxLng = round($arrRes['lng']+$degrees, 2);

					$coordinatesIds = array();
					$coordinatesTable = $this->controller->extKeyShort . '_coordinates';
					$coordinatesWhere = array(
						'coordinatex >= ' . $minLat,
						'coordinatex <= ' . $maxLat,
						'coordinatey >= ' . $minLng,
						'coordinatey <= ' . $maxLng
					);
					$coordinatesWhere = $this->database->getWhere($coordinatesWhere);
					$coordinatesRecords = $this->database->getQueryRecords($coordinatesTable, 'uid', $coordinatesWhere);		

					if(!$coordinatesRecords || !\tx_nclib::isLoopable($coordinatesRecords)) {
						//$where[] = '1=0';
					}
					else {
						foreach($coordinatesRecords as $record) {
							$coordinatesIds[] = $record['uid'];
						}
						$this->setIterationArray($coordinatesRecords);
						$where[] = sprintf('permits.coordinates in (%s)', implode(',', $coordinatesIds));
					}
				}
			}
		}
		if (!empty($zipcode) && $this->controller->configModel->get('regionStringSearch')) {
			$addressTable = $this->controller->extKeyShort . '_addresses';
			$addressIds = array();
			$addressWhere = array(
				'zipcode LIKE \'%' . addslashes($zipcode) . '%\'',
			);
			$addressWhere = $this->database->getWhere($addressWhere);
			$addressRecords = $this->database->getQueryRecords($addressTable, 'uid', $addressWhere);
			if(\tx_nclib::isLoopable($addressRecords)) {
				foreach($addressRecords as $record) {
					$addressIds[] = $record['uid'];
				}
				$where[] = sprintf('permits.objectaddresses in (%s)', implode(',', $addressIds));				
			}
		}
                
		$where = $this->database->getWhere($where);
		//$orderBy = 'permits.publishdate DESC';
        $orderBy = 'permits.publishdate DESC, addresses.address ASC';
		$groupBy = '';
            
		$table = sprintf(
			"%s permits LEFT JOIN %s addresses ON addresses.uid IN (permits.objectaddresses) AND addresses.hidden = 0 AND addresses.deleted = 0 AND addresses.pid IN (%s)",
			$this->getTableName(),
			$this->controller->extKeyShort . '_addresses',
			implode(',', $pageIds)
		);

		$this->database->clear();
		$records = $this->database->getQueryRecords($table, $fields, $where, $groupBy, $orderBy);
		if(!$records || !\tx_nclib::isLoopable($records)) {
			$this->setIterationArray(array());
			return false;
		}
		$this->setIterationArray($records);
		return true;
	}

	private function getFullQuotedPhaseList($input) {
		$result = array();
		if(is_array($input) && count($input) > 0) {
			foreach($input as $item) {
				$result[] = sprintf('\'%s\'', addslashes($item));
			}
		}
		return $result;
	}
	
	/**
	 * Returns RTE description.
	 * @return string the description
	 */
	public function getRichDescription() {
		$rteText = trim($this->getField('description', true));
		return $this->controller->pi_RTEcssText($rteText);
	}

	/**
	 * Returns RTE publication body.
	 * @return string the publucation body
	 */
	public function getRichPublicationBody() {
		$rteText = trim($this->getField('publicationbody', true));
		return $this->controller->pi_RTEcssText($rteText);
	}

	/**
	 * Returns a descriptive title of the item.
	 * @return string
	 */
	public function getTitle() {
		if($this->isPermit()) {
			$addresses = $this->getField('objectaddresses');
			if($addresses != false) {
				$location = $addresses[0]['zipcode'];
				$location .= ' ' . $addresses[0]['addressnumber'];
				$location .= $addresses[0]['addressnumberadditional'];
				$location .= ' ' . $addresses[0]['city'];
				$result = sprintf(
					'%s %s %s',
					$this->getField('producttype'),
					$this->getField('productactivities'),
					$location
				);
			} else {
				$result = sprintf(
					'%s %s',
					$this->getField('producttype'),
					$this->getField('productactivities')
				);
			}
		} else {
			$result = $this->getField('title');
		}
		return $result;
	}

	/**
	 * Returns the type of spatial information
	 * @param int $index
	 * @return bool|string 'PostcodeHuisnummer', 'Gemeente', 'Provincie', 'Waterschap' or false if none set
	 */
	public function getOwmsSpatialType($index=0) {
		$result = false;
		$addresses = $this->getField('objectaddresses');
		if($addresses != false) {
			if($addresses[$index]['zipcode'] != '' && $addresses[$index]['addressnumber'] != '') {
				$result = 'PostcodeHuisnummer';
			} elseif($addresses[$index]['municipality'] != '' && $addresses[$index]['address'] == '') {
				$result = 'Gemeente';
			} elseif( ($addresses[$index]['municipality'] != '' || $addresses[$index]['city'] != '') && $addresses[$index]['address'] != '') {	
				$result = 'Street';
			} elseif($addresses[$index]['province'] != '') {
				$result = 'Provincie';
			} 
		}
		return $result;
	}

	/**
	 * Returns the address.
	 * @param int $index
	 * @return string
	 */
	public function getAddress($index=0) {
		$addresses = $this->getField('objectaddresses');
		$addresses = $addresses[$index];
		switch($this->getOwmsSpatialType($index)) {
			case 'Street':
				$location = $addresses['address'];
				if($addresses['city'] != '') {
					$location .= ' ' . $addresses['city'];
				} elseif ($addresses['municipality'] != '') {
					$location .= ' ' . $addresses['municipality'];
				}
				$result = $location;
				break;
			case 'PostcodeHuisnummer':
				$location = $addresses['address'];
				$location .= ' ' . $addresses['addressnumber'];
				$location .= $addresses['addressnumberadditional'];
				$location .= ' ' . $addresses['zipcode'];
				$location .= ' ' . $addresses['city'];
				$result = $location;
				break;
			case 'Gemeente':
				$result = $addresses['municipality'];
				break;
			case 'Provincie':
				$result = $addresses['province'];
				break;
			default:
				$result = '';
		}
		return $result;
	}

	/**
	 * Loads the product types for the permits
	 * @throws \tx_nclib_exception
	 * @return array
	 */
	public function getProductTypes() {
		$pageIds = $this->database->getPageIdsRecursive(
			$this->controller->configModel->get('storageFolder'),
			$this->controller->configModel->get('recurseDepth')
		);
		if($pageIds === false) {
			throw new \tx_nclib_exception('label_error_no_pages_found', $this->controller);
		}
		$fields = 'DISTINCT producttype as productType';
		$startTime = $this->controller->dateFilter['iStartTime'];
		$endTime = $this->controller->dateFilter['iEndTime'];
		$where = array(
			'producttype != \'\'',
			'hidden=0',
			'deleted=0',
			'type=' . $this->getModelType(),
			'publishdate >= ' . $startTime,
			'publishdate <= ' . $endTime,
			sprintf('%s.pid in (%s)', $this->getTableName(), implode(',', $pageIds)),
		);
		$where = $this->database->getWhere($where);
		$orderBy = 'producttype';
		$groupBy = '';

		$this->database->clear();

		$records = $this->database->getQueryRecords($this->getTableName(), $fields, $where, $groupBy, $orderBy);
		if(!$records || !\tx_nclib::isLoopable($records)) {
			return false;
		}
		$result = array();
		foreach($records as $record) {
			$result[] = $record['productType'];
		}
		return $result;
	}

	/**
	 * Loads the phases for the permits
	 * @throws \tx_nclib_exception
	 * @return array
	 */
	public function getPhases() {
		$pageIds = $this->database->getPageIdsRecursive(
			$this->controller->configModel->get('storageFolder'),
			$this->controller->configModel->get('recurseDepth')
		);
		if($pageIds === false) {
			throw new \tx_nclib_exception('label_error_no_pages_found', $this->controller);
		}
		$fields = 'DISTINCT phase';
		$startTime = $this->controller->dateFilter['iStartTime'];
		$endTime = $this->controller->dateFilter['iEndTime'];
		$where = array(
			'phase != \'\'',
			'hidden=0',
			'deleted=0',
			'type=' . $this->getModelType(),
			'publishdate >= ' . $startTime,
			'publishdate <= ' . $endTime,
			sprintf('%s.pid in (%s)', $this->getTableName(), implode(',', $pageIds)),
		);
		$where = $this->database->getWhere($where);
		$orderBy = 'phase';
		$groupBy = '';

		$this->database->clear();

		$records = $this->database->getQueryRecords($this->getTableName(), $fields, $where, $groupBy, $orderBy);
		if(!$records || !\tx_nclib::isLoopable($records)) {
			return false;
		}
		$result = array();
		foreach($records as $record) {
			$result[] = $record['phase'];
		}
		return $result;
	}

	/**
	 * Loads the term types for the permits
	 * @throws \tx_nclib_exception
	 * @return array
	 */
	public function getTermTypes() {
		$pageIds = $this->database->getPageIdsRecursive(
			$this->controller->configModel->get('storageFolder'),
			$this->controller->configModel->get('recurseDepth')
		);
		if($pageIds === false) {
			throw new \tx_nclib_exception('label_error_no_pages_found', $this->controller);
		}
		$fields = 'DISTINCT termtype';
		$startTime = $this->controller->dateFilter['iStartTime'];
		$endTime = $this->controller->dateFilter['iEndTime'];
		$where = array(
			'termtype != \'\'',
			'hidden=0',
			'deleted=0',
			'type=' . $this->getModelType(),
			'publishdate >= ' . $startTime,
			'publishdate <= ' . $endTime,
			sprintf('%s.pid in (%s)', $this->getTableName(), implode(',', $pageIds)),
		);
		$where = $this->database->getWhere($where);
		$orderBy = 'termtype';
		$groupBy = '';

		$this->database->clear();

		$records = $this->database->getQueryRecords($this->getTableName(), $fields, $where, $groupBy, $orderBy);
		if(!$records || !\tx_nclib::isLoopable($records)) {
			return false;
		}
		$result = array();
		foreach($records as $record) {
			$result[] = $record['termtype'];
		}
		return $result;
	}

	/**
	 * Returns the spacial value
	 * @param int $index
	 * @return string
	 */
	public function getOwmsSpatialValue($index=0) {
		$addresses = $this->getField('objectaddresses');
		$addresses = $addresses[$index];
		switch($this->getOwmsSpatialType($index)) {
			case 'PostcodeHuisnummer':
				$location = str_replace(' ', '', strtoupper($addresses['zipcode']));
				$location .= $addresses['addressnumber'];
				$location .= $addresses['addressnumberadditional'];
				$result = $location;
				break;
			case 'Street':
			case 'Gemeente':
				$result = $addresses['municipality'];
				break;
			case 'Provincie':
				$result = $addresses['province'];
				break;
			default:
				$result = '';
		}
		return $result;
	}

	/**
	 * Is the company info filled in?
	 * @return boolean
	 */
	public function hasCompanyInfo() {
		$result = false;
		if($this->getField('company') != '' && $this->getField('companyzipcode') != '') {
			$result = true;
		}
		return $result;
	}


	/**
	 * Returns the zipcode appended with the addressnumber and optional addressnumberadditional
	 * @return string
	 */
	public function getOwmsCompanyAddress() {
		$location = str_replace(' ', '', $this->getField('companyzipcode'));
		$location .= $this->getField('companyaddressnumber');
		$location .= $this->getField('companyaddressadditional');
		return $location;
	}
	protected function _getField_getDocuments($field) {
		$list = trim($this->getField($field, true));
		if(empty($list)) {
			$list = false;
		} else {
			$list = GeneralUtility::trimExplode(',', $list);
		}
		return $list;
	}
	protected function _getField_getDocumentTypes($field) {
		$list = $this->getField($field, true);
		$list = GeneralUtility::trimExplode(',', $list);
		return $list;
	}
	protected function _getField_getCreationDate($field) {
		$time = $this->getField($field, true);
		if(empty($time)) {
			return false;
		}
		$date = date($this->controller->configModel->get('config.dateFormat'), $time);
		return $date;
	}
	protected function _getField_getTstamp($field) {
		$time = $this->getField($field, true);
		if(empty($time)) {
			return false;
		}
		$date = date($this->controller->configModel->get('config.dateFormat'), $time);
		return $date;
	}
	protected function _getField_getModifiedDate($field) {
		$time = $this->getField($field, true);
		if(empty($time)) {
			return false;
		}
		$date = date($this->controller->configModel->get('config.dateFormat'), $time);
		return $date;
	}
	protected function _getField_getValidityStart($field) {
		$time = $this->getField($field, true);
		if(empty($time)) {
			return false;
		}
		$date = date($this->controller->configModel->get('config.dateFormat'), $time);
		return $date;
	}
	protected function _getField_getValidityEnd($field) {
		$time = $this->getField($field, true);
		if(empty($time)) {
			return false;
		}
		$date = date($this->controller->configModel->get('config.dateFormat'), $time);
		return $date;
	}
	protected function _getField_getPublishDate($field) {
		$time = $this->getField($field, true);
		if(empty($time)) {
			return false;
		}
		$date = date($this->controller->configModel->get('config.dateFormat'), $time);
		return $date;
	}
	protected function _getField_getObjectZipcode($field) {
		return strtoupper($this->getField($field, true));
	}
	protected function _getField_getObjectAddresses() {
		if($this->addresses !== false) {
			return $this->addresses;
		}
		$pageIds = $this->database->getPageIdsRecursive(
			$this->controller->configModel->get('storageFolder'),
			$this->controller->configModel->get('recurseDepth')
		);
		if($pageIds === false) {
			throw new \tx_nclib_exception('label_error_no_pages_found', $this->controller);
		}
		$fields = '*';
		$addressIds = $this->getField('objectaddresses', true);
		if(empty($addressIds)) {
			return false;
		}
		$where = array(
			'uid IN(' . $addressIds . ')',
			'hidden=0',
			'deleted=0',
			sprintf('pid in (%s)', implode(',', $pageIds)),
		);
		$where = $this->database->getWhere($where);
		$orderBy = '';
		$groupBy = '';

		$this->database->clear();
		$records = $this->database->getQueryRecords($this->controller->extKeyShort . '_addresses', $fields, $where, $groupBy, $orderBy);
		if(!$records || !\tx_nclib::isLoopable($records)) {
			return false;
		}
		$this->addresses = $records;
		return $records;
	}
	protected function _getField_getLots() {
		if($this->lots !== false) {
			return $this->lots;
		}
		$pageIds = $this->database->getPageIdsRecursive(
			$this->controller->configModel->get('storageFolder'),
			$this->controller->configModel->get('recurseDepth')
		);
		if($pageIds === false) {
			throw new \tx_nclib_exception('label_error_no_pages_found', $this->controller);
		}
		$fields = '*';
		$addressIds = $this->getField('lots', true);
		if(empty($addressIds)) {
			return false;
		}
		$where = array(
			'uid IN(' . $addressIds . ')',
			'hidden=0',
			'deleted=0',
			sprintf('pid in (%s)', implode(',', $pageIds)),
		);
		$where = $this->database->getWhere($where);
		$orderBy = '';
		$groupBy = '';

		$this->database->clear();
		$records = $this->database->getQueryRecords($this->controller->extKeyShort . '_lots', $fields, $where, $groupBy, $orderBy);
		if(!$records || !\tx_nclib::isLoopable($records)) {
			return false;
		}
		$this->lots = $records;
		return $records;
	}
	protected function _getField_getCoordinates() {
		if($this->coordinates !== false) {
			return $this->coordinates;
		}
		$pageIds = $this->database->getPageIdsRecursive(
			$this->controller->configModel->get('storageFolder'),
			$this->controller->configModel->get('recurseDepth')
		);
		if($pageIds === false) {
			throw new \tx_nclib_exception('label_error_no_pages_found', $this->controller);
		}
		$fields = '*';
		$addressIds = $this->getField('coordinates', true);
		if(empty($addressIds)) {
			return false;
		}
		$where = array(
			'uid IN(' . $addressIds . ')',
			'hidden=0',
			'deleted=0',
			sprintf('pid in (%s)', implode(',', $pageIds)),
		);
		$where = $this->database->getWhere($where);
		$orderBy = '';
		$groupBy = '';

		$this->database->clear();
		$records = $this->database->getQueryRecords($this->controller->extKeyShort . '_coordinates', $fields, $where, $groupBy, $orderBy);
		if(!$records || !\tx_nclib::isLoopable($records)) {
			return false;
		}
		$this->coordinates = $records;
		return $records;
	}
	protected function _getField_getPublications() {
		/** @var \TYPO3\CMS\Core\Database\DatabaseConnection $TYPO3_DB */
		global $TYPO3_DB;

		if($this->publications) {
			return $this->publications;
		}
		$publicationsStorageFolder = MathUtility::canBeInterpretedAsInteger($this->controller->configModel->get('publicationsStorageFolder'))
			? $this->controller->configModel->get('publicationsStorageFolder')
			: $this->controller->configModel->get('storageFolder');
		$pageIds = $this->database->getPageIdsRecursive(
			$publicationsStorageFolder,
			$this->controller->configModel->get('recurseDepth')
		);
		if($pageIds === false) {
			throw new \tx_nclib_exception('label_error_no_pages_found', $this->controller);
		}
		$fields = '*';
		$caeseReference = $this->getField('casereference', true);
		if(empty($caeseReference)) {
			return false;
		}
		$where = array(
			sprintf('casereference_pub = \'%s\'', $TYPO3_DB->getDatabaseHandle()->real_escape_string($caeseReference)),
			'type=' . self::TYPE_PUBLICATION,
			'hidden=0',
			'deleted=0',
			sprintf('pid in (%s)', implode(',', $pageIds)),
		);
		$where = $this->database->getWhere($where);
		$orderBy = '';
		$groupBy = '';

		$this->database->clear();
		$records = $this->database->getQueryRecords($this->controller->extKeyShort . '_permits', $fields, $where, $groupBy, $orderBy);
		if(!$records || !\tx_nclib::isLoopable($records)) {
			return false;
		}
		$this->publications = $records;
		return $records;
	}

	/**
	 * xml functions
	 */
	public function getTransactionType() {
		$result = 'U'; // safe default
		$d = $this->getField('deleted') == 1 || $this->getField('hidden') == 1;
		if($d) {
			$result = 'D';
		} else {
			if($this->getField('lastpublished', true) == 0) {
				$result = 'C';
			} elseif ($this->getField('lastmodified', true) > $this->getField('lastpublished', true)) {
				$result = 'U';
			}
		}
		return $result;
	}

	public function skipThisUpdate() {
		// NOTE: \Netcreators\NcgovPermits\Domain\Model\Permit::loadPublishablePermits() anyway only loads records with deleted=0! Wtf?
		// This entire method seems obsolete, due to the SELECT ... WHERE in loadPublishablePermits().
		$result = false;
		$d = $this->getField('deleted') == 1 || $this->getField('hidden') == 1;
		if($d || $this->getField('lastpublished', true) > $this->getField('lastmodified', true)) {
			$result = true;
		}
		return $result;
	}

	/**
	 * Returns transaction id.
	 * @return string
	 */
	public function getTransactionId() {
		return $this->transactionId;
	}

	/**
	 * Returns the filetype of the document, deduced from the extension.
	 * @param int $index		the index of the document being investigated
	 * @return string
	 */
	public function getFileTypeFromDocument($index) {
		$documents = $this->getField('documents');
		$document = $documents[$index];
		$result = GeneralUtility::split_fileref($document);
		$ext = strtolower($result['realFileext']);
		if(empty($ext)) {
			$ext = strtolower($result['fileext']);
		}
		if(empty($ext)) {
			$result = false;
		} else {
			switch($ext) {
				case 'pdf':
					$result = 'application/pdf';
					break;
				case 'doc':
					$result = 'application/msword';
					break;
				case 'htm':
				case 'html':
					$result = 'text/html';
					break;
				default:
					$result = false;
			}
		}
		return $result;
	}

	/**
	 * Checks if this record is a permit record, or an publication
	 * @return boolean
	 */
	public function isPermit() {
		if($this->isLoaded()) {
			$result = $this->getField('type') == self::TYPE_PERMIT;
		} else {
			$result = false;
		}
		return $result;
	}

	// iteration functions to be handled (used for the loadRecord functions)
	public final function onNextRecord() {
		$this->lots = false;
		$this->setRecord($this->getCurrentRecord(), true);
		if($this->isLoaded()) {
			$this->transactionId = $this->getId() . date('YmdHis');
			$this->addresses = false;
		}
	}

	public final function onPreviousRecord() {
		$this->lots = false;
		$this->setRecord($this->getCurrentRecord(), true);
		if($this->isLoaded()) {
			$this->transactionId = $this->getId() . date('YmdHis');
			$this->addresses = false;
		}
	}

	public final function onRecordIndexChange() {
		$this->lots = false;
		$this->setRecord($this->getCurrentRecord(), true);
		if($this->isLoaded()) {
			$this->transactionId = $this->getId() . date('YmdHis');
			$this->addresses = false;
		}
	}
}

?>
