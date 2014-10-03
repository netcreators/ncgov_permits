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

$currentDir = dirname(__FILE__) . '/';
require_once($currentDir . '../includes.php');

class tx_ncgovpermits_exception_view extends tx_ncgovpermits_base_view {
	/**
	 * Initializes the class.
	 *
	 * @param object $oController the controller object
	 */
	public function initialize(&$controller) {
		$this->setAutoDetermineTemplate(false);
		parent::initialize($controller);
		$this->setTemplateFile('EXT:' . $controller->extKey . '/templates/exception_view.html');
	}

	/**
	 * Returns the rendered view.
	 *
	 * @return string	the content
	 */
	public function getContent($exception) {
		$subparts = array();

		if(get_class($exception) == 'tx_nclib_exception') {
			$subparts['ERROR_MESSAGE'] = $exception->getErrorMessage();
		} else {
			$subparts['ERROR_MESSAGE'] = $exception->getMessage();
		}
		$fields = array('file', 'line', 'class', 'function');
		$trace = $exception->getTrace();
		if(tx_nclib::isLoopable($trace)) {
			foreach($trace as $index=>$traceStep) {
				if(tx_nclib::isLoopable($fields)) {
					foreach($fields as $key) {
						$subparts['TRACE'][$index][strtoupper($key)] = $traceStep[$key];
					}
				}
			}
		}

		$content = $this->subpartReplaceRecursive($subparts, 'EXCEPTION_VIEW');
		return $content;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ncgov_permits/view/class.tx_ncgovpermits_exception_view.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ncgov_permits/view/class.tx_ncgovpermits_exception_view.php']);
}
?>