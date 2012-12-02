<?php

require_once(PATH_tslib.'class.tslib_fe.php');
require_once(PATH_t3lib.'class.t3lib_userauth.php');
require_once(PATH_tslib.'class.tslib_feuserauth.php');
require_once(PATH_t3lib.'class.t3lib_cs.php');
require_once(PATH_tslib.'class.tslib_content.php');
require_once(PATH_t3lib.'class.t3lib_tstemplate.php');
require_once(PATH_t3lib.'class.t3lib_page.php');

class Bekendmaking {
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

	function Bekendmaking($xml_template, $user, $pass, $test = 0) {
		$this->xml_template = $xml_template;
		$this->user = $user;
		$this->pass = $pass;
		$this->test = $test;

		$this->testuser = $user;
		$this->testpass = $pass;

		$this->data = array();
		$this->xml = '';

		$this->url = 'https://zdpushservice.overheid.nl/pushxml/pushxml-bm';
		$this->testurl = 'https://acc1zoekdienst.overheid.nl/BMPushServices/BMPushService.asmx/PushBMContent';
	}

	function setData($data) {
		$this->data = $data;
	}

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

		return $requiredFilled && $locationFilled && !$locationInvalid;
	}

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

	function buildXML() {
		if ($GLOBALS['TSFE'] === null) {
			$TTclassName = t3lib_div::makeInstanceClassName('t3lib_timeTrack');
			$TSFEclassName = t3lib_div::makeInstanceClassName('tslib_fe');
			$id = isset($HTTP_GET_VARS['id'])?$HTTP_GET_VARS['id']:0;

			$GLOBALS['TT'] = new $TTclassName();
			$GLOBALS['TT']->start();

			$GLOBALS['TSFE'] = new $TSFEclassName($TYPO3_CONF_VARS, $id, '0', 1, '',
			'','','');
			$GLOBALS['TSFE']->connectToMySQL();
			$GLOBALS['TSFE']->initFEuser();
			$GLOBALS['TSFE']->fetch_the_id();
			$GLOBALS['TSFE']->getPageAndRootline();
			$GLOBALS['TSFE']->initTemplate();
			$GLOBALS['TSFE']->tmpl->getFileName_backPath = PATH_site;
			$GLOBALS['TSFE']->forceTemplateParsing = 1;
			$GLOBALS['TSFE']->getConfigArray();
		}

		$cObj = t3lib_div::makeInstance('tslib_cObj');
		$cObj->start(array(),'');

		$absFile = t3lib_div::getFileAbsFileName($this->xml_template);
		if(is_file($absFile)) {
			$templateCode = file_get_contents($absFile);
		} else {
			die('FATAL: could not read template: ' . $absFile);
		}
		//$templateCode = $cObj->fileResource($this->xml_template);
		$localTemplateCode = $cObj->getSubpart($templateCode, '###XML###');

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

		$temporalTemplateCode = $cObj->getSubpart($localTemplateCode, '###TEMPORAL###');
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

		if (!empty($this->data['validity_end'])) {
			$temporalendTemplateCode = $cObj->getSubpart($temporalTemplateCode, '###TEMPORALEND###');
			$temporalendMarkerArray = array(
				'###TEMPORAL_END###' => $this->data['validity_end']
			);

			$temporalSubpartArray['###TEMPORALEND###'] = $cObj->substituteMarkerArrayCached($temporalendTemplateCode, $temporalendMarkerArray);
		}

		$localSubpartArray['###TEMPORAL###'] = $cObj->substituteMarkerArrayCached($temporalTemplateCode, $temporalMarkerArray, $temporalSubpartArray);

		if (!empty($this->data['publication'])) {
			$publicatieTemplateCode = $cObj->getSubpart($localTemplateCode, '###PUBLICATIEBLOCK###');
			$publicatieMarkerArray = array(
				'###PUBLICATIE###' => $this->data['publication']
			);
			$localSubpartArray['###PUBLICATIEBLOCK###'] = $cObj->substituteMarkerArrayCached($publicatieTemplateCode, $publicatieMarkerArray);
		}

		if (!empty($this->data['casereference_pub'])) {
			$zaakTemplateCode = $cObj->getSubpart($localTemplateCode, '###ZAAK###');
			$zaakMarkerArray = array(
				'###ZAAKNUMMER###' => $this->data['casereference_pub'],
				'###ZAAKURL###' => $this->data['caseurl']
			);
			$localSubpartArray['###ZAAK###'] = $cObj->substituteMarkerArrayCached($zaakTemplateCode, $zaakMarkerArray);
		}

		if (!empty($this->data['termtype_start']) || !empty($this->data['termtype_end'])) {
			$termijnTemplateCode = $cObj->getSubpart($localTemplateCode, '###TERMIJN###');
			$termijnSubpartArray = array(
				'###STARTTERMIJN###' => '',
				'###EINDTERMIJN###' => ''
			);

			if (!empty($this->data['termtype_start'])) {
				$startTermijnTemplateCode = $cObj->getSubpart($termijnTemplateCode, '###STARTTERMIJN###');
				$startTermijnMarkerArray = array(
					'###STARTDATUMTERMIJN###' => $this->data['termtype_start']
				);
				$termijnSubpartArray['###STARTTERMIJN###'] = $cObj->substituteMarkerArrayCached($startTermijnTemplateCode, $startTermijnMarkerArray);
			}

			if (!empty($this->data['termtype_end'])) {
				$eindTermijnTemplateCode = $cObj->getSubpart($termijnTemplateCode, '###EINDTERMIJN###');
				$eindTermijnMarkerArray = array(
					'###EINDDATUMTERMIJN###' => $this->data['termtype_end']
				);
				$termijnSubpartArray['###EINDTERMIJN###'] = $cObj->substituteMarkerArrayCached($eindTermijnTemplateCode, $eindTermijnMarkerArray);
			}

			$localSubpartArray['###TERMIJN###'] = $cObj->substituteMarkerArrayCached($termijnTemplateCode, null, $termijnSubpartArray);
		}

		if (!empty($this->data['objectreference'])) {
			$referentienummerTemplateCode = $cObj->getSubpart($localTemplateCode, '###REFERENTIENUMMERBLOCK###');
			$referentienummerMarkerArray = array(
				'###REFERENTIENUMMER###' => $this->data['objectreference']
			);
			$localSubpartArray['###REFERENTIENUMMERBLOCK###'] = $cObj->substituteMarkerArrayCached($referentienummerTemplateCode, $referentienummerMarkerArray);
		}

		foreach ($this->data['addresses'] as $address) {
			$adresTemplateCode = $cObj->getSubpart($localTemplateCode, '###LOCATIEADRES###');
			$adresSubpartArray = array(
				'###POSTCODEHUISNUMMER###' => '',
				'###GEMEENTEBLOCK###' => '',
				'###PROVINCIEBLOCK###' => ''
			);

			if (!empty($address['zipcode'])) {
				$postcodehuisnummerTemplateCode = $cObj->getSubpart($adresTemplateCode, '###POSTCODEHUISNUMMER###');
				$postcodehuisnummerMarkerArray = array(
					'###POSTCODE###' => $address['zipcode']
				);

				$postcodehuisnummerSubpartArray = array(
					'###HUISNUMMERBLOCK###' => '',
					'###HUISNUMMERTOEVOEGINGBLOCK###' => ''
				);

				if (!empty($address['addressnumber'])) {
					$huisnummerTemplateCode = $cObj->getSubpart($postcodehuisnummerTemplateCode, '###HUISNUMMERBLOCK###');
					$huisnummerMarkerArray = array(
						'###HUISNUMMER###' => $address['addressnumber']
					);
					$postcodehuisnummerSubpartArray['###HUISNUMMERBLOCK###'] = $cObj->substituteMarkerArrayCached($huisnummerTemplateCode, $huisnummerMarkerArray);
				}

				if (!empty($address['addressnumberadditional'])) {
					$huisnummertoevoegingTemplateCode = $cObj->getSubpart($postcodehuisnummerTemplateCode, '###HUISNUMMERTOEVOEGINGBLOCK###');
					$huisnummertoevoegingMarkerArray = array(
						'###HUISNUMMERTOEVOEGINGBLOCK###' => $addresss['addressnumberadditional']
					);
					$postcodehuisnummerSubpartArray['###HUISNUMMERTOEVOEGING###'] = $cObj->substituteMarkerArrayCached($huisnummertoevoegingTemplateCode, $huisnummertoevoegingMarkerArray);
				}

				$adresSubpartArray['###POSTCODEHUISNUMMER###'] = $cObj->substituteMarkerArrayCached($postcodehuisnummerTemplateCode, $postcodehuisnummerMarkerArray, $postcodehuisnummerSubpartArray);
			}

			if (!empty($address['municipality'])) {
				$gemeenteTemplateCode = $cObj->getSubpart($adresTemplateCode, '###GEMEENTEBLOCK###');
				$gemeenteMarkerArray = array(
					'###GEMEENTE###' => $address['municipality']
				);
				$adresSubpartArray['###GEMEENTEBLOCK###'] = $cObj->substituteMarkerArrayCached($gemeenteTemplateCode, $gemeenteMarkerArray);
			}

			if (!empty($address['province'])) {
				$provincieTemplateCode = $cObj->getSubpart($adresTemplateCode, '###PROVINCIEBLOCK###');
				$provincieMarkerArray = array(
					'###PROVINCIE###' => $address['province']
				);
				$adresSubpartArray['###PROVINCIEBLOCK###'] = $cObj->substituteMarkerArrayCached($provincieTemplateCode, $provincieMarkerArray);
			}

			$localSubpartArray['###LOCATIEADRES###'] .= $cObj->substituteMarkerArrayCached($adresTemplateCode, null, $adresSubpartArray);
		}

		foreach ($this->data['parcels'] as $parcel) {
			$perceelTemplateCode = $cObj->getSubpart($localTemplateCode, '###LOCATIEPERCEEL###');
			$perceelMarkerArray = array(
				'###KADASTRALEGEMEENTE###' => $parcel['cadastremunicipality'],
				'###SECTIE###' => $parcel['section'],
				'###NUMMER###' => $parcel['number']
			);
			$localSubpartArray['###LOCATIEPERCEEL###'] .= $cObj->substituteMarkerArrayCached($perceelTemplateCode, $perceelMarkerArray);
		}

		foreach ($this->data['coordinates'] as $coordinates) {
			$coordinatenTemplateCode = $cObj->getSubpart($localTemplateCode, '###LOCATIECOORDINATEN###');
			$coordinatenMarkerArray = array(
				'###XWAARDE###' => $coordinates['coordinatex'],
				'###YWAARDE###' => $coordinates['coordinatey']
			);
			$localSubpartArray['###LOCATIECOORDINATEN###'] .= $cObj->substituteMarkerArrayCached($coordinatenTemplateCode, $coordinatenMarkerArray);
		}

		$this->xml = $cObj->substituteMarkerArrayCached($localTemplateCode, $localMarkerArray, $localSubpartArray);
	}

	function pushXML() {
		$url = $this->test ? $this->testurl : $this->url;
		$username = $this->test ? $this->testuser : $this->user;
		$password = $this->test ? $this->testpass : $this->pass;

		$content = $this->curlRequest($url, $username, $password, $this->xml);
		return $content;
	}

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
		fputs($fp, "Content-length: " . strlen($this->xml) . "\r\n");
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
}
?>