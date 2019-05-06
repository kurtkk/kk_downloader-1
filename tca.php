<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$GLOBALS['TCA']["tx_kkdownloader_images"] = [
	"ctrl" => $GLOBALS['TCA']["tx_kkdownloader_images"]["ctrl"],
	"interface" => [
		"showRecordFieldList" => "sys_language_uid,l18n_parent,l18n_diffsource,hidden,name,image,description,longdescription,clicks,cat"
    ],
	"feInterface" => $GLOBALS['TCA']["tx_kkdownloader_images"]["feInterface"],
	"columns" => [
		'sys_language_uid' => [
			'exclude' => 1,
			'label'  => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => [
				'type'                => 'select',
				'renderType' 		  => 'selectSingle',
				'foreign_table'       => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => [
					['LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1],
					['LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0]
                ]
            ]
        ],
		'l18n_parent' => [
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude'     => 1,
			'label'       => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config'      => [
				'type'  => 'select',
				'items' => [
					['', 0],
                ],
				'renderType' 		  => 'selectSingle',
				'foreign_table'       => 'tx_kkdownloader_images',
				'foreign_table_where' => 'AND tx_kkdownloader_images.pid=###CURRENT_PID### AND tx_kkdownloader_images.sys_language_uid IN (-1,0)',
            ]
        ],
		'l18n_diffsource' => [
			'config' => [
				'type' => 'passthrough'
            ]
        ],
		'hidden' => [
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => [
				'type'    => 'check',
				'default' => '0'
            ]
        ],
		"name" => [
			"exclude" => 1,
			"label" => "LLL:EXT:kk_downloader/locallang_db.xml:tx_kkdownloader_images.name",
			"config" => [
				"type" => "input",
				"size" => "30",
            ]
        ],
		"cat" => [
			"exclude" => 1,
			"label" => "LLL:EXT:kk_downloader/locallang_db.xml:tx_kkdownloader_images.cat",
			"config" => [
				"type" => "select",
				'renderType' 	=> 'selectSingleBox',
				"foreign_table" => "tx_kkdownloader_cat",
//				"foreign_table_where" => "AND tx_kkdownloader_cat.pid=###STORAGE_PID### AND sys_language_uid=###REC_FIELD_sys_language_uid### ORDER BY tx_kkdownloader_cat.cat",
				"foreign_table_where" => "AND sys_language_uid=###REC_FIELD_sys_language_uid### ORDER BY tx_kkdownloader_cat.cat",
				"size" => 4,
				"minitems" => 0,
				"maxitems" => 10,
            ]
        ],
		"image" => [
			"exclude" => 1,
			"label" => "LLL:EXT:kk_downloader/locallang_db.xml:tx_kkdownloader_images.image",
			"config" => [
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
            ]
        ],
		"imagepreview" => [
			"exclude" => 1,
			"label" => "LLL:EXT:kk_downloader/locallang_db.xml:tx_kkdownloader_images.imagepreview",
			"config" => [
				"type" => "group",
				"internal_type" => "file",
				"allowed" => "jpg,gif,png",
				"max_size" => 10000,
				"uploadfolder" => "uploads/tx_kkdownloader",
				"show_thumbs" => 1,
				"size" => 1,
				"minitems" => 0,
				"maxitems" => 1,
            ]
        ],
		"downloaddescription" => [
			"exclude" => 1,
			"label" => "LLL:EXT:kk_downloader/locallang_db.xml:tx_kkdownloader_images.imagedescription",
			"config" => [
				"type" => "text",
				"cols" => "30",
				"rows" => "5",
            ]
        ],
		"description" => [
			"exclude" => 1,
			"label" => "LLL:EXT:kk_downloader/locallang_db.xml:tx_kkdownloader_images.description",
			"config" => [
				"type" => "text",
				"cols" => "30",
				"rows" => "3",
            ]
        ],
		"longdescription" => [
			"exclude" => 1,
			"label" => "LLL:EXT:kk_downloader/locallang_db.xml:tx_kkdownloader_images.longdescription",
			"config" => [
				"type" => "text",
				"cols" => "30",
				"rows" => "5",
				"wizards" => [
					"_PADDING" => 2,
					"RTE" => [
						"notNewRecords" => 1,
						"RTEonly" => 1,
						"type" => "script",
						"title" => "Full screen Rich Text Editing|Formatteret redigering i hele vinduet",
						"icon" => "wizard_rte2.gif",
						'module' => [
							'name' => 'wizard_rte'
                        ]
                    ],
                ],
            ]
        ],
		"clicks" => [
			"exclude" => 1,
			"label" => "LLL:EXT:kk_downloader/locallang_db.xml:tx_kkdownloader_images.clicks",
			"config" => [
				"type" => "input",
				"size" => "30",
            ]
        ],
    ],
	"types" => [
		"0" => ["showitem" => "sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, hidden;;1, name, image, cat, imagepreview, downloaddescription, description,longdescription;;;richtext[paste|bold|italic|underline|formatblock|class|left|center|right|orderedlist|unorderedlist|outdent|indent|link|image]:rte_transform[mode=ts], clicks"]
    ],
	"palettes" => [
		"1" => ["showitem" => ""]
    ]
];



$GLOBALS['TCA']["tx_kkdownloader_cat"] = [
	"ctrl" => $GLOBALS['TCA']["tx_kkdownloader_cat"]["ctrl"],
	"interface" => [
		"showRecordFieldList" => "sys_language_uid,l18n_parent,l18n_diffsource,hidden,cat"
    ],
	"feInterface" => $GLOBALS['TCA']["tx_kkdownloader_cat"]["feInterface"],
	"columns" => [
		'sys_language_uid' => [
			'exclude' => 1,
			'label'  => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => [
				'type'                => 'select',
				'renderType' 		  => 'selectSingle',
				'foreign_table'       => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => [
					['LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1],
					['LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0]
                ]
            ]
        ],
		'l18n_parent' => [
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude'     => 1,
			'label'       => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config'      => [
				'type'  => 'select',
				'renderType' 		  => 'selectSingle',
				'items' => [
					['', 0],
                ],
				'foreign_table'       => 'tx_kkdownloader_cat',
				'foreign_table_where' => 'AND tx_kkdownloader_cat.pid=###CURRENT_PID### AND tx_kkdownloader_cat.sys_language_uid IN (-1,0)',
            ]
        ],
		'l18n_diffsource' => [
			'config' => [
				'type' => 'passthrough'
            ]
        ],
		'hidden' => [
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => [
				'type'    => 'check',
				'default' => '0'
            ]
        ],
		"cat" => [
			"exclude" => 1,
			"label" => "LLL:EXT:kk_downloader/locallang_db.xml:tx_kkdownloader_cat.cat",
			"config" => [
				"type" => "input",
				"size" => "30",
            ]
        ],
    ],
	"types" => [
		"0" => ["showitem" => "sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, hidden;;1, cat"]
    ],
	"palettes" => [
		"1" => ["showitem" => ""]
    ]
];
?>
