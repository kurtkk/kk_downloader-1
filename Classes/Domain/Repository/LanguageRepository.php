<?php
namespace JWeiland\KkDownloader\Domain\Repository;

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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Repository for all configured languages in TYPO3
 */
class LanguageRepository
{
    /**
     * TableName for languages
     *
     * @var string
     */
    protected $tableName = 'sys_language';

    /**
     * Returns all languages configured in TYPO3
     *
     * @return array
     */
    public function getAllLanguages(): array
    {
        $languages = [];

        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($this->tableName);
        $statement = $queryBuilder
            ->select('*')
            ->from($this->tableName)
            ->execute();

        while ($row = $statement->fetch()) {
            $languages[$row['uid']] = $row;
        }

        return $languages;
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
