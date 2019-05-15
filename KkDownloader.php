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

use JWeiland\KkDownloader\Domain\Repository\CategoryRepository;
use JWeiland\KkDownloader\Domain\Repository\DownloadRepository;
use JWeiland\KkDownloader\Domain\Repository\LanguageRepository;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidExtensionNameException;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Page\PageRepository;
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
    public $defaultTemplate = 'EXT:kk_downloader/Resources/Private/Templates/MainTemplate.html';

    public $langArr;

    public $showCats;
    public $template;
    public $internal = [];

    /**
     * Path to download ($_GET)
     *
     * @var string
     */
    protected $download = '';

    /**
     * @var int
     */
    protected $did = 0;

    /**
     * UID of download to show on detail page
     *
     * @var int
     */
    protected $uidOfDownload = 0;

    /**
     * Contains settings of FlexForm
     *
     * @var array
     */
    protected $settings = [];

    protected $languageUid = 0;
    protected $languageMode = null;
    protected $languageOverlayMode = false;

    /**
     * @var DownloadRepository
     */
    protected $downloadRepository;

    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @var MarkerBasedTemplateService
     */
    protected $templateService;

    /**
     * The main method of the PlugIn
     *
     * @param string $content: The PlugIn content
     * @param array $conf: The PlugIn configuration
     * @return string The content that is displayed on the website
     */
    public function main(string $content, array $conf): string
    {
        $this->conf = $conf; // Storing configuration as a member var
        $this->pi_loadLL(); // Loading language-labels
        $this->pi_setPiVarDefaults(); // Set default piVars from TS

        $this->download = GeneralUtility::_GP('download');
        $this->did = (int)GeneralUtility::_GP('did');
        $this->uidOfDownload = (int)$this->piVars['uid'];

        // flexform Integration
        $this->pi_initPIflexform(); // Init and get the flexform data of the plugin
        $this->initialize();

        // if a download has happened
        if (!empty($this->download)) {
            $this->downloadImage(basename($this->download), $this->did);
        }

        // Template settings
        $templateFile = trim($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'template_file', 'sDEF'));
        $templateFile = $templateFile ?: $this->conf['templateFile'];
        if (empty($templateFile)) {
            $templateFile = $this->defaultTemplate;
        }

        $defaultDownloadPid = $this->conf['defaultDownloadPid'];
        if (empty($defaultDownloadPid)) {
            $defaultDownloadPid = 'all';
        }

        $this->internal['results_at_a_time'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'results_at_a_time', 'sDEF');
        $this->internal['results_at_a_time'] = $this->internal['results_at_a_time'] > 0 ? intval($this->internal['results_at_a_time']) : intval($this->conf['results_at_a_time']);
        $this->internal['results_at_a_time'] = $this->internal['results_at_a_time'] > 0 ? intval($this->internal['results_at_a_time']) : 1001;
        $this->internal['maxPages'] = $this->conf['pageBrowser.']['maxPages'] > 0 ? intval($this->conf['pageBrowser.']['maxPages']) : 10;

        $view = $this->getView();
        $view->setTemplatePathAndFilename($templateFile);
        if ($this->settings['whatToDisplay'] === 'SINGLE') {
            $download = $this->downloadRepository->getDownloadByUid($this->uidOfDownload);
            $download = $this->languageOverlay($download, 'tx_kkdownloader_images');

            if ( empty($download) ) {
               $download['singleDirectlyCalled'] = true;
            } else {
               if ($this->settings['showCats']) {
                   $download['categories'] = $this->completeCATs($download['cat']);
               }
               if ($this->settings['showImagePreview']) {
                   $download['previewImage'] = $this->createPreviewImage($download);
               }
               $download['fileItems'] = $this->generateDownloadLinks(
                   (int)$download['uid'],
                   (int)$this->conf['linkdescription']
               );
            }

            $view->assign('download', $download);
        } else {
            $storageFoldersForDownloads = $this->cObj->data['pages'];
            if (!$storageFoldersForDownloads) {
                $storageFoldersForDownloads = $defaultDownloadPid;
            }
            if (
                !empty($storageFoldersForDownloads)
                && strtolower(trim($storageFoldersForDownloads)) === 'all'
            ) {
                $storageFoldersForDownloads = '';
            }
            $downloads = $this->downloadRepository->getDownloads(
                GeneralUtility::intExplode(',', $storageFoldersForDownloads, true),
                $this->settings['categoryUid'],
                $this->settings['orderBy'],
                $this->settings['orderDirection']
            );
            foreach ($downloads as &$download) {
                if ($this->settings['showCats']) {
                    $download['categories'] = $this->completeCATs($download['cat']);
                }
                if ($this->settings['showImagePreview']) {
                    $download['previewImage'] = $this->createPreviewImage($download);
                }
                $download['fileItems'] = $this->generateDownloadLinks(
                    (int)$download['uid'],
                    (int)$this->conf['linkdescription']
                );
            }

            $view->assign('downloads', $downloads);

            // Browse list items;
            $this->internal['res_count'] = count($downloads);

            if ($this->internal['results_at_a_time'] > 0 && count($downloads) > $this->internal['results_at_a_time']) {
                if (!$this->conf['pageBrowser.']['showPBrowserText']) {
                    $this->LOCAL_LANG[$this->LLkey]['pi_list_browseresults_page'] = '';
                }

                $this->addPageBrowserSettingsToView($view);
            } else {
                if ($this->conf['pageBrowser.']['showResultCount']) {
                    $this->addPageBrowserSettingsToView($view);
                }
            }
        }

        $view->assignMultiple([
            'settings' => $this->settings,
            'pidOfDetailPage' => $this->conf['singlePID'] ?: $this->getTypoScriptFrontendController()->id
        ]);

        return $view->render();
    }

    protected function createPreviewImage(array $download): string
    {
        $previewImageForDownload = '';
        $allowedMimeTypes = [
            'image/gif',
            'image/jpeg',
            'image/png',
            'image/bmp',
            'image/tiff',
        ];

        // if download record contains a preview image
        if (!empty($download['imagepreview'])) {
            $preview = $download['imagepreview'];
            $filePath = $this->filebasepath . $preview;
            $img = $this->conf['image.'];
            $img['file'] = $filePath;
            $previewImageForDownload = $this->cObj->cObjGetSingle('IMAGE', $img);
        } else {
            // Loop throw download images and use first image with allowed mimetype as thumbnail
            $images = GeneralUtility::trimExplode(',', $download['image'], true);
            foreach ($images as $image) {
                $filePath = $this->filebasepath . $image;
                $imageExt = $this->checkMimeType($filePath);

                // create IMG-Tag, if image has allowed MimeType
                if (in_array($imageExt, $allowedMimeTypes)) {
                    $img = $this->conf['image.'];
                    $img['file'] = $filePath;
                    $previewImageForDownload = $this->cObj->cObjGetSingle('IMAGE', $img);
                    break;
                } else {
                    // MimeType is not an image, check against 'pdf'
                    $fileInfo = GeneralUtility::split_fileref($image);
                    $fileExt = trim($fileInfo['fileext']);
                    if ($fileExt === 'pdf') {
                        $img = $this->conf['image.'];
                        $img['file'] = $filePath;
                        $previewImageForDownload = $this->cObj->cObjGetSingle('IMAGE', $img);
                        break;
                    }
                }
            }
        }

        return $previewImageForDownload;
    }

    protected function initialize()
    {
        $this->initializeLanguage();
        $this->settings = $this->getFlexFormSettings();
        $this->downloadRepository = GeneralUtility::makeInstance(DownloadRepository::class);
        $this->categoryRepository = GeneralUtility::makeInstance(CategoryRepository::class);
        $this->templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
    }

    /**
     * Collect language information
     *
     * @return void
     */
    protected function initializeLanguage()
    {
        if (is_object($this->getTypoScriptFrontendController())) {
            $this->languageUid = (int)$this->getTypoScriptFrontendController()->sys_language_content;
            $this->languageOverlayMode = $this->getTypoScriptFrontendController()->sys_language_contentOL ?: false;
            $this->languageMode = $this->getTypoScriptFrontendController()->sys_language_mode ?: null;
        }

        $languageRepository = GeneralUtility::makeInstance(LanguageRepository::class);
        $this->langArr = $languageRepository->getAllLanguages();
    }

    /**
     * load all FLEXFORM data fields into variables for further use:
     */
    protected function getFlexFormSettings(): array
    {
        $settings = [];
        $settings['categoryUid'] = (int)$this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'dynField', 'sDEF');
        $settings['showCats'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showCats', 'sDEF');
        $settings['orderBy'] = trim($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'orderby', 'sDEF'));
        $settings['orderDirection'] = trim($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'ascdesc', 'sDEF'));
        $settings['orderDirection'] = $settings['orderDirection'] ?: 'ASC';
        $settings['showFileSize'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'filesize', 'sDEF');
        $settings['showPagebrowser'] = (bool)$this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showPagebrowser', 'sDEF');
        $settings['showImagePreview'] = (bool)$this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'imagepreview', 'sDEF');
        $settings['showDownloadsCount'] = (bool)$this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'downloads', 'sDEF');

        // @ToDo: needed for SINGLE view
        $settings['creationDateType'] = trim($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showCRDate', 'sDEF'));
        $settings['creationDateType'] = $settings['creationDateType'] ?: $this->conf['displayCreationDate'];

        $settings['showEditDate'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showEditDate', 'sDEF');
        $settings['showDateLastDownload'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showDateLastDownload', 'sDEF');
        $settings['showIPLastDownload'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showIPLastDownload', 'sDEF');
        $settings['showFileMDate'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showFileMDate', 'sDEF');
        $settings['whatToDisplay'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'what_to_display', 'sDEF');


        $creationDateType = trim($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showCRDate', 'sDEF'));
//        $settings['creationDateType'] = $settings['creationDateType'] ?: $this->conf['displayCreationDate'];
        $settings['creationDateType'] = '';
        if ($creationDateType > 0) {
             if ($creationDateType == '1') {
                 $dtf = $this->conf['dateformat'];
             } else {
                 $dtf = $this->conf['datetimeformat'];
             }
             if (empty($dtf)) {
                 $dtf = 'd.m.Y H:i';
             }
             $settings['creationDateType'] = $dtf;
         }


        return $settings;
    }

    /**
     * Generates the download links
     *
     * @param int $uid: The download uid
     * @param int $downloadDescriptionType:1 = filename.fileextension, 2 = filename, 3 = fileextension
     * @return string The generated links
     */
    protected function generateDownloadLinks(int $uid, int $downloadDescriptionType = 1)
    {
        $download = $this->downloadRepository->getDownloadByUid($uid);
        $images = GeneralUtility::trimExplode(',', $download['image'], true);
        $downloadDescriptions = GeneralUtility::trimExplode(
            '<br />',
            nl2br($download['downloaddescription']),
            true
        );
        $content = '';
        foreach ($images as $key => $image) {
            $fileInfo = GeneralUtility::split_fileref($image);
            $fileDescription = $downloadDescriptions[$key];
            if (empty($row['downloaddescription'])) {
                // Set fileDescription as configured by Type
                switch ($downloadDescriptionType) {
                    case 1:
                        $fileDescription = $fileInfo['filebody'] . '.' . $fileInfo['fileext'];
                        break;
                    case 2:
                        $fileDescription = $fileInfo['filebody'];
                        break;
                    case 3:
                        $fileDescription = $fileInfo['fileext'];
                        break;
                }
            }

            // Render DownloadIcon
            if (empty($this->conf['downloadIcon'])) {
                // If DownloadIcon is not configured, we try to get Icon by file-ext
                try {
                    $fileObject = ResourceFactory::getInstance()->retrieveFileOrFolderObject(
                        $this->filebasepath . $image
                    );
                    $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
                    $fileExtIcon = $iconFactory->getIconForResource($fileObject, Icon::SIZE_SMALL)->render();
                } catch (\Exception $e) {
                    $fileExtIcon = sprintf(
                        '<img src="%s" alt="allgemeine Datei-Ikone" />&nbsp;',
                        PathUtility::getAbsoluteWebPath(
                            GeneralUtility::getFileAbsFileName($this->conf['missingDownloadIcon'])
                        )
                    );
                }
            } else {
                $fileExtIcon = sprintf(
                    '<img src="%s" alt="File-Icon" />&nbsp;',
                    PathUtility::getAbsoluteWebPath(
                        GeneralUtility::getFileAbsFileName($this->conf['downloadIcon'])
                    )
                );
            }

            // render the LINK-Part:
            $content .= '<div class="linkOutput"><div class="dl-link"><div class="dl-link-text">' . $fileExtIcon . '&nbsp;';
            $content .= $this->pi_linkTP($fileDescription, $urlParameters= ['download' => $image, 'did' => $uid]);

               // add the filesize block, if desired
               if ($this->settings['showFileSize']) {
                   $downloadfile = $this->filebasepath.$image;
                   $fileSize = filesize($downloadfile);
                   $decimals = 2;
                   if ($fileSize < 1024) {
                       $decimals = 0;
                   }
                   $formattedFileSize = $this->format_size($fileSize, $decimals);
                   $fsc = trim($this->conf['filesizeClass']);
                   if (empty($fsc)) {
                       $filesizedivB = '<div>';
                       $filesizedivE = '</div>';
                   } else {
                       $filesizedivB = '<div class="'.$fsc.'">';
                       $filesizedivE = '</div>';
                   }
                   $content .= sprintf(
                       '</div> %s&nbsp;(%s%s)%s',
                       $filesizedivB,
                       LocalizationUtility::translate('filesize', 'kkDownloader'),
                       $formattedFileSize,
                       $filesizedivE
                   );
               }

            $content .= '</div>';

            // add the file date+time block, if desired
            if ($this->settings['showFileMDate']) {
                $downloadfile = $this->filebasepath . $image;
                $fileModificationTime = filemtime($downloadfile);
                if ($this->settings['showFileMDate'] == '1') {
                    $dtf = $this->conf['dateformat'];
                } else {
                    $dtf = $this->conf['datetimeformat'];
                }
                if (empty($dtf)) {
                    $dtf = 'd.m.Y H:i';
                }
                $formattedFileDate = ' ' . date($dtf, $fileModificationTime);
                $mdsc = trim($this->conf['fileMDateClass']);
                if (empty($mdsc)) {
                    $fileMDatedivB = '<div>';
                    $fileMDatedivE = '</div>';
                } else {
                    $fileMDatedivB = '<div class="' . $mdsc . '">';
                    $fileMDatedivE = '</div>';
                }
                $content .= sprintf(
                    ' %s%s%s%s',
                    $fileMDatedivB,
                    LocalizationUtility::translate('fileMDate', 'kkDownloader'),
                    $formattedFileDate,
                    $fileMDatedivE
                );
            }

            $content .= '</div>';
        }

        return $content;
    }

    /**
     * Format completeCats
     *
     * @param string $commaSeparatedCategories: Comma separated list of category UIDs
     * @return string formated category-marker content
     */
    protected function completeCats(string $commaSeparatedCategories)
    {
        $categories = [];
        $fullCategories = $this->categoryRepository->getCategoriesByUids($commaSeparatedCategories);
        foreach ($fullCategories as $fullCategory) {
            $categories[] = $fullCategory['cat'];
        }

        return implode(', ', $categories);
    }

    /**
     * Format FileSize
     *
     * @param int $size: size of file in bytes
     * @param int $round: filesize: true/false
     * @return string return formatted FileSize
     */
    protected function format_size(int $size, int $round = 0): string
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
        $downloadfile = $this->filebasepath . $image;
        if (!is_file($downloadfile)) {
            exit;
        }

        $valfilesize = filesize($downloadfile);
        $filename = $image;

        // check Mimetype
        $mimetype = $this->checkMimeType($downloadfile);

        header('Content-Type: ' . $mimetype);
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Content-Length: ' . $valfilesize);
        readfile($downloadfile);

        $this->downloadRepository->updateImageRecordAfterDownload($uid);

        exit;
    }

    /**
     * Add variables to view
     *
     * @param StandaloneView $view
     * @return void
     */
    protected function addPageBrowserSettingsToView(StandaloneView $view)
    {
        $amountOfDownloads = $this->internal['res_count'] ;
        $beginAt = (int)$this->piVars['pointer'] * $this->internal['results_at_a_time'];

        // Make Next link
        if ($amountOfDownloads > $beginAt + $this->internal['results_at_a_time']) {
            $next = ($beginAt + $this->internal['results_at_a_time'] > $amountOfDownloads) ? $amountOfDownloads - $this->internal['results_at_a_time']:$beginAt + $this->internal['results_at_a_time'];
            $next = intval($next / $this->internal['results_at_a_time']);
            $params = ['pointer' => $next];
            $next_link = $this->pi_linkTP_keepPIvars(
                LocalizationUtility::translate('pi_list_browseresults_next', 'kkDownloader'),
                $params
            );
            $view->assign('linkNext', $this->cObj->stdWrap($next_link, $this->conf['pageBrowser.']['next_stdWrap.']));
        }

        // Make Previous link
        if ($beginAt) {
            $prev = ($beginAt - $this->internal['results_at_a_time'] < 0)?0:$beginAt - $this->internal['results_at_a_time'];
            $prev = intval($prev / $this->internal['results_at_a_time']);
            $params = ['pointer' => $prev];
            $prev_link = $this->pi_linkTP_keepPIvars(
                LocalizationUtility::translate('pi_list_browseresults_prev', 'kkDownloader'),
                $params
            );
            $view->assign('linkPrev', $this->cObj->stdWrap($prev_link, $this->conf['pageBrowser.']['previous_stdWrap.']));
        }
        $pages = ceil($amountOfDownloads / $this->internal['results_at_a_time']);
        $actualPage = floor($beginAt / $this->internal['results_at_a_time']);

        if (ceil($actualPage - $this->internal['maxPages']/2) > 0) {
            $firstPage = ceil($actualPage - $this->internal['maxPages']/2);
            $addLast = 0;
        } else {
            $firstPage = 0;
            $addLast = floor(($this->internal['maxPages']/2)-$actualPage);
        }

        if (ceil($actualPage + $this->internal['maxPages']/2) <= $pages) {
            $lastPage = ceil($actualPage + $this->internal['maxPages'] / 2) > 0 ? ceil($actualPage + $this->internal['maxPages']/2) : 0;
            $subFirst = 0;
        } else{
            $lastPage = $pages;
            $subFirst = ceil($this->internal['maxPages']/2-($pages-$actualPage));
        }

        $firstPage = ($firstPage - $subFirst) > 0 ? ($firstPage - $subFirst) : $firstPage;
        $lastPage = ($lastPage + $addLast) <= $pages ? ($lastPage + $addLast) : $pages;
        $pages = '';
        for ($i = $firstPage; $i < $lastPage; $i++) {
            $item = (string)($i + 1);
            if ($this->conf['pageBrowser.']['showPBrowserText']) {
                $item = sprintf(
                    '%s %s',
                    LocalizationUtility::translate(
                        'pi_list_browseresults_page',
                        'kkDownloader'
                    ),
                    $item
                );
            }
            if (($beginAt >= $i * $this->internal['results_at_a_time']) && ($beginAt < $i * $this->internal['results_at_a_time'] + $this->internal['results_at_a_time'])) {
                $pages .= $this->cObj->stdWrap($item, $this->conf['pageBrowser.']['activepage_stdWrap.']) . ' ';
            } else {
                $params = ['pointer' => $i];
                $link = $this->pi_linkTP_keepPIvars($this->cObj->stdWrap($item, $this->conf['pageBrowser.']['pagelink_stdWrap.']) , $params) . ' ';
                $pages .= $this->cObj->stdWrap($link, $this->conf['pageBrowser.']['page_stdWrap.']);
            }
        }
        $view->assign('pages', $pages);

        $end_at = ($beginAt + $this->internal['results_at_a_time']);

        if ($this->conf['pageBrowser.']['showResultCount']) {
            if ($this->internal['res_count']) {
                $startingSpanTag = '<span' . $this->pi_classParam('browsebox-strong') . '>';
                $closingSpanTag = '</span>';
                $pageResultCount = sprintf(
                    LocalizationUtility::translate('pi_list_browseresults_displays', 'kkDownloader'),
                    $startingSpanTag . ($this->internal['res_count'] > 0 ? ($beginAt + 1) : 0) . $closingSpanTag,
                    $startingSpanTag . (min([$this->internal['res_count'], $end_at])) . $closingSpanTag,
                    $startingSpanTag . $this->internal['res_count'] . $closingSpanTag
                );
            } else {
                $pageResultCount = LocalizationUtility::translate('pi_list_browseresults_noResults', 'kkDownloader');
            }
            $view->assign('resultCount', $pageResultCount);
        }
    }

    protected function getView()
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);

        try {
            $view->getRequest()->setControllerExtensionName('kkDownloader');
        } catch (InvalidExtensionNameException $e) {
        }

        return $view;
    }

    protected function languageOverlay(array $row, string $tableName)
    {
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        if (
            isset($GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField'])
            && $row[$GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField']] > 0
        ) {
            //force overlay by faking default language record, as getRecordOverlay can only handle default language records
            $row['uid'] = $row[$GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField']];
            $row[$GLOBALS['TCA'][$tableName]['ctrl']['languageField']] = 0;
        }

        return $pageRepository->getRecordOverlay(
            $tableName,
            $row,
            $this->languageUid,
            (string)$this->languageOverlayMode
        );
    }

    /**
     * @return mixed|\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'];
    }
}
