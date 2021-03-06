<?php

namespace Netcreators\NcgovPermits\Controller;

use TYPO3\CMS\Core\Utility\GeneralUtility;

if (!defined('TYPO3_cliMode')) {
    die ('Access denied.');
}

class CommandLineController extends \TYPO3\CMS\Core\Controller\CommandLineController
{
    // protected $prefix;
    protected $xmlTemplatePath;
    protected $invalidDataErrors;
    protected $remotePushErrors;
    protected $sentPublications;
    protected $batchLimit = 1000;

    //Defaults
    protected $producttypeScheme = 'overheidbm:BekendmakingtypeGemeente';

    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    function __construct()
    {
        parent::__construct();

        $this->cli_help['name'] = '';
        $this->cli_help['synopsis'] = '###OPTIONS###';
        $this->cli_help['description'] = "";
        $this->cli_help['examples'] = "";
        $this->cli_help['author'] = "Erwin de Jong, (c) 2010";

        // $this->prefix = 'tx_ncgovpermits_';
        $this->xmlTemplatePath = 'EXT:ncgov_permits/Resources/Private/Templates/Publication.html';

        $this->invalidDataErrors = 0;
        $this->remotePushErrors = 0;
        $this->sentPublications = array(
            'C' => 0,
            'U' => 0,
            'D' => 0
        );
    }

    /**
     * Main function
     * @param array $argv
     * @return void
     */
    function cli_main($argv)
    {
        $conf = array();
        foreach ($argv as $arg) {
            if (strpos($arg, '=') !== false) {
                list($key, $value) = explode('=', $arg, 2);
                $conf[$key] = $value;
            } elseif ($arg == 'help') {
                $this->displayHelp();
            }
        }

        if (empty($conf['user'])) {
            $this->displayHelp('Missing username!');
        } elseif (empty($conf['pass'])) {
            $this->displayHelp('Missing password!');
        } elseif (empty($conf['base'])) {
            $this->displayHelp('Missing baseURL!');
        } elseif (empty($conf['pid'])) {
            $this->displayHelp('Missing PID!');
        } elseif (empty($conf['page'])) {
            $this->displayHelp('Missing page UID!');
        } elseif (empty($conf['casepage'])) {
            $this->displayHelp('Missing case page UID!');
        } elseif (empty($conf['creator'])) {
            $this->displayHelp('Missing creator!');
        } else {
            // Get publications that have not yet been sent or that have been edited after they were sent
            $select = array(
                'fields' => '*',
                'table' => 'tx_ncgovpermits_permits',
                'where' => 'tx_ncgovpermits_permits.pid = ' . $conf['pid'] . ' AND hidden = 0 AND type = 1 '

                    // Note:
                    //		'lastmodified' is updated by ncgov_permits_sync and, for manual edits,
                    //		in @see \Netcreators\NcgovPermits\Service\CoreDataHandler\ProcessDatamapHook::processDatamap_postProcessFieldArray().
                    //		However, we cannot trust that deleted records will have their lastmodified date updated.
                    //		Therefore, for deleted records, we need to rely on tstamp, which represents the date and time of deletion.
                    . ' AND (
						(deleted = 0 AND lastmodified > lastpublished)
						OR
						(deleted = 1 AND tstamp > lastpublished)
					)
					AND publishdate <= UNIX_TIMESTAMP(NOW())'
            );

            $totalPublicationCount = $this->getDatabaseConnection()->exec_SELECTcountRows(
                '*',
                $select['table'],
                $select['where']
            );
            echo "STAT: Total count of publications to be processed: " . $totalPublicationCount . "\n";

            $publications = $this->getDatabaseConnection()->exec_SELECTgetRows(
                $select['fields'],
                $select['table'],
                $select['where'],
                '', '',
                $this->batchLimit
            );
            echo "STAT: Count of publications to be processsed in this run (max " . $this->batchLimit . "): "
                . count($publications) . "\n\n";
            flush();

            if (!empty($publications)) {
                $publicationPushService = new \Netcreators\NcgovPermits\Service\Publication\PublicationPushService($this->xmlTemplatePath, $conf['user'], $conf['pass'], $conf['test']);

                foreach ($publications as $publication) {
                    $publicationData = array();

                    foreach ($publication as $publicationField => $publicationValue) {
                        //if (strpos($publicationField, $this->prefix) !== false) {
                        //$publicationData[substr($publicationField, strlen($this->prefix))] = $publicationValue;
                        $publicationData[$publicationField] = $publicationValue;
                        //}
                    }
                    //$publicationData['producttype_scheme'] = '';

                    // Get producttype
                    /*if ($publicationData['producttype']) {
                        $producttype = $this->getDatabaseConnection()->exec_SELECTgetRows(
                            'scheme, name',
                            'tx_windbekendmakingen_producttypes',
                            'uid = ' . $publicationData['producttype']
                        );
                        $producttype = $producttype[0];
                        $publicationData['producttype_scheme'] = $producttype['scheme'];
                        $publicationData['producttype'] = $producttype['name'];
                    }
                    else {
                        $publicationData['producttype'] = '';
                    }*/

                    $publicationData['producttype_scheme'] = $this->producttypeScheme;

                    // Get addresses
                    $publicationData['addresses'] = array();
                    $addresses = $this->getDatabaseConnection()->exec_SELECTgetRows(
                        'zipcode, addressnumber, addressnumberadditional, municipality, province',
                        'tx_ncgovpermits_addresses',
                        'uid IN (' . $publicationData['objectaddresses'] . ') AND hidden = 0 AND deleted = 0'
                    );

                    $publicationData['location_scheme'] = $publicationData['location'] = '';
                    if (!empty($addresses)) {
                        foreach ($addresses as $address) {
                            // Get municipality
                            /*							if ($address['municipality']) {
                                                            $municipality = $this->getDatabaseConnection()->exec_SELECTgetRows(
                                                                'name',
                                                                'tx_windbekendmakingen_municipalities',
                                                                'uid = ' . $address['municipality']
                                                            );
                                                            $municipality = $municipality[0];
                                                            $address['municipality'] = $municipality['name'];
                                                        }
                                                        else {
                                                            $address['municipality'] = '';
                                                        }*/

                            // Get province
                            /*							if ($address['province']) {
                                                            $province = $this->getDatabaseConnection()->exec_SELECTgetRows(
                                                                'name',
                                                                'tx_windbekendmakingen_provinces',
                                                                'uid = ' . $address['province']
                                                            );
                                                            $province = $province[0];
                                                            $address['province'] = $province['name'];
                                                        }
                                                        else {
                                                            $address['province'] = '';
                                                        }*/
                            // always add the municipality
                            $address['municipality'] = $conf['creator'];
                            $address['zipcode'] = str_replace(' ', '', strtoupper($address['zipcode']));

                            $publicationData['addresses'][] = $address;
                        }
                        //$firstAddress = $publicationData['addresses'][0];
                        /*
                             * ncfrans: fix nav commentaar overheid.nl
                            if (!empty($firstAddress['zipcode'])) {
                                $location = $firstAddress['zipcode'];
                                $location .= $firstAddress['addressnumber'];
                                $location .= $firstAddress['addressnumberadditional'];
                                $publicationData['location_scheme'] = 'overheid:PostcodeHuisnummer';
                                $publicationData['location'] = $location;
                            } elseif (!empty($firstAddress['municipality'])) {
                                $publicationData['location_scheme'] = 'overheid:Gemeente';
                                $publicationData['location'] = $firstAddress['municipality'];
                            } elseif (!empty($firstAddress['province'])) {
                                $publicationData['location_scheme'] = 'overheid:Provincie';
                                $publicationData['location'] = $firstAddress['province'];
                            }
                             */
                        $publicationData['location_scheme'] = 'overheid:Gemeente';
                        $publicationData['location'] = $conf['creator'];
                    } else {
                        $publicationData['location_scheme'] = 'overheid:Gemeente';
                        $publicationData['location'] = $conf['creator'];
                        $publicationData['addresses'][] = array('municipality' => $conf['creator']);
                    }

                    // Get parcels
                    $publicationData['parcels'] = array();
                    $parcels = array();
                    if (trim($publication['lots'])) {
                        $parcels = $this->getDatabaseConnection()->exec_SELECTgetRows(
                            'cadastremunicipality, section, number',
                            'tx_ncgovpermits_lots',
                            'uid IN (' . $publication['lots'] . ') AND hidden = 0 AND deleted = 0'
                        );
                    }
                    if (!empty($parcels)) {
                        foreach ($parcels as $parcel) {
                            // Get cadastral municipality
                            /*							if ($parcel['cadastral_municipality']) {
                                                            $cadastral_municipality = $this->getDatabaseConnection()->exec_SELECTgetRows(
                                                                'name',
                                                                'tx_windbekendmakingen_cadastral_municipalities',
                                                                'uid = ' . $parcel['cadastral_municipality']
                                                            );
                                                            $cadastral_municipality = $cadastral_municipality[0];
                                                            $parcel['cadastral_municipality'] = $cadastral_municipality['name'];
                                                        }
                                                        else {
                                                            $parcel['cadastral_municipality'] = '';
                                                        }*/

                            $publicationData['parcels'][] = $parcel;
                        }
                    }

                    // Get coordinates
                    $publicationData['coordinates'] = array();
                    $coordinates = array();
                    if (trim($publication['coordinates'])) {
                        $coordinates = $this->getDatabaseConnection()->exec_SELECTgetRows(
                            'coordinatex, coordinatey',
                            'tx_ncgovpermits_coordinates',
                            'uid IN (' . $publication['coordinates'] . ') AND hidden = 0 AND deleted  = 0'
                        );
                    }
                    if (!empty($coordinates)) {
                        foreach ($coordinates as $coordinate) {
                            $publicationData['coordinates'][] = $coordinate;
                        }
                    }

                    // Set remaining fields
                    if ($publication['deleted']) {
                        $publicationData['transactiontype'] = 'D';
                    } elseif ($publicationData['lastpublished'] == 0) {
                        $publicationData['transactiontype'] = 'C';
                    } else {
                        $publicationData['transactiontype'] = 'U';
                    }

                    #if (empty($publicationData['transactionid'])) {
                    $publicationData['transactionid'] = $publication['uid'] . time();
                    #}

                    #if (empty($publicationData['sequence'])) {
                    $publicationData['sequence'] = '1';
                    #}

                    $publicationData['creationdate'] = date('Y-m-d', $publication['crdate']);

                    $publicationData['identifier'] = $this->buildURL($conf['base'], $conf['page'], $publication['uid']);

                    $publicationData['title'] = $publication['title'];

                    $publicationData['creator'] = $conf['creator'];

                    $publicationData['modified'] = $publication['deleted']
                        ? date('Y-m-d', $publication['tstamp'])
                        : date('Y-m-d', $publication['lastmodified']);

                    if ($publicationData['validity_start'] > 0) {
                        $publicationData['validity_start'] = date('Y-m-d', $publicationData['validity_start']);
                    }

                    if ($publicationData['validity_end'] > 0) {
                        $publicationData['validity_end'] = date('Y-m-d', $publicationData['validity_end']);
                    }

                    if ($publicationData['termtype_start'] > 0) {
                        $publicationData['termtype_start'] = date('Y-m-d', $publicationData['termtype_start']);
                    }
                    if ($publicationData['termtype_end'] > 0) {
                        $publicationData['termtype_end'] = date('Y-m-d', $publicationData['termtype_end']);
                    }

                    $publicationData['content'] = !empty($publication['publicationbody']) ? $publication['publicationbody'] : $publication['description'];

                    #if (empty($publicationData['description'])) {
                    $publicationData['description'] = strip_tags($publicationData['description']);
                    #}

                    if (!empty($publicationData['casereference_pub'])) {
                        $case = $this->getDatabaseConnection()->exec_SELECTgetRows(
                            'uid',
                            'tx_ncgovpermits_permits',
                            'hidden = 0 AND deleted = 0 AND type = 0 AND casereference = ' . $publicationData['casereference_pub']
                        );
                        if (!empty($case)) {
                            $case = $case[0];
                            $publicationData['caseurl'] = urlencode(
                                $this->buildURL($conf['base'], $conf['casepage'], $case['uid'])
                            );
                        } else {
                            $publicationData['caseurl'] = '';
                        }
                    }

                    // Set data in object
                    $publicationPushService->setData($publicationData);

                    list(
                        $validData,
                        $requiredFilled,
                        $locationFilled,
                        $locationInvalid
                        ) = $publicationPushService->validateData();

                    $pushError = '';
                    if ($validData) {
                        $this->displayInfo(
                            'Sending transaction \'' . $publicationData['transactiontype'] . '\''
                            . ' for Publication uid: ' . $publication['uid']
                            . ' / identifier: ' . $publication['identifier']
                            . ' / remote identifier: ' . $publicationData['identifier']
                        );
                        $pushError = $publicationPushService->process();
                    } else {
                        $this->invalidDataErrors++;
                        $this->displayError(
                            'Publicationdata is incomplete or insufficient (uid: ' . $publication['uid'] . ')'
                        );
                        if (!$requiredFilled) {
                            $this->displayError(
                                'Not all required fields are filled for uid: ' . $publication['uid']
                            );
                        }
                        if (!$locationFilled) {
                            $this->displayError(
                                'No addresses, parcels or coordinates are given for uid: ' . $publication['uid']
                            );
                        }
                        if ($locationInvalid) {
                            $this->displayError(
                                'The location data is invalid for uid: ' . $publication['uid']
                            );
                        }
                    }

                    if (empty($pushError)) {
                        $this->getDatabaseConnection()->exec_UPDATEquery(
                            'tx_ncgovpermits_permits',
                            'uid = ' . $publication['uid'],
                            array(
                                'lastpublished' => time()
                            )
                        );
                        $this->sentPublications[$publicationData['transactiontype']]++;
                    } else {
                        $this->remotePushErrors++;
                        $this->displayError('Server reply for uid ' . $publication['uid'] . ': ' . $pushError);
                    }

                    $publicationPushService->clear();
                }
            } else {
                echo("No publications to send...\n");
                flush();
            }

            $this->displayStats();
        }
    }

    /**
     * Builds url to publication / permit
     * @param string $baseURL
     * @param integer $pageUID
     * @param integer $contentUID
     * @return string
     */
    function buildURL($baseURL, $pageUID, $contentUID)
    {
        /* @var $cacheHashCalculator \TYPO3\CMS\Frontend\Page\CacheHashCalculator */
        $cacheHashCalculator = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Page\\CacheHashCalculator');


        $queryString = sprintf('id=%d&tx_ncgovpermits_controller[id]=%d', (int)$pageUID, (int)$contentUID);
        $url = sprintf(
            'http://%s/index.php?%s&cHash=%s',
            $baseURL,
            $queryString,
            $cacheHashCalculator->generateForParameters($queryString)
        );
        return $url;
    }

    /**
     * Shows error message
     * @param string $message
     * @return void
     */
    function displayError($message)
    {
        echo("! " . $message . "\n");
        flush();
    }

    /**
     * Shows info message
     * @param string $message
     * @return void
     */
    function displayInfo($message)
    {
        echo("  " . $message . "\n");
        flush();
    }

    /**
     * Shows statistics
     * @return void
     */
    function displayStats()
    {
        echo("\nSTAT: 'Create' Publications sent: " . $this->sentPublications['C'] . "\n");
        echo("STAT: 'Update' Publications sent: " . $this->sentPublications['U'] . "\n");
        echo("STAT: 'Delete' Publications sent: " . $this->sentPublications['D'] . "\n");
        echo("STAT: Total Publications sent: " . array_sum($this->sentPublications) . "\n");

        echo("\nSTAT: 'Invalid Data' Errors: " . $this->invalidDataErrors . "\n");
        echo("STAT: 'Remote Push' Errors: " . $this->remotePushErrors . "\n");
        echo("STAT: Total Errors: " . ($this->invalidDataErrors + $this->remotePushErrors) . "\n");

        echo("\nDone!\n");
        flush();
    }

    /**
     * Shows help message
     * @param string $message additional message
     * @return void
     */
    function displayHelp($message = '')
    {
        if ($message) {
            echo($message . "\n\n");
            flush();
        }

        echo("Usage: cli_dispatch.phpsh ncgov_permits base=[www.example.com] pid=[Pid] page=[singleUid] casepage=[case singleUid] user=[Username] pass=[Password] creator=[Creator] [test=1 for pushing to the acc server]\n");
        exit;
    }
}

$startstopcache = GeneralUtility::makeInstance('Netcreators\\NcgovPermits\\Controller\\CommandLineController');
$startstopcache->cli_main($_SERVER['argv']);

