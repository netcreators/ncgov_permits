<?php

namespace Netcreators\NcgovPermits\Service\RealUrl;

class RealUrlAutoConfigurationService
{

    /**
     * Generates additional RealURL configuration and merges it with provided configuration
     *
     * @param array $params configuration
     * @param \tx_realurl_autoconfgen $realUrlAutoConfigurationGenerator object
     * @return array Updated configuration
     */
    function addNcGovPermitsRealUrlConfiguration($params, \tx_realurl_autoconfgen &$realUrlAutoConfigurationGenerator)
    {
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

