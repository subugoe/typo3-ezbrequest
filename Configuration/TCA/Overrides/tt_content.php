<?php

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin([
    'LLL:EXT:ezbrequest/Resources/Private/Language/locallang_db.xml:tt_content.list_type_pi1',
    'ezbrequest_pi1',
    ],
    'list_type',
    'ezbrequest'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    'ezbrequest_pi1',
    'FILE:EXT:ezbrequest/Configuration/FlexForms/flexform.xml'
);

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['ezbrequest_pi1'] = 'layout,select_key';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['ezbrequest_pi1_pi1'] = 'pi_flexform';
