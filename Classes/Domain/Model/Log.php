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

class Log extends Base {
	private $messageNumber;

	function initialize(\Netcreators\NcgovPermits\Controller\PermitController &$controller) {
		parent::initialize($controller);
		$this->setTableName('tx_ncgovpermits_log');
		$this->messageNumber = 0;
	}

	/**
	 * Writes a log message with a certain type
	 *
	 * @param string $message the log message.
	 * @param array $variables
	 * @param string $type the type of the message
	 * @param bool $timestamp
	 * @return boolean true when successful
	 */
	public function log($message, $variables = array(), $type = 'message', $timestamp = false) {
		$this->messageNumber++;
		if($variables !== false && \tx_nclib::isLoopable($variables)) {
			$message .= ' (';
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
			$pageId = \tx_nclib_tsfe_model::getPageId();
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

?>