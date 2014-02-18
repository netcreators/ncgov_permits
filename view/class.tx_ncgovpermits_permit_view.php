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

class tx_ncgovpermits_permit_view extends tx_ncgovpermits_base_view {

	/**
	 * WecMap Google Map API object
	 * @var tx_wecmap_map_google
	 */
	protected $map;


	/**
	 * Is Google Maps Enabled?
	 * @var mixed (zero default, true or false after initialzation)
	 */
	protected $googleMapsEnabled = 0;

	/**
	 * Initializes the class.
	 *
	 * @param object $controller the controller object
	 */
	public function initialize(&$controller, $mode) {
		parent::initialize($controller);
		$this->addCssIncludeToHeader(
			$this->controller->configModel->get('includeCssFile'),
			$this->controller->configModel->get('includeCssFilePathIsRelative')
		);
		if($this->hasGoogleMapsEnabled()) {
			$this->initializeGoogleMaps($mode);
		}
		if((boolean)$this->controller->configModel->get('convertLatinToUtf8') == TRUE) {
			$this->initCharactersetConversionMap();
		}
	}

	/**
	 * Initializes conversionmap
	 * @return void
	 */
	function initCharactersetConversionMap(){
		for($x=128;$x<256;++$x){
			$this->conversionMap[chr($x)]=utf8_encode(chr($x));
		}
		$cp1252Map=array(
			"\x80"=>"\xE2\x82\xAC",		// EURO SIGN
			"\x82" => "\xE2\x80\x9A",	// SINGLE LOW-9 QUOTATION MARK
			"\x83" => "\xC6\x92",		// LATIN SMALL LETTER F WITH HOOK
			"\x84" => "\xE2\x80\x9E",	// DOUBLE LOW-9 QUOTATION MARK
			"\x85" => "\xE2\x80\xA6",	// HORIZONTAL ELLIPSIS
			"\x86" => "\xE2\x80\xA0",	// DAGGER
			"\x87" => "\xE2\x80\xA1",	// DOUBLE DAGGER
			"\x88" => "\xCB\x86",		// MODIFIER LETTER CIRCUMFLEX ACCENT
			"\x89" => "\xE2\x80\xB0",	// PER MILLE SIGN
			"\x8A" => "\xC5\xA0",		// LATIN CAPITAL LETTER S WITH CARON
			"\x8B" => "\xE2\x80\xB9",	// SINGLE LEFT-POINTING ANGLE QUOTATION MARK
			"\x8C" => "\xC5\x92",		// LATIN CAPITAL LIGATURE OE
			"\x8E" => "\xC5\xBD",		// LATIN CAPITAL LETTER Z WITH CARON
			"\x91" => "\xE2\x80\x98",	// LEFT SINGLE QUOTATION MARK
			"\x92" => "\xE2\x80\x99",	// RIGHT SINGLE QUOTATION MARK
			"\x93" => "\xE2\x80\x9C",	// LEFT DOUBLE QUOTATION MARK
			"\x94" => "\xE2\x80\x9D",	// RIGHT DOUBLE QUOTATION MARK
			"\x95" => "\xE2\x80\xA2",	// BULLET
			"\x96" => "\xE2\x80\x93",	// EN DASH
			"\x97" => "\xE2\x80\x94",	// EM DASH
			"\x98" => "\xCB\x9C",		// SMALL TILDE
			"\x99" => "\xE2\x84\xA2",	// TRADE MARK SIGN
			"\x9A" => "\xC5\xA1",		// LATIN SMALL LETTER S WITH CARON
			"\x9B" => "\xE2\x80\xBA",	// SINGLE RIGHT-POINTING ANGLE QUOTATION MARK
			"\x9C" => "\xC5\x93",		// LATIN SMALL LIGATURE OE
			"\x9E" => "\xC5\xBE",		// LATIN SMALL LETTER Z WITH CARON
			"\x9F" => "\xC5\xB8" 		// LATIN CAPITAL LETTER Y WITH DIAERESIS
		);
		foreach($cp1252Map as $k=>$v) {
			$this->conversionMap[$k]=$v;
		}
	}

	/**
	 * Converts latin string to utf-8 string.
	 * @param string $input
	 * @return string
	 */
	function convertLatinToUtf8($input) {
		$asciiChar='[\x00-\x7F]';
		$contByte='[\x80-\xBF]';
		$utf8_2='[\xC0-\xDF]'.$contByte;
		$utf8_3='[\xE0-\xEF]'.$contByte.'{2}';
		$utf8_4='[\xF0-\xF7]'.$contByte.'{3}';
		$utf8_5='[\xF8-\xFB]'.$contByte.'{4}';
		$normalCharacterRegularExpression = "@^($asciiChar+|$utf8_2|$utf8_3|$utf8_4|$utf8_5)(.*)$@s";

		if(mb_check_encoding($input,'UTF-8')) {
			$result = $input; // no need for the rest if it's all valid UTF-8 already
		} else {
			$result='';
			$char='';
			$rest='';
			while((strlen($input))>0){
				if(preg_match($normalCharacterRegularExpression, $input, $match) == 1) {
					$char = $match[1];
					$rest = $match[2];
					$output .= $char;
				} elseif (preg_match('@^(.)(.*)$@s', $input, $match) == 1) {
					$char = $match[1];
					$rest = $match[2];
					$output .= $this->conversionMap[$char];
				}
				$input = $rest;
			}
			$result = $output;
		}
		return $result;
	}

	/**
	 *
	 * Adds markers for all addresses in the given record.
	 * @param array $record
	 * @return bool was an address added?
	 */
	function addAddressMarkersForRecord($record) {
		$addressesAdded = false;
		if(tx_nclib::isLoopable($record['objectaddresses'])) {
			foreach($record['objectaddresses'] as $index => $address) {
				if(tx_nclib::isLoopable($address)) {
					if($this->hasGoogleMapsEnabled() && (!empty($address['address']) || !empty($address['municipality']))) {
						$addressesAdded = true;
						$city = $address['city'];
						if(empty($city)) {
							$city = trim($address['municipality']);
						}
						if(empty($city)) {
							$city = $this->controller->configModel->get('owmsDefaults.city');
						}
						if($this->controller->permitsModel->isPermit()) {
							$title = ucfirst($this->controller->permitsModel->getField('producttype')) . '<br />';
						} else {
							$title = ucfirst($this->controller->permitsModel->getField('title')) . '<br />';
						}
                                                
                                                // link the publication to permit (field related) 
                                                if ($record['related']){ 
                                                    $link = $this->controller->getHtmlCompliantLinkToController(false,
							$this->controller->configModel->get('permitPage'),
							array('id' => $record['related']),
							false
                                                    );        
                                                }else{
                                                    $link = $this->controller->getHtmlCompliantLinkToController(false,
							$this->controller->configModel->get('displayPage'),
							array('id' => $record['uid']),
							false
                                                    );
                                                }                                                
						/*$link = $this->controller->getHtmlCompliantLinkToController(false,
							$this->controller->configModel->get('displayPage'),
							array('id' => $record['uid']),
							false
						);*/
						$title = sprintf("<a href='%s'>%s</a>", $link, $title);
						$this->map->addMarkerByAddress(
							$address['address'] . ' ' . $address['addressnumber'] . $address['addressnumberadditional'],
							$city,
							'', //$this->controller->configModel->get('owmsDefaults.province'),
							$address['zipcode'],
							'Netherlands',
							$title,
							ucfirst($address['address']) . ' ' . $address['addressnumber'] . $address['addressnumberadditional'] . '<br />'  . $address['zipcode'] . ' ' . $city
						);
					}
				}
			}
		}
		return $addressesAdded;
	}

	/**
	 * Shows a list of permits.
	 *
	 * @return string	the content
	 */
	public function getList() {
		$subparts = array();
		$this->addTranslationLabel('label_view_details');
		$this->addTranslationLabel('label_radius');
		$this->addTranslationLabel('label_submit');

		$permitFields = $this->controller->permitsModel->getFieldList();
		if($this->controller->getPluginMode() == 'permits' || $this->controller->getPluginMode() == 'latest_permits') {
			$main = 'PERMIT_LIST_VIEW';
			$configBasePath = 'viewPermitList.';
		} else {
			$main = 'PUBLICATION_LIST_VIEW';
			$configBasePath = 'viewPublicationList.';
		}
		$permitIndex = 0;
		if($this->controller->permitsModel->getCount() > 0) {
			$addressesAdded = false;
			while ($this->controller->permitsModel->hasNextRecord()) {
				$record = $this->controller->permitsModel->getRecord();
				foreach($permitFields as $field) {
					$content = $this->getFieldWrap(
						$configBasePath,
						$field,
						$record,
						$this->controller->permitsModel->getTableName()
					);
					$permit['FIELD_' . strtoupper($field)] = $content;
				}
				$permit['FIELD_ADDRESS'] = $this->controller->permitsModel->getAddress();

				$permit['FIELD_LINK'] = $this->controller->getHtmlCompliantLinkToController(false, false,
					array(
						'id' => $record['uid'],
						'activeMonth' => $this->controller->getPiVar('activeMonth'),
						'activeYear' => $this->controller->getPiVar('activeYear')
					),
					false
				);

				$permit['FIELD_LINK'] = $this->controller->getURLToFilteredResult(array('id' => $record['uid']));

				$permit['FIELD_WEEK'] = $this->getTranslatedLabel('label_week') . ' '
					. date('W', $this->controller->permitsModel->getField('publishdate', true));

				if($this->addAddressMarkersForRecord($record)) {
					$addressesAdded = true;
				}

				$subparts['RECORDS'][$permitIndex] = $permit;

				$permitIndex++;
				$this->controller->permitsModel->moveToNextRecord();
			}

			if($addressesAdded === false) {
				$subparts['HAS_MAPS'] = array();
				$this->disableGoogleMaps();
			}

			if($this->hasGoogleMapsEnabled()) {
				$subparts['GOOGLE_MAPS'] = $this->map->drawMap();
				$subparts['GOOGLE_MAPS'] = $this->fixIssuesWithMap($subparts['GOOGLE_MAPS']);
			} else {
				$subparts['GOOGLE_MAPS'] = '';
			}
			
		} else {
			$subparts['RECORDS_AVAILABLE'] = array(
				$this->getTranslatedLabel('no_records_available')
			);
			$subparts['HAS_MAPS'] = array();
		}
		$subparts['DATE_FILTER'] = $this->getDateFilter();
		$subparts['FULLTEXT_FILTER'] = $this->getFulltextFilter();
		$subparts['PRODUCT_TYPE_FILTER'] = $this->getProductTypeFilter();
		$subparts['PHASE_FILTER'] = $this->getPhaseFilter();
		$subparts['TERMTYPE_FILTER'] = $this->getTermTypeFilter();
		$subparts['POSTCODE_FILTER'] = $this->getPostcodeFilter();
		$subparts['ACTION'] = $this->controller->getLinkToController(false, false, array(
			'activeMonth' => $this->controller->getPiVar('activeMonth'),
			'activeYear' => $this->controller->getPiVar('activeYear')
		));
		
		$content = $this->subpartReplaceRecursive(
			$subparts, $main, false, true, $this->controller->configModel->get('cleanRemainingMarkers')
		);
		return $content;
	}

        
        /**
	 * Shows a list of permits. New interface - all months/years
	 *
	 * @return string	the content
	 */
	public function getListAll() {
		$subparts = array();
		$this->addTranslationLabel('label_view_details');
		$this->addTranslationLabel('label_radius');
		$this->addTranslationLabel('label_submit');

		$permitFields = $this->controller->permitsModel->getFieldList();
		//$main = 'PERMIT_LIST_VIEWALL';
		//$configBasePath = 'viewPermitList.';
                
		if($this->controller->getPluginMode() == 'permitsall') {
			$main = 'PERMIT_LIST_VIEWALL';
			$configBasePath = 'viewPermitList.';
		} else {
			$main = 'PUBLICATION_LIST_VIEW_ALL';
			$configBasePath = 'viewPublicationList.';
		}                
                

		$permitIndex = 0;
                $firsttime = true;
                $previousweeknumber = 0;
		if($this->controller->permitsModel->getCount() > 0) {
			$addressesAdded = false;
			while ($this->controller->permitsModel->hasNextRecord()) {
				$record = $this->controller->permitsModel->getRecord();    
                               
                               //$weeknumber = (int)date('W', $this->controller->permitsModel->getField('publishdate', true));                              
                               $weeknumber = (int)date('oW', $this->controller->permitsModel->getField('publishdate', true));                              
                               $strweeknumber = date('W', $this->controller->permitsModel->getField('publishdate', true));
                               //$year = date('Y', $this->controller->permitsModel->getField('publishdate', true));                                         
                               $year = date('o', $this->controller->permitsModel->getField('publishdate', true)); 
                               $weeknumberyear=$weeknumber.$year;
                               //$weeknumberindex = $weeknumber+$year;
                               //$weeknumberyear =  $weeknumber.$year;
                               //$weeknumberyearstr = strval($weeknumber.$year);                       
                                //if ($previousweeknumber != $weeknumber)
                               if ($previousweeknumber != $weeknumberyear)
                                {
                                   $permitIndex = 0; 
                                   $permitweek['WEEKNUMBER'] = 'wk'.$strweeknumber;
                                   $permitweek['WEEKNUMBERSTRING'] = 'Week '.$strweeknumber.' '.$year;
                                   if ($firsttime == true){
                                       $permitweek['CLASS'] = 'class=active';
                                       $permitweek['PERMITSRESULTCLASS'] = 'permit-result-active';
                                       $firsttime = false;
                                   }else{
                                       $permitweek['CLASS'] = 'class=not-active';
                                       $permitweek['PERMITSRESULTCLASS'] = 'permit-result-not-active';
                                   }                                       
                                   
                                   $subparts['RECORDS'][$weeknumberyear] = $permitweek;                                               
                                }                                   
				foreach($permitFields as $field) {
					$content = $this->getFieldWrap(
						$configBasePath,
						$field,
						$record,
						$this->controller->permitsModel->getTableName()
					);
					$permit['FIELD_' . strtoupper($field)] = $content;
                              }
                                
				$permit['FIELD_ADDRESS'] = $this->controller->permitsModel->getAddress();
                                                               
				$permit['FIELD_LINK'] = $this->controller->getHtmlCompliantLinkToController(false, false,
					array(
						'id' => $record['uid'],
						'activeMonth' => $this->controller->getPiVar('activeMonth'),
						'activeYear' => $this->controller->getPiVar('activeYear')
					),
					false
				);
                                
                               if($this->controller->getPluginMode() == 'permitsall') {
                                   $permit['FIELD_LINK'] = $this->controller->getURLToFilteredResult(array('id' => $record['uid']));    
                                }else{
                                   // link the publication to permit (field related) 
                                   if ($record['related']){ 
                                      $permit['FIELD_LINK'] = $this->controller->getURLToFilteredResultPublication(array('id' => $record['related']));    
                                   }else{
                                       $permit['FIELD_LINK'] = $this->controller->getURLToFilteredResult(array('id' => $record['uid']));    
                                   }
                                }
                                $permit['FIELD_WEEK'] =  date('W', $this->controller->permitsModel->getField('publishdate', true));
                                                          
				if($this->addAddressMarkersForRecord($record)) {
					$addressesAdded = true;
				}
                                                   
                                $subparts['RECORDS'][$weeknumberyear]['SUBRECORDS'][$permitIndex] = $permit;      
                               
				$permitIndex++;                            
                                $previousweeknumber = $weeknumberyear;
				$this->controller->permitsModel->moveToNextRecord();
			}
                        
                        //var_dump( $subparts['RECORDS']);
			if($addressesAdded === false) {
				$subparts['HAS_MAPS'] = array();
				$this->disableGoogleMaps();
			}

			if($this->hasGoogleMapsEnabled()) {
				$subparts['GOOGLE_MAPS'] = $this->map->drawMap();
				$subparts['GOOGLE_MAPS'] = $this->fixIssuesWithMap($subparts['GOOGLE_MAPS']);
			} else {
				$subparts['GOOGLE_MAPS'] = '';
			}
			
		} else {
			$subparts['RECORDS_AVAILABLE'] = array(
				$this->getTranslatedLabel('no_records_available')
			);
			$subparts['HAS_MAPS'] = array();
		}
                $subparts['PATH']= '/typo3conf/ext/ncgov_permits'; 
                
		$subparts['DATE_FILTER'] = $this->getDateFilterAll();
		$subparts['FULLTEXT_FILTER'] = $this->getFulltextFilter();
                $subparts['MONTH_FILTER'] = $this->getMonthFilter();
                $subparts['YEAR_FILTER'] = $this->getYearFilter();                
		$subparts['PRODUCT_TYPE_FILTER'] = $this->getProductTypeFilter();
		$subparts['PHASE_FILTER'] = $this->getPhaseFilter();
		$subparts['TERMTYPE_FILTER'] = $this->getTermTypeFilter();
		$subparts['POSTCODE_FILTER'] = $this->getPostcodeFilter();
		$subparts['ACTION'] = $this->controller->getLinkToController(false, false, array(
			'activeMonth' => $this->controller->getPiVar('activeMonth'),
			'activeYear' => $this->controller->getPiVar('activeYear')
		));

		$content = $this->subpartReplaceRecursive(
			$subparts, $main, false, true, $this->controller->configModel->get('cleanRemainingMarkers')
		);
		return $content;
	}

	/**
	 * Shows the latest top X items there are 
	 * @return string the content
	 */
	public function getLatestList() {
		$subparts = array();
		$this->addTranslationLabel('label_view_details');

		$permitFields = $this->controller->permitsModel->getFieldList();
		if($this->controller->permitsModel->isPermit()) {
			$main = 'PERMIT_LATEST_VIEW';
			$configBasePath = 'viewLatestPermits.';
		} else {
			$main = 'PUBLICATION_LATEST_VIEW';
			$configBasePath = 'viewLatestPublications.';
		}
		$permitIndex = 0;
		if($this->controller->permitsModel->getCount() > 0) {
			$addressesAdded = false;
			while ($this->controller->permitsModel->hasNextRecord()) {
				$record = $this->controller->permitsModel->getRecord();
				foreach($permitFields as $field) {
					$content = $this->getFieldWrap(
						$configBasePath,
						$field,
						$record,
						$this->controller->permitsModel->getTableName()
					);
					$permit['FIELD_' . strtoupper($field)] = $content;
				}
				$permit['FIELD_ADDRESS'] = $this->controller->permitsModel->getAddress();

				$permit['FIELD_LINK'] = $this->controller->getHtmlCompliantLinkToController(false,
					$this->controller->configModel->get('displayPage'),
					array(
						'id' => $record['uid']
					),
					false
				);

				$permit['FIELD_WEEK'] = $this->getTranslatedLabel('label_week') . ' '
					. date('W', $this->controller->permitsModel->getField('publishdate', true));
				$permit['FIELD_PUBLISHDATE'] = date(
					$this->controller->configModel->get('config.dateFormat'),
					$this->controller->permitsModel->getField('publishdate', true)
				);

				if($this->addAddressMarkersForRecord($record)) {
					$addressesAdded = true;
				}
				
				$subparts['RECORDS'][$permitIndex] = $permit;

				$permitIndex++;
				$this->controller->permitsModel->moveToNextRecord();
			}

			if($addressesAdded === false) {
				$subparts['HAS_MAPS'] = array();
				$this->disableGoogleMaps();
			}

			if($this->hasGoogleMapsEnabled()) {
				$subparts['GOOGLE_MAPS'] = $this->map->drawMap();
				$subparts['GOOGLE_MAPS'] = $this->fixIssuesWithMap($subparts['GOOGLE_MAPS']);
			} else {
				$subparts['GOOGLE_MAPS'] = '';
			}
			
		} else {
			$subparts['RECORDS_AVAILABLE'] = array(
				$this->getTranslatedLabel('no_records_available_for_period')
			);
			$subparts['HAS_MAPS'] = array();
		}

		$content = $this->subpartReplaceRecursive(
			$subparts, $main, false, true, $this->controller->configModel->get('cleanRemainingMarkers')
		);
		return $content;
	}

	/**
	 * detail view of a permit
	 * @return string	the content
	 */
	public function getDetails() {
		$this->addTranslationLabel('label_return');
		$subparts = array();
		$helpIcon = $this->controller->configModel->get('help.icon');
        $showFileName = $this->controller->configModel->get('showFileName');
		$permit = array();

		if($this->controller->permitsModel->isPermit()) {
			$main = 'PERMIT_DETAIL_VIEW';
			$configBasePath = 'viewPermitDetails.';
			$permitFields = $this->controller->permitsModel->getFieldList();
			$removeFields = array(
				'uid', 'pid', 'cruser_id', 'deleted', 'hidden', 'documenttypes', 'municipality', 'lastpublished', 'language',
				'type', 'title', 'publishdate', 'link', 'objectaddresses', 'coordinates', 'lots', 'related'
			);
			$permitFields = array_diff($permitFields, $removeFields);
		} else {
			$main = 'PUBLICATION_DETAIL_VIEW';
			$configBasePath = 'viewPublicationDetails.';
			$permitFields = array(
				'publishdate', 'title', 'description', 'zipcode', 'address', 'addressnumber',
				'addressnumberadditional', 'municipality', 'tstamp', 'related', 'link',
			);
		}
		if($this->controller->configModel->get('showDetailsFieldList') != FALSE) {
			$permitFields = t3lib_div::trimExplode(',', $this->controller->configModel->get('showDetailsFieldList'));
		}
		foreach($permitFields as $field) {
			$helpText = '';
			if($this->controller->configModel->exists('help.' . $field)) {
				$helpText = htmlentities($this->controller->configModel->get('help.'.$field));	
			}
			if(!empty($helpText)) {
				$helpText = sprintf('<img src="%s" title="%s" alt="%s" />', $helpIcon, $helpText, $helpText);
			}
			$permit['LABEL_FIELDHEADER_'.strtoupper($field)] = $this->getTranslatedLabel('label_fieldheader_' . $field) . $helpText;
		}
		$permitIndex = 0;
		if($this->controller->permitsModel->isLoaded()) {
			$record = $this->controller->permitsModel->getRecord();
			$permit['FIELD_ADDRESS'] = $this->controller->permitsModel->getAddress();
			foreach($permitFields as $field) {
				$content = $this->getFieldWrap(
					$configBasePath,
					$field,
					$record,
					$this->controller->permitsModel->getTableName()
				);
				if($field == 'link') {
					$field = 'link2';
				}
				if($field == 'description') {
					$content = $this->controller->permitsModel->getRichDescription();
				}
				if($field == 'publicationbody') {
					$content = $this->controller->permitsModel->getRichPublicationBody();
				}
				if(empty($content)) {
					$permit['HAS_' . strtoupper($field)] = array();
					//$content = '-';
				} else {
					if($field != 'link2') {
						$permit['FIELD_' . strtoupper($field)] = ucfirst($content);
					}
				}
			}
			if(tx_nclib::isLoopable($record['documents'])) {
				foreach($record['documents'] as $index => $document) {
					$doctype = $record['documenttypes'][$index];

                    if ($showFileName== false) {
                        $permit['DOCUMENTS'][$index]['PERMIT_DOCUMENT'] = $this->controller->getLinkToController(
                            $doctype, false, array('id' => $record['uid'], 'doc' => md5($document))
					    );
                    }else{
                        $permit['DOCUMENTS'][$index]['PERMIT_DOCUMENT'] = $this->controller->getLinkToController(
                            $document, false, array('id' => $record['uid'], 'doc' => md5($document))
                        );
                    }
					$permit['DOCUMENTS'][$index]['PERMIT_DOCUMENTTYPE'] = $doctype;


				}
			} else {
				$permit['HAS_DOCUMENTS'] = array();
				//$permit['DOCUMENTS'] = array('<li>-</li>');
			}
			$addressesAdded = $this->addAddressMarkersForRecord($record);
			
			$skipFields = array('uid', 'crdate', 'tstamp', 'hidden', 'pid', 'cruser_id', 'deleted');
			if(tx_nclib::isLoopable($record['objectaddresses'])) {
				foreach($record['objectaddresses'] as $index => $address) {
					if(tx_nclib::isLoopable($address)) {
						foreach($address as $key=>$value) {
							if(array_search($key, $skipFields) === false) {
								$permit['ADDRESSES'][$index]['LABEL_FIELDHEADER_'.strtoupper($key)] =
									$this->getTranslatedLabel('label_fieldheader_' . $key);
								if(empty($value)) {
									$permit['ADDRESSES'][$index]['HAS_'.strtoupper($key)] = array();
									//$value = '-';
								} else {
									$permit['ADDRESSES'][$index]['FIELD_'.strtoupper($key)] = ucfirst($value);
								}
							}
						}
					}
				}
			} else {
				$permit['ADDRESSES'] = array();
			}
			if($addressesAdded === false) {
				$permit['HAS_MAPS'] = array();
				$this->disableGoogleMaps();
			}
			$skipFields = array('uid', 'crdate', 'tstamp', 'hidden', 'pid', 'cruser_id', 'deleted');
			if(tx_nclib::isLoopable($record['lots'])) {
				foreach($record['lots'] as $index => $lot) {
					if(tx_nclib::isLoopable($lot)) {
						foreach($lot as $key=>$value) {
							if(array_search($key, $skipFields) === false) {
								$permit['LOTS'][$index]['LABEL_FIELDHEADER_'.strtoupper($key)] =
									$this->getTranslatedLabel('label_fieldheader_' . $key);
								if(empty($value)) {
									$permit['LOTS'][$index]['HAS_'.strtoupper($key)] = array();
									//$value = '-';
								} else {
									$permit['LOTS'][$index]['FIELD_'.strtoupper($key)] = $value;
								}
							}
						}
					}
				}
			} else {
				$permit['LOTS'] = array();
			}
			$skipFields = array('uid', 'crdate', 'tstamp', 'hidden', 'pid', 'cruser_id', 'deleted');
			if(tx_nclib::isLoopable($record['coordinates'])) {
				foreach($record['coordinates'] as $index => $coordinates) {
					$permit['COORDINATES'][$index]['LABEL_FIELDHEADER_COORDINATES'] =
						$this->getTranslatedLabel('label_fieldheader_coordinates') . ' (x, y, z)';
					foreach($coordinates as $subIndex => $coordinate) {
						if(empty($coordinate)) {
							$coordinates[$subIndex] = '-';
						}
					}
					$coords = '(' . $coordinates['coordinatex'] . ', ' . $coordinates['coordinatey'];
					if(!empty($coordinates['coordinatez'])) {
						$coords .= ', ' . $coordinates['coordinatez'] . ')';
					} else {
						$coords .= ')';
					}
					$permit['COORDINATES'][$index]['FIELD_COORDINATES'] = $coords;
				}
			} else {
				$permit['COORDINATES'] = array('');
			}

			if(!$this->controller->permitsModel->hasCompanyInfo()) {
				$permit['HAS_COMPANY_INFO'] = array();
			}
			$permit['FIELD_TITLE'] = $this->controller->permitsModel->getTitle();
			$permit['FIELD_LINK'] = $this->controller->getHtmlCompliantLinkToController(false, false,
				array('id' => $record['uid']),
				false
			);

			if($this->hasGoogleMapsEnabled()) {
				// google maps stuff
				$permit['GOOGLE_MAPS'] = $this->map->drawMap();
				$permit['GOOGLE_MAPS'] = $this->fixIssuesWithMap($permit['GOOGLE_MAPS']);
			} else {
				$subparts['GOOGLE_MAPS'] = '';
			}
			$subparts = $permit;
			$this->addMetaDataRecord();

		} else {
			$subparts['DETAILS'] = array(
				$this->getTranslatedLabel('record_not_found')
			);
		}
		$subparts['LINK_BACK'] = $this->controller->getHtmlCompliantLinkToController(false, false,
			array(
				'activeMonth' => $this->controller->getPiVar('activeMonth'),
				'activeYear' => $this->controller->getPiVar('activeYear')
			),
			false
		);

		$content = $this->subpartReplaceRecursive(
			$subparts, $main, false, true, $this->controller->configModel->get('cleanRemainingMarkers')
		);
		return $content;
	}

	/**
	 * detail view of a permit - New interface - all months/years
         * Show filename
	 * @return string	the content
	 */
	public function getDetailsAll() {
		$this->addTranslationLabel('label_return');
		$subparts = array();
		$helpIcon = $this->controller->configModel->get('help.icon');
		$permit = array();

		if($this->controller->permitsModel->isPermit()) {
			$main = 'PERMIT_DETAIL_VIEW_ALL';
			$configBasePath = 'viewPermitDetails.';
			$permitFields = $this->controller->permitsModel->getFieldList();
			$removeFields = array(
				'uid', 'pid', 'cruser_id', 'deleted', 'hidden', 'documenttypes', 'municipality', 'lastpublished', 'language',
				'type', 'title', 'publishdate', 'link', 'objectaddresses', 'coordinates', 'lots', 'related'
			);
			$permitFields = array_diff($permitFields, $removeFields);
		} else {
			$main = 'PUBLICATION_DETAIL_VIEW_ALL';
			$configBasePath = 'viewPublicationDetails.';
			$permitFields = array(
				'publishdate', 'title', 'description', 'zipcode', 'address', 'addressnumber',
				'addressnumberadditional', 'municipality', 'tstamp', 'related', 'link',
			);
		}
		if($this->controller->configModel->get('showDetailsFieldList') != FALSE) {
			$permitFields = t3lib_div::trimExplode(',', $this->controller->configModel->get('showDetailsFieldList'));
		}
		foreach($permitFields as $field) {
			$helpText = '';
			if($this->controller->configModel->exists('help.' . $field)) {
				$helpText = htmlentities($this->controller->configModel->get('help.'.$field));	
			}
			if(!empty($helpText)) {
				$helpText = sprintf('<img src="%s" title="%s" alt="%s" />', $helpIcon, $helpText, $helpText);
			}
			$permit['LABEL_FIELDHEADER_'.strtoupper($field)] = $this->getTranslatedLabel('label_fieldheader_' . $field) . $helpText;
		}
		$permitIndex = 0;
		if($this->controller->permitsModel->isLoaded()) {
			$record = $this->controller->permitsModel->getRecord();
			$permit['FIELD_ADDRESS'] = $this->controller->permitsModel->getAddress();
			foreach($permitFields as $field) {
				$content = $this->getFieldWrap(
					$configBasePath,
					$field,
					$record,
					$this->controller->permitsModel->getTableName()
				);
				if($field == 'link') {
					$field = 'link2';
				}
				if($field == 'description') {
					$content = $this->controller->permitsModel->getRichDescription();
				}
				if($field == 'publicationbody') {
					$content = $this->controller->permitsModel->getRichPublicationBody();
				}
				if(empty($content)) {
					$permit['HAS_' . strtoupper($field)] = array();
					//$content = '-';
				} else {
					if($field != 'link2') {
						$permit['FIELD_' . strtoupper($field)] = ucfirst($content);
					}
				}
			}
			if(tx_nclib::isLoopable($record['documents'])) {
				foreach($record['documents'] as $index => $document) {
					$doctype = $record['documenttypes'][$index];
                                        $filename = basename($document);
					$permit['DOCUMENTS'][$index]['PERMIT_DOCUMENT'] = $this->controller->getLinkToController(
						$doctype, false, array('id' => $record['uid'], 'doc' => md5($document))
					);
					$permit['DOCUMENTS'][$index]['PERMIT_DOCUMENTTYPE'] = $doctype;
                                        
                                        $permit['DOCUMENTS'][$index]['PERMIT_DOWNLOAD'] = $this->controller->getLinkToFile(
							'Download',
							$this->controller->getLinkToItemFileUrl(
								'documents', $document
							)." _blank"
						);                                        
                                    $lengthstrhostname = strlen(t3lib_div::getHostname());
                                    $file = substr($document, $lengthstrhostname+6);

                                    $findleiden = 999;
                                    $findleiden = strpos(t3lib_div::getHostname(),'gemeente.leiden.nl');

                                    if ($findleiden == 0){
                                        // import document path http://www.leiden.nl/fileadmin/vergunningen/...
                                        // domain website http://gemeente.leiden.nl/
                                        $file = substr($document, $lengthstrhostname+3);
                                    }

                                        $search_string ='%20';
                                        $file = str_replace($search_string, ' ', $file);

                                        $filename = substr(basename($file),0,strrpos(basename($file), '.'));
                                        // strip filename
                                        if (strrpos($filename, '_') > 0 ){
                                            $pos_underscore = strrpos($filename, '_');  
                                                $filename = substr($filename,$pos_underscore+1);
                                        }

                                        if (file_exists($file)) {
                                            $file_size = filesize($file);

                                        if ($file_size < 1024) $file_size.' B';
                                            elseif ($file_size < 1048576) $file_size = round($file_size / 1024, 2).' KB';
                                            elseif ($file_size < 1073741824) $file_size = round($file_size / 1048576, 2).' MB';

                                        }else{
                                            $file_size = '';
                                        }

                                        $file_extension = strtoupper(substr(strrchr(basename($file),'.'),1));
                                           
                                         $permit['DOCUMENTS'][$index]['PERMIT_DOCUMENTFILESIZE'] =  $file_extension.' / '. $file_size;
                                         $permit['DOCUMENTS'][$index]['PERMIT_FILENAME'] = $filename;
                                        //$permit['DOCUMENTS'][$index]['PERMIT_DOWNLOAD'] = $document;
				}
			} else {
				$permit['HAS_DOCUMENTS'] = array();
				//$permit['DOCUMENTS'] = array('<li>-</li>');
			}
			$addressesAdded = $this->addAddressMarkersForRecord($record);
			
			$skipFields = array('uid', 'crdate', 'tstamp', 'hidden', 'pid', 'cruser_id', 'deleted');
			if(tx_nclib::isLoopable($record['objectaddresses'])) {
				foreach($record['objectaddresses'] as $index => $address) {
					if(tx_nclib::isLoopable($address)) {
						foreach($address as $key=>$value) {
							if(array_search($key, $skipFields) === false) {
								$permit['ADDRESSES'][$index]['LABEL_FIELDHEADER_'.strtoupper($key)] =
									$this->getTranslatedLabel('label_fieldheader_' . $key);
								if(empty($value)) {
									$permit['ADDRESSES'][$index]['HAS_'.strtoupper($key)] = array();
									//$value = '-';
								} else {
									$permit['ADDRESSES'][$index]['FIELD_'.strtoupper($key)] = ucfirst($value);
								}
							}
						}
					}
				}
			} else {
				$permit['ADDRESSES'] = array();
			}
			if($addressesAdded === false) {
				$permit['HAS_MAPS'] = array();
				$this->disableGoogleMaps();
			}
			$skipFields = array('uid', 'crdate', 'tstamp', 'hidden', 'pid', 'cruser_id', 'deleted');
			if(tx_nclib::isLoopable($record['lots'])) {
				foreach($record['lots'] as $index => $lot) {
					if(tx_nclib::isLoopable($lot)) {
						foreach($lot as $key=>$value) {
							if(array_search($key, $skipFields) === false) {
								$permit['LOTS'][$index]['LABEL_FIELDHEADER_'.strtoupper($key)] =
									$this->getTranslatedLabel('label_fieldheader_' . $key);
								if(empty($value)) {
									$permit['LOTS'][$index]['HAS_'.strtoupper($key)] = array();
									//$value = '-';
								} else {
									$permit['LOTS'][$index]['FIELD_'.strtoupper($key)] = $value;
								}
							}
						}
					}
				}
			} else {
				$permit['LOTS'] = array();
			}
			$skipFields = array('uid', 'crdate', 'tstamp', 'hidden', 'pid', 'cruser_id', 'deleted');
			if(tx_nclib::isLoopable($record['coordinates'])) {
				foreach($record['coordinates'] as $index => $coordinates) {
					$permit['COORDINATES'][$index]['LABEL_FIELDHEADER_COORDINATES'] =
						$this->getTranslatedLabel('label_fieldheader_coordinates') . ' (x, y, z)';
					foreach($coordinates as $subIndex => $coordinate) {
						if(empty($coordinate)) {
							$coordinates[$subIndex] = '-';
						}
					}
					$coords = '(' . $coordinates['coordinatex'] . ', ' . $coordinates['coordinatey'];
					if(!empty($coordinates['coordinatez'])) {
						$coords .= ', ' . $coordinates['coordinatez'] . ')';
					} else {
						$coords .= ')';
					}
					$permit['COORDINATES'][$index]['FIELD_COORDINATES'] = $coords;
				}
			} else {
				$permit['COORDINATES'] = array('');
			}

			if(!$this->controller->permitsModel->hasCompanyInfo()) {
				$permit['HAS_COMPANY_INFO'] = array();
			}
			$permit['FIELD_TITLE'] = $this->controller->permitsModel->getTitle();
			$permit['FIELD_LINK'] = $this->controller->getHtmlCompliantLinkToController(false, false,
				array('id' => $record['uid']),
				false
			);

			if($this->hasGoogleMapsEnabled()) {
				// google maps stuff
				$permit['GOOGLE_MAPS'] = $this->map->drawMap();
				$permit['GOOGLE_MAPS'] = $this->fixIssuesWithMap($permit['GOOGLE_MAPS']);
			} else {
				$subparts['GOOGLE_MAPS'] = '';
			}
			$subparts = $permit;
			$this->addMetaDataRecord();

		} else {
			$subparts['DETAILS'] = array(
				$this->getTranslatedLabel('record_not_found')
			);
		}
		$subparts['LINK_BACK'] = $this->controller->getHtmlCompliantLinkToController(false, false,
			array(
				'activeMonth' => $this->controller->getPiVar('activeMonth'),
				'activeYear' => $this->controller->getPiVar('activeYear')
			),
			false
		);

		$content = $this->subpartReplaceRecursive(
			$subparts, $main, false, true, $this->controller->configModel->get('cleanRemainingMarkers')
		);
		return $content;
	}
        
        
        
	/**
	 * Checks if Google Maps is enabled for this extension.
	 * @return boolean	true if so, false otherwise
	 */
	protected function hasGoogleMapsEnabled() {
		if($this->googleMapsEnabled === 0) {
			$this->googleMapsEnabled = $this->controller->configModel->get('googleMaps.enabled') !== false;
		}
		return $this->googleMapsEnabled;
	}

	public function disableGoogleMaps() {
		$this->googleMapsEnabled = false;
	}

	/**
	 * Initializes Google maps if available.
	 * @return void
	 */
	protected function initializeGoogleMaps($mode='default') {
		if($this->hasGoogleMapsEnabled()) {
			// google maps stuff
			$this->map = t3lib_div::makeInstance(
				'tx_wecmap_map_google',
				$this->controller->configModel->get('googleMaps.apiKey'),
				$this->controller->configModel->get('googleMaps.width'),
				$this->controller->configModel->get('googleMaps.height')
			);
			$this->map->mapName = 'map_txncgovpermits_' . $mode;
			$controls = $this->controller->configModel->get('googleMaps.controls');
			if(!empty($controls)) {
				$controls = t3lib_div::trimExplode(',', $controls);
				foreach($controls as $control) {
					$this->map->addControl($control);
				}
			}
			$type = $this->controller->configModel->get('googleMaps.type');
			if(!empty($type)) {
				$this->map->setType($type);
			}
			$radius = $this->controller->configModel->get('googleMaps.radius');
			if(!empty($radius)) {
				$this->map->setType((float)$radius);
			}
		}
	}

	/**
	 * Fixes all issues with google maps & wec_maps
	 * @param string $map
	 * @return string
	 */
	protected function fixIssuesWithMap($map) {
		$map = $this->removeStyleAttributeFromMaps($map);
		$map = $this->addInlineWidthInitializationToMap($map);
		$map = $this->preventJsLinkDeformationByAddingCommentToJavascript($map);
		return $map;
	}

	/**
	 * Adds comments to javascript definition for google maps (otherwise links will get deformed)
	 * @param string $map
	 * @return string
	 */
	protected function preventJsLinkDeformationByAddingCommentToJavascript($map) {
		if($this->controller->configModel->get('googleMaps.preventJsLinkDeformationByAddingCommentToJavascript')) {
			$map = str_replace('<script type="text/javascript">', '<script type="text/javascript"><!--', $map);
			$map = str_replace('</script>', '--></script>', $map);
		}
		return $map;
	}
	
	/**
	 * Returns the Maps javascript with width initialization also correctly set (otherwise maps remain empty :( )
	 * @param string $map the map configuration
	 * @return string the fixed map
	 */
	protected function addInlineWidthInitializationToMap($map) {
		$search = 'document.getElementById("' . $this->map->mapName . '").style.height="' . $this->controller->configModel->get('googleMaps.height') . 'px";';
		$replace = $search . 'document.getElementById("'. $this->map->mapName .'").style.width="' . $this->controller->configModel->get('googleMaps.width') . 'px";';
		$map = str_replace($search, $replace, $map);
		return $map;
	}
	
	/**
	 * Removes inline style from maps div
	 * @param string $map
	 * @return string
	 */
	protected function removeStyleAttributeFromMaps($map) {
		$width = $this->controller->configModel->get('googleMaps.width');
		$height = $this->controller->configModel->get('googleMaps.height');
		$map = str_replace(' style="width:' . $width . 'px; height:'.$height.'px;"', '', $map);
		//debug($map);
		return $map;
	}
	
	/**
	 * Document view
	 * @return string	the content
	 */
	public function getDocument() {
		$this->addTranslationLabel('label_return');
		$this->addTranslationLabel('label_fieldheader_document');
		$this->addTranslationLabel('label_general');
		$this->addTranslationLabel('label_location');
		$subparts = array();
		$main = 'PERMIT_DOCUMENT_VIEW';
		$permitFields = $this->controller->permitsModel->getFieldList();
		$removeFields = array(
			'uid', 'pid', 'cruser_id', 'deleted', 'hidden', 'documenttypes', 'municipality', 'lastpublished', 'language',
			'type', 'link', 'objectaddresses', 'lots'
		);
		$permitFields = array_diff($permitFields, $removeFields);
		foreach($permitFields as $field) {
			$this->addTranslationLabel('label_fieldheader_' . $field);
		}
		$configBasePath = 'viewPermitDocument.';
		$permitIndex = 0;
		$currentDocument = false;
		$record = $this->controller->permitsModel->getRecord();
		if($record !== false) {
			$record = $this->controller->permitsModel->getRecord();
			foreach($permitFields as $field) {
				$content = $this->getFieldWrap(
					$configBasePath,
					$field,
					$record,
					$this->controller->permitsModel->getTableName()
				);
				$permit['PERMIT_' . strtoupper($field)] = $content;
			}
			$permit['PERMIT_LOCATION'] = $this->controller->permitsModel->getAddress();
			if(tx_nclib::isLoopable($record['documents'])) {
				foreach($record['documents'] as $index => $document) {
					if($this->controller->getPiVar('doc') === md5($document)) {
						$currentDocument = $document;
						$doctype = $record['documenttypes'][$index];
						$permit['PERMIT_DOCUMENT'] = $this->controller->getLinkToFile(
							$doctype,
							$this->controller->getLinkToItemFileUrl(
								'documents', $document
							)
						);
						$permit['PERMIT_DOCUMENTTYPE'] = $doctype;

						$permit['PERMIT_DOWNLOAD'] = $this->controller->getLinkToFile(
							$this->getTranslatedLabel('label_download'),
							$this->controller->getLinkToItemFileUrl(
								'documents', $document
							)
						);
						break;
					}
				}
			}
			$permit['PERMIT_TITLE'] = $this->controller->permitsModel->getTitle();
			$permit['PERMIT_LINK'] = $this->controller->getHtmlCompliantLinkToController(false, false,
				array('id' => $record['uid']),
				false
			);
			$subparts = $permit;
		} else {
			$subparts['PERMIT_DOCUMENT_DETAILS'] = array(
				$this->getTranslatedLabel('permit_not_found')
			);
		}
		$this->addMetaDataDocument($currentDocument);

		$content = $this->subpartReplaceRecursive(
			$subparts, $main, false, true, $this->controller->configModel->get('cleanRemainingMarkers')
		);
		return $content;
	}

	/**
	 * Document view - New interface - all months/years
         * Show filenames
	 * @return string	the content
	 */
	public function getDocumentAll() {
		$this->addTranslationLabel('label_return');
		$this->addTranslationLabel('label_fieldheader_document');
		$this->addTranslationLabel('label_general');
		$this->addTranslationLabel('label_location');
		$subparts = array();
		$main = 'PERMIT_DOCUMENT_VIEW_ALL';
		$permitFields = $this->controller->permitsModel->getFieldList();
		$removeFields = array(
			'uid', 'pid', 'cruser_id', 'deleted', 'hidden', 'documenttypes', 'municipality', 'lastpublished', 'language',
			'type', 'link', 'objectaddresses', 'lots'
		);
		$permitFields = array_diff($permitFields, $removeFields);
		foreach($permitFields as $field) {
			$this->addTranslationLabel('label_fieldheader_' . $field);
		}
		$configBasePath = 'viewPermitDocument.';
		$permitIndex = 0;
		$currentDocument = false;
		$record = $this->controller->permitsModel->getRecord();
		if($record !== false) {
			$record = $this->controller->permitsModel->getRecord();
			foreach($permitFields as $field) {
				$content = $this->getFieldWrap(
					$configBasePath,
					$field,
					$record,
					$this->controller->permitsModel->getTableName()
				);
				$permit['PERMIT_' . strtoupper($field)] = $content;
			}
			$permit['PERMIT_LOCATION'] = $this->controller->permitsModel->getAddress();
			if(tx_nclib::isLoopable($record['documents'])) {
				foreach($record['documents'] as $index => $document) {
					if($this->controller->getPiVar('doc') === md5($document)) {
						$currentDocument = $document;
						//$doctype = $record['documenttypes'][$index];
                                                $doctype =  basename($document);
						$permit['PERMIT_DOCUMENT'] = $this->controller->getLinkToFile(
							$doctype,
							$this->controller->getLinkToItemFileUrl(
								'documents', $document
							)." _blank"
						);

                                                //echo t3lib_div::getFileAbsFileName($document);
                                                //echo basename($document);
                                                $permit['PERMIT_FILENAME'] = basename($document);
                                                
						$permit['PERMIT_DOCUMENTTYPE'] = $doctype;

						$permit['PERMIT_DOWNLOAD'] = $this->controller->getLinkToFile(
							$this->getTranslatedLabel('label_download'),
							$this->controller->getLinkToItemFileUrl(
								'documents', $document
							)
						);
						break;
					}
				}
			}
			$permit['PERMIT_TITLE'] = $this->controller->permitsModel->getTitle();
			$permit['PERMIT_LINK'] = $this->controller->getHtmlCompliantLinkToController(false, false,
				array('id' => $record['uid']),
				false
			);
			$subparts = $permit;
		} else {
			$subparts['PERMIT_DOCUMENT_DETAILS'] = array(
				$this->getTranslatedLabel('permit_not_found')
			);
		}
		$this->addMetaDataDocument($currentDocument);

		$content = $this->subpartReplaceRecursive(
			$subparts, $main, false, true, $this->controller->configModel->get('cleanRemainingMarkers')
		);
		return $content;
	}
        
        
        
	/**
	 * Publication list
	 * @return string content
	 */
	public function getPublicationList() {
		$subparts = array();
		if($this->controller->permitsModel->getCount() > 0) {
			$permitIndex = 0;
			while ($this->controller->permitsModel->hasNextRecord()) {
				$permit = array();
				$record = $this->controller->permitsModel->getRecord();
				$permit['FIELD_PUBLISHDATE'] = $this->controller->permitsModel->getField('publishdate');
				$permit['FIELD_TITLE'] = $this->controller->permitsModel->getTitle();
				$permit['FIELD_ADDRESS'] = $this->controller->permitsModel->getAddress();
				$permit['FIELD_LINK'] = $this->controller->getHtmlCompliantLinkToController(
					false,
					$this->controller->configModel->get('displayPage'),
					array(
						'id' => $this->controller->permitsModel->getId(),
						'activeMonth' => date('m',$this->controller->permitsModel->getField('publishdate', true)),
						'activeYear' => date('Y', $this->controller->permitsModel->getField('publishdate', true))
					),
					false
				);
				$subparts['RECORDS'][$permitIndex] = $permit;
				$permitIndex++;
				$this->controller->permitsModel->moveToNextRecord();
			}
		} else {
			// empty page
			$subparts['RECORDS_AVAILABLE'] = array();
		}
		$content = $this->subpartReplaceRecursive(
			$subparts, 'PUBLICATION_PUBLISHLIST_VIEW', false, true, $this->controller->configModel->get('cleanRemainingMarkers')
		);
		return $content;
	}

	/**
	 * Creates year - month(- week) filter for use in the front-end.
	 * @return array
	 */
	function getDateFilter() {
		$aData = $this->controller->dateFilter;
		$aResult = array();

		$aYears = range($aData['iStartYear'], $aData['iEndYear']);
		$aMonths = range(1, 12);
		$aWeeks = range($aData['iStartWeek'], $aData['iEndWeek']);

		if(tx_nclib::isLoopable($aYears)) {
			foreach($aYears as $iIndex=>$iYear) {
				if($iYear == $aData['iActiveYear']) {
					$aResult['YEARS'][$iIndex]['YEAR'] = $iYear;
					$aResult['YEARS'][$iIndex]['ACTIVE'] = 'active';
				} else {
					$aResult['YEARS'][$iIndex]['YEAR'] =
						$this->controller->getLinkToFilteredResult($iYear, array('activeYear' => $iYear));
					$aResult['YEARS'][$iIndex]['ACTIVE'] = 'inactive';
				}
			}
		} else {
			$aResult['YEARS'] = array();
		}

		if(tx_nclib::isLoopable($aMonths)) {
			foreach($aMonths as $iIndex=>$iMonth) {
				$sMonth = $this->getTranslatedLabel('label_month_' . $iMonth);
				if($iMonth == $aData['iActiveMonth']) {
					$aResult['MONTHS'][$iIndex]['MONTH'] = $sMonth;
					$aResult['MONTHS'][$iIndex]['ACTIVE'] = 'active';
				} else {
					if($iMonth > $aData['iCurrentMonth'] && $aData['bCurrentYearActive']) {
					//	$aResult['MONTHS'][$iIndex]['MONTH'] = $sMonth;
						$aResult['MONTHS'][$iIndex]['MONTH'] =
							$this->controller->getLinkToFilteredResult($sMonth, array('activeYear' => $aData['iActiveYear'], 'activeMonth' => $iMonth));
					} else {
						$aResult['MONTHS'][$iIndex]['MONTH'] =
							$this->controller->getLinkToFilteredResult($sMonth, array('activeYear' => $aData['iActiveYear'], 'activeMonth' => $iMonth));
					}
					$aResult['MONTHS'][$iIndex]['ACTIVE'] = 'inactive';
				}
			}
		} else {
			$aResult['MONTHS'] = array();
		}

		if(tx_nclib::isLoopable($aWeeks)) {
			foreach($aWeeks as $iIndex=>$iWeek) {
				$sWeek = $this->getTranslatedLabel('label_week') . ' ' . $iWeek;
				if($iWeek == $aData['iActiveWeek']) {
					$aResult['WEEKS'][$iIndex]['WEEK'] = $sWeek;
					$aResult['WEEKS'][$iIndex]['ACTIVE'] = 'active';
				} else {
					if($iWeek > $aData['iCurrentWeek'] && $aData['bCurrentYearActive']) {
						//$aResult['WEEKS'][$iIndex]['WEEK'] = $sWeek;
						$aResult['WEEKS'][$iIndex]['WEEK'] =
							$this->controller->getLinkToFilteredResult($sWeek, array('activeYear' => $aData['iActiveYear'], 'activeMonth' => $aData['iActiveMonth'], 'activeWeek' => $iWeek));
					} else {
						$aResult['WEEKS'][$iIndex]['WEEK'] =
							$this->controller->getLinkToFilteredResult($sWeek, array('activeYear' => $aData['iActiveYear'], 'activeMonth' => $aData['iActiveMonth'], 'activeWeek' => $iWeek));
					}
					$aResult['WEEKS'][$iIndex]['ACTIVE'] = 'inactive';
				}
			}
		} else {
			$aResult['WEEKS'] = array();
		}
		return $aResult;
	}
	/**
	 * Creates year - month(- week) filter for use in the front-end - New interface - all months/years
	 * @return array
	 */
	function getDateFilterAll() {
		$aData = $this->controller->dateFilter;
		$aResult = array();

		$aWeeks = range($aData['iStartWeek'], $aData['iEndWeek']);


		if(tx_nclib::isLoopable($aWeeks)) {
			foreach($aWeeks as $iIndex=>$iWeek) {
				$sWeek = $this->getTranslatedLabel('label_week') . ' ' . $iWeek;
				if($iWeek == $aData['iActiveWeek']) {
					$aResult['WEEKS'][$iIndex]['WEEK'] = $sWeek;
					$aResult['WEEKS'][$iIndex]['ACTIVE'] = 'active';
				} else {
					if($iWeek > $aData['iCurrentWeek'] && $aData['bCurrentYearActive']) {
						//$aResult['WEEKS'][$iIndex]['WEEK'] = $sWeek;
						$aResult['WEEKS'][$iIndex]['WEEK'] =
							$this->controller->getLinkToFilteredResult($sWeek, array('activeYear' => $aData['iActiveYear'], 'activeMonth' => $aData['iActiveMonth'], 'activeWeek' => $iWeek));
					} else {
						$aResult['WEEKS'][$iIndex]['WEEK'] =
							$this->controller->getLinkToFilteredResult($sWeek, array('activeYear' => $aData['iActiveYear'], 'activeMonth' => $aData['iActiveMonth'], 'activeWeek' => $iWeek));
					}
					$aResult['WEEKS'][$iIndex]['ACTIVE'] = 'inactive';
				}
			}
		} else {
			$aResult['WEEKS'] = array();
		}
		return $aResult;
	}

	/**
	 * Creates month filter for use in the front-end.
	 * @return array
	 */
	function getMonthFilter() {
		$aData = $this->controller->dateFilter;
		$aResult = array();

		$aMonths = range(1, 13);                
                $count = 1;
                
                
                if($this->controller->getPiVar('activeYear')!=9999)
                {
                    if($this->controller->getPiVar('activeMonth') != '') {
                            $actMonth = $this->controller->getPiVar('activeMonth');
                    }  else {
                            $actMonth = $aData['iActiveMonth'];
                    }  
                }else{
                   $actMonth = 13; 
                }
                
                if(tx_nclib::isLoopable($aMonths)) {
			foreach($aMonths as $iIndex=>$iMonth) {
				$sMonth = $this->getTranslatedLabel('label_month_' . $iMonth);
    				//$aResult['MONTHS'][$iIndex]['TITLE'] =
						//$this->controller->getLinkToFilteredResult($sMonth, array('activeYear' => $aData['iActiveYear'], 'activeMonth' => $iMonth));
                                $aResult['MONTHS'][$iIndex]['TITLE'] = $sMonth;
                                $aResult['MONTHS'][$iIndex]['VALUE'] = $count;	
                                $aResult['MONTHS'][$iIndex]['SELECTED'] = $actMonth == $iMonth ? ' selected="selected"' : '';	
                                //$aResult['MONTHS'][$iIndex]['SELECTED'] = $iMonth ? ' selected="selected"' : '';
                                $count++;
                                
                                if($productTypes[$index] == '')
                                    $result['PRODUCT_TYPES'][$index]['TITLE'] = htmlentities($this->getTranslatedLabel('label_type_'.
					($this->controller->permitsModel->getModelType() ==  tx_ncgovpermits_permits_model::TYPE_PERMIT ? 'permit' : 'publication')
				));                                
			}
		} else {
			$aResult['MONTHS'] = array();
		}
                
                
		return $aResult;
	}

	/**
	 * Creates year filter for use in the front-end. New interface - all months/years
	 * @return array
	 */
	function getYearFilter() {
		$aData = $this->controller->dateFilter;
		$aResult = array();

		$aYears = range($aData['iStartYear'], $aData['iEndYear']); 
                
                //add option - all - value 9999
                $aYears[] = 9999;
                
		if($this->controller->getPiVar('activeYear') != '') {
			$actYear = $this->controller->getPiVar('activeYear');
		}else {
                    
                    $actYear = $aData['iActiveYear'];
                }
                   
                
		if(tx_nclib::isLoopable($aYears)) {
			foreach($aYears as $iIndex=>$iYear) {
                                if ($iYear == 9999)
                                {
                                    $sYear = $this->getTranslatedLabel('label_year_' . $iYear);
                                    $aResult['YEARS'][$iIndex]['TITLE'] = $sYear;
                                }else{    
                                    $aResult['YEARS'][$iIndex]['TITLE'] = $iYear;
                                }
                                $aResult['YEARS'][$iIndex]['VALUE'] = $iYear;   	
                                $aResult['YEARS'][$iIndex]['SELECTED'] = $actYear ==$iYear ? ' selected="selected"' : '';
                        }
                }else{        
			$aResult['YEARS'] = array();
		}		
                
                
		return $aResult;
	}
                
        
	/**
	 * Creates fulltext filter for use in the front-end.
	 * @return array
	 */
	function getFulltextFilter() {
		$result = array();
		$result['VALUE'] = htmlentities($this->controller->getPiVar('fulltext'));
		$result['PLACEHOLDER'] = htmlentities($this->getTranslatedLabel('label_fulltext_placeholder'));
		return $result;
	}

	/**
	 * Creates product type filter for use in the front-end.
	 * @return array
	 */
	function getProductTypeFilter() {
		$productTypes = $this->controller->productTypeFilter;
		if(!is_array($productTypes) || !count($productTypes))
			return array();
		$result['PRODUCT_TYPES'] = array();
		array_unshift($productTypes, '');
		for($index=0; $index<count($productTypes); $index++) {
			// generate links on the server side instead of submitting a form, in order to be able to create cHashes and have proper caching!
			//$result['PRODUCT_TYPES'][$index]['VALUE'] = $this->controller->htmlEntitiesForQuotes( $this->controller->getURLToFilteredResult(array('productType' => $productTypes[$index])) );
			$result['PRODUCT_TYPES'][$index]['VALUE'] = $productTypes[$index];
			$result['PRODUCT_TYPES'][$index]['TITLE'] = htmlentities($productTypes[$index]);
			$result['PRODUCT_TYPES'][$index]['SELECTED'] = $this->controller->getPiVar('productType') == $productTypes[$index] ? ' selected="selected"' : '';

			if($productTypes[$index] == '')
				$result['PRODUCT_TYPES'][$index]['TITLE'] = htmlentities($this->getTranslatedLabel('label_type_'.
					($this->controller->permitsModel->getModelType() ==  tx_ncgovpermits_permits_model::TYPE_PERMIT ? 'permit' : 'publication')
				));
		}
		return $result;
	}

	/**
	 * Creates product type filter for use in the front-end.
	 * @return array
	 */
	function getTermTypeFilter() {
		$termTypes = $this->controller->termTypeFilter;
		if(!is_array($termTypes) || !count($termTypes))
			return array();
		$result['TERM_TYPES'] = array();
		array_unshift($termTypes, '');
		for($index=0; $index<count($termTypes); $index++) {
			// generate links on the server side instead of submitting a form, in order to be able to create cHashes and have proper caching!
			//$result['TERM_TYPES'][$index]['VALUE'] = $this->controller->htmlEntitiesForQuotes( $this->controller->getURLToFilteredResult(array('termType' => $termTypes[$index])) );
			$result['TERM_TYPES'][$index]['VALUE'] = $termTypes[$index];
			$result['TERM_TYPES'][$index]['TITLE'] = htmlentities($termTypes[$index]);
			$result['TERM_TYPES'][$index]['SELECTED'] = $this->controller->getPiVar('termType') == $termTypes[$index] ? ' selected="selected"' : '';

			if($termTypes[$index] == '')
				$result['TERM_TYPES'][$index]['TITLE'] = htmlentities($this->getTranslatedLabel('label_termtype'));
		}
		return $result;
	}

	/**
	 * Creates phase filter for use in the front-end.
	 * @return array
	 */
	function getPhaseFilter() {
		$phases = $this->controller->phaseFilter;
		if(!is_array($phases) || !count($phases))
			return array();
		$selectedPhases = $this->controller->getPiVar('phase');
		$result['PHASE_OPTIONS'] = array();
		for($index=0; $index<count($phases); $index++) {
			$phaseIsChecked = is_array($selectedPhases) && isset($selectedPhases[$index]) && $selectedPhases[$index] == 1;
			// generate links on the server side instead of submitting a form, in order to be able to create cHashes and have proper caching!
//			$newPhase = $this->controller->getPiVar('phase');
//			$newPhase[$phases[$index]] = !$phaseIsChecked;
//			$result['PHASE_OPTIONS'][$index]['URL'] = $this->controller->htmlEntitiesForQuotes( $this->controller->getURLToFilteredResult(array('phase' => $newPhase)) );
			$result['PHASE_OPTIONS'][$index]['NAME'] = $index;
			$result['PHASE_OPTIONS'][$index]['TITLE'] = htmlentities($phases[$index]);
			$result['PHASE_OPTIONS'][$index]['CHECKED'] = $phaseIsChecked ? ' checked="checked"' : '';
			$result['PHASE_OPTIONS'][$index]['ID'] = 'phase-'.htmlentities(str_replace(' ', '-', $phases[$index]));
		}
		return $result;
	}

	/**
	 * Creates postcode filter for use in the front-end.
	 * @return array
	 */
	function getPostcodeFilter() {
		$result = array();
		if($this->controller->configModel->get('regionSearch') || $this->controller->configModel->get('regionStringSearch')) {
			$result['LABEL'] = htmlentities($this->getTranslatedLabel('label_postcode'));
			$result['NUM_VALUE'] = htmlentities($this->controller->getPiVar('postcodeNum'));
			$result['NUM_PLACEHOLDER'] = htmlentities($this->getTranslatedLabel('label_postcodeNum_placeholder'));
			$result['ALPHA_VALUE'] = htmlentities($this->controller->getPiVar('postcodeAlpha'));
			$result['ALPHA_PLACEHOLDER'] = htmlentities($this->getTranslatedLabel('label_postcodeAlpha_placeholder'));
			$result['SUBMIT_LABEL'] = htmlentities($this->getTranslatedLabel('label_postcode_submit'));
			$radius = $this->controller->getPiVar('radius');
			$radiusOptions = $this->controller->configModel->get('searchRadiusOptions');
			if (empty($radiusOptions)) {
				$radiusOptions = '5';
			}
			$arrOptions = t3lib_div::trimExplode(',', $radiusOptions);
			$optionsIndex = 0;
			foreach ($arrOptions as $valOption) {
				$option['OPTION_VALUE'] = $valOption;
				$option['OPTION_SELECTED'] = $valOption == $radius ? ' selected="selected"' : '';
				$option['OPTION_TEXT'] = $valOption . ' km';
				$result['OPTIONS'][$optionsIndex] = $option;
				$optionsIndex++;
			}
		}
		return $result;
	}
	

	/**
	 * Adds the metadata for the record
	 * @return void
	 */
	public function addMetaDataRecord() {
		$lines = $this->getMetaDataRecord();
		$this->addMetaDataToPageHeader($lines);
	}

	/**
	 * function writes headerlines to page
	 * @param lines		array	containing the lines = array(array(name, content, scheme), ...);
	 * @return void
	 */
	protected function addMetaDataToPageHeader($lines) {
		foreach($lines as $line) {
			if($line['meta'] !== false) {
				$name = '';
				$content = '';
				$scheme = '';
				list($name, $content, $scheme) = $line['meta'];
				$this->addDublinCoreMetaDataHeaderLine($name, $content, $scheme);
			}
		}
	}

	/**
	 * Adds owms data to the page header, for the detail view of a document
	 * @return void
	 */
	public function getMetaDataRecord($isDocument=false) {
		$lines = array();
		if($this->controller->permitsModel->isPermit() || empty($link)) {
			$link = $this->getDublinCoreMetaData('identifier',
				tx_nclib_tsfe_model::getBaseUrl() .
				$this->controller->getLinkToController(
					false,
					$this->controller->configModel->get('displayPage'),
					array('id' => $this->controller->permitsModel->getId())
				)
			);
			$lines[] = $link;
		} else {
			$link = $this->controller->getLinkToController(false, $link);
			if(strpos($link, 'http://') === false) {
				$link = tx_nclib_tsfe_model::getBaseUrl() .
					$this->controller->getLinkToController(false, $link);
			}
			$lines[] = $this->getDublinCoreMetaData('identifier', $link);
		}
		if((boolean)$this->controller->configModel->get('convertLatinToUtf8') == true) {
			$title = $this->convertLatinToUtf8($this->controller->permitsModel->getTitle());
		} else {
			$title = $this->controller->permitsModel->getTitle();
		}
		$lines[] = $this->getDublinCoreMetaData('title',
			str_replace(
				'&', '&amp;',
				str_replace(
					'&amp;', '&',
					htmlentities(
						$title,
						ENT_COMPAT,
						$this->controller->configModel->get('htmlEntitiesCharset')
					)
				)
			)
		);
		$lines[] = $this->getDublinCoreMetaData('language', 'nl');
		$lines[] = $this->getDublinCoreMetaData('informatietype', $this->controller->configModel->get('owmsDefaults.informationType'));

		$lines[] = $this->getDublinCoreMetaData('creator',
			$this->controller->configModel->get('owmsDefaults.municipality'),
			$this->controller->configModel->get('owmsDefaults.creatorType')
		);
		if(!$isDocument) {
			$lines[] = $this->getDublinCoreMetaData('references', $this->controller->permitsModel->getField('casereference'));
			$documents = $this->controller->permitsModel->getField('documents');
			$types = $this->controller->permitsModel->getField('documenttypes');
			if(tx_nclib::isLoopable($documents)) {
				foreach($documents as $index=>$document) {
					$lines[] = $this->getDublinCoreMetaData(
						'hasPart',
						$types[$index],
						tx_nclib_tsfe_model::getBaseUrl()
						. $this->controller->getLinkToController(
							false,
							$this->controller->configModel->get('displayPage'),
							array('id' => $this->controller->permitsModel->getId(), 'doc' => md5($document))
						)
					);
				}
			}
			
			$publications = $this->controller->permitsModel->getField('publications');
			if (tx_nclib::isLoopable($publications)) {
				foreach($publications as $publication) {
					$lines[] = $this->getDublinCoreMetaData(
						'hasPart', 
						$publication['title'],
						tx_nclib_tsfe_model::getBaseUrl()
						. $this->controller->getLinkToController(
							false,
							$this->controller->configModel->get('publicationsDisplayPage'),
							array('id' => $publication['uid'])
						)
					);
				}
			}
		}
		$lines[] = $this->getDublinCoreMetaData('description', $this->controller->permitsModel->getField('description'));
		$lines[] = $this->getDublinCoreMetaData('format', 'text/html');
		$link = $this->controller->permitsModel->getField('link');
		if(!$this->controller->permitsModel->isPermit()) {
			$lines[] = $this->getDublinCoreMetaData(
				'pbinformatietype',
				$this->controller->permitsModel->getField('producttype')
			);
			$lines[] = $this->getDublinCoreMetaData(
				'publishdate',
				date(
					$this->controller->configModel->get('owmsDefaults.dateFormat'),
					$this->controller->permitsModel->getField('publishdate', true)
				)
			);
		}
		$lines[] = $this->getDublinCoreMetaData('modified', $this->controller->permitsModel->getField('tstamp', true));
		// references ???
		$addresses = $this->controller->permitsModel->getField('objectaddresses');
		if(tx_nclib::isLoopable($addresses)) {
			foreach($addresses as $index => $address) {
				$spatialType = $this->controller->permitsModel->getOwmsSpatialType($index);
				if($spatialType !== false) {
					$lines[] = $this->getDublinCoreMetaData('spatial',
						$this->controller->permitsModel->getOwmsSpatialValue($index),
						$spatialType
					);
				}
			}
		} else {
			$lines[] = $this->getDublinCoreMetaData('spatial',
				$this->controller->configModel->get('owmsDefaults.municipality'),
				'Gemeente'
			);
		}

		$temporal = false;
		if($this->controller->permitsModel->getField('validity_start') != 0) {
			$temporal = sprintf(
				'start=%s; ',
				date(
					$this->controller->configModel->get('owmsDefaults.dateFormat'),
					$this->controller->permitsModel->getField('validity_start', true)
				)
			);
		}
		if($this->controller->permitsModel->getField('validity_end') != 0) {
			$temporal .= sprintf(
				'end=%s;',
				date(
					$this->controller->configModel->get('owmsDefaults.dateFormat'),
					$this->controller->permitsModel->getField('validity_end', true)
				)
			);
		}
		if($temporal !== false) {
			$lines[] = $this->getDublinCoreMetaData('temporal', $temporal);
		}
		$lines[] = $this->getDublinCoreMetaData('organisationType', $this->controller->configModel->get('owmsDefaults.organisationType'));
		return $lines;
	}

	/**
	 * Adds owms metadata for permit document to page header
	 * @return void
	 */
	public function addMetaDataDocument($document) {
		$lines = $this->getMetaDataDocument($document);
		$this->addMetaDataToPageHeader($lines);
	}

	/**
	 * Returns owms data lines for the header / xml, for the document view
	 * @param $document
	 * @return array	lines containing $name, $content, $scheme
	 */
	public function getMetaDataDocument($document) {
		$lines = $this->getMetaDataRecord();
		$lines = array_merge_recursive($lines, $this->getDublinCoreMetaData(
			'isPartOf',
			tx_nclib_tsfe_model::getBaseUrl() .
			$this->controller->getLinkToController(
				false,
				$this->controller->configModel->get('displayPage'),
				array('id' => $this->controller->permitsModel->getId())
			)
		));
		$lines = array_merge_recursive($lines, $this->getDublinCoreMetaData(
			'hasPart',
			tx_nclib_tsfe_model::getBaseUrl() .
			$this->controller->getLinkToController(
				false,
				$this->controller->getLinkToItemFileUrl(
					'documents', $document
				)
			)
		));
		return $lines;
	}

	/**
	 * Adds dublin core metadata to the page header
	 * @param $field
	 * @param $value
	 * @param $schemeValue
	 * @return void
	 */
	public function getDublinCoreMetaData($field, $value, $schemeValue=false) {
		// %s = fieldtitle
		$fieldTypes = array();
		$fieldTypes['meta'] = array(
			'creator' => 			array('DC.%s', 				'OVERHEID.' . $schemeValue),
			'description'	=>		array('DC.%s', 				false),
			'format'	=>			array('DC.%s', 				'DCTERMS.IMT'),
			'hasPart' => 			array('DCTERMS.%s', 		false),
			'references' => 		array('DCTERMS.%s', 		'DCTERMS.URI'),
			'identifier' =>			array('DC.identifier', 		'DCTERMS.URI'),
			'isPartOf' => 			array('DCTERMS.%s', 		'DCTERMS.URI'),
			'language' => 			array('DC.%s', 				'DCTERMS.RFC3066'),
			'modified' => 			array('DCTERMS.modified', 	false),
			'spatial' => 			array('DCTERMS.%s', 		'OVERHEID.' . $schemeValue),	// is dit niet afhankelijk van de inhoud?
			'temporal' => 			array('DCTERMS.Temporal', 	'DCTERMS.Period'),
			'title' => 				array('DC.%s', 				false),
			'organisationType' => 	array('OVERHEID.%s', 		'OVERHEID.organisatietype'),
			'informatietype' => 	array('DC.type', 			'OVERHEID.informatietype'),
			'pbinformatietype' => 	array('DC.type', 			'OVERHEIDbm.bekendmakingtypeGemeente'),
			'publishdate' => 		array('DCTERMS.available', 	'DCTERMS.W3CDTF'),
		//dcterms:references (Referenties)
		//'dossiertype' => 		array('DC.type', 'OVERHEIDvg.%s'),
			//'phase'	=>				array('OVERHEIDvg.status', 'OVERHEIDvg.status'),
			//'vergunninghouder' => 	array('OVERHEIDvg.vergunninghouder', false),
		// que?
			//'documenttype'	=>		array('DC.type', 'OVERHEIDvg.%s'),
			//'references'	=>		array('DCTERMS.references', false), // nodig?
		);
		$fieldTypes['xml'] = array(
			'creator' => 			array('dcterms:%s', 		'overheid:' . $schemeValue, 	'owmskern'),
			'description'	=>		array('dcterms:%s', 		false, 							'owmsmantel'),
			//'format'	=>			array('DC.%s', 				'DCTERMS.IMT', 					false),
			'hasPart' => 			array('dcterms:%s', 		$schemeValue, 					'owmsmantel'),
			'identifier' =>			array('dcterms:%s', 		false,		 					'owmskern'),
			'isPartOf' => 			array('dcterms:%s', 		$schemeValue, 					'owmsmantel'),
			'language' => 			array('dcterms:%s', 		false, 							'owmskern'),
			'modified' => 			array('dcterms:%s', 		false, 							'owmskern'),
			'spatial' => 			array('dcterms:%s', 		'overheid:' . $schemeValue, 	'owmskern'),	// is dit niet afhankelijk van de inhoud?
			'references' => 		array('dcterms:%s', 		false, 							'owmsmantel'),
		//'temporal' => 			array('dcterms:%s', 		false,			 				'owmskern'),
			'title' => 				array('dcterms:%s', 		false, 							'owmskern'),
//			'organisationType' => 	array('OVERHEID.%s', 		'OVERHEID.organisatietype', 	false),
			'informatietype' => 	array('dcterms:type', 		'overheid:Informatietype', 		'owmskern'),
		);

		switch($field) {
			case 'language':
				$value = 'nl';
				break;
			case 'description':
				if((boolean)$this->controller->configModel->get('convertLatinToUtf8') == true) {
					$value = $this->convertLatinToUtf8($value);
				}
				$value = str_replace(
					'&', '&amp;',
					str_replace(
						'&amp;', '&',
						htmlentities(strip_tags($value), ENT_COMPAT, $this->controller->configModel->get('htmlEntitiesCharset'))
					)
				);
				break;
			case 'modified':
				$value = $this->controller->permitsModel->getField('tstamp', true);
				$value = date($this->controller->configModel->get('owmsDefaults.dateFormat'), $value);
				break;
			case 'informatietype':
				$value = $this->controller->configModel->get('owmsDefaults.informationType');
				break;
		}
		$result = false;
		if(isset($fieldTypes['meta'][$field])) {
			// add meta only for known fields
			$metaName = $fieldTypes['meta'][$field][0];
			$metaName = sprintf($metaName, $field);
			$metaScheme = $fieldTypes['meta'][$field][1];
			/*if($metaScheme !== false) {
				$metaScheme = sprintf($metaScheme, $field);
			}*/
			$xmlNode = $fieldTypes['xml'][$field][2];
			$xmlName = $fieldTypes['xml'][$field][0];
			$xmlName = sprintf($xmlName, $field);
			$xmlScheme = $fieldTypes['xml'][$field][1];
			/*if($xmlScheme !== false) {
				$xmlScheme = sprintf($xmlScheme, $field);
			}*/
			$result = array(
				'meta' => array($metaName, $value, $metaScheme),
				'xml' => array($xmlName, $value, $xmlScheme, $xmlNode)
			);
		}
		return $result;
	}

	/**
	 * Actually adds the line to the header
	 * @param $name
	 * @param $content
	 * @param $scheme
	 * @return void
	 */
	public function addDublinCoreMetaDataHeaderLine($name, $content, $scheme = false) {
		$content = htmlentities($content);
		if(!empty($name) && !empty($content)) {
			if($scheme === false) {
				$this->addHeaderLine(sprintf('<meta name="%s" content="%s" />', $name, $content));
			} else {
				$this->addHeaderLine(sprintf('<meta name="%s" scheme="%s" content="%s" />', $name, $scheme, $content));
			}
		}
	}

	/**
	 * Returns the xmls for the currently active permit model record.
	 * @return array
	 */
	public function getPermitXmls() {
		$xmls = array();
		$xmls[$this->controller->getCaseFileName()] = $this->getPermitXml(0);
        $publishPermitDocuments = $this->controller->configModel->get('publishPermitDocuments');
        if ($publishPermitDocuments == true){
            $documents = $this->controller->permitsModel->getField('documents');
            if(tx_nclib::isLoopable($documents)) {
                foreach($documents as $index => $document) {
                    $xmls[$this->controller->getCaseFileName($index)] = $this->getPermitDocumentXml($index+1);
                }
            }
        }
		return $xmls;
	}

	/**
	 * Returns permit document xml info
	 * @param $document	the document being generated
	 * @param $index	the index of the document in the current record
	 * @return string	the xml
	 */
	public function getPermitDocumentXml($index) {
		list($dom, $permitmeta, $mantle) = $this->getPermitXmlCaseHeader(
			'vergunningdocument',
			'http://standaarden.overheid.nl/vergunningen/4.0/document',
			$index,
			true
		);
		$filetype = $this->controller->permitsModel->getFileTypeFromDocument($index-1);
		$mantle->appendChild(
			$dom->createElement('dcterms:format', $filetype)
		);
		$part = $dom->createElement(
			'dcterms:isPartOf', 'Vergunning'
		);
		$part->setAttribute(
			'resourceIdentifier',
			str_replace(
				'&', '&amp;',
				tx_nclib_tsfe_model::getBaseUrl() . $this->controller->getLinkToController(
					false,
					$this->controller->configModel->get('displayPage'),
					array('id' => $this->controller->permitsModel->getId())
				)
			)
		);
		$mantle->appendChild($part);
		$documenttypes = $this->controller->permitsModel->getField('documenttypes');
		$dt = $dom->createElement('documenttype', $documenttypes[$index-1]);
		$dt->setAttribute('scheme', 'overheidvg:Documenttype');
		$permitmeta->appendChild(
			$dt
		);
		return $dom->saveXML();
	}

	/**
	 *
	 * @param $type
	 * @param $namespace
	 * @param $index
	 * @param $isDocument
	 * @return unknown_type
	 */
	protected function getPermitXmlCaseHeader($type, $namespace, $index, $isDocument = false) {
		$dom = new DOMDocument('1.0', 'UTF-8');
		$dom->formatOutput = true;
		$message = $dom->createElementNS('http://standaarden.overheid.nl/vergunningen/4.0/', 'message');
		// set all relevant namespaces
		if($isDocument) {
			$message->setAttributeNS(
				'http://www.w3.org/2001/XMLSchema-instance',
				'xsi:schemaLocation',
				'http://standaarden.overheid.nl/vergunningen/4.0/ http://standaarden.overheid.nl/vergunningen/4.0/xsd/vergunningdocument.xsd'
			);
		} else {
			$message->setAttributeNS(
				'http://www.w3.org/2001/XMLSchema-instance',
				'xsi:schemaLocation',
				'http://standaarden.overheid.nl/vergunningen/4.0/ http://standaarden.overheid.nl/vergunningen/4.0/xsd/vergunningzaak.xsd'
			);
		}
		$message->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:dcterms', 'http://purl.org/dc/terms/');
		$message->setAttributeNS(
			'http://www.w3.org/2000/xmlns/',
			'xmlns:overheidvg',
			'http://standaarden.overheid.nl/vergunningen/4.0/'
		);
		$message->setAttributeNS(
			'http://www.w3.org/2000/xmlns/',
			'xmlns:overheid',
			'http://standaarden.overheid.nl/owms/terms/'
		);
		$message->setAttribute('owms-version', '3.5'); // actually 4.0?
		$dom->appendChild($message);

		$message->appendChild(
			$dom->createElement('transactietype', $this->controller->permitsModel->getTransactionType())
		);
		$message->appendChild(
			$dom->createElement('transactieID', $this->controller->permitsModel->getTransactionId())
		);
		$message->appendChild(
			$dom->createElement('volgnummer', $index)
		);
		$message->appendChild(
			$dom->createElement(
				'aanmaakdatum',
				date(
					$this->controller->configModel->get('owmsDefaults.dateFormat'),
					$this->controller->permitsModel->getField('crdate', true)
				)
			)
		);
		$permitcase = $dom->createElementNS(
			$namespace,
			$type,
			''
		);
		$message->appendChild($permitcase);
		$meta = $dom->createElement('meta');
		$permitcase->appendChild($meta);

		$core = $dom->createElement('owmskern', '');
		$meta->appendChild($core);
		$mantle = $dom->createElement('owmsmantel', '');
		$meta->appendChild($mantle);
		$permitmeta = $dom->createElement('vergunningenmeta', '');
		$meta->appendChild($permitmeta);
		$lines = $this->getMetaDataRecord($isDocument);
		foreach($lines as $key => $line) {
			$name = $vlaue = $scheme = $type = '';
			list($name, $value, $scheme, $type) = $line['xml'];
			if($name == 'dcterms:creator' && $isDocument) {
				$scheme = 'overheid:'.ucfirst($value = $this->controller->configModel->get('owmsDefaults.creatorType'));
				$value = $this->controller->configModel->get('owmsDefaults.creator');
			}
			if($name == 'dcterms:identifier' && $isDocument) {
				$documents = $this->controller->permitsModel->getField('documents');
				$value = tx_nclib_tsfe_model::getBaseUrl() . $this->controller->getLinkToController(
					false,
					$this->controller->configModel->get('displayPage'),
					array('id' => $this->controller->permitsModel->getId(), 'doc' => md5($documents[$index-1]))
				);
			}
			if($name == 'dcterms:identifier') {
				$value = htmlentities($value);
			}
			if($name == 'dcterms:description' && $isDocument) {
				$value = $this->controller->permitsModel->getField('documenttypes');
				$value = $value[$index-1];
			}
			switch($type) {
				case 'owmskern':
					$child = $dom->createElement($name, $value);
					if($scheme !== false) {
						$child->setAttribute('scheme', $scheme);
					}
					$core->appendChild($child);
					break;
				case 'owmsmantel':
					$child = $dom->createElement($name, $value);
					if($name == 'dcterms:references') {
						$child->setAttribute('resourceIdentifier', '');
					} elseif($name == 'dcterms:hasPart') {
						$child->setAttribute('resourceIdentifier', $scheme);
					} elseif($scheme !== false) {
						$child->setAttribute('scheme', $scheme);
					}
					$mantle->appendChild($child);
					break;
				case 'vergunningenmeta':
					$child = $dom->createElement($name, $value);
					if($scheme !== false) {
						$child->setAttribute('scheme', $scheme);
					}
					$permitmeta->appendChild($child);
					break;
			}
		}
		$temporal = $dom->createElement('dcterms:temporal', '');
		$addTemporal = false;
		if($this->controller->permitsModel->getField('validity_start') != 0) {
			$start = $dom->createElement('start',
				date(
					$this->controller->configModel->get('owmsDefaults.dateFormat'),
					$this->controller->permitsModel->getField('validity_start', true)
				)
			);
			$start->setAttribute(
				'xmlns',
				''
			);
			$addTemporal = true;
			$temporal->appendChild(
				$start
			);
		}
		if($this->controller->permitsModel->getField('validity_end') != 0) {
			$end  = $dom->createElement('end',
				date(
					$this->controller->configModel->get('owmsDefaults.dateFormat'),
					$this->controller->permitsModel->getField('validity_end', true)
				)
			);
			$end->setAttribute(
				'xmlns',
				''
			);
			$addTemporal = true;
			$temporal->appendChild($end);
		}
		if($addTemporal === true) {
			$core->appendChild($temporal);
		}

		return array(&$dom, &$permitmeta, &$mantle);
	}

	/**
	 * Returns the xml to publish a permit
	 * @return string	the xml
	 */
	public function getPermitXml($index) {
		list($dom, $permitmeta, $mantle) = $this->getPermitXmlCaseHeader(
			'vergunningzaak',
			'http://standaarden.overheid.nl/vergunningen/4.0/zaak',
			$index
		);
		$product = $dom->createElement('product', '');
		$permitmeta->appendChild($product);
		$child = $dom->createElement('producttype', $this->controller->permitsModel->getField('producttype'));
		$child->setAttribute('scheme', 'overheidvg:Product');
		$product->appendChild($child);
		$activities = $this->controller->permitsModel->getField('productactivities');
		if(!empty($activities)) {
			$activities = t3lib_div::trimExplode(',', $activities);
			if(tx_nclib::isLoopable($activities)) {
				foreach($activities as $index=>$type) {
					$child = $dom->createElement('activiteit', $type);
					$child->setAttribute('scheme', 'overheidvg:Activiteit');
					$product->appendChild($child);
				}
			}
		}
		$term = $dom->createElement('termijn', '');
		$permitmeta->appendChild($term);
		$child = $dom->createElement('fase', $this->controller->permitsModel->getField('phase'));
		$term->appendChild($child);
		$termType = trim($this->controller->permitsModel->getField('termtype'));
		if(!empty($termType)) {
			$termTypePeriod = $dom->createElement('termijnsoortPeriode', '');
			$term->appendChild($termTypePeriod);
			$child = $dom->createElement('termijnsoort', $termType);
			$termTypePeriod->appendChild($child);
			$child = $dom->createElement(
				'startdatumTermijn',
				date(
					$this->controller->configModel->get('owmsDefaults.dateFormat'),
					$this->controller->permitsModel->getField('termtype_start', true)
				)
			);
			$termTypePeriod->appendChild($child);
			$child = $dom->createElement(
				'einddatumTermijn',
				date(
					$this->controller->configModel->get('owmsDefaults.dateFormat'),
					$this->controller->permitsModel->getField('termtype_end', true)
				)
			);
			$termTypePeriod->appendChild($child);
		}
		$company = $this->controller->permitsModel->getField('company');
		if(!empty($company)) {
			$actor = $dom->createElement('actor', '');
			$permitmeta->appendChild($actor);
			if((boolean)$this->controller->configModel->get('convertLatinToUtf8') == true) {
				$company = $this->convertLatinToUtf8($company);
			}
			$child = $dom->createElement('bedrijfsnaam',
				str_replace(
					'&', '&amp;',
					str_replace(
						'&amp;', '&',
						htmlentities($company, ENT_COMPAT, $this->controller->configModel->get('htmlEntitiesCharset'))
					)
				)
			);
			$actor->appendChild($child);
			$value = $this->controller->permitsModel->getOwmsCompanyAddress();
			if(!empty($value)) {
				$child = $dom->createElement('bedrijfsadres', $value);
				$actor->appendChild($child);
				$child->setAttribute('scheme', 'overheid:PostcodeHuisnummer');
			}
			/* 20091102 ncfrans - not needed anymore?
			$value = $this->controller->permitsModel->getField('companynumber');
			if(!empty($value)) {
				$child = $dom->createElement('bedrijfsnummer', $value);
				$actor->appendChild($child);
			}
			*/
		}
		$object = $dom->createElement('object', '');
		$permitmeta->appendChild($object);
		if($this->controller->permitsModel->getField('objectreference') != '') {
			$child = $dom->createElement('referentienummer', $this->controller->permitsModel->getField('objectreference'));
			$object->appendChild($child);
		}
		$appendAddress = false;
		$addresses = $this->controller->permitsModel->getField('objectaddresses');
		if(tx_nclib::isLoopable($addresses)) {
			foreach($addresses as $adresIndex=>$adres) {
				$address = $dom->createElement('adres', '');
				$addresses[$adresIndex]['zipcode'] = trim($addresses[$adresIndex]['zipcode']);
				if(!empty($addresses[$adresIndex]['zipcode'])) {
					$zip = $dom->createElement('postcodeHuisnummer', '');
					$address->appendChild($zip);
					$zip->appendChild(
						$dom->createElement('postcode', str_replace(' ', '', strtoupper($addresses[$adresIndex]['zipcode'])))
					);
					$zip->appendChild(
						$dom->createElement('huisnummer', $addresses[$adresIndex]['addressnumber'])
					);
					if($addresses[$adresIndex]['addressnumberadditional'] != '') {
						$zip->appendChild(
							$dom->createElement(
								'huisnummertoevoeging',
								$addresses[$adresIndex]['addressnumberadditional']
							)
						);
					}

				} elseif($addresses[$adresIndex]['city'] != '') {
					$addressCity = $dom->createElement('woonplaatsAdres', '');
					$addressCity->appendChild(
						$dom->createElement(
							'woonplaats',
							$addresses[$adresIndex]['city']
						)
					);
					$address->appendChild($addressCity);
				} elseif($addresses[$adresIndex]['municipality'] != '') {
					$municipality = $dom->createElement(
						'gemeente',
						$addresses[$adresIndex]['municipality']
					);
					$address->appendChild($municipality);
				} elseif($addresses[$adresIndex]['province'] != '') {
					$municipality = $dom->createElement(
						'provincie',
						$addresses[$adresIndex]['province']
					);
					$address->appendChild($municipality);
				}
				$object->appendChild($address);
			}
		}
		$lots = $this->controller->permitsModel->getField('lots');
		if(tx_nclib::isLoopable($lots)) {
			foreach($lots as $lotIndex=>$lot) {
				$lotXml = $dom->createElement('perceel');
				$child = $dom->createElement(
					'kadastralegemeente',
					$lot['cadastremunicipality']
				);
				$child->setAttribute('scheme', 'overheid:KadastraleGemeente');
				$lotXml->appendChild($child);
				$child = $dom->createElement(
					'sectie',
					$lot['section']
				);
				$lotXml->appendChild($child);
				$child = $dom->createElement(
					'nummer',
					$lot['number']
				);
				$lotXml->appendChild($child);
				$object->appendChild($lotXml);
			}
		}
		$coordinates = $this->controller->permitsModel->getField('coordinates');
		if(tx_nclib::isLoopable($coordinates)) {
			foreach($coordinates as $coordinateIndex=>$coordinate) {
				$child = $dom->createElement(
					'coordinaten',
					''
				);
				$child->appendChild($dom->createElement(
					'x-waarde',
					intval($coordinate['coordinatex'])
				));
				$child->appendChild($dom->createElement(
					'y-waarde',
					intval($coordinate['coordinatey'])
				));
				if(isset($coordinate['coordinatez'])) {
					$child->appendChild($dom->createElement(
						'z-waarde',
						intval($coordinate['coordinatez'])
					));
				}
				$object->appendChild($child);
			}
		}
		return $dom->saveXML();
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ncgov_permits/view/class.tx_ncgovpermits_permit_view.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ncgov_permits/view/class.tx_ncgovpermits_permit_view.php']);
}
?>