<?php
namespace JWeiland\KkDownloader\Plugin;

/*
 * This file is part of the kk_downloader project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Plugin\AbstractPlugin;

/**
 * Main Plugin class
 */
class KkDownloader extends AbstractPlugin
{
    /**
     * Same as class name
     *
     * @var string
     */
    public $prefixId = 'tx_kkdownloader_pi1';

    /**
     * Path to this script relative to the extension dir.
     *
     * @var string
     */
    public $scriptRelPath = 'pi1/class.tx_kkdownloader_pi1.php';

    /**
     * Path to extension
     *
     * @var string
     */
    public $extPath = 'typo3conf/ext/kk_downloader/';

    /**
     * The extension key
     *
     * @var string
     */
    public $extKey = 'kk_downloader';

    public $pi_checkCHash = true;
    public $filebasepath = 'uploads/tx_kkdownloader/';
    public $defaultTemplate = 'kk_download_template.html';

    public $langArr;
    public $sys_language_mode;

    public $filesize;
    public $showFileMDate;
    public $showCats;
    public $template;
    public $debug = false;
    public $internal = [];

    protected $did = '';

    /**
     * The main method of the PlugIn
     *
     * @param string $content: The PlugIn content
     * @param array $conf: The PlugIn configuration
     * @return string The content that is displayed on the website
     */
    public function main(string $content, array $conf)
    {
        $this->conf = $conf; // Storing configuration as a member var
        $this->pi_loadLL(); // Loading language-labels
        $this->pi_setPiVarDefaults(); // Set default piVars from TS

        $this->debug = $this->conf['debug'];

        if ($this->debug > 1) {
            print '<style>body {background:white !important; overflow:auto !important;} #header, #col1, #col2, #col3, #col3-2{left:500% !important; </style><div id="kk_downloader-debug" style="background:#fcfcfc;z-index:1200;} "><h1>Start</h1>';
        }

        // test if language version available
        // Die im Backend definierten Sprachen laden und
        // die Handhabung f�r �bersetzungen ermitteln.
        $lres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_language', '1=1' . $this->cObj->enableFields('sys_language'));

        $this->langArr = [];
        while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($lres)) {
            $this->langArr[$row['uid']] = $row;
        }

        // get sys_language_mode
        $this->sys_language_mode = $this->conf['sys_language_mode']?$this->conf['sys_language_mode']:$GLOBALS['TSFE']->sys_language_mode;

        $langUID = $GLOBALS['TSFE']->config['config']['sys_language_uid'];
        // if config.sys_language_uid is not set, $langUID = 0
        if (empty($langUID)) {
            $langUID = '0';
        }

        // take objects from dedicated page or sysfolder
        $this->download = GeneralUtility::_GP('download');
        $this->did = intval(GeneralUtility::_GP('did'));
        $this->pid = intval($this->piVars['uid']);


        // if a download has happened
        if (isset($this->download)) {
            $this->downloadImage(basename($this->download), $this->did);
        }

        // flexform Integration
        $this->pi_initPIflexform(); // Init and get the flexform data of the plugin


        // Template settings
        $templateflex_file = trim($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'template_file', 'sDEF'));
        $templateflex_file = $templateflex_file ? $templateflex_file : $this->conf['templateFile'];
        if (empty($templateflex_file) || $templateflex_file == '') {
            $templateflex_file = $this->extPath . $this->defaultTemplate;
        }
        if ($this->debug) {
            print '<p style="color:#f0f;">';
            print "Default-Template=$this->defaultTemplate";
            print "<br>this->extPath=$this->extPath";
            print "<br>templateflex_file=$templateflex_file";
            print '</p>';
        }
        $this->templateCode = $this->cObj->fileResource($templateflex_file);
        if (empty($this->templateCode)) return '<p>'.$this->pi_getLL('errNoTemplate').'</p>';


        // load all FLEXFORM data fields into variables for further use:
        $cat = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'dynField', 'sDEF');
        $this->showCats = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showCats', 'sDEF');
        $orderby = trim($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'orderby', 'sDEF'));
        $ascdesc = trim($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'ascdesc', 'sDEF'));
        $ascdesc = $ascdesc == '' ? ' ASC' : $ascdesc;
        $this->filesize = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'filesize', 'sDEF');
        $imagepreview = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'imagepreview', 'sDEF');
        $downloadcount = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'downloads', 'sDEF');
        // Display Creationdate and -time !?    (KK)
        $showCRDate = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showCRDate', 'sDEF');
        $showCRDate = trim($showCRDate) == '' ? $this->conf["displayCreationDate"] : $showCRDate;
        $defaultDownloadPid = $this->conf["defaultDownloadPid"];
        if (empty($defaultDownloadPid)) $defaultDownloadPid = 'all';
        $showEditDate = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showEditDate', 'sDEF');
        $showDateLastDownload = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showDateLastDownload', 'sDEF');
        $showIPLastDownload = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showIPLastDownload', 'sDEF');
        $this->showFileMDate = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showFileMDate', 'sDEF');
        $view = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'what_to_display', 'sDEF');

        $this->internal['results_at_a_time'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'results_at_a_time', 'sDEF');
        $this->internal['results_at_a_time'] = $this->internal['results_at_a_time'] > 0 ? intval($this->internal['results_at_a_time']) : intval($this->conf['results_at_a_time']);
        $this->internal['results_at_a_time'] = $this->internal['results_at_a_time'] > 0 ? intval($this->internal['results_at_a_time']) : 1001;
        $this->internal['maxPages'] = $this->conf['pageBrowser.']['maxPages'] > 0 ? intval($this->conf['pageBrowser.']['maxPages']) : 10;

        if ($this->debug > 1) {
            print '<h2>this->internal =</h2><p style="color:#a00;">"';
            print_r ($this->internal);
            print '"</p>';
        }


        $where = "tx_kkdownloader_images.deleted = 0 AND tx_kkdownloader_images.hidden = 0 ";
        $where1 = $where2 = '';

        $markerArray = [];

        if($view == 'SINGLE') {
            // get record data (multilanguage check)
            $abfrage = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                'l18n_parent, sys_language_uid',
                'tx_kkdownloader_images',
                "tx_kkdownloader_images.deleted=0 AND tx_kkdownloader_images.hidden=0 AND tx_kkdownloader_images.uid=$this->pid");


            while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($abfrage)) {
                // Id of default record
                $parentId = $row['l18n_parent'];
                // language of record
                $referenceLanguage = $row['sys_language_uid'];
            }

            // if $parentID = 0 its the default record
            if($parentId == "0" && $langUID != $referenceLanguage){
                $abfrageLang = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                    'uid',
                    'tx_kkdownloader_images',
                    "tx_kkdownloader_images.deleted=0
						AND tx_kkdownloader_images.hidden=0
						AND tx_kkdownloader_images.sys_language_uid=$langUID
						AND tx_kkdownloader_images.l18n_parent=$this->pid");

                while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($abfrageLang)) {
                    $langID = $row['uid'];
                }
                $where2 = " AND tx_kkdownloader_images.uid=$langID";
            } else {
                if($referenceLanguage == $langUID){
                    // do nothing, cause this is the correct record
                    $where2 = " AND tx_kkdownloader_images.uid=$this->pid";
                } else {
                    // get the translated records of the parentID
                    $abfrageLang = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                        'uid',
                        'tx_kkdownloader_images',
                        "tx_kkdownloader_images.deleted=0
   						AND tx_kkdownloader_images.hidden=0
   				  		AND tx_kkdownloader_images.sys_language_uid=$langUID
   						AND tx_kkdownloader_images.l18n_parent=$parentId");

                    // check if record available
                    $num = $GLOBALS['TYPO3_DB']->sql_num_rows($abfrageLang);
                    // if no record is available check if parent record exists
                    if(empty($num)) {
                        $langID = $parentId;
                    } else {
                        while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($abfrageLang)) {
                            $langID = $row['uid'];
                        }
                    }
                    $where2 = " AND tx_kkdownloader_images.uid=$langID";
                } // if($referenceLanguage == $langUID){
            }//if($parentId == "0"){
            $orderby = '';
            $ascdesc = '';
        } else {
            //  View = LIST:
            if ($this->debug > 1) print '<h1>L I S T E:</h1>';
            // check if language versin available
            $queryCheck = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                '*',
                'tx_kkdownloader_images',
                "tx_kkdownloader_images.sys_language_uid=$langUID");

            $numCheck = $GLOBALS['TYPO3_DB']->sql_num_rows($queryCheck);
            // if language version not available get default language
            if(empty($numCheck) && $langUID > 0) $langUID = 0;

            // WHERE parts:

            $downloadfolder = $GLOBALS['TYPO3_DB']->quoteStr($this->cObj->data['pages'],'tt_content');
            if ($this->debug > 1) {
                print '<br>download-folder = "' . $downloadfolder . '"';
                print "<h6>this->cObj->data['pages']= (this->pidList) ";
                print_r ($this->pidList);
                print "</h6>";
            }
            if (!$downloadfolder) $downloadfolder = $defaultDownloadPid;
            if ($this->debug > 1) print '<br>download-folder-PIDs = "' . $downloadfolder . '"';
            if(!empty($downloadfolder)){
                if (strtolower(trim($downloadfolder)) == 'all') {
                    $where1 = '';
                } else {
                    $where1 = " AND tx_kkdownloader_images.pid IN (".strval(intval($downloadfolder)).")";
                }
            }
            if ($this->debug > 1) print '<p style="color:#f80;">$where1 = "' . $where1 . '"</p>';

            if ($this->debug > 1) print '<br>$cat = "' . $cat . '"';
            // test if all categories
            if($cat == "0" || empty($cat)){
                $where2 = " AND tx_kkdownloader_images.sys_language_uid=$langUID";
                $group  = 'tx_kkdownloader_images.uid';
            } else {
                $cat = $GLOBALS['TYPO3_DB']->quoteStr($cat,'tt_content');
                #				$where2 = " AND (tx_kkdownloader_images.cat LIKE '%$cat%')";
                #           Thanks to Michael Hilgers ... - doesn't work with sets of categories like "2,1,3"
                $where2 = " AND ($cat IN (tx_kkdownloader_images.cat))";
                #           Thanks to Michael Hilgers for the new correct statement
                $where2 = " AND (find_in_set('$cat',tx_kkdownloader_images.cat )) >0";
                $where2.= " AND tx_kkdownloader_images.sys_language_uid=$langUID";
                $group  = '';
            }

            if($orderby == "" || empty($orderby)) {
                $order = "tx_kkdownloader_images.name ASC";
            } else {
                $order = "tx_kkdownloader_images." . $orderby . ' ' . $ascdesc;
            }
        }

        $field0 = "tx_kkdownloader_images.uid";
        $fields = "tx_kkdownloader_images.uid,
				  tx_kkdownloader_images.crdate,
				  tx_kkdownloader_images.tstamp,
				  tx_kkdownloader_images.name,
				  tx_kkdownloader_images.image,
				  tx_kkdownloader_images.imagepreview,
				  tx_kkdownloader_images.description,
				  tx_kkdownloader_images.longdescription,
				  tx_kkdownloader_images.clicks,
				  tx_kkdownloader_images.last_downloaded,
				  tx_kkdownloader_images.ip_last_download,
				  tx_kkdownloader_images.cat,
				  tx_kkdownloader_images.downloaddescription ";
        $table = "tx_kkdownloader_images";

        if ($this->debug) {
            print '<p style="color:#f40;">$where.$where1.$where2 = "' . $where.$where1.$where2 . '"</p>';
            print '<p style="color:#f60;">$group = "' . $group . '"</p>';
            print '<p style="color:#f80;">$order = "' . $order . '"</p>';
        }

        $query = $GLOBALS['TYPO3_DB']->exec_SELECTquery($field0, $table, $where.$where1.$where2, $group, $order);

        $num = $GLOBALS['TYPO3_DB']->sql_num_rows($query);

        if ($this->debug) print '<br>$Anzahl Records "num" = "' . $num . '"';

        // if no sql data available
        if (empty($num)) {
            $content = '<p style="color:#000;">' . $this->pi_getLL('novalue') . '</p>';
            $content .= '<p style="color:#000;">These are the SQL-Select-Parameters:"';
            $content .= '<p style="color:#000;">Tabelle = "' . $table . '"</p>';
            $content .= '<p style="color:#fa0;">where = "' . $where.$where1.$where2 . '"</p>';
            $content .= '<p style="color:#f00;">group = "' . $group . '"</p>';
            $content .= '<p style="color:#00f;">order = "' . $order . '"</p>';
            // let's get the download files
        } else {
            // if  S I N G L E   view
            if($view == 'SINGLE'){
                //		Template einbinden
                $this->template['template'] = $this->cObj->getSubpart($this->templateCode,'###TEMPLATE_SINGLE###');


                // if  L I S T       view
            } else {
                // include Template
                $this->template['template'] = $this->cObj->getSubpart($this->templateCode,'###TEMPLATE###');
                $this->template['listItem'] = $this->cObj->getSubpart($this->template['template'],'###LIST_ITEM###');
            } // end if($view == 'SINGLE')

            if ($this->debug > 1) print '<br>$this->internal[results_at_a_time] = "' . $this->internal['results_at_a_time'] . '"';

            // Browse list items;
            $this->internal['res_count'] = $num;

            if ($this->internal['results_at_a_time'] > 0 && $num > $this->internal['results_at_a_time']) {
                // configure pagebrowser
                if (!$this->conf['pageBrowser.']['showPBrowserText']) {
                    $this->LOCAL_LANG[$this->LLkey]['pi_list_browseresults_page'] = '';
                }

                //      			$markerArray['###BROWSE_LINKS###'] = $this->pi_list_browseresults($this->conf['pageBrowser.']['showResultCount'],$this->conf['pageBrowser.']['tableParams']);
                $markerArray = $this->getPageBrowser($markerArray);

            } else {
                if ($this->conf['pageBrowser.']['showResultCount']) {
                    $markerArray = $this->getPageBrowser($markerArray);
                } else {
                    $markerArray['###RESULT_COUNT###'] = '';
                }

                //      			$markerArray['###BROWSE_LINKS###'] = '';
                $markerArray['###LINK_PREV###'] = '';
                $markerArray['###PAGES###'] = '';
                $markerArray['###LINK_NEXT###'] = '';
            }


            if ($this->piVars['pointer'] > 0) {
                $limit_start = $this->piVars['pointer']*$this->internal['results_at_a_time'];
            } else {
                $limit_start = 0;
            }

            $query = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                $fields,
                $table,
                $where.$where1.$where2,
                $group,
                $order,
                $limit_start.','.$this->internal['results_at_a_time']
            );
            if ($this->debug) {
                print '<p style="color:#800;">$where.$where1.$where2 = "' . $where.$where1.$where2 . '"</p>';
                print '<p style="color:#800;">$group = "' . $group . '"</p>';
                print '<p style="color:#800;">$order = "' . $order . '"</p>';
            }

            $num = $GLOBALS['TYPO3_DB']->sql_num_rows($query);

            if ($this->debug) print '<br>$Anzahl Records "num" = "' . $num . '"';



            #   		   $this->template['template_filedetail'] = $this->cObj->getSubpart($this->templateCode,'###TEMPLATE_FILE_DETAIL###');
            $this->template['template_filedetail'] = $this->cObj->getSubpart($this->templateCode,'###TEMPLATE_DETAIL###');
            $this->template['linkItem'] = $this->cObj->getSubpart($this->template['template_filedetail'],'###LINK_ITEM###');

            if ($this->debug > 1) {
                print '<p style="color:#f00; font-weight:bold;">$this->template = <br>';
                print_r ($this->template);
                print '<hr>$this->template[template] = '.$this->template['template'];
                print '<br>$this->template[listItem] = '.$this->template['listItem'];
                print '</p><p style="color:#f60;">$this->template[linkItem] = '.$this->template['linkItem'];
                print '<br>$this->template[template_filedetail] = '.$this->template['template_filedetail'];
                print '</p>';
            }

            //          Text-Marker f�llen, generell ALLE, die im Template stehen!
            $this->fillTextMarker($markerArray);



            // Image setting
            $img = $this->conf["image."];
            $imageTablewidth = $img["file."];
            $imagewidth= $imageTablewidth['maxW']+ $this->conf["imageDistance"];
            // Site Id
            $siteid = $this->pi_getPageLink($GLOBALS["TSFE"]->id);
            // 	loop over all available entry in database
            for ($i = 0; $i < $num; $i++){
                $value = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($query);
                if ($this->debug > 1) {
                    print '<p style="color:blue;">$nach SQL-Zugriff "$value" = <br>';
                    print_r ($value);
                    print '</p>';
                }

                // if preview image
                if(!empty($value['imagepreview'])) {
                    $preview = $value['imagepreview'];
                    $filepath = $this->filebasepath.$preview;
                    $imageext = $this->checkMimeType($filepath);
                    $img = $this->conf["image."];
                    $img["file"] = $filepath;
                    $showImage = $this->cObj->IMAGE($img);
                } else {
                    // Check images, the first image or pdf file will be displayed as thumbnail
                    $images = explode(',',$value['image']);

                    foreach ($images as $val) {
                        $filepath = $this->filebasepath.$val;
                        $imageext = $this->checkMimeType($filepath);

                        // allowed mime types
                        $imagemimetypes = [
                            'image/gif',
                            'image/jpeg',
                            'image/png',
                            'image/bmp',
                            'image/tiff',
                        ];
                        // if no preview image
                        if(in_array($imageext,$imagemimetypes)) {
                            $img = $this->conf["image."];
                            $img["file"] = $filepath;
                            $showImage = $this->cObj->IMAGE($img);
                            break;
                        } else {
                            // check fileext
                            $fileinfo = GeneralUtility::split_fileref($val);
                            $fileExt=trim($fileinfo['fileext']);
                            if($fileExt == "pdf") {
                                $img = $this->conf["image."];
                                $img["file"] = $filepath;
                                $showImage = $this->cObj->IMAGE($img);
                                break;
                            } else {
                                $showImage = '';
                            }
                        }

                    } // foreach ($images as $val)
                } // if(!empty($value['imagepreview')) {
                if(empty($value['clicks'])) $value['clicks'] = '0';

                if ($imagepreview == true) {
                    $markerArray['###IMAGE###'] = $showImage;
                } else {
                    $markerArray['###IMAGE###'] = '';
                    $markerArray['###TEXT_IMAGE###'] = '';
                }

                // SinglePID of the site
                $id=$this->conf["singlePID"];
                // singleID of
                $singleID = $value['uid'];
                $more = $this->pi_linkToPage($this->pi_getLL('more'),$id,$target='',$urlParameters= ['tx_kkdownloader_pi1[uid]' => $singleID]);
                $markerArray['###MORE###'] = $more;

                // Ist der Marker ###LINKS### vorhanden!?
                $bLinks = strpos($this->template['template'], '###LINKS###') > 0;
                // NEU: Einzel-Marker f�r die Dateiwerte: ###FILE###, ###FILESIZE###, ###FILEMDATE###
                if (!empty($this->template['template_filedetail']) AND strpos($this->template['template_filedetail'], '###FILE###') AND $bLinks) {
                    // replace submarker-area with content:
                    $markerArray['###LINKS###'] = $this->generateFileItems($value['uid'],$this->conf["linkdescription"],$this->conf["downloadIcon"]);
                } else {
                    if ($bLinks) $markerArray['###LINKS###'] = $this->generateDownloadLinks($value['uid'],$this->conf["linkdescription"],$this->conf["downloadIcon"]);
                }

                $markerArray['###TITLE###'] = $value['name'];

                if($downloadcount){
                    $markerArray['###CLICKS###'] = $value['clicks'];
                } else {
                    $markerArray['###CLICKS###'] = '';
                    $markerArray['###TEXT_CLICKS###'] = '';
                }

                // print out CATs in List and Single view!?
                if ($this->debug > 1) print '<br><i>$showCats = "' . $this->showCats .'"</i>';
                if (!empty($this->showCats) && ($this->showCats == true || $this->showCats == '1')) {
                    $markerArray['###CATEGORIES###'] = $this->completeCATs($value['uid'], $value['cat']);
                } else {
                    $markerArray['###CATEGORIES###'] = '';
                    $markerArray['###TEXT_CATEGORIES###'] = '';
                }


                // Display Creationdate and -time !?    (KK)
                // displayCreationDate (0 = no date & time, 1 = only date, 2 = date & time)
                //							$showCRDate = 2;
                if($showCRDate == "1") {
                    $markerArray['###DATE###'] = date($this->conf['dateformat'], $value['crdate']);
                } elseif($showCRDate == "2"){
                    $markerArray['###DATE###'] = date($this->conf['dateformat'], $value['crdate']) . '&nbsp;' . date($this->conf['timeformat'], $value['crdate']) . '&nbsp;' . $this->pi_getLL('oclock');
                } else {
                    $markerArray['###DATE###'] = '';
                    $markerArray['###TEXT_DATE###'] = '';
                }
                if($showEditDate) {
                    $markerArray['###LASTEDIT###'] = date($this->conf['dateformat'], $value['tstamp']);
                } else {
                    $markerArray['###LASTEDIT###'] = '';
                    $markerArray['###TEXT_LASTEDIT###'] = '';
                }
                if($showDateLastDownload && $value['last_downloaded'] > 0) {
                    $markerArray['###LASTDOWNLOAD###'] = date($this->conf['datetimeformat'], $value['last_downloaded']);
                } else {
                    $markerArray['###LASTDOWNLOAD###'] = '';
                    $markerArray['###TEXT_LASTDOWNLOAD###'] = '';
                }
                if($showIPLastDownload && $value['ip_last_download'] != "") {
                    $markerArray['###IPLASTDOWNLOAD###'] = $value['ip_last_download'];
                } else {
                    $markerArray['###IPLASTDOWNLOAD###'] = '';
                    $markerArray['###TEXT_IPLASTDOWNLOAD###'] = '';
                }

                if(!empty($value['description'])){
                    $crop = intval($this->conf["stdwrap."]['description_stdwrap.']['crop']);
                    $crop = $crop > 10 ? $crop : 10000;
                    $description = substr ( $value['description'], 0, $crop );
                    $markerArray['###SHORTDESCRIPTION###'] = nl2br($description);
                } else {
                    $markerArray['###SHORTDESCRIPTION###'] = '';
                    $markerArray['###TEXT_SHORTDESCRIPTION###'] = '';
                }

                if(!empty($value['longdescription'])){
                    if (empty($this->conf['parseFunc.'])) {
                        $parseFunc = $GLOBALS['TSFE']->tmpl->setup['lib.']['parseFunc_RTE.'];
                        if (is_array($parseFunc)) {
                            $markerArray['###DESCRIPTION###'] = $this->cObj->parseFunc($value['longdescription'], $parseFunc);
                        } else {
                            $markerArray['###DESCRIPTION###'] = nl2br($value['longdescription']);
                        }
                    } else {
                        $markerArray['###DESCRIPTION###'] = $this->cObj->parseFunc($value['longdescription'],$this->conf['parseFunc.']);
                    }
                } else {
                    $markerArray['###DESCRIPTION###'] = '';
                    $markerArray['###TEXT_DESCRIPTION###'] = '';
                }

                $content_item .= $this->cObj->substituteMarkerArrayCached($this->template['listItem'], $markerArray, $filesize);
            }   // end for ($i = 0; $i < $num; $i++)

            if ($this->debug > 1) {
                print '<div style="color:#a44; font-size:16px; background:#ff0; color:#000;">Textmarker:<br />';
                print_r ($markerArray);
                print '<p style="color:#00f; font-family:arial;">$ci1 = "' . str_replace('<', '< ', $ci1) . '"</p>';
                print '<p style="color:#00f; font-family:arial;">$content_item = "' . str_replace('<', '< ', $content_item) . '"</p>';
                print '</div>';
            }

            // substitute subpart of template
            if($view == 'LIST'){
                $subpartArray['###CONTENT###'] = $content_item;
                $content .= $this->cObj->substituteMarkerArrayCached($this->template['template'], $markerArray, $subpartArray);
            } else {
                // substitue single template
                $content = $this->cObj->substituteMarkerArrayCached($this->template['template'], [], $markerArray);
            }
        }

        $content = str_replace('\n', '<br />', $content);

        if ($this->debug > 1) {
            print '<br><hr><br></div>';
        }

        return $content;
    }

    /**
     * Generates the download links
     *
     * @param int $uid: The download uid
     * @param int $downloaddescription:1 = filename.fileextension, 2 = filename, 3 = fileextension
     * @param string $downloadIcon: which downloadicon
     * @return string The generated links
     */
    protected function generateDownloadLinks(int $uid, int $downloaddescription = 1, string $downloadIcon = '')
    {
        if ($this->debug > 1) print '<h1 style="color:#00f;">START generateDownloadLinks</h1>';

        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'tx_kkdownloader_images.image, tx_kkdownloader_images.downloaddescription',
            'tx_kkdownloader_images',
            "tx_kkdownloader_images.uid=$uid");

        while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
            // explode images
            $images = explode(',',$row['image']);
            // explode link description
            $description = explode('<br />',nl2br($row['downloaddescription']));
            $i = 0;

            foreach ($images as $val) {
                $fileinfo = GeneralUtility::split_fileref($val);
                // no description given
                if(empty($row['downloaddescription'])){
                    // check typoscript settings
                    switch($downloaddescription){
                        case 1:
                            $fileName=trim($fileinfo['filebody']).'.'.trim($fileinfo['fileext']);
                            break;
                        case 2:
                            $fileName=trim($fileinfo['filebody']);
                            break;
                        case 3:
                            $fileName=trim($fileinfo['fileext']);
                            break;
                    }
                    // description given
                } else {
                    $fileName=trim($description[$i]);
                }

                // Render Downloadicon        (KK)
                $strDLI = '';
                if(!empty($downloadIcon)){
                    if (strlen(strrchr($downloadIcon, '/')) == 1) {     	// so the last letter is a Slash!
                        // now we take the corresponding GIFs for the different file-extensions,
                        // normally in folder "typo3/gfx/fileicons/"
                        // if file icon exist
                        if(file_exists($downloadIcon.trim($fileinfo['fileext'].'.gif'))){
                            $fi = trim($fileinfo['fileext']);
                            $strDLI = '<img src="'.$downloadIcon.$fi.'.gif" width="18" height="16"   alt="'.$fi.'-File-Icon" />&nbsp;';
                        } else {
                            $strDLI = '<img src="'.$this->conf["missingDownloadIcon"].'" alt="allgemeine Datei-Ikone" />&nbsp;';
                        }
                    } else {
                        $strDLI = '<img src="'.$downloadIcon.'" alt="File-Icon" />';
                    }
                }


                // render the LINK-Part:
                $content.='<div class="linkOutput"><div class="dl-link">' . $strDLI . '&nbsp;';
                $content.=$this->pi_linkTP($fileName, $urlParameters= ['download' => $val, 'did' => $uid]);
                $content.='</div>';

                // add the filesize block, if desired
                if($this->filesize){
                    $downloadfile = $this->filebasepath.$val;
                    //					$filesize = filesize($downloadfile);
                    $valfilesize = filesize($downloadfile);
                    $decimals = 2;
                    if ($valfilesize < 1024) $decimals = 0;
                    $valfilesize = $this->format_size($valfilesize, $decimals);
                    $fsc = trim($this->conf['filesizeClass']);
                    if ($this->debug > 1) print '<br>$fsc = "' . $fsc . '"';
                    if (empty($fsc) || $fsc == '') {
                        $filesizedivB = '<div>';
                        $filesizedivE = '</div>';
                    } else {
                        $filesizedivB = '<div class="'.$fsc.'">';
                        $filesizedivE = '</div>';
                    }
                    $content.= ' '.$filesizedivB.$this->pi_getLL('bracketstart').$this->pi_getLL('filesize').$valfilesize.$this->pi_getLL('bracketend').$filesizedivE;
                }
                // add the file date+time block, if desired
                if($this->showFileMDate && $this->showFileMDate != '0'){
                    $downloadfile = $this->filebasepath.$val;
                    $filemtime = filemtime($downloadfile);
                    // displayCreationDate (0 = no date & time, 1 = only date, 2 = date & time)
                    if($this->showFileMDate == '1') {
                        $dtf = $this->conf['dateformat'];
                    } elseif($showCRDate == '2'){
                        $dtf = $this->conf['datetimeformat'];
                    }
                    $dtf = $this->conf['datetimeformat'];
                    if ($dtf == '' || empty($dtf)) $dtf = "d.m.Y H:i";
                    $strFilemtime = date($dtf, $filemtime);
                    $mdsc = trim($this->conf['fileMDateClass']);
                    if ($this->debug > 1) print '<p style="color:#f0f;"><br>$mdsc = "' . $mdsc . '" <br>/$downloadfile = "' . $downloadfile . '" <br>/$filemtime ="' . $filemtime . '" <br>$strFilemtime = "' . $strFilemtime . '"</p>';
                    if (empty($mdsc) || $mdsc == '') {
                        $fileMDatedivB = '<div>';
                        $fileMDatedivE = '</div>';
                    } else {
                        $fileMDatedivB = '<div class="'.$mdsc.'">';
                        $fileMDatedivE = '</div>';
                    }
                    $content.= ' '.$fileMDatedivB.$this->pi_getLL('fileMDate').$strFilemtime.$filesizedivE;
                }
                $content.='</div>';
                if ($this->debug > 1) print '<p style="color:#099;">generateDownloadLinks ###LINKS### ->$content = "' . str_replace('<', '< ', $content) . '"</p>';
                $i++;
            }
        }

        return $content;
    }

    /**
     * Generates the separate items for the download links
     *
     * @param int $uid: The download uid
     * @param int $downloaddescription: 1 = filename.fileextension, 2 = filename, 3 = fileextension
     * @param string $downloadIcon: which downloadicon
     * @return string FileItems rendered as HTML
     */
    protected function generateFileItems(int $uid, int $downloaddescription = 1, string $downloadIcon = '')
    {
        if ($this->debug > 1) print '<h1 style="color:#F00;">START generateFileItems</h1>';

        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'tx_kkdownloader_images.image, tx_kkdownloader_images.downloaddescription',
            'tx_kkdownloader_images',
            "tx_kkdownloader_images.uid=$uid");

        $ci = '';
        while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
            // explode images
            $images = explode(',',$row['image']);
            // explode link description
            $description = explode('<br />',nl2br($row['downloaddescription']));
            $i = 0;

            foreach ($images as $val) {
                $fileinfo = GeneralUtility::split_fileref($val);

                // no description given
                if(empty($row['downloaddescription'])){
                    // check typoscript settings
                    switch($downloaddescription){
                        case 1:
                            $fileName=trim($fileinfo['filebody']).'.'.trim($fileinfo['fileext']);
                            break;
                        case 2:
                            $fileName=trim($fileinfo['filebody']);
                            break;
                        case 3:
                            $fileName=trim($fileinfo['fileext']);
                            break;
                    }
                    // description given
                } else {
                    $fileName=trim($description[$i]);
                }

                // Render Downloadicon        (KK)
                $strDLI = '';
                if(!empty($downloadIcon)){
                    if (strlen(strrchr($downloadIcon, '/')) == 1) {     	// so the last letter is a Slash!
                        // now we take the corresponding GIFs for the different file-extensions,
                        // normally in folder "typo3/gfx/fileicons/"
                        // if file icon exist
                        if(file_exists($downloadIcon.trim($fileinfo['fileext'].'.gif'))){
                            $fi = trim($fileinfo['fileext']);
                            $strDLI = '<img src="'.$downloadIcon.$fi.'.gif" width="18" height="16" alt="'.$fi.'-File-Icon" />&nbsp;';
                        } else {
                            $strDLI = '<img src="'.$this->conf["missingDownloadIcon"].'" alt="allgemeine Datei-Ikone" />&nbsp;';
                        }
                    } else {
                        $strDLI = '<img src="'.$downloadIcon.'" alt="File-Icon" />';
                    }
                }

                $ma['###TEXT_FILE###'] = $this->pi_getLL('txtFILE');
                $ma['###FILE###'] = $this->pi_linkTP($fileName, $urlParameters= ['download' => $val, 'did' => $uid]);
                $ma['###ICON###'] = $strDLI;

                // add the filesize block, if desired
                if ($this->debug > 1) print '<br><h2 style="color:red;">if($this->filesize) = "' . $this->filesize . '"</h2>';
                if($this->filesize){
                    $downloadfile = $this->filebasepath.$val;
                    //						$filesize = filesize($downloadfile);
                    $valfilesize = filesize($downloadfile);
                    $decimals = 2;
                    if ($valfilesize < 1024) $decimals = 0;
                    $valfilesize = $this->format_size($valfilesize, $decimals);
                    $fsc = trim($this->conf['filesizeClass']);
                    if ($this->debug > 1) print '<br>$fsc(generateFileItems) = "' . $valfilesize . '"';

                    $ma['###TEXT_FILESIZE###'] = $this->pi_getLL('txtFILESIZE');
                    $ma['###FILESIZE###'] = $valfilesize;
                } else {
                    $ma['###TEXT_FILESIZE###'] = '';
                    $ma['###FILESIZE###'] = '';
                }

                if ($this->debug > 1) print '<br>$this->showFileMDate = "' . $this->showFileMDate . '"';
                if ($this->showFileMDate && $this->showFileMDate != '0') {
                    $downloadfile = $this->filebasepath.$val;
                    $filemtime = filemtime($downloadfile);
                    // displayCreationDate (0 = no date & time, 1 = only date, 2 = date & time)
                    $dtf = $this->conf['datetimeformat'];
                    if($this->showFileMDate == '1') $dtf = $this->conf['dateformat'];
                    if ($dtf == '' || empty($dtf)) $dtf = "d.m.Y H:i";

                    $ma['###TEXT_FILEMDATE###'] = $this->pi_getLL('txtFILEMDATE');
                    $ma['###FILEMDATE###'] = date($dtf, $filemtime);
                    if ($ma['###FILEMDATE###'] == '###FILEMDATE###') $ma['###FILEMDATE###'] = '?';
                } else {
                    $ma['###TEXT_FILEMDATE###'] = '';
                    $ma['###FILEMDATE###'] = '';
                }

                $ci .= $this->cObj->substituteMarkerArrayCached($this->template['template_filedetail'], $ma);

                if ($this->debug == 2) {
                    print '<h2 style="color:#089;">i = "' . $i . '</h2>';
                    print '<p style="color:#089;">1.) generateFileItems $ci = "' . str_replace('<', '< ', $ci) . '"</p>';
                }
                $i++;
            }
        }

        if ($this->debug > 1) print '<h1 style="color:#F00;">ENDE generateFileItems</h1>';

        return $ci;
    }

    /**
     * Format completeCats
     *
     * @param int $uid: ID of the calling object
     * @param string $cats: Comma separated list of category UIDs
     * @return string formated category-marker content
     */
    protected function completeCats(int $uid, string $cats)
    {
        $cont = '';
        $uid  = $GLOBALS['TYPO3_DB']->quoteStr($uid, 'tt_content');
        $cats = $GLOBALS['TYPO3_DB']->quoteStr($cats, 'tt_content');
        if ($cats === '' || empty($cats)) {
            $cats = 0;
        }
        $fields = 'tx_kkdownloader_cat.cat';
        $table = " tx_kkdownloader_cat INNER JOIN tx_kkdownloader_images ON (tx_kkdownloader_cat.uid IN ($cats))";
        $where = "tx_kkdownloader_images.uid = $uid";
        if ($this->debug > 1) {
            print '<p style="color:#d0d;">$completeCats-><br>uid = '.$uid.'<br>cats = ' .$cats.'<br>sql =<br>' . $sql .'</p>';
        }
        $ret = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields,$table,$where);
        $num = $GLOBALS['TYPO3_DB']->sql_num_rows($ret);

        if ($this->debug > 1) print '<br>completeCats->$Anzahl Records "num" = "' . $num . '"';

        // if no sql data available
        if(!empty($num)) {
            #         $cont = $this->pi_getLL('category_text');
            while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($ret)) {
                $cont .= $row["cat"] . ', ';
            }
            $cont = substr($cont, 0, -2);
            if ($this->debug > 1) print '<br>$cont = "' . str_replace('<', '[', $cont) . '"';
        }

        return $cont;
    }

    /**
     * Format filesize
     *
     * @param int $size: size of file in bytes
     * @param int $round: filesize: true/false
     * @return float return formatted filesize
     */
    protected function format_size(int $size, int $round = 0)
    {
        //Size must be bytes!
        $sizes = [' Bytes', ' kB', ' MB', ' GB', ' TB', ' PB', ' EB', ' ZB', ' YB'];
        for ($i = 0; $size > 1024 && $i < count($sizes) - 1; $i++) {
            $size /= 1024;
        }

        return round($size, $round) . $sizes[$i];
    }

    /**
     * Checks mime_type of an image
     *
     * @param string $file
     * @return string
     */
    protected function checkMimeType(string $file)
    {
        $imageinfos = getimagesize($file);
        $imagetype = $imageinfos[2];
        $mimetype = image_type_to_mime_type($imagetype);

        return $mimetype;
    }

    /**
     * Download the file
     *
     * @param string $image: Name of download
     * @param int $uid: download uid for click counter
     * @return void
     */
    protected function downloadImage(string $image, int $uid)
    {
        $downloadfile = $this->filebasepath.$image;
        if (!is_file($downloadfile)) {
            exit;
        }

        $valfilesize = filesize($downloadfile);
        $filename = $image;

        // check Mimetype
        $mimetype = $this->checkMimeType($downloadfile);

        header("Content-Type: $mimetype");
        header("Content-Disposition: attachment; filename=$filename");
        header("Content-Length: $valfilesize");

        // TYPO3 output buffering beenden:
        // ab 13.07.2012 raus genommen wg. Schwierigkeiten bei einem User (php-Fehlermeldung)      ob_end_clean();
        readfile($downloadfile);

        $GLOBALS['TYPO3_DB']->sql_query("UPDATE tx_kkdownloader_images
									SET tx_kkdownloader_images.clicks = tx_kkdownloader_images.clicks + 1
									WHERE tx_kkdownloader_images.uid = $uid");
        $z = date("U");
        $GLOBALS['TYPO3_DB']->sql_query("UPDATE tx_kkdownloader_images
									SET tx_kkdownloader_images.last_downloaded = $z
									WHERE tx_kkdownloader_images.uid = $uid");

        $z1 = $_SERVER["REMOTE_ADDR"];
        $GLOBALS['TYPO3_DB']->sql_query("UPDATE tx_kkdownloader_images
									SET tx_kkdownloader_images.ip_last_download = '$z1'
									WHERE tx_kkdownloader_images.uid = $uid");

        exit;
    }

    /**
     * Replace all possible textmarkers:
     * call by ref: &
     *
     * @param string $markerArray: self-explanatory
     * @return void
     */
    protected function fillTextMarker(array &$markerArray)
    {
        // find first position of a TEXT_marker and the full length of the string
        $i = strpos($this->template['template'], '###TEXT_', 0);
        $l = strlen($this->template['template']);

        // no TEXT_marker!? go back
        if ($i <= 0) return;

        // build an array with the TEXT-Markers in and separate the markers key-word
        while ($i < $l AND $i > 1) {
            $k = strpos($this->template['template'], '###', $i+1);
            $s = substr ($this->template['template'], $i+8, $k-$i-8);
            $textmarker_arrayl[$x++] = $s;
            $i = strpos($this->template['template'], '###TEXT_', $k+3);
        }
        $textmarker_array = array_unique($textmarker_arrayl);

        // and now, replace them with content out of the locallang.xml:
        foreach ($textmarker_array as $tt) {
            $mi = '###TEXT_' . $tt . '###';
            $LLi = 'txt' . $tt;
            $markerArray[$mi] = $this->pi_getLL($LLi);
        }

        if ($this->debug > 1) {
            print '<div style="color:#484;">Textmarker:<br />';
            print_r ($markerArray);
            print '</div>';
        }
    }

    /**
     * Getting the page browser for paging
     *
     * @param array $markerArray: Array containing the template marker
     * @return array $markerArray: Array containing the template marker
     */
    protected function getPageBrowser($markerArray): array
    {
        if ($this->debug > 1) {
            print '<p style="color:#0d0;">$this->piVars = <br>';
            print_r ($this->piVars);
            print '</p>';
        }
        $newsCount = $this->internal['res_count'] ;
        $begin_at = intval($this->piVars['pointer']) * $this->internal['results_at_a_time'];

        if ($this->debug > 1) print '<p style="color:#0df;">$newsCount = '.$newsCount.'</p>';
        if ($this->debug > 1) print '<p style="color:#0df;">$begin_at = '.$begin_at.'</p>';


        // Make Next link
        if ($newsCount > $begin_at + $this->internal['results_at_a_time']) {
            $next = ($begin_at + $this->internal['results_at_a_time'] > $newsCount) ? $newsCount - $this->internal['results_at_a_time']:$begin_at + $this->internal['results_at_a_time'];
            $next = intval($next / $this->internal['results_at_a_time']);
            $params = ['pointer' => $next];
            $next_link = $this->pi_linkTP_keepPIvars($this->pi_getLL('pi_list_browseresults_next', 'Next >'), $params);
            $markerArray['###LINK_NEXT###'] = $this->cObj->stdWrap($next_link, $this->conf['pageBrowser.']['next_stdWrap.']);

        } else {
            $markerArray['###LINK_NEXT###'] = '';
        }
        if ($this->debug > 1) print '<p style="color:#0df;">$markerArray[###LINK_NEXT###] = '.$markerArray['###LINK_NEXT###'].'</p>';
        // Make Previous link
        if ($begin_at) {
            $prev = ($begin_at - $this->internal['results_at_a_time'] < 0)?0:$begin_at - $this->internal['results_at_a_time'];
            $prev = intval($prev / $this->internal['results_at_a_time']);
            $params = ['pointer' => $prev];
            $prev_link = $this->pi_linkTP_keepPIvars($this->pi_getLL('pi_list_browseresults_prev', '< Previous'), $params);
            $markerArray['###LINK_PREV###'] = $this->cObj->stdWrap($prev_link, $this->conf['pageBrowser.']['previous_stdWrap.']);
        } else {
            $markerArray['###LINK_PREV###'] = '';
        }
        if ($this->debug > 1) print '<p style="color:#0fd;">$markerArray[###LINK_PREV###] = '.$markerArray['###LINK_PREV###'].'</p>';


        if ($this->debug > 1) {
            print '<p style="color:#008;">$params = <br>';
            print_r ($params);
            print '</p>';
        }


        $firstPage = 0;
        $lastPage = $pages = ceil($newsCount / $this->internal['results_at_a_time']);
        $actualPage = floor($begin_at / $this->internal['results_at_a_time']);

        if(ceil($actualPage - $this->internal['maxPages']/2) > 0){
            $firstPage=ceil($actualPage - $this->internal['maxPages']/2);
            $addLast=0;
        } else {
            $firstPage=0;
            $addLast=floor(($this->internal['maxPages']/2)-$actualPage);
        }

        if(ceil($actualPage + $this->internal['maxPages']/2) <= $pages){
            $lastPage=ceil($actualPage + $this->internal['maxPages']/2) > 0 ? ceil($actualPage + $this->internal['maxPages']/2) : 0;
            $subFirst=0;
        } else{
            $lastPage=$pages;
            $subFirst=ceil($this->internal['maxPages']/2-($pages-$actualPage));
        }

        $firstPage=($firstPage-$subFirst)>0?($firstPage-$subFirst):$firstPage;
        $lastPage=($lastPage+$addLast)<=$pages?($lastPage+$addLast):$pages;
        if ($this->debug > 1) {
            print '<p style="color:#d66;">$firstPage = '.$firstPage.'</p>';
            print '<p style="color:#d66;">$lastPage = '.$lastPage.'</p>';
        }

        for ($i = $firstPage; $i < $lastPage; $i++) {
            if (($begin_at >= $i * $this->internal['results_at_a_time']) && ($begin_at < $i * $this->internal['results_at_a_time'] + $this->internal['results_at_a_time'])) {
                $item = ($this->conf['pageBrowser.']['showPBrowserText']?$this->pi_getLL('pi_list_browseresults_page', 'Page'):'') . (string)($i + 1);
                $markerArray['###PAGES###'] .= $this->cObj->stdWrap($item, $this->conf['pageBrowser.']['activepage_stdWrap.']) . ' ';
            } else {
                $item = ($this->conf['pageBrowser.']['showPBrowserText']?$this->pi_getLL('pi_list_browseresults_page', 'Page'):'') . (string)($i + 1);
                $params = ['pointer' => $i];
                $link = $this->pi_linkTP_keepPIvars($this->cObj->stdWrap($item, $this->conf['pageBrowser.']['pagelink_stdWrap.']) , $params) . ' ';
                $markerArray['###PAGES###'] .= $this->cObj->stdWrap($link, $this->conf['pageBrowser.']['page_stdWrap.']);
            }
        }
        if ($this->debug > 1) {
            print '<p style="color:#909;">$item = '.$item.'<br>';
            print '$link = '.$link.'<br>';
            print '$params = ';
            print_r ($params);
            print '<br>$markerArray[###PAGES###] = '.$markerArray['###PAGES###'].'</p>';
        }

        $end_at = ($begin_at + $this->internal['results_at_a_time']);

        if ($this->conf['pageBrowser.']['showResultCount']) {
            if ($this->debug > 1) print '<h6>~~~~~~~~~~~ IF (showResultCount) = TRUE</h6>';
            $markerArray['###RESULT_COUNT###'] = ($this->internal['res_count'] ?
                sprintf(
                    str_replace('###SPAN_BEGIN###','<span'.$this->pi_classParam('browsebox-strong').'>',$this->pi_getLL('pi_list_browseresults_displays','Displaying results ###SPAN_BEGIN###%s to %s</span> out of ###SPAN_BEGIN###%s</span>')),
                    $this->internal['res_count'] > 0 ? ($begin_at+1) : 0,
                    min([$this->internal['res_count'],$end_at]),
                    $this->internal['res_count']
                ) :
                $this->pi_getLL('pi_list_browseresults_noResults','Sorry, no items were found.'));
        }
        else {
            if ($this->debug > 1) print '<h6>~~~~~~~~~~~ IF (showResultCount) = FALSE</h6>';
            $markerArray['###RESULT_COUNT###'] = '';
        }

        return $markerArray;
    }
}
