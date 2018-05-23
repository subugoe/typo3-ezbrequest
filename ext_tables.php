<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin([
    'LLL:EXT:ezbrequest/Resources/Private/Language/locallang_db.xml:tt_content.list_type_pi1',
    $_EXTKEY.'_pi1',
], 'list_type');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript/', 'ezbrequest');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($_EXTKEY.'_pi1',
    'FILE:EXT:'.$_EXTKEY.'/Configuration/FlexForms/flexform.xml');
