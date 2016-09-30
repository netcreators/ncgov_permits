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

namespace Netcreators\NcgovPermits\Controller;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class BaseController extends \tx_nclib_base_controller
{

    // NOTE the false path helps the pibase class find the locallang.xml
    // Not used elsewhere
    // Path to this script relative to the extension dir.
    public $scriptRelPath = 'Resources/Private/Language/BaseController';

    function __construct()
    {
    }

    function initialize($configuration)
    {
        if (!$this->cObj) {
            $this->cObj = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
        }
        parent::initialize($configuration);
    }
}

