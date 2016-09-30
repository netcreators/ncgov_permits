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

namespace Netcreators\NcgovPermits\View;

class ExceptionView extends BaseView
{
    /**
     * Initializes the class.
     *
     * @param \tx_nclib_base_controller $controller
     * @internal param object $oController the controller object
     */
    public function initialize(&$controller)
    {
        $this->setAutoDetermineTemplate(false);
        parent::initialize($controller);
        $this->setTemplateFile('EXT:' . $controller->extKey . '/Resources/Private/Templates/Exception.html');
    }

    /**
     * Returns the rendered view.
     *
     * @param \Exception $exception
     * @return string    the content
     */
    public function getContent(\Exception $exception)
    {
        $subparts = array();

        if ($exception instanceof \tx_nclib_exception) {
            $subparts['ERROR_MESSAGE'] = $exception->getErrorMessage();
        } else {
            $subparts['ERROR_MESSAGE'] = $exception->getMessage();
        }
        $fields = array('file', 'line', 'class', 'function');
        $trace = $exception->getTrace();
        if (\tx_nclib::isLoopable($trace)) {
            foreach ($trace as $index => $traceStep) {
                if (\tx_nclib::isLoopable($fields)) {
                    foreach ($fields as $key) {
                        $subparts['TRACE'][$index][strtoupper($key)] = $traceStep[$key];
                    }
                }
            }
        }

        $content = $this->subpartReplaceRecursive($subparts, 'EXCEPTION_VIEW');
        return $content;
    }
}

