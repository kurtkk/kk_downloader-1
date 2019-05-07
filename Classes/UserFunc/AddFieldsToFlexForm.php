<?php
namespace JWeiland\KkDownloader\UserFunc;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * UserFunc to add fields to FlexForm
 */
class AddFieldsToFlexForm
{
    /**
     * add fields to flexform
     *
     * @param array $config
     * @return array
     */
    public function addFields(array $config): array
    {
        $storagePid = $this->getStorageFolderPid();
        $optionList = [
            0 => [
                0 => 'all',
                1 => 0
            ]
        ];

        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_kkdownloader_cat');
        if (!empty($storagePid)) {
            $queryBuilder->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($storagePid, \PDO::PARAM_INT)
                )
            );
        }

        $statement = $queryBuilder
            ->select('uid', 'cat')
            ->from('tx_kkdownloader_cat')
            ->where()
            ->orderBy('cat', 'ASC')
            ->execute();

        while ($row = $statement->fetch()) {
            array_push(
                $optionList,
                [
                    0 => $row['cat'],
                    1 => $row['uid']
                ]
            );
        }

        $config['items'] = array_merge($config['items'], $optionList);

        return $config;
    }

    /**
     * Returning sysfolder ID where records are stored
     */
    public function getStorageFolderPid(): int
    {
        $positionPid = htmlspecialchars_decode(GeneralUtility::_GET('id'));

        if (empty($positionPid)) {
            $siteId = GeneralUtility::_GET('returnUrl');
            $siteId = GeneralUtility::explodeUrl2Array($siteId);
            $siteId = $siteId['db_list.php?id'];
            $positionPid = $siteId;
        }

        // Negative PID values is pointing to a page on the same level as the current.
        if ($positionPid < 0) {
            $pidRow = BackendUtility::getRecord('pages', abs($positionPid), 'pid');
            $positionPid = $pidRow['pid'];
        }

        $row = BackendUtility::getRecord('pages', $positionPid);
        $TSconfig = BackendUtility::getTCEFORM_TSconfig('pages', $row);

        return (int)$TSconfig['_STORAGE_PID'];
    }

    /**
     * Get TYPO3s Connection Pool
     *
     * @return ConnectionPool
     */
    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
