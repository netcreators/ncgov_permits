<?php

class tx_ncgovpermits_realurl_configuration {

	/**
	 * Generates additional RealURL configuration and merges it with provided configuration
	 *
	 * @param array $paramsDefault configuration
	 * @param tx_realurl_autoconfgen $pObjParent object
	 * @return array Updated configuration
	 */
	function addNcGovPermitsRealurlConfig($params, &$pObj) {
		return array_merge_recursive(
			$params['config'],
			array(
				'postVarSets' => array(
					'_DEFAULT' => array(
						'detail' => array(
							array(
								'GETvar' => 'tx_ncgovpermits_controller[id]',
							),
							array(
								'GETvar' => 'tx_ncgovpermits_controller[doc]',
							),
						),
						'list' => array(
							array(
								'GETvar' => 'tx_ncgovpermits_controller[activeYear]',
							),
							array(
								'GETvar' => 'tx_ncgovpermits_controller[activeMonth]',
							),
							array(
								'GETvar' => 'tx_ncgovpermits_controller[activeWeek]',
							),
						),
					)
				)
			)
		);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ncgov_permits/controller/class.tx_ncgovpermits_realurl_configuration.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ncgov_permits/controller/class.tx_ncgovpermits_realurl_configuration.php']);
}

?>