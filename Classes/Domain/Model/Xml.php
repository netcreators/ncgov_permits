<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008 Frans van der Veen [netcreators] <extensions@netcreators.com>
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

class Xml extends \tx_nclib_xml_model {
	function initialize(\Netcreators\NcgovPermits\Controller\PermitController &$controller) {
		parent::initialize($controller);
	}

	/**
	 * Reads gov xml file, returns valuelist
	 * @param $file
	 * @param $topicanaidAsValue
	 * @throws \Exception
	 * @return array
	 */
	public function getGovXmlValueList($file, $topicanaidAsValue) {
		if(!$this->loadXMLFromFile($file)) {
			throw new \Exception('Could not load XML file: ' . $file);
		} else {
			$this->xmlRoot->registerXPathNameSpace('ns', 'http://standaarden.overheid.nl/owms/terms');
			$items = $this->xmlRoot->xpath('/ns:cv');
			$result = array();
			foreach($items[0] as $node) {
				if($topicanaidAsValue) {
					$topicanaid = trim($node);
				} else {
					$topicanaid = trim($node['topicanaid']);
				}
				$result[$topicanaid] = trim($node);
			}
		}
		return $result;
	}

	/**
	 * Reads gov txt file, returns valuelist
	 * @param $file
	 * @return array
	 */
	public function getGovTxtValueList($file) {
		$absFile = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($file);
		$lines = file($absFile, FILE_IGNORE_NEW_LINES);
		$result = array();

		foreach($lines as $index=>$line) {
			$line = trim($line);
			if($index == 0
				||$line[0] == '#'
				|| empty($line)) {
				// skip comments, empty lines
				// and the first line (which for some reason is a comment, but does not start with #)
				continue;
			}
			$result[$line] = $line;
		}

		return $result;
	}

	// iteration functions to be handled (used for the loadRecord functions)
	public final function onNextRecord() {
		$this->setRecord($this->getCurrentRecord(), true);
	}

	public final function onPreviousRecord() {
		$this->setRecord($this->getCurrentRecord(), true);
	}

	public final function onRecordIndexChange() {
		$this->setRecord($this->getCurrentRecord(), true);
	}
}

?>