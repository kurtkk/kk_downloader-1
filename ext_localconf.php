<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

call_user_func(function ($extKey) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('options.saveDocNew.tx_kkdownloader_images = 1');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('options.saveDocNew.tx_kkdownloader_cat = 1');

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
        $extKey,
        'editorcfg',
        'tt_content.CSS_editor.ch.tx_kkdownloader_pi1 = < plugin.tx_kkdownloader_pi1.CSS_editor',
        43
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43(
        $extKey,
        'pi1/class.tx_kkdownloader_pi1.php',
        '_pi1',
        'list_type',
        1
    );
}, $_EXTKEY);
