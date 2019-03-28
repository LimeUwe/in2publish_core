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

use In2code\In2publishCore\Utility\DatabaseUtility;
use PDO;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Connection;

/**
 * Class LocalDomainFactoryV8
 */
class LocalDomainFactoryV8 implements DomainFactoryInterface
{
    /**
     * @var Connection
     */
    protected $connection = null;

    /**
     * LocalDomainFactoryV8 constructor.
     */
    public function __construct()
    {
        $this->connection = DatabaseUtility::buildLocalDatabaseConnection();
    }

    /**
     * @param int $uid
     * @param string $table
     *
     * @return string
     */
    public function getDomainForRecord(int $uid, string $table = 'pages'): string
    {
        if ('pages' !== $table) {
            $query = $this->connection->createQueryBuilder();
            $pid = $query->select('pid')->from($table)->where('uid=' . (int)$uid)->execute()->fetch();
        } else {
            $pid = $uid;
        }

        $rootLine = BackendUtility::BEgetRootLine($pid);
        foreach ($rootLine as $page) {
            $query = $this->connection->createQueryBuilder();
            $query->getRestrictions()->removeAll();
            $domainRecord = $query->select('domainName')
                                  ->from('sys_domain')
                                  ->where($query->expr()->eq('pid', (int)$page['uid']))
                                  ->andWhere($query->expr()->eq('hidden', 0))
                                  ->orderBy('sorting', 'ASC')
                                  ->setMaxResults(1)
                                  ->execute()
                                  ->fetch(PDO::FETCH_ASSOC);
            if (isset($domainRecord['domainName'])) {
                return $domainRecord['domainName'];
            }
        }
        return '';
    }
}
