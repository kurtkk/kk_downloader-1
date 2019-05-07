<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

call_user_func(function() {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('options.saveDocNew.tx_kkdownloader_images = 1');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('options.saveDocNew.tx_kkdownloader_cat = 1');

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
        'kk_downloader',
        'setup',
        '
# Setting kk_downloader plugin TypoScript
' . trim('
plugin.kkdownloader_pi1 = USER
plugin.kkdownloader_pi1.userFunc = JWeiland\KkDownloader\Plugin\KkDownloader->main
'
        )
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
        'kk_downloader',
        'setup',
        '
# Setting kk_downloader plugin TypoScript
tt_content.list.20.kk_downloader_pi1 = < plugin.kkdownloader_pi1
'
        ,
        'defaultContentRendering'
    );
});
