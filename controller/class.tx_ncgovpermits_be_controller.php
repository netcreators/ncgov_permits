<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Frans van der Veen <extensions@netcreators.com>
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

$sCurrentDir = dirname(__FILE__) . '/';
require_once($sCurrentDir . '../includes.php');

if(!class_exists('tslib_cObj')) {
	require_once(PATH_tslib . 'class.tslib_content.php');
}

/**
 * Backend controller(helper) class for 'nc_products' extension.
 *
 * @author	Frans van der Veen <extensions@netcreators.com>
 * @package	TYPO3
 * @subpackage	tx_ncproducts
 */
class tx_ncgovpermits_be_controller {
	protected $controller;

	public function initialize() {
		try {
			$cObj = t3lib_div::makeInstance('tslib_cObj');
			$this->controller = t3lib_div::makeInstance('tx_ncgovpermits_controller');
			$this->controller->initialize(array());
			$this->controller->cObj = $cObj;
		} catch(tx_nclib_exception $exception) {
			die('<strong>' .get_class($this) . '</strong> exception: ' . $exception->getErrorMessage());
		}
	}

	/**
	 * Getting municipalities for OWMS
	 *
	 * @param array $config
	 * @return array	TCA item list
	 */
	function user_getMunicipalities($config) {
		$this->initialize();
		$row = $config['row'];
		try {
			$list = tx_nclib_base_model::getAssociatedArrayConvertedToTCAItems(
				$this->controller->xmlModel->getGovXmlValueList(
					$this->controller->configModel->getExtensionConfiguration('permitMunicipalityXmlFile'),
					true
				)
			);
			$config['items'] = $list;
		} catch(Exception $exception) {
			die($this->controller->getException($exception));
		}
		return $config;
	}

	function user_getObjectMunicipalities($config) {
		$items = $config['items'];
		$temp = $this->user_getMunicipalities($config);
		array_unshift($config['items'], $items[0]);
		return $config;
	}

	/**
	 * Getting Product types for IPV 4.0
	 *
	 * @param array $config
	 * @return array	TCA item list
	 */
	function user_getProductTypes($config) {
		$this->initialize();
		try {
			$row = $config['row'];
			switch($row['type']) {
				case tx_ncgovpermits_permits_model::TYPE_PERMIT:
					$list = tx_nclib_base_model::getAssociatedArrayConvertedToTCAItems(
						$this->controller->xmlModel->getGovXmlValueList(
							$this->controller->configModel->getExtensionConfiguration('permitProductTypeXmlFile'),
							true
						)
					);
					break;
				case tx_ncgovpermits_permits_model::TYPE_PUBLICATION:
					$list = tx_nclib_base_model::getAssociatedArrayConvertedToTCAItems(
						$this->controller->xmlModel->getGovTxtValueList(
							$this->controller->configModel->getExtensionConfiguration('publicationProductTypeTxtFile')
						)
					);
					break;
			}
			$config['items'] = $list;
		} catch(Exception $exception) {
			die($this->controller->getException($exception));
		}
		return $config;
	}

	/**
	 * Getting Product activities for IPV 4.0
	 *
	 * @param array $config
	 * @return array	TCA item list
	 */
	function user_getProductActivities($config) {
		$this->initialize();
		try {
			$list = tx_nclib_base_model::getAssociatedArrayConvertedToTCAItems(
				$this->controller->xmlModel->getGovXmlValueList(
					$this->controller->configModel->getExtensionConfiguration('permitProductActivityXmlFile'),
					true
				)
			);
			$config['items'] = $list;
		} catch(Exception $exception) {
			die($this->controller->getException($exception));
		}
		return $config;
	}

	/**
	 * Getting permit phases
	 *
	 * @param array $config
	 * @return array	TCA item list
	 */
	function user_getPhases($config) {
		$this->initialize();
		try {
			$list = tx_nclib_base_model::getAssociatedArrayConvertedToTCAItems(
				$this->controller->xmlModel->getGovXmlValueList(
					$this->controller->configModel->getExtensionConfiguration('permitPhaseXmlFile'),
					true
				)
			);
			// add the first 'select x' to the list
			array_unshift($list, $config['items'][0]);
			$config['items'] = $list;
		} catch(Exception $exception) {
			die($this->controller->getException($exception));
		}
		return $config;
	}

	/**
	 * Getting permit term types
	 *
	 * @param array $config
	 * @return array	TCA item list
	 */
	function user_getTermTypes($config) {
		$this->initialize();
		try {
			$list = tx_nclib_base_model::getAssociatedArrayConvertedToTCAItems(
				$this->controller->xmlModel->getGovXmlValueList(
					$this->controller->configModel->getExtensionConfiguration('permitTermTypeXmlFile'),
					true
				)
			);
			// add the first 'select x' to the list
			array_unshift($list, $config['items'][0]);
			$config['items'] = $list;
		} catch(Exception $exception) {
			die($this->controller->getException($exception));
		}
		return $config;
	}

	/**
	 * Getting permit term types
	 *
	 * @param array $config
	 * @return array	TCA item list
	 */
	function user_getProvinces($config) {
		$this->initialize();
		try {
			$list = tx_nclib_base_model::getAssociatedArrayConvertedToTCAItems(
				$this->controller->xmlModel->getGovXmlValueList(
					$this->controller->configModel->getExtensionConfiguration('permitProvinceXmlFile'),
					true
				)
			);
			// add the first 'select x' to the list
			array_unshift($list, $config['items'][0]);
			$config['items'] = $list;
		} catch(Exception $exception) {
			die($this->controller->getException($exception));
		}
		return $config;
	}

	/**
	 * Getting cadastre municipalities
	 *
	 * @param array $config
	 * @return array	TCA item list
	 */
	function user_getCadastreMunicipalities($config) {
		$this->initialize();
		try {
			$list = tx_nclib_base_model::getAssociatedArrayConvertedToTCAItems(
				$this->controller->xmlModel->getGovXmlValueList(
					$this->controller->configModel->getExtensionConfiguration('permitCadastreMunicipalityXmlFile'),
					true
				)
			);
			// add the first 'select x' to the list
			array_unshift($list, $config['items'][0]);
			$config['items'] = $list;
		} catch(Exception $exception) {
			die($this->controller->getException($exception));
		}
		return $config;
	}

	/**
	 * Getting cadastre municipalities
	 *
	 * @param array $config
	 * @return array	TCA item list
	 */
	function user_getDocumentTypes($config) {
		$this->initialize();
		try {
			$list = tx_nclib_base_model::getAssociatedArrayConvertedToTCAItems(
				$this->controller->xmlModel->getGovXmlValueList(
					$this->controller->configModel->getExtensionConfiguration('permitDocumentTypeXmlFile'),
					true
				)
			);
			$config['items'] = $list;
		} catch(Exception $exception) {
			die($this->controller->getException($exception));
		}
		return $config;
	}

	/**
	 * BE form rendering stuff
	 * @param $table	table
	 * @param $row	record
	 * @param $parent	$this
	 * @return void
	 */
	function getMainFields_preProcess($table, $row, $parent) {
		//print("table:$table");
		//debug($row);
		//debug($parent);
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nc_permits/controller/class.tx_ncgovpermits_be_controller.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nc_permits/controller/class.tx_ncgovpermits_be_controller.php']);
}

?>