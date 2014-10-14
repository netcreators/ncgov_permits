<?php

namespace Netcreators\NcgovPermits\Service\CoreDataHandler;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ProcessDatamapHook {

	/**
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}
	
	function processDatamap_afterDatabaseOperations($status, $table, $id, &$fieldArray, &$pObj) {
		if ((GeneralUtility::_GP('_saveandclosedok_x') || GeneralUtility::_GP('_savedok_x')) && $table == 'tx_ncgovpermits_permits') {
			$currentPid = $pObj->checkValue_currentRecord['pid'];
			$tsconfig = BackendUtility::getModTSconfig($currentPid, 'tx_ncgovpermits.');
			$tsconfig = $tsconfig['properties']['properties.'];
			if($tsconfig['createPublicationCopyFromPermitEnabled']) {
				$values = $pObj->datamap[$table][$id];
				if (!is_numeric($id)) {
					$id = $pObj->substNEWwithIDs[$id];
				}

				# Vergunning
				if ($values['type'] == 0) {
					# Coordinates
					if (empty($values['coordinates']) && !empty($values['objectaddresses']) && $tsconfig['enableCoordinateAutocomplete']) {
						$values['coordinates'] = $this->addCoordinates(
							$values['objectaddresses'],
							$id,
							$table,
							'tx_ncgovpermits_addresses',
							'tx_ncgovpermits_coordinates',
							$tsconfig['pro6ppAuthKey']
						);
					}

					# Create publication copy
					$pid = $tsconfig['publicationsPid'];
					$permitAlreadyHasCopy = $this->permitAlreadyHasCopy($table, $pid, $id);
					if(!$permitAlreadyHasCopy) {
						$values['objectaddresses'] = $this->copyRecords(
							'tx_ncgovpermits_addresses',
							'zipcode, addressnumber, addressnumberadditional, address, city, municipality, province',
							$values['objectaddresses'],
							$pid,
							$pObj
						);

						$values['lots'] = $this->copyRecords(
							'tx_ncgovpermits_lots',
							'cadastremunicipality, section, number',
							$values['lots'],
							$pid,
							$pObj
						);

						$values['coordinates'] = $this->copyRecords(
							'tx_ncgovpermits_coordinates',
							'coordinatex, coordinatey, coordinatez',
							$values['coordinates'],
							$pid,
							$pObj
						);

						$defvalue =	'&defVals[' . $table . '][type]=1' . 
									'&defVals[' . $table . '][publishdate]=' . $values['publishdate'] . 
									'&defVals[' . $table . '][casereference_pub]=' . $values['casereference'] . 
									'&defVals[' . $table . '][objectaddresses]=' . $values['objectaddresses'] . 
									'&defVals[' . $table . '][lots]=' . $values['lots'] . 
									'&defVals[' . $table . '][coordinates]=' . $values['coordinates'] . 
									'&defVals[' . $table . '][related]=' . $id;

						if ($values['termtype'] == 'bezwaar') {
							$defvalue .=	'&defVals[' . $table . '][termtype_start]=' . $values['termtype_start'] .
											'&defVals[' . $table . '][termtype_end]=' . $values['termtype_end'];
						}

						if (GeneralUtility::_GP('_saveandclosedok_x')) {
							$retUrl = 'returnUrl=' . rawurlencode('db_list.php?id=' . $tsconfig['permitsPid'] . '&table=&edit[' . $table . '][' . $id . ']=edit'); 
						}
						else {
							$retUrl = 'returnUrl=' . rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI'));
						}

						$params = '&edit[' . $table . '][' . $pid . ']=new' . $defvalue;
						$url = $GLOBALS['BACK_PATH'] . 'alt_doc.php?' . $retUrl . $params;

						header('Location: ' . $url); die;						
					}
				}
				# Publication
				else {
					# Coordinates
					$extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ncgov_permits']);
					if (array_key_exists('enableCoordinatesForPublications', $extensionConfiguration) && $extensionConfiguration['enableCoordinatesForPublications']) {
						if (empty($values['coordinates']) && !empty($values['objectaddresses']) && $tsconfig['enableCoordinateAutocomplete']) {
							$values['coordinates'] = $this->addCoordinates(
								$values['objectaddresses'],
								$id,
								$table,
								'tx_ncgovpermits_addresses',
								'tx_ncgovpermits_coordinates',
								$tsconfig['pro6ppAuthKey']
							);
						}
					}
					
					// update reference
					$pid = $tsconfig['permitsPid'];
					if(!empty($values['casereference'])) {
						$this->getDatabaseConnection()->exec_UPDATEquery(
							$table,
							'pid = ' . $pid . ' AND type = 0 AND casereference = ' . $values['casereference_pub'],
							array('tstamp' => time())
						);						
					}
					
					// update related back
					if(isset($values['related'])) {
						$related = $values['related'];
						if(!is_numeric($values['related'])) {
							$related = GeneralUtility::trimExplode('_', $values['related']);
							$related = array_pop($related);
						}
						if($related) {
							// only try with valid values
							$this->getDatabaseConnection()->exec_UPDATEquery(
								$table,
								'pid = ' . $pid . ' AND type = 0 AND uid = ' . $related,
								array('tstamp' => time(), 'related' => $id)
							);
						}
					}
				}
			}
		}
	}

	function permitAlreadyHasCopy($table, $publicationPid, $permitUid) {
		$result = false;
		$permits = $this->getDatabaseConnection()->exec_SELECTgetRows(
			'*',
			$table,
			'pid = ' . $publicationPid . ' AND related = \'' . $permitUid . '\' AND deleted = 0'
		);
		if(count($permits) > 0) {
			$result = true;
		}
		return $result;
	}
	
	function addCoordinates($addressUids, $permitUid, $permitsTable, $addressesTable, $coordinatesTable, $pro6ppAuthKey) {
		$addresses = $this->getDatabaseConnection()->exec_SELECTgetRows(
			'cruser_id, hidden, pid, zipcode, addressnumber',
			$addressesTable,
			'uid IN (' . $addressUids . ') AND deleted = 0'
		);
		
		$arrCoordinatesUids = array();
		$client = new \SoapClient("http://api.pro6pp.nl/v1/soap/api.wsdl");
		foreach ($addresses as $address) {
			$zipcode = trim(str_replace(' ', '', $address['zipcode']));
			
			$arrReq = array('auth_key' => $pro6ppAuthKey);
			if (strlen($zipcode) == 4) {
				$arrReq['nl_fourpp'] = $zipcode;
			} elseif (strlen($zipcode) == 6) {
				$arrReq['nl_sixpp'] = $zipcode;
			}

			if (count($arrReq) > 1) {
				$result = $client->autocomplete($arrReq); 
				$arrRes = get_object_vars($result->results->result);
				
				$coordinatex = round($arrRes['lat'], 2);
				$coordinatey = round($arrRes['lng'], 2);
				
				$record['tstamp'] = $record['crdate'] = time();
				$record['pid'] = $address['pid'];
				$record['cruser_id'] = $address['cruser_id'];
				$record['hidden'] = $address['hidden'];
				$record['coordinatex'] = $coordinatex;
				$record['coordinatey'] = $coordinatey;
				$this->getDatabaseConnection()->exec_INSERTquery(
					$coordinatesTable,
					$record
				);
				$arrCoordinatesUids[] = $this->getDatabaseConnection()->sql_insert_id();
			}
		}
		$coordinatesUids = implode(',', $arrCoordinatesUids);
		
		$this->getDatabaseConnection()->exec_UPDATEquery(
			$permitsTable,
			'uid = ' . $permitUid,
			array('coordinates' => $coordinatesUids)
		);
		
		return $coordinatesUids;
	}
	
	function copyRecords($table, $fields, $uids, $pid, &$pObj) {
		if (!empty($uids)) {
			$arrUids = GeneralUtility::trimExplode(',', $uids);
			foreach ($arrUids as &$uid)
			{
				if (!is_numeric($uid)) {
					$uid = $pObj->substNEWwithIDs[$uid];
				}
			}
			$uids = implode(',', $arrUids);
			
			$records = $this->getDatabaseConnection()->exec_SELECTgetRows(
				'cruser_id, deleted, hidden, ' . $fields,
				$table,
				'uid IN (' . $uids . ') AND deleted = 0'
			);
			
			$newRecords = array();
			foreach ($records as $record) {
				$record['tstamp'] = $record['crdate'] = time();
				$record['pid'] = $pid;
				$this->getDatabaseConnection()->exec_INSERTquery(
					$table,
					$record
				);
				$newRecords[] = $this->getDatabaseConnection()->sql_insert_id();
			}
			
			return implode(',', $newRecords);
		}
		else {
			return '';
		}
	}
}
?>