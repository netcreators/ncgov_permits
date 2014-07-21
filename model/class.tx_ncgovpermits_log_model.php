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

class tx_ncgovpermits_log_model extends tx_ncgovpermits_base_model {
	private $messageNumber;

	function initialize(&$controller) {
		parent::initialize($controller);
		$this->messageNumber = 0;
	}

	/**
	 * Writes a log message with a certain type
	 *
	 * @param string $message the log message.
	 * @param string $type the type of the message
	 * @return boolean true when succesful
	 * @throws tx_nclib_exception if a query error occurs
	 */
	public function log($message, $variables = false, $type = 'message', $timestamp = false) {
		$this->messageNumber++;
		if($variables !== false && tx_nclib::isLoopable($variables)) {
			$messages .= ' (';
			$index = 0;
			foreach($variables as $key=>$value) {
				if($index > 0) {
					$message .= ',';
				}
				$message .= $key . '=' . (string)$value;
				$index++;
			}
			$message .= ')';
		}
		if($this->controller->configModel->get('logFolder') === false) {
			$pageId = tx_nclib_tsfe_model::getPageId();
		} else {
			$pageId = $this->controller->configModel->get('logFolder');
		}
		$record = array(
			'message' => $message,
			'logtype' => $type,
			'pid' => $pageId,
			'messagenumber' => $this->messageNumber,
		);
		if($timestamp) {
			$record['tstamp'] = $timestamp;
		} else {
			$record['tstamp'] = time();
		}
		$this->database->insertRecord($this->getTableName(), $record);
		return true;
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

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ncgov_permits/model/class.tx_ncgovpermits_log_model.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ncgov_permits/model/class.tx_ncgovpermits_log_model.php']);
}

?>