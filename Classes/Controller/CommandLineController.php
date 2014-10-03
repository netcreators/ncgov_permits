<?php
if (!defined('TYPO3_cliMode')) {
 	die ('Access denied.');
}

require_once(PATH_tslib.'class.tslib_fe.php');
require_once(PATH_t3lib.'class.t3lib_userauth.php');
require_once(PATH_tslib.'class.tslib_feuserauth.php');
require_once(PATH_t3lib.'class.t3lib_cs.php');
require_once(PATH_tslib.'class.tslib_content.php');
require_once(PATH_t3lib.'class.t3lib_tstemplate.php');
require_once(PATH_t3lib.'class.t3lib_page.php');

//debug
//require_once(PATH_site.'typo3conf/realurl_conf.php');

require_once(PATH_t3lib.'class.t3lib_cli.php');
require_once(t3lib_extMgm::extPath('ncgov_permits') . 'class.bekendmaking.php');

class tx_ncgovpermits_cli extends t3lib_cli {
	var $prefix;
	var $xml_template;
	var $errors;
	var $sentPublications;

	//Defaults
	var $creator = '';
	var $producttypeScheme = 'overheidbm:BekendmakingtypeGemeente';

	function tx_ncgovpermits_cli() {
		parent::t3lib_cli();

		$this->cli_help['name'] = '';
		$this->cli_help['synopsis'] = '###OPTIONS###';
		$this->cli_help['description'] = "";
		$this->cli_help['examples'] = "";
		$this->cli_help['author'] = "Erwin de Jong, (c) 2010";

		$this->prefix = 'tx_ncgovpermits_';
		$this->xml_template = 'EXT:ncgov_permits/templates/publication_xml.html';

		$this->errors = 0;
		$this->sentPublications = 0;
	}

	/**
	 * Main function
	 * @param array $argv
	 * @return void
	 */
	function cli_main($argv) {
		$conf = array();
		foreach ($argv as $arg) {
			if (strpos($arg, '=') !== false) {
				list($key, $value) = explode('=', $arg, 2);
				$conf[$key] = $value;
			}
			elseif ($arg == help) {
				$this->displayHelp();
			}
		}

		if (empty($conf['user'])) {
			$this->displayHelp('Missing username!');
		}
		elseif (empty($conf['pass'])) {
			$this->displayHelp('Missing password!');
		}
		elseif (empty($conf['base'])) {
			$this->displayHelp('Missing baseURL!');
		}
		elseif (empty($conf['pid'])) {
			$this->displayHelp('Missing PID!');
		}
		elseif (empty($conf['page'])) {
			$this->displayHelp('Missing page UID!');
		}
		elseif (empty($conf['casepage'])) {
			$this->displayHelp('Missing case page UID!');
		}
		elseif (empty($conf['creator'])) {
			$this->displayHelp('Missing creator!');
		}
		else {
			// Get publications that have not yet been sent or that have been edited after they were sent
			$publications = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'*',
				'tx_ncgovpermits_permits',
				'tx_ncgovpermits_permits.pid = ' . $conf['pid'] . ' AND hidden = 0 AND deleted = 0 AND type = 1 AND tstamp > lastpublished AND publishdate <= UNIX_TIMESTAMP(NOW())'
			);

			if (!empty($publications)) {
				$objBekendmaking = new Bekendmaking($this->xml_template, $conf['user'], $conf['pass'], $conf['test']);

				foreach ($publications as $publication)
				{
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
						$producttype = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
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
					$addresses = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
						'zipcode, addressnumber, addressnumberadditional, municipality, province',
						'tx_ncgovpermits_addresses',
						'uid IN (' . $publicationData['objectaddresses'] . ') AND hidden = 0 AND deleted = 0'
					);

					$publicationData['location_scheme'] = $publicationData['location'] = '';
					if (!empty($addresses)) {
						foreach ($addresses as $address) {
							// Get municipality
/*							if ($address['municipality']) {
								$municipality = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
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
								$province = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
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
						$firstAddress = $publicationData['addresses'][0];
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
					$parcels = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
						'cadastremunicipality, section, number',
						'tx_ncgovpermits_lots',
						'uid IN (' . $publication['lots'] . ') AND hidden = 0 AND deleted = 0'
					);
					if (!empty($parcels)) {
						foreach ($parcels as $parcel) {
							// Get cadastral municipality
/*							if ($parcel['cadastral_municipality']) {
								$cadastral_municipality = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
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
					$coordinates = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
						'coordinatex, coordinatey',
						'tx_ncgovpermits_coordinates',
						'uid IN (' . $publication['coordinates'] . ') AND hidden = 0 AND deleted  = 0'
					);
					if (!empty($coordinates)) {
						foreach ($coordinates as $coordinate) {
							$publicationData['coordinates'][] = $coordinate;
						}
					}

					// Set remaining fields
					if ($publicationData['lastpublished'] == 0) {
						$publicationData['transactiontype'] = 'C';
					}
					elseif ($publication['deleted'] == 1) {
						$publicationData['transactiontype'] = 'D';
					}
					else {
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

					$publicationData['modified'] = date('Y-m-d' ,$publication['tstamp']);

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
						$case = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
							'uid',
							'tx_ncgovpermits_permits',
							'hidden = 0 AND deleted = 0 AND type = 0 AND casereference = ' . $publicationData['casereference_pub']
						);
						if (!empty($case)) {
							$case = $case[0];
							$publicationData['caseurl'] = urlencode($this->buildURL($conf['base'], $conf['casepage'], $case['uid']));
						} else {
							$publicationData['caseurl'] = '';
						}
					}

					// Set data in object
					$objBekendmaking->setData($publicationData);

					$validData = $objBekendmaking->validateData();

					$pushError = '';
					if ($validData) {
						$pushError = $objBekendmaking->process();
					}
					else {
						$this->displayError('Publicationdata is incomplete or insufficient (uid: ' . $publication['uid'] . ')');
					}

					if (empty($pushError)) {
						$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
							'tx_ncgovpermits_permits',
							'uid = ' . $publication['uid'],
							array(
								'lastpublished' => time()
							)
						);
						$this->sentPublications++;
					}
					else {
						$this->displayError('Server reply for uid ' . $publication['uid'] . ': ' . $pushError);
					}

					$objBekendmaking->clear();
				}
			}
			else {
				echo("No publications to send...\n");
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
	function buildURL($baseURL, $pageUID, $contentUID) {
		$additionalParams = sprintf('&tx_ncgovpermits_controller[id]=%d', (int)$contentUID);
		$url = sprintf('http://%s/index.php?id=%d%s&cHash=%s', $baseURL, (int)$pageUID, $additionalParams, t3lib_div::generateCHash($additionalParams));
		return $url;
	}

	/**
	 * Shows error message
	 * @param string $message
	 * @return void
	 */
	function displayError($message) {
		echo("! " . $message . "\n");
		$this->errors++;
	}

	/**
	 * Shows statistics
	 * @return void
	 */
	function displayStats() {
		echo("\nPublications sent: " . $this->sentPublications . "\n");
		echo("\nErrors: " . $this->errors . "\n");
		echo("\nDone!\n");
	}

	/**
	 * Shows help message
	 * @param string $message additional message
	 * @return void
	 */
	function displayHelp($message = false) {
		if ($message) {
			echo($message . "\n\n");
		}

		echo("Usage: cli_dispatch.phpsh ncgov_permits base=[www.example.com] pid=[Pid] page=[singleUid] casepage=[case singleUid] user=[Username] pass=[Password] creator=[Creator]\n");
		exit;
	}
}

$startstopcache = t3lib_div::makeInstance('tx_ncgovpermits_cli');
$startstopcache->cli_main($_SERVER['argv']);
?>