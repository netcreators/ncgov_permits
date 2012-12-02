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

/**
 * Class that adds the wizard icon.
 *
 * @author	Frans van der Veen [netcreators] <extensions@netcreators.com>
 * @package	TYPO3
 * @subpackage	tx_ncfeurnavision
 */
class tx_ncgovpermits_wizicon {
	/**
	 * Processing the wizard items array
	 *
	 * @param	array		$wizardItems: The wizard items
	 * @return	Modified array with wizard items
	 */
	function proc($wizardItems)	{
		global $LANG;

		$this->initialize();
		$LL = $this->includeLocalLang();

		$wizardItems['plugins_'.$this->extKeyShort.'_controller'] = array(
			'icon'=>t3lib_extMgm::extRelPath($this->extKey).'res/icons/ce_wiz.gif',
			'title'=>$LANG->getLLL('wizicon_title',$LL),
			'description'=>$LANG->getLLL('wizicon_description',$LL),
			'params'=>'&defVals[tt_content][CType]=list&defVals[tt_content][list_type]='.$this->extKey.'_controller'
		);
		return $wizardItems;
	}

	/**
	 * Reads the [extDir]/locallang.xml and returns the $LOCAL_LANG array found in that file.
	 *
	 * @return	The array with language labels
	 */
	function includeLocalLang()	{
		$llFile = t3lib_extMgm::extPath($this->extKey).'lang/locallang_wizicon.xml';
		$LOCAL_LANG = t3lib_div::readLLXMLfile($llFile, $GLOBALS['LANG']->lang);
		
		return $LOCAL_LANG;
	}
	
	function initialize() {
		$this->extKey='ncgov_permits';
		$this->extKeyShort='tx_ncfeurnavision';
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ncgov_permits/controller/class.tx_ncgovpermits_wizicon.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ncgov_permits/controller/class.tx_ncgovpermits_wizicon.php']);
}

?>