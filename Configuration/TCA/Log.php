<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$_EXTKEY = 'ncgov_permits';
$_EXTKEYSHORT = 'tx_ncgovpermits';
$_TABLENAME = $_EXTKEYSHORT . '_log';

$TCA[$_TABLENAME] = array (
    'ctrl' => $TCA[$_TABLENAME]['ctrl'],
    'interface' => array (
        'showRecordFieldList' => 'hidden,message,logtype'
    ),
    'feInterface' => $TCA[$_TABLENAME]['feInterface'],
    'columns' => array (
        'hidden' => array (
            'exclude' => 1,
            'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
            'config'  => array (
                'type'    => 'check',
                'default' => '0'
            )
        ),
        'message' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $_EXTKEY . '/lang/locallang_tca.xml:' . $_TABLENAME . '.message',
            'config' => Array (
                'type' => 'text',
                'cols' => '30',
                'rows' => '10',
            )
        ),
		'messagenumber' => array (
            'exclude' => 0,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/lang/locallang_tca.xml:' . $_TABLENAME . '.messagenumber',
            'config' => array (
                'type'     => 'input',
                'size'     => '4',
                'max'      => '4',
                'eval'     => 'int',
                'checkbox' => '0',
                'default' => 0
            )
		),
        'logtype' => Array (
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $_EXTKEY . '/lang/locallang_tca.xml:' . $_TABLENAME . '.logtype',
            'config' => Array (
                'type' => 'input',
                'size' => '10',
                'max' => '10',
                'eval' => 'trim',
            )
        ),
		'smscount' => array (
            'exclude' => 0,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/lang/locallang_tca.xml:' . $_TABLENAME . '.smscount',
            'config' => array (
                'type'     => 'input',
                'eval'     => 'int',
                'default' => 0
            )
		),
		'emailcount' => array (
            'exclude' => 0,
			'label' => 'LLL:EXT:' . $_EXTKEY . '/lang/locallang_tca.xml:' . $_TABLENAME . '.emailcount',
            'config' => array (
                'type'     => 'input',
                'eval'     => 'int',
                'default' => 0
            )
		),
	),
    'types' => array (
        '0' => array('showitem' => 'hidden, message, logtype, smscount, emailcount')
    ),
    'palettes' => array (
        '1' => array('showitem' => '')
    )
);


?>