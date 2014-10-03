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

class tx_ncgovpermits_base_view extends tx_nclib_base_view {

	/**
	 * Returns a content object rendered. Needs the config path where the id of the content object is stored.
	 * Uses cObjGetSingle.
	 *
	 * @param string	$configPath	the path in the config model where the content object can be found.
	 * @return string	the content object, rendered.
	 */
	public function getContentObject($configPath) {
		$storageFolder = $this->controller->configModel->get('content.storageFolder');
		if(empty($storageFolder) || $storageFolder === false) {
			throw new tx_nclib_exception('label_error_content_elements_storage_folder_not_set', $this->controller);
		}

		$contentUid = $this->controller->configModel->get($configPath);
		$config = array(
			'table' => 'tt_content',
			'select.' => array(
				'pidInList' => $storageFolder,
				'where' => 'uid=' . $contentUid,
			)
		);
		$result = $this->controller->cObj->cObjGetSingle('CONTENT', $config);
		if(empty($result)) {
			throw new tx_nclib_exception(
				'label_error_content_object_not_found',
				$this->controller,
				array('path' => $configPath)
			);
		}
		return $result;
	}

	/**
	 * Adds a list of labels.
	 *
	 * @param array	$labels
	 */
	public function addTranslationLabels($labels) {
		if(tx_nclib::isLoopable($labels)) {
			foreach($labels as $label) {
				$this->getTranslatedLabel($label);
			}
		}
	}

	/**
	 * Returns the date full (eg monday 23 november 2008) using translation labels.
	 *
	 * @param integer $timestamp	timestamp to translate
	 */
	public function getFullDate($timestamp) {
		$day = $this->getTranslatedLabel('label_day_' . date('N', $timestamp));
		return sprintf('%s %d %s %d', $day, date('d', $timestamp), $this->getMonthName($timestamp), date('Y', $timestamp));
	}

	public function getMonthName($date=false) {
		if(!$date) {
			$date = time();
		}
		return $this->getTranslatedLabel('label_month_' . date('m', $date));
	}

	protected function getProductsNameList($products) {
		$list = '';
		if(tx_nclib::isLoopable($products)) {
			foreach($products as $index=>$product) {
				if($index > 0) {
					$list .= ', ';
				}
				$list .= sprintf('"%s"', $product['name']);
			}
		}
		return $list;
	}

	protected function getPersonsLabel($persons) {
		$result = '';
		$maxPersonsAllowed = $this->controller->configModel->get('maxNumberOfPersonsAllowedForProduct');
		if($persons > 1) {
			if($persons > $maxPersonsAllowed) {
				$this->addTranslationLabel('label_more_than', array('maxNumberOfPersons' => $maxPersonsAllowed));
				$persons = $this->getTranslatedLabel('label_more_than');
			}
			$this->addTranslationLabel('label_multiple_persons', array('persons' => $persons));
			$result = $this->getTranslatedLabel('label_multiple_persons');
		}
		return $result;
	}

	protected function getProductRequirements($products) {
		$result = array();
		if(tx_nclib::isLoopable($products)) {
			foreach($products as $index => $product) {
				$this->addTranslationLabel('label_for_product', array('productName' => $product['name']));
				$result[$index]['PRODUCT_NAME'] = $this->getTranslatedLabel('label_for_product');
				$result[$index]['PRODUCT_REQUIREDS'] = $product['requireds'];
			}
		}
		return $result;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ncgov_permits/view/class.tx_ncgovpermits_base_view.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ncgov_permits/view/class.tx_ncgovpermits_base_view.php']);
}
?>
