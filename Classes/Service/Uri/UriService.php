<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Service\Uri;

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

use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Service\Uri\Factory\DomainFactoryInterface;
use In2code\In2publishCore\Service\Uri\Factory\ForeignDomainFactoryV8;
use In2code\In2publishCore\Service\Uri\Factory\ForeignDomainFactoryV9;
use In2code\In2publishCore\Service\Uri\Factory\LocalDomainFactoryV8;
use In2code\In2publishCore\Service\Uri\Factory\LocalDomainFactoryV9;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use function version_compare;
use const TYPO3_branch;

/**
 * Class UriService
 */
class UriService
{
    const LOCAL = 'Local';
    const FOREIGN = 'Foreign';
    CONST VERSION_8 = 'Version 8';
    CONST VERSION_9 = 'Version 9';
    const FACTORY_MAP = [
        self::LOCAL => [
            self::VERSION_8 => LocalDomainFactoryV8::class,
            self::VERSION_9 => LocalDomainFactoryV9::class,
        ],
        self::FOREIGN => [
            self::VERSION_8 => ForeignDomainFactoryV8::class,
            self::VERSION_9 => ForeignDomainFactoryV9::class,
        ],
    ];

    /**
     * @param int $identifier
     * @param string $table
     * @param string $stage
     *
     * @return string
     */
    public function getDomain(int $identifier, string $table = 'pages', string $stage = self::LOCAL): string
    {
        return $this->getFactory($stage)->getDomainForRecord($identifier, $table);
    }

    public function buildUri(RecordInterface $record)
    {

    }

    /**
     * @param string $stage
     *
     * @return DomainFactoryInterface
     */
    protected function getFactory(string $stage): DomainFactoryInterface
    {
        $version = version_compare(TYPO3_branch, '9.3', '>=') ? self::VERSION_9 : self::VERSION_8;
        $factoryClass = self::FACTORY_MAP[$stage][$version];
        return GeneralUtility::makeInstance($factoryClass);
    }
}
