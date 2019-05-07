<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

include_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'pi1/class.tx_kkdownloader_addFieldsToFlexForm.php');

// t3lib_extMgm::allowTableOnStandardPages('tx_kkdownloader_images');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages(
	'tx_kkdownloader_images'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages(
	'tx_kkdownloader_cat'
);
