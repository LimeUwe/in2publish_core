<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Service\Uri\Factory;

/*
 * Copyright notice
 *
 * (c) 2019 in2code.de
 * Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

use In2code\In2publishCore\Domain\Service\ForeignSiteFinder;
use In2code\In2publishCore\Service\Configuration\TcaService;
use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class ForeignDomainFactoryV9 implements DomainFactoryInterface
{
    /**
     * @var Connection
     */
    protected $connection = null;

    /**
     * @var TcaService
     */
    protected $tcaService = null;

    /**
     * @var SiteFinder
     */
    protected $siteFinder = null;

    /**
     * @var null
     */
    protected $foreignDomainFactoryV8 = null;

    /**
     * LocalDomainFactoryV8 constructor.
     */
    public function __construct()
    {
        $this->connection = DatabaseUtility::buildForeignDatabaseConnection();
        $this->tcaService = GeneralUtility::makeInstance(TcaService::class);
        $this->siteFinder = GeneralUtility::makeInstance(ForeignSiteFinder::class);
        $this->foreignDomainFactoryV8 = GeneralUtility::makeInstance(ForeignDomainFactoryV8::class);
    }

    /**
     * @param int $uid
     * @param string $table
     *
     * @return string
     */
    public function getDomainForRecord(int $uid, string $table = 'pages'): string
    {
        // TYPO3 prefers sys_domain records, so we mimic this behaviour
        if ($uri = $this->foreignDomainFactoryV8->getDomainForRecord($uid, $table)) {
            return $uri;
        }
        $languageField = $this->tcaService->getLanguageField($table);

        $query = $this->connection->createQueryBuilder();
        $recordRow = $query->select('*')->from($table)->where('uid=' . $uid)->execute()->fetch();

        $sysLanguage = $recordRow[$languageField];

        if ('pages' !== $table) {
            $query = $this->connection->createQueryBuilder();
            $pageIdentifier = $query->select('*')
                                    ->from('pages')
                                    ->where('uid=' . (int)$recordRow['pid'])
                                    ->execute()
                                    ->fetch();
        } else {
            $pageIdentifier = $uid;
        }

        try {
            $site = $this->siteFinder->getSiteByPageId($pageIdentifier);
        } catch (SiteNotFoundException $e) {
            return '';
        }

        return (string)$site->getLanguageById($sysLanguage)->getBase();
    }
}
