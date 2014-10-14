<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2010 Frans van der Veen [Netcreators] <extensions@netcreators.com>
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

namespace Netcreators\NcgovPermits\Service\BackendFormEngine;

/**
 * tce forms helper
 *
 * @author Frans van der Veen <extensions@netcreators.com>
 * @copyright Netcreators
 * @package NcgovPermits
 */
class GetMainFieldsHook {

	protected $table = '';
	protected $row = '';
	protected $parent = '';

	protected $extensionConfiguration = '';

	/**
	 * Initialize this object, so configuration values can be read.
	 * 
	 * @return void
	 */
	public function initialize() {
		$this->extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ncgov_permits']);
	}

	/**
	 * Preprocess selector
	 * 
	 * @param $table
	 * @param $row
	 * @param $parent
	 * @return void
	 */
	function getMainFields_preProcess($table,&$row,&$parent) {
		$this->initialize();
		$this->table = $table;
		$this->row = &$row;
		$this->parent = &$parent;
		switch($table) {
			case 'tx_ncgovpermits_permits':
				$this->preProcessPermits();
				break;
			default:
				break;
		}
	}

	/**
	 * Preprocess permits table
	 * 
	 * @return void
	 */
	protected function preProcessPermits() {

		if($this->row['type'] == 1) {
			if ($this->extensionConfiguration['enableAdditionalPublicationElements']) {
				// prevent the record from being saved
				$this->addFieldsAndMakePhaseNotRequired($this->table);
			}

			if ($this->extensionConfiguration['enableCoordinatesForPublications']) {
				$this->enableCoordinatesField();
			}
		}
	}

	/**
	 * Makes the phase field not required for Publications
	 * 
	 * @param string $table tablename
	 */
	protected function addFieldsAndMakePhaseNotRequired($table) {
		$_EXTKEY = 'ncgov_permits';
		$GLOBALS['TCA'][$table]['columns']['phase']['config']['minitems'] = 0;
		$GLOBALS['TCA'][$table]['columns']['phase']['label'] = 'LLL:EXT:' . $_EXTKEY . '/lang/locallang_tca.xml:' . $table . '.phase_not_required';
		$GLOBALS['TCA'][$table]['types']['1']['showitem'] = str_replace('link,', 'link, phase, termtype,', $GLOBALS['TCA'][$table]['types']['1']['showitem']);
	}
	
	/**
	 * Enables the coordinates field for Publications
	 */
	protected function enableCoordinatesField() {
		$GLOBALS['TCA'][$this->table]['types']['1']['showitem'] = str_replace('objectaddresses,', 'objectaddresses,coordinates,', $GLOBALS['TCA'][$this->table]['types']['1']['showitem']);
	}
}

?>