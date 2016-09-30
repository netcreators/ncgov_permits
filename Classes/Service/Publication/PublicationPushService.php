<?php

namespace Netcreators\NcgovPermits\Service\Publication;

use TYPO3\CMS\Core\Html\HtmlParser;

class PublicationPushService {
	var $xml_template;
	var $user;
	var $pass;
	var $test;

	var $testuser;
	var $testpass;

	var $data;
	var $xml;

	var $url;
	var $testurl;

	/**
	 * @var array
	 */
	var $substMarkerCache = array();

	/**
	 * Constructor
	 * @param string $xml_template
	 * @param string $user
	 * @param string $pass
	 * @param boolean $test
	 */
	function __construct($xml_template, $user, $pass, $test = 0) {
		$this->xml_template = $xml_template;
		$this->user = $user;
		$this->pass = $pass;
		$this->test = $test;


		$this->data = array();
		$this->xml = '';

		$this->url = 'https://zdpushservice.overheid.nl/pushxml/pushxml-bm';

		// Find Publications pushed to the ACC environment here:
		//     http://preprod.zoekdienst.asp4all.nl/Bekendmakingen/bmZoeken.aspx?searchtype=Advanced
		$this->testuser = $user;
		$this->testpass = $pass;
		$this->testurl = 'https://zdpushservice-preprod.overheid.nl/BMPushServices/BMPushService.asmx/PushBMContent';
	}

	/**
	 * Sets the data
	 * @param array $data
	 * @return void
	 */
	function setData($data) {
		$this->data = $data;
	}

	/**
	 * Validates data and returns if it was ok
	 * @return boolean
	 */
	function validateData() {
		$requiredFields = array(
			'transactiontype',
			'transactionid',
			'sequence',
			'creationdate',
			'identifier',
			'title',
			'language',
			'creator',
			'modified',
			'location_scheme',
			'location',
			'description',
			'producttype_scheme',
			'producttype'
		);

		$requiredFilled = true;
		foreach ($requiredFields as $field) {
			if (empty($this->data[$field])) {
				$requiredFilled = false;
			}
		}

		// At least 1 location indicator is required, from either of the 3 possible options.
		$locationFilled = !empty($this->data['addresses']) || !empty($this->data['parcels']) || !empty($this->data['coordinates']);

		$locationInvalid = !$this->validateAddresses() || !$this->validateParcels() || !$this->validateCoordinates();

		return array(
			$requiredFilled && $locationFilled && !$locationInvalid,
			$requiredFilled,
			$locationFilled,
			$locationInvalid
		);
	}

	/**
	 * Validates currently active addresses
	 * @return boolean
	 */
	function validateAddresses() {
		$return = true;
		foreach ($this->data['addresses'] as $i => $address) {
			# Fix superfluous information, as it's actually not allowed
			if (!empty($address['zipcode'])) {
				$address['zipcode'] = str_replace(' ', '', $address['zipcode']);
				$address['municipality'] = '';
				$address['province'] = '';
			}
			elseif (!empty($address['municipality'])) {
				$address['province'] = '';
			}
			$this->data['addresses'][$i] = $address;
			# Now validate
			$addressFilled =	!empty($address['zipcode']) ||
								!empty($address['municipality']) ||
								!empty($address['province']);
			if (!$addressFilled) $return = false;
		}
		return $return;
	}

	/**
	 * Validates parcels
	 * @return boolean
	 */
	function validateParcels() {
		$return = true;
		foreach ($this->data['parcels'] as $parcel) {
			$parcelFilled = !empty($parcel['cadastremunicipality']) &&
							!empty($parcel['section']) &&
							!empty($parcel['number']);
			$parcelInvalid =	(!empty($parcel['cadastremunicipality']) ||
								!empty($parcel['section']) ||
								!empty($parcel['number'])) &&
								!$parcelFilled;
			if ($parcelInvalid) $return = false;
		}
		return $return;
	}

	/**
	 * Validates currently active coordinates
	 * @return boolean
	 */
	function validateCoordinates() {
		$return = true;
		foreach ($this->data['coordinates'] as $coordinates) {
			$coordinatesFilled = !empty($coordinates['coordinatex']) &&
								!empty($coordinates['coordinatey']);
			$coordinatesInvalid =	(!empty($coordinates['coordinatex']) ||
									!empty($coordinates['coordinatey'])) &&
									!$coordinatesFilled;
			if ($coordinatesInvalid) $return = false;
		}
		return $return;
	}

	/**
	 * Processes the given Publication and pushes it to government
	 * @return string error
	 */
	function process() {
		$this->buildXML();
		$response = $this->pushXML();

		$error = '';
		if (strpos($response, '<status>0001: NOK</status>') !== false) {
			$error = substr($response, strpos($response, '<message>')+9);
			$error = substr($error, 0, strpos($error, '</message>'));
		}
		elseif (strpos($response, '<status>0000: OK</status>') === false) {
			$error = $response;
		}

		return $error;
	}

	/**
	 * Creates xml for Publication
	 */
	function buildXML() {

		$absFile = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($this->xml_template);
		if(is_file($absFile)) {
			$templateCode = file_get_contents($absFile);
		} else {
			die('FATAL: could not read template: ' . $absFile);
		}
		//$templateCode = $cObj->fileResource($this->xml_template);
		$localTemplateCode = HtmlParser::getSubpart($templateCode, '###XML###');

		$localMarkerArray = array(
			'###TRANSACTIETYPE###' => $this->data['transactiontype'],
			'###TRANSACTIEID###' => $this->data['transactionid'],
			'###VOLGNUMMER###' => $this->data['sequence'],
			'###AANMAAKDATUM###' => $this->data['creationdate'],
			'###IDENTIFIER###' => $this->data['identifier'],
			'###TITLE###' => $this->data['title'],
			'###LANGUAGE###' => $this->data['language'],
			'###CREATOR###' => $this->data['creator'],
			'###MODIFIED###' => $this->data['modified'],
			'###SPATIAL_SCHEME###' => $this->data['location_scheme'],
			'###SPATIAL###' => $this->data['location'],
			'###DESCRIPTION###' => $this->data['description'],
			'###PRODUCTTYPE_SCHEME###' => $this->data['producttype_scheme'],
			'###PRODUCTTYPE###' => $this->data['producttype'],
			'###CONTENT###' => $this->data['content']
		);

		$localSubpartArray = array(
			'###TEMPORAL###' => '',
			'###ZAAK###' => '',
			'###PUBLICATIEBLOCK###' => '',
			'###TERMIJN###' => '',
			'###REFERENTIENUMMERBLOCK###' => '',
			'###LOCATIEADRES###' => '',
			'###LOCATIEPERCEEL###' => '',
			'###LOCATIECOORDINATEN###' => ''
		);

		$temporalTemplateCode = HtmlParser::getSubpart($localTemplateCode, '###TEMPORAL###');
		if(!empty($this->data['validity_start'])) {
			$temporalMarkerArray = array(
				'###TEMPORAL_START###' => $this->data['validity_start']
			);
		} else {
			$temporalMarkerArray = array(
				'###TEMPORAL_START###' => date('Y-m-d', $this->data['publishdate'])
			);
		}

		$temporalSubpartArray = array(
			'###TEMPORALEND###' => ''
		);
		$validityEnd = $this->data['validity_end'];

		if(empty($validityEnd)) {
			$validityEnd = $this->data['publishenddate'];
            if (empty($validityEnd)){
                // 5184000 -> 60 dagen (2 maanden ongeveer)
                $validityEnd = time() + 5184000;
            }
		}

		if (!empty($validityEnd)) {
			$temporalendTemplateCode = HtmlParser::getSubpart($temporalTemplateCode, '###TEMPORALEND###');
			$temporalendMarkerArray = array(
				'###TEMPORAL_END###' => date('Y-m-d', $validityEnd)
			);

			$temporalSubpartArray['###TEMPORALEND###'] = $this->substituteMarkerArrayCached($temporalendTemplateCode, $temporalendMarkerArray);
		}

		$localSubpartArray['###TEMPORAL###'] = $this->substituteMarkerArrayCached($temporalTemplateCode, $temporalMarkerArray, $temporalSubpartArray);

		if (!empty($this->data['publication'])) {
			$publicatieTemplateCode = HtmlParser::getSubpart($localTemplateCode, '###PUBLICATIEBLOCK###');
			$publicatieMarkerArray = array(
				'###PUBLICATIE###' => $this->data['publication']
			);
			$localSubpartArray['###PUBLICATIEBLOCK###'] = $this->substituteMarkerArrayCached($publicatieTemplateCode, $publicatieMarkerArray);
		}

		if (!empty($this->data['casereference_pub'])) {
			$zaakTemplateCode = HtmlParser::getSubpart($localTemplateCode, '###ZAAK###');
			$zaakMarkerArray = array(
				'###ZAAKNUMMER###' => $this->data['casereference_pub'],
				'###ZAAKURL###' => $this->data['caseurl']
			);
			$localSubpartArray['###ZAAK###'] = $this->substituteMarkerArrayCached($zaakTemplateCode, $zaakMarkerArray);
		}

		if (!empty($this->data['termtype_start']) || !empty($this->data['termtype_end'])) {
			$termijnTemplateCode = HtmlParser::getSubpart($localTemplateCode, '###TERMIJN###');
			$termijnSubpartArray = array(
				'###STARTTERMIJN###' => '',
				'###EINDTERMIJN###' => ''
			);

			if (!empty($this->data['termtype_start'])) {
				$startTermijnTemplateCode = HtmlParser::getSubpart($termijnTemplateCode, '###STARTTERMIJN###');
				$startTermijnMarkerArray = array(
					'###STARTDATUMTERMIJN###' => $this->data['termtype_start']
				);
				$termijnSubpartArray['###STARTTERMIJN###'] = $this->substituteMarkerArrayCached($startTermijnTemplateCode, $startTermijnMarkerArray);
			}

			if (!empty($this->data['termtype_end'])) {
				$eindTermijnTemplateCode = HtmlParser::getSubpart($termijnTemplateCode, '###EINDTERMIJN###');
				$eindTermijnMarkerArray = array(
					'###EINDDATUMTERMIJN###' => $this->data['termtype_end']
				);
				$termijnSubpartArray['###EINDTERMIJN###'] = $this->substituteMarkerArrayCached($eindTermijnTemplateCode, $eindTermijnMarkerArray);
			}

			$localSubpartArray['###TERMIJN###'] = $this->substituteMarkerArrayCached($termijnTemplateCode, null, $termijnSubpartArray);
		}

		if (!empty($this->data['objectreference'])) {
			$referentienummerTemplateCode = HtmlParser::getSubpart($localTemplateCode, '###REFERENTIENUMMERBLOCK###');
			$referentienummerMarkerArray = array(
				'###REFERENTIENUMMER###' => $this->data['objectreference']
			);
			$localSubpartArray['###REFERENTIENUMMERBLOCK###'] = $this->substituteMarkerArrayCached($referentienummerTemplateCode, $referentienummerMarkerArray);
		}

		foreach ($this->data['addresses'] as $address) {
			$adresTemplateCode = HtmlParser::getSubpart($localTemplateCode, '###LOCATIEADRES###');
			$adresSubpartArray = array(
				'###POSTCODEHUISNUMMER###' => '',
				'###GEMEENTEBLOCK###' => '',
				'###PROVINCIEBLOCK###' => ''
			);

			if (!empty($address['zipcode'])) {
				$postcodehuisnummerTemplateCode = HtmlParser::getSubpart($adresTemplateCode, '###POSTCODEHUISNUMMER###');
				$postcodehuisnummerMarkerArray = array(
					'###POSTCODE###' => $address['zipcode']
				);

				$postcodehuisnummerSubpartArray = array(
					'###HUISNUMMERBLOCK###' => '',
					'###HUISNUMMERTOEVOEGINGBLOCK###' => ''
				);

				if (!empty($address['addressnumber'])) {
					$huisnummerTemplateCode = HtmlParser::getSubpart($postcodehuisnummerTemplateCode, '###HUISNUMMERBLOCK###');
					$huisnummerMarkerArray = array(
						'###HUISNUMMER###' => $address['addressnumber']
					);
					$postcodehuisnummerSubpartArray['###HUISNUMMERBLOCK###'] = $this->substituteMarkerArrayCached($huisnummerTemplateCode, $huisnummerMarkerArray);
				}

				if (!empty($address['addressnumberadditional'])) {
					$huisnummertoevoegingTemplateCode = HtmlParser::getSubpart($postcodehuisnummerTemplateCode, '###HUISNUMMERTOEVOEGINGBLOCK###');
					$huisnummertoevoegingMarkerArray = array(
						'###HUISNUMMERTOEVOEGINGBLOCK###' => $address['addressnumberadditional']
					);
					$postcodehuisnummerSubpartArray['###HUISNUMMERTOEVOEGING###'] = $this->substituteMarkerArrayCached($huisnummertoevoegingTemplateCode, $huisnummertoevoegingMarkerArray);
				}

				$adresSubpartArray['###POSTCODEHUISNUMMER###'] = $this->substituteMarkerArrayCached($postcodehuisnummerTemplateCode, $postcodehuisnummerMarkerArray, $postcodehuisnummerSubpartArray);
			}

			if (!empty($address['municipality'])) {
				$gemeenteTemplateCode = HtmlParser::getSubpart($adresTemplateCode, '###GEMEENTEBLOCK###');
				$gemeenteMarkerArray = array(
					'###GEMEENTE###' => $address['municipality']
				);
				$adresSubpartArray['###GEMEENTEBLOCK###'] = $this->substituteMarkerArrayCached($gemeenteTemplateCode, $gemeenteMarkerArray);
			}

			if (!empty($address['province'])) {
				$provincieTemplateCode = HtmlParser::getSubpart($adresTemplateCode, '###PROVINCIEBLOCK###');
				$provincieMarkerArray = array(
					'###PROVINCIE###' => $address['province']
				);
				$adresSubpartArray['###PROVINCIEBLOCK###'] = $this->substituteMarkerArrayCached($provincieTemplateCode, $provincieMarkerArray);
			}

			$localSubpartArray['###LOCATIEADRES###'] .= $this->substituteMarkerArrayCached($adresTemplateCode, null, $adresSubpartArray);
		}

		foreach ($this->data['parcels'] as $parcel) {
			$perceelTemplateCode = HtmlParser::getSubpart($localTemplateCode, '###LOCATIEPERCEEL###');
			$perceelMarkerArray = array(
				'###KADASTRALEGEMEENTE###' => $parcel['cadastremunicipality'],
				'###SECTIE###' => $parcel['section'],
				'###NUMMER###' => $parcel['number']
			);
			$localSubpartArray['###LOCATIEPERCEEL###'] .= $this->substituteMarkerArrayCached($perceelTemplateCode, $perceelMarkerArray);
		}

		foreach ($this->data['coordinates'] as $coordinates) {
			$coordinatenTemplateCode = HtmlParser::getSubpart($localTemplateCode, '###LOCATIECOORDINATEN###');
			$coordinatenMarkerArray = array(
				'###XWAARDE###' => $coordinates['coordinatex'],
				'###YWAARDE###' => $coordinates['coordinatey']
			);
			$localSubpartArray['###LOCATIECOORDINATEN###'] .= $this->substituteMarkerArrayCached($coordinatenTemplateCode, $coordinatenMarkerArray);
		}

		$this->xml = $this->substituteMarkerArrayCached($localTemplateCode, $localMarkerArray, $localSubpartArray);
	}

	/**
	 * Pushes xml to government
	 * @return string the result
	 */
	function pushXML() {
		$url = $this->test ? $this->testurl : $this->url;
		$username = $this->test ? $this->testuser : $this->user;
		$password = $this->test ? $this->testpass : $this->pass;

		$content = $this->curlRequest($url, $username, $password, $this->xml);
		return $content;
	}

	/**
	 * Pushes data using sockets
	 * @param string $url
	 * @param string $username
	 * @param string $password
	 * @param string $xml
	 * @return string
	 */
	function fputsRequest($url, $username, $password, $xml) {
		$url = parse_url($url);
		$host = $url['host'];
		$path = $url['path'];
		$port = 80;

		$fp = fsockopen($host, $port, $errno, $errstr);
		if (!$fp) {
			return $errstr . ' (' . $errno . ')';
		}

		fputs($fp, "POST " . $path . " HTTP/1.1\r\n");
		fputs($fp, "Authorization: Basic " . base64_encode($username . ':' . $password) . "\r\n");
		fputs($fp, "Host: " . $host . "\r\n");
		fputs($fp, "Content-type: application/xml; charset=UTF-8\r\n");
		fputs($fp, "Content-length: " . strlen($xml) . "\r\n");
		fputs($fp, "Connection: close\r\n\r\n");
		fputs($fp, $this->xml);

		$result = '';
		while(!feof($fp)) {
			$result .= fgets($fp, 128);
		}

		fclose($fp);

		$result = explode("\r\n\r\n", $result, 2);
		$header = isset($result[0]) ? $result[0] : '';
		$content = isset($result[1]) ? $result[1] : '';
		return $content;
	}

	/**
	 * Pushes data using curl
	 * @param string $url
	 * @param string $username
	 * @param string $password
	 * @param string $xml
	 * @return string the response
	 */
	function curlRequest($url, $username, $password, $xml) {
		$resource = curl_init();

		// set url
		curl_setopt($resource, CURLOPT_URL, $url);

		// CURLOPT_HEADER allows us to receive the HTTP header
		curl_setopt($resource, CURLOPT_HEADER, TRUE);

		// CURLOPT_RETURNTRANSFER will return the response
		curl_setopt($resource, CURLOPT_RETURNTRANSFER, 1);

		// send the right header
		curl_setopt($resource, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));

		// username, password
		curl_setopt($resource, CURLOPT_USERPWD, $username . ':' . $password);

		// send it through post
		curl_setopt($resource, CURLOPT_POST, 1);

		// Tell curl that this is the body of the POST
		curl_setopt($resource, CURLOPT_POSTFIELDS, $xml);
		// return the header sent
		curl_setopt($resource, CURLINFO_HEADER_OUT, true);

		// prevent certificate validation
		curl_setopt($resource, CURLOPT_SSL_VERIFYPEER, false);

		// Perform the request
		$response = curl_exec($resource);

		// you can save the debug info in test.xml and uncomment the line above to debug the result
		//$requestHeader = curl_getinfo($resource,CURLINFO_HEADER_OUT);

		// errors?
		$error = curl_error($resource);
		if(!empty($error)) {
			return $error;
		}

		// Close the curl session
		curl_close($resource);

		if($response === FALSE) {
			print('Sending request failed');
		}

		return $response;
	}

	function clear() {
		unset($this->data);
		$this->data = array();
		$this->xml = '';
	}

	/**
	 * COPIED so we're not dependant on TSFE for this work
	 *
	 * Multi substitution function with caching.
	 *
	 * This function should be a one-stop substitution function for working
	 * with HTML-template. It does not substitute by str_replace but by
	 * splitting. This secures that the value inserted does not themselves
	 * contain markers or subparts.
	 *
	 * Note that the "caching" won't cache the content of the substition,
	 * but only the splitting of the template in various parts. So if you
	 * want only one cache-entry per template, make sure you always pass the
	 * exact same set of marker/subpart keys. Else you will be flooding the
	 * users cache table.
	 *
	 * This function takes three kinds of substitutions in one:
	 * $markContentArray is a regular marker-array where the 'keys' are
	 * substituted in $content with their values
	 *
	 * $subpartContentArray works exactly like markContentArray only is whole
	 * subparts substituted and not only a single marker.
	 *
	 * $wrappedSubpartContentArray is an array of arrays with 0/1 keys where
	 * the subparts pointed to by the main key is wrapped with the 0/1 value
	 * alternating.
	 *
	 * @param	string		The content stream, typically HTML template content.
	 * @param	array		Regular marker-array where the 'keys' are substituted in $content with their values
	 * @param	array		Exactly like markContentArray only is whole subparts substituted and not only a single marker.
	 * @param	array		An array of arrays with 0/1 keys where the subparts pointed to by the main key is wrapped with the 0/1 value alternating.
	 * @return	string		The output content stream
	 * @see substituteSubpart(), substituteMarker(), substituteMarkerInObject(), TEMPLATE()
	 */
	public function substituteMarkerArrayCached($content, array $markContentArray = NULL, array $subpartContentArray = NULL, array $wrappedSubpartContentArray = NULL) {

			// If not arrays then set them
		if (is_null($markContentArray))
			$markContentArray = array(); // Plain markers
		if (is_null($subpartContentArray))
			$subpartContentArray = array(); // Subparts being directly substituted
		if (is_null($wrappedSubpartContentArray))
			$wrappedSubpartContentArray = array(); // Subparts being wrapped
			// Finding keys and check hash:
		$sPkeys = array_keys($subpartContentArray);
		$wPkeys = array_keys($wrappedSubpartContentArray);
		$aKeys = array_merge(array_keys($markContentArray), $sPkeys, $wPkeys);
		if (!count($aKeys)) {
			return $content;
		}
		asort($aKeys);
		$storeKey = md5('substituteMarkerArrayCached_storeKey:' . serialize(array(
			$content, $aKeys
		)));
		if ($this->substMarkerCache[$storeKey]) {
			$storeArr = $this->substMarkerCache[$storeKey];
			//$GLOBALS['TT']->setTSlogMessage('Cached', 0);
		} else {
			//$storeArrDat = $GLOBALS['TSFE']->sys_page->getHash($storeKey);
			//if (!isset($storeArrDat)) {
					// Initialize storeArr
				$storeArr = array();

					// Finding subparts and substituting them with the subpart as a marker
				foreach ($sPkeys as $sPK) {
					$content = HtmlParser::substituteSubpart($content, $sPK, $sPK);
				}

					// Finding subparts and wrapping them with markers
				foreach ($wPkeys as $wPK) {
					$content = HtmlParser::substituteSubpart($content, $wPK, array(
						$wPK, $wPK
					));
				}

					// traverse keys and quote them for reg ex.
				foreach ($aKeys as $tK => $tV) {
					$aKeys[$tK] = preg_quote($tV, '/');
				}
				$regex = '/' . implode('|', $aKeys) . '/';
					// Doing regex's
				$storeArr['c'] = preg_split($regex, $content);
				preg_match_all($regex, $content, $keyList);
				$storeArr['k'] = $keyList[0];
					// Setting cache:
				$this->substMarkerCache[$storeKey] = $storeArr;

					// Storing the cached data:
				//$GLOBALS['TSFE']->sys_page->storeHash($storeKey, serialize($storeArr), 'substMarkArrayCached');

				//$GLOBALS['TT']->setTSlogMessage('Parsing', 0);
			/*} else {
					// Unserializing
				$storeArr = unserialize($storeArrDat);
					// Setting cache:
				$this->substMarkerCache[$storeKey] = $storeArr;
				$GLOBALS['TT']->setTSlogMessage('Cached from DB', 0);
			}*/
		}

			// Substitution/Merging:
			// Merging content types together, resetting
		$valueArr = array_merge($markContentArray, $subpartContentArray, $wrappedSubpartContentArray);

		$wSCA_reg = array();
		$content = '';
			// traversing the keyList array and merging the static and dynamic content
		foreach ($storeArr['k'] as $n => $keyN) {
			$content .= $storeArr['c'][$n];
			if (!is_array($valueArr[$keyN])) {
				$content .= $valueArr[$keyN];
			} else {
				$content .= $valueArr[$keyN][(intval($wSCA_reg[$keyN]) % 2)];
				$wSCA_reg[$keyN]++;
			}
		}
		$content .= $storeArr['c'][count($storeArr['k'])];

		return $content;
	}

}
