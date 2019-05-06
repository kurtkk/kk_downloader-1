<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA["tx_kkdownloader_images"] = array (
	"ctrl" => $TCA["tx_kkdownloader_images"]["ctrl"],
	"interface" => array (
		"showRecordFieldList" => "sys_language_uid,l18n_parent,l18n_diffsource,hidden,name,image,description,longdescription,clicks,cat"
	),
	"feInterface" => $TCA["tx_kkdownloader_images"]["feInterface"],
	"columns" => array (
		'sys_language_uid' => array (
			'exclude' => 1,
			'label'  => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array (
				'type'                => 'select',
				'renderType' 		  => 'selectSingle',				
				'foreign_table'       => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0)
				)
			)
		),
		'l18n_parent' => array (
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude'     => 1,
			'label'       => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config'      => array (
				'type'  => 'select',
				'items' => array (
					array('', 0),
				),
				'renderType' 		  => 'selectSingle',				
				'foreign_table'       => 'tx_kkdownloader_images',
				'foreign_table_where' => 'AND tx_kkdownloader_images.pid=###CURRENT_PID### AND tx_kkdownloader_images.sys_language_uid IN (-1,0)',
			)
		),
		'l18n_diffsource' => array (
			'config' => array (
				'type' => 'passthrough'
			)
		),
		'hidden' => array (
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		"name" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:kk_downloader/locallang_db.xml:tx_kkdownloader_images.name",
			"config" => Array (
				"type" => "input",
				"size" => "30",
			)
		),
		"cat" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:kk_downloader/locallang_db.xml:tx_kkdownloader_images.cat",
			"config" => Array (
				"type" => "select",
				'renderType' 	=> 'selectSingleBox',
				"foreign_table" => "tx_kkdownloader_cat",
//				"foreign_table_where" => "AND tx_kkdownloader_cat.pid=###STORAGE_PID### AND sys_language_uid=###REC_FIELD_sys_language_uid### ORDER BY tx_kkdownloader_cat.cat",
				"foreign_table_where" => "AND sys_language_uid=###REC_FIELD_sys_language_uid### ORDER BY tx_kkdownloader_cat.cat",
				"size" => 4,
				"minitems" => 0,
				"maxitems" => 10,
			)
		),
		"image" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:kk_downloader/locallang_db.xml:tx_kkdownloader_images.image",
			"config" => Array (
				"type" => "group",
				"internal_type" => "file",
				"allowed" => "",
				"disallowed" => "php",
				"max_size" => 5000000,
				"uploadfolder" => "uploads/tx_kkdownloader",
				"show_thumbs" => 1,
				"size" => 10,
				"minitems" => 0,
				"maxitems" => 10,
			)
		),
		"imagepreview" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:kk_downloader/locallang_db.xml:tx_kkdownloader_images.imagepreview",
			"config" => Array (
				"type" => "group",
				"internal_type" => "file",
				"allowed" => "jpg,gif,png",
				"max_size" => 10000,
				"uploadfolder" => "uploads/tx_kkdownloader",
				"show_thumbs" => 1,
				"size" => 1,
				"minitems" => 0,
				"maxitems" => 1,
			)
		),
		"downloaddescription" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:kk_downloader/locallang_db.xml:tx_kkdownloader_images.imagedescription",
			"config" => Array (
				"type" => "text",
				"cols" => "30",
				"rows" => "5",
			)
		),
		"description" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:kk_downloader/locallang_db.xml:tx_kkdownloader_images.description",
			"config" => Array (
				"type" => "text",
				"cols" => "30",
				"rows" => "3",
			)
		),
		"longdescription" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:kk_downloader/locallang_db.xml:tx_kkdownloader_images.longdescription",
			"config" => Array (
				"type" => "text",
				"cols" => "30",
				"rows" => "5",
				"wizards" => Array(
					"_PADDING" => 2,
					"RTE" => array(
						"notNewRecords" => 1,
						"RTEonly" => 1,
						"type" => "script",
						"title" => "Full screen Rich Text Editing|Formatteret redigering i hele vinduet",
						"icon" => "wizard_rte2.gif",
						'module' => array(
							'name' => 'wizard_rte'
						)
					),
				),
			)
		),
		"clicks" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:kk_downloader/locallang_db.xml:tx_kkdownloader_images.clicks",
			"config" => Array (
				"type" => "input",
				"size" => "30",
			)
		),
	),
	"types" => array (
		"0" => array("showitem" => "sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, hidden;;1, name, image, cat, imagepreview, downloaddescription, description,longdescription;;;richtext[paste|bold|italic|underline|formatblock|class|left|center|right|orderedlist|unorderedlist|outdent|indent|link|image]:rte_transform[mode=ts], clicks")
	),
	"palettes" => array (
		"1" => array("showitem" => "")
	)
);



$TCA["tx_kkdownloader_cat"] = array (
	"ctrl" => $TCA["tx_kkdownloader_cat"]["ctrl"],
	"interface" => array (
		"showRecordFieldList" => "sys_language_uid,l18n_parent,l18n_diffsource,hidden,cat"
	),
	"feInterface" => $TCA["tx_kkdownloader_cat"]["feInterface"],
	"columns" => array (
		'sys_language_uid' => array (
			'exclude' => 1,
			'label'  => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array (
				'type'                => 'select',
				'renderType' 		  => 'selectSingle',
				'foreign_table'       => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0)
				)
			)
		),
		'l18n_parent' => array (
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude'     => 1,
			'label'       => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config'      => array (
				'type'  => 'select',
				'renderType' 		  => 'selectSingle',			
				'items' => array (
					array('', 0),
				),
				'foreign_table'       => 'tx_kkdownloader_cat',
				'foreign_table_where' => 'AND tx_kkdownloader_cat.pid=###CURRENT_PID### AND tx_kkdownloader_cat.sys_language_uid IN (-1,0)',
			)
		),
		'l18n_diffsource' => array (
			'config' => array (
				'type' => 'passthrough'
			)
		),
		'hidden' => array (
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		"cat" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:kk_downloader/locallang_db.xml:tx_kkdownloader_cat.cat",
			"config" => Array (
				"type" => "input",
				"size" => "30",
			)
		),
	),
	"types" => array (
		"0" => array("showitem" => "sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, hidden;;1, cat")
	),
	"palettes" => array (
		"1" => array("showitem" => "")
	)
);
?>
