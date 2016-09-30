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

class Base extends \tx_nclib_base_model
{
    /**
     * @var \Netcreators\NcgovPermits\Controller\PermitController
     */
    protected $controller;

    /**
     * @param \Netcreators\NcgovPermits\Controller\PermitController $controller
     */
    function initialize(\Netcreators\NcgovPermits\Controller\PermitController &$controller)
    {
        parent::initialize($controller);
    }

    /**
     * Applies pi_RTEcssText to the RTE field.
     * @param string $field
     */
    protected function _getField_rteField($field)
    {
        // getrecord will cause an infinite loop
        // getfield (<one of the rte fields>, false) also
        return $this->controller->pi_RTEcssText($this->getField($field, true));
    }
}

