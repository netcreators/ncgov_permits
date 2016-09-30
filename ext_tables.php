<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

// change it once per file at most
$_EXTKEY = 'ncgov_permits';
$_EXTKEYSHORT = 'tx_ncgovpermits';

// add flexform
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY . '_controller'] = 'layout,select_key,pages,recursive';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY . '_controller'] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    $_EXTKEY . '_controller',
    'FILE:EXT:' . $_EXTKEY . '/Configuration/FlexForms/flexform_ds.xml'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    array(
        'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_be.xml:controller_title',
        $_EXTKEY . '_controller'
    ),
    'list_type'
);

// Add TypoScript resource
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    $_EXTKEY,
    'Configuration/TypoScript/',
    'Permit publication plugin ts (ncgov_permits)'
);

