<?php

namespace Netcreators\NcgovPermits\Service\CoreDataHandler;

use Netcreators\NcgovPermits\Domain\Model\Permit;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ProcessDatamapHook
{

    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * @param $table
     * @param array &$fieldArray
     * @return bool
     */
    protected function canProcess($table, array &$fieldArray)
    {

        if ($table != 'tx_ncgovpermits_permits') {
            return false;
        }

        if (!(GeneralUtility::_GP('_saveandclosedok_x') || GeneralUtility::_GP('_savedok_x'))) {
            return false;
        }

        if (!count($fieldArray)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $status
     * @param string $table
     * @param string|integer $id
     * @param array &$fieldArray
     * @param DataHandler $dataHandler
     */
    public function processDatamap_postProcessFieldArray(
        $status,
        $table,
        $id,
        array &$fieldArray,
        DataHandler $dataHandler
    ) {
        if (!$this->canProcess($table, $fieldArray)) {
            return;
        }

        $this->updateLastModifiedDateFieldIfApplicable($fieldArray);
    }


    /**
     * @param string $status
     * @param string $table
     * @param string|integer $id
     * @param array &$fieldArray
     * @param DataHandler $dataHandler
     */
    public function processDatamap_afterDatabaseOperations(
        $status,
        $table,
        $id,
        array &$fieldArray,
        DataHandler $dataHandler
    ) {
        if (!$this->canProcess($table, $fieldArray)) {
            return;
        }

        $tsconfig = BackendUtility::getModTSconfig(
            $dataHandler->checkValue_currentRecord['pid'],
            'tx_ncgovpermits.'
        );
        $tsconfig = $tsconfig['properties']['properties.'];

        if (!$tsconfig['createPublicationCopyFromPermitEnabled']) {
            return;
        }


        $values = $dataHandler->datamap[$table][$id];
        $id = $this->getInsertedNewUid($id, $dataHandler);

        # Vergunning
        if ($values['type'] == Permit::TYPE_PERMIT) {
            $this->processDatamap_afterDatabaseOperationsForPermit($table, $id, $dataHandler, $tsconfig, $values);
        } # Publication
        else {
            $this->processDatamap_afterDatabaseOperationsForPublication($table, $id, $tsconfig, $values);

        }

    }


    /**
     * @param $table
     * @param $publicationPid
     * @param $permitUid
     * @return bool
     */
    function permitAlreadyHasCopy($table, $publicationPid, $permitUid)
    {

        $result = false;
        $permits = $this->getDatabaseConnection()->exec_SELECTgetRows(
            '*',
            $table,
            'pid = ' . $publicationPid . ' AND related = \'' . $permitUid . '\' AND deleted = 0'
        );
        if (count($permits) > 0) {
            $result = true;
        }
        return $result;
    }


    /**
     * @param $addressUids
     * @param $permitUid
     * @param $permitsTable
     * @param $addressesTable
     * @param $coordinatesTable
     * @param $pro6ppAuthKey
     * @return string
     */
    function addCoordinates($addressUids, $permitUid, $permitsTable, $addressesTable, $coordinatesTable, $pro6ppAuthKey)
    {

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

                $record['pid'] = $address['pid'];
                $record['cruser_id'] = $address['cruser_id'];
                $record['hidden'] = $address['hidden'];
                $record['coordinatex'] = $coordinatex;
                $record['coordinatey'] = $coordinatey;
                $arrCoordinatesUids[] = $this->insertAndReturnUid($coordinatesTable, $record);
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


    /**
     * @param $table
     * @param $fields
     * @param $uids
     * @param $pid
     * @param DataHandler $dataHandler
     * @return string
     */
    function copyRecords($table, $fields, $uids, $pid, DataHandler $dataHandler)
    {

        if (empty($uids)) {
            return '';
        }

        $arrUids = GeneralUtility::trimExplode(',', $uids);
        foreach ($arrUids as &$uid) {
            $this->getInsertedNewUid($uid, $dataHandler);
        }
        $uids = implode(',', $arrUids);

        $records = $this->getDatabaseConnection()->exec_SELECTgetRows(
            'cruser_id, deleted, hidden, ' . $fields,
            $table,
            'uid IN (' . $uids . ') AND deleted = 0'
        );

        $newRecords = array();
        foreach ($records as $record) {
            $record['pid'] = $pid;
            $newRecords[] = $this->insertAndReturnUid($table, $record);
        }

        return implode(',', $newRecords);
    }


    /**
     * @param array &$fieldArray
     */
    protected function updateLastModifiedDateFieldIfApplicable(array &$fieldArray)
    {
        if (!count($fieldArray)) {
            // No changes to the record - in this case, not even tstamp is being updated.
            return;
        }

        $fieldArray['lastmodified'] = $GLOBALS['EXEC_TIME']; // == 'tstamp'; @see \TYPO3\CMS\Core\DataHandling\DataHandler::process_datamap()
    }

    /**
     * @param $table
     * @param array &$record
     * @return int
     */
    protected function insertAndReturnUid($table, array &$record)
    {

        $record['tstamp'] = $record['crdate'] = $GLOBALS['EXEC_TIME'];
        $this->getDatabaseConnection()->exec_INSERTquery(
            $table,
            $record
        );
        return $this->getDatabaseConnection()->sql_insert_id();
    }

    /**
     * @param $uid
     * @param DataHandler $dataHandler
     * @return int
     */
    protected function getInsertedNewUid($uid, DataHandler $dataHandler)
    {

        if (is_numeric($uid)) {
            return (int)$uid;
        }
        return (int)$dataHandler->substNEWwithIDs[$uid];
    }


    /**
     * @param string $table
     * @param integer $id
     * @param array &$values
     * @param array &$tsconfig
     */
    protected function autoAddCoordinates($table, $id, array &$values, array &$tsconfig)
    {

        if (!empty($values['coordinates'])) {
            // Nothing to add.
            return;
        }

        if (empty($values['objectaddresses'])) {
            // Missing lookup data.
            return;
        }

        if (!$tsconfig['enableCoordinateAutocomplete']) {
            // Feature disabled.
            return;
        }

        $values['coordinates'] = $this->addCoordinates(
            $values['objectaddresses'],
            $id,
            $table,
            'tx_ncgovpermits_addresses',
            'tx_ncgovpermits_coordinates',
            $tsconfig['pro6ppAuthKey']
        );

    }


    /**
     * @param $table
     * @param $id
     * @param DataHandler $dataHandler
     * @param array &$tsconfig
     * @param array &$values
     */
    protected function processDatamap_afterDatabaseOperationsForPermit(
        $table,
        $id,
        DataHandler $dataHandler,
        array &$tsconfig,
        array &$values
    ) {

        $this->autoAddCoordinates($table, $id, $values, $tsconfig);

        # Create publication copy
        $pid = $tsconfig['publicationsPid'];
        $permitAlreadyHasCopy = $this->permitAlreadyHasCopy($table, $pid, $id);
        if ($permitAlreadyHasCopy) {
            return;
        }

        // Copy permit into a publication.
        $values['objectaddresses'] = $this->copyRecords(
            'tx_ncgovpermits_addresses',
            'zipcode, addressnumber, addressnumberadditional, address, city, municipality, province',
            $values['objectaddresses'],
            $pid,
            $dataHandler
        );

        $values['lots'] = $this->copyRecords(
            'tx_ncgovpermits_lots',
            'cadastremunicipality, section, number',
            $values['lots'],
            $pid,
            $dataHandler
        );

        $values['coordinates'] = $this->copyRecords(
            'tx_ncgovpermits_coordinates',
            'coordinatex, coordinatey, coordinatez',
            $values['coordinates'],
            $pid,
            $dataHandler
        );

        $defvalue = '&defVals[' . $table . '][type]=1' .
            '&defVals[' . $table . '][publishdate]=' . $values['publishdate'] .
            '&defVals[' . $table . '][lastmodified]=' . $values['lastmodified'] .
            '&defVals[' . $table . '][casereference_pub]=' . $values['casereference'] .
            '&defVals[' . $table . '][objectaddresses]=' . $values['objectaddresses'] .
            '&defVals[' . $table . '][lots]=' . $values['lots'] .
            '&defVals[' . $table . '][coordinates]=' . $values['coordinates'] .
            '&defVals[' . $table . '][related]=' . $id;

        if ($values['termtype'] == 'bezwaar') {
            $defvalue .= '&defVals[' . $table . '][termtype_start]=' . $values['termtype_start'] .
                '&defVals[' . $table . '][termtype_end]=' . $values['termtype_end'];
        }

        if (GeneralUtility::_GP('_saveandclosedok_x')) {
            $retUrl = 'returnUrl=' . rawurlencode(
                    'db_list.php?id=' . $tsconfig['permitsPid'] . '&table=&edit[' . $table . '][' . $id . ']=edit'
                );
        } else {
            $retUrl = 'returnUrl=' . rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI'));
        }

        $params = '&edit[' . $table . '][' . $pid . ']=new' . $defvalue;
        $url = $GLOBALS['BACK_PATH'] . 'alt_doc.php?' . $retUrl . $params;

        header('Location: ' . $url);
        die;

    }


    /**
     * @param $table
     * @param $id
     * @param array &$tsconfig
     * @param array &$values
     */
    protected function processDatamap_afterDatabaseOperationsForPublication(
        $table,
        $id,
        array &$tsconfig,
        array &$values
    ) {

        # Coordinates
        $extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ncgov_permits']);
        if (array_key_exists(
                'enableCoordinatesForPublications',
                $extensionConfiguration
            ) && $extensionConfiguration['enableCoordinatesForPublications']
        ) {
            $this->autoAddCoordinates($table, $id, $values, $tsconfig);
        }

        // update reference
        $pid = $tsconfig['permitsPid'];
        if (!empty($values['casereference'])) {
            $this->getDatabaseConnection()->exec_UPDATEquery(
                $table,
                'pid = ' . $pid . ' AND type = 0 AND casereference = ' . $values['casereference_pub'],
                array('tstamp' => time())
            );
        }

        // update related back
        if (isset($values['related'])) {
            $related = $values['related'];
            if (!is_numeric($values['related'])) {
                $related = GeneralUtility::trimExplode('_', $values['related']);
                $related = array_pop($related);
            }
            if ($related) {
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