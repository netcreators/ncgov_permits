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

class tx_ncgovpermits_owms_model extends tx_ncgovpermits_base_model {
	function initialize(&$controller) {
		parent::initialize($controller);
		/*
		$this->setGetMethod('cluster_name', '_getField_clusterName');
		$this->setGetMethod('requireds', '_getField_rteField');
		$this->setGetMethod('intro_text', '_getField_rteField');
		*/
	}

	/**
	 * Loads all the product records for a specified cluster
	 *
	 * @param integer	$clusterId	the clusterid
	 * @return boolean	true if succesful, false otherwise
	 */
	public function loadProductsForCluster($clusterId, $showHidden = false) {
		$pageIds = $this->database->getPageIdsRecursive(
			$this->controller->configModel->get('storageFolder'),
			$this->controller->configModel->get('recurseDepth')
		);
		if($pageIds === false) {
			throw new tx_nclib_exception('label_error_no_pages_found', $this->controller);
		}
		$fields = '*';
		$where = array(
			'deleted=0',
			'cluster IN(' . $clusterId . ')',
			sprintf('%s.pid in (%s)', $this->getTableName(), implode(',', $pageIds)),
		);
		if($showHidden === false) {
			$where[] = 'hidden=0';
		} /*else {
			$where[] = 'hidden=1';
		}*/
		$where = $this->database->getWhere($where);
		$orderBy = 'sorting';
		$groupBy = '';

		$this->database->clear();
		$records = $this->database->getQueryRecords($this->getTableName(), $fields, $where, $groupBy, $orderBy);
		if(!$records || !tx_nclib::isLoopable($records)) {
			return false;
		}
		$this->setIterationArray($records);
		return true;
	}

	/**
	 * loads products by specified ids.
	 *
	 * TODO: test if not existing id will fail the specified query
	 *
	 * @param array	$productIds	array containing productids
	 * @return boolean	true when succesful, false when no results
	 */
	public function loadProductsByIds($productIds) {
		$productIds = implode(',', $productIds);
		$pageIds = $this->database->getPageIdsRecursive(
			$this->controller->configModel->get('storageFolder'),
			$this->controller->configModel->get('recurseDepth')
		);
		if($pageIds === false) {
			throw new tx_nclib_exception('label_error_no_pages_found', $this->controller);
		}
		$fields = '*';
		$where = array(
			//'hidden=0', NOTE: COMMENTED OUT ON PURPOSE!
			//'deleted=0', NOTE: COMMENTED OUT ON PURPOSE!
			'uid IN(' . $productIds . ')',
			sprintf('%s.pid in (%s)', $this->getTableName(), implode(',', $pageIds)),
		);
		$where = $this->database->getWhere($where);
		$orderBy = '';
		$groupBy = '';

		$this->database->clear();
		$records = $this->database->getQueryRecords($this->getTableName(), $fields, $where, $groupBy, $orderBy);
		if(!$records || !tx_nclib::isLoopable($records)) {
			return false;
		}
		$this->setIterationArray($records);
		return true;
	}

	/*
	protected function _getField_clusterName($key) {
		$this->controller->clustersModel->pushState();
		if(!$this->controller->clustersModel->loadRecordById($this->getField('cluster'))) {
			throw new tx_nclib_exception(
				'label_error_product_has_no_cluster',
				$this->controller,
				array('productId'=>$this->getId())
			);
		}
		$clusterName = $this->controller->clustersModel->getField('name');
		$this->controller->clustersModel->popState();
		return $clusterName;
	}
	*/

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

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ncgov_permits/model/class.tx_ncgovpermits_owms_model.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ncgov_permits/model/class.tx_ncgovpermits_owms_model.php']);
}

?>