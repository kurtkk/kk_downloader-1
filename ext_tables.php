<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');
include_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY).'pi1/class.tx_kkdownloader_addFieldsToFlexForm.php');

$GLOBALS['TCA']["tx_kkdownloader_images"] = [
	"ctrl" => [
		'title'     => 'LLL:EXT:kk_downloader/locallang_db.xml:tx_kkdownloader_images',
		'label'     => 'name',
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'sortby' => 'sorting',
		'languageField'            => 'sys_language_uid',
		'transOrigPointerField'    => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'default_sortby' => "ORDER BY name",
		'delete' => 'deleted',
		'enablecolumns' => [
			'disabled' => 'hidden',
        ],
		'dynamicConfigFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY).'tca.php',
		'iconfile'          => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY).'icon_tx_kkdownloader_images.gif',
    ],
	"feInterface" => [
		"fe_admin_fieldList" => "sys_language_uid, l18n_parent, l18n_diffsource, hidden, name, image, imagepreview, downloaddescription, description, longdescription, clicks, cat",
    ]
];

$GLOBALS['TCA']["tx_kkdownloader_cat"] = [
	"ctrl" => [
		'title'     => 'LLL:EXT:kk_downloader/locallang_db.xml:tx_kkdownloader_cat',
		'label'     => 'cat',
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'languageField'            => 'sys_language_uid',
		'transOrigPointerField'    => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'default_sortby' => "ORDER BY cat ASC",
		'delete' => 'deleted',
		'enablecolumns' => [
			'disabled' => 'hidden',
        ],
		'dynamicConfigFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY).'tca.php',
		'iconfile'          => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY).'icon_tx_kkdownloader_cat.gif',
    ],
	"feInterface" => [
		"fe_admin_fieldList" => "sys_language_uid, l18n_parent, l18n_diffsource, hidden, cat",
    ]
];

//t3lib_div::loadTCA('tt_content');

// insert CSS file
// t3lib_extMgm::addStaticFile($_EXTKEY,'static/css/','downloader');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
	$_EXTKEY,
	'static/css/',
	'downloader'
);

// t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_pi1', 'FILE:EXT:'.$_EXTKEY.'/pi1/flexform.xml');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
	$_EXTKEY.'_pi1',
	'FILE:EXT:'.$_EXTKEY.'/pi1/flexform.xml'
);

// t3lib_extMgm::allowTableOnStandardPages('tx_kkdownloader_images');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages(
	'tx_kkdownloader_images'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages(
	'tx_kkdownloader_cat'
);

// t3lib_extMgm::addToInsertRecords('tx_kkdownloader_images');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToInsertRecords(
	'tx_kkdownloader_images'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToInsertRecords(
	'tx_kkdownloader_cat'
);


// you add pi_flexform to be renderd when your plugin is shown
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi1']='pi_flexform';                   // new!

// t3lib_extMgm::addPlugin(array('LLL:EXT:kk_downloader/locallang_db.xml:tt_content.list_type_pi1', $_EXTKEY.'_pi1'),'list_type');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
	[
		'LLL:EXT:kk_downloader/locallang_db.xml:tt_content.list_type_pi1',
		$_EXTKEY . '_pi1'
    ],
	'list_type'
);
?>
