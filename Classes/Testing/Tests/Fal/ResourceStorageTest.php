<?php
namespace In2code\In2publishCore\Testing\Tests\Fal;

/***************************************************************
 * Copyright notice
 *
 * (c) 2016 in2code.de and the following authors:
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
 ***************************************************************/

use In2code\In2publishCore\Testing\Tests\TestCaseInterface;
use In2code\In2publishCore\Testing\Tests\TestResult;
use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\FlexFormService;

/**
 * Class ResourceStorageTest
 */
class ResourceStorageTest implements TestCaseInterface
{
    /**
     * @var FlexFormService
     */
    protected $flexFormService = null;

    /**
     * ResourceStorageTest constructor.
     */
    public function __construct()
    {
        $this->flexFormService = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Service\\FlexFormService');
    }

    /**
     * @return TestResult Returns a TestResult object holding all information about the failure or success
     */
    public function run()
    {
        $foreignDatabase = DatabaseUtility::buildForeignDatabaseConnection();
        $localDatabase = DatabaseUtility::buildLocalDatabaseConnection();

        $foreignStorages = $foreignDatabase->exec_SELECTgetRows('*', 'sys_file_storage', '1=1', '', '', '', 'uid');
        $localStorages = $localDatabase->exec_SELECTgetRows('*', 'sys_file_storage', '1=1', '', '', '', 'uid');

        $missingOnForeign = array_diff(array_keys($localStorages), array_keys($foreignStorages));
        $missingOnLocal = array_diff(array_keys($foreignStorages), array_keys($localStorages));

        $messages = array();

        if (!empty($missingOnLocal)) {
            $messages[] = 'fal.missing_on_local';
            foreach ($missingOnLocal as $storageUid) {
                $messages[] = $foreignStorages[$storageUid]['name'];
                unset($foreignStorages[$storageUid]);
            }
        }

        if (!empty($missingOnForeign)) {
            $messages[] = 'fal.missing_on_foreign';
            foreach ($missingOnForeign as $storageUid) {
                $messages[] = $localStorages[$storageUid]['name'];
                unset($localStorages[$storageUid]);
            }
        }

        $differentCaseSens = array();
        $differentDriver = array();
        $differentConfig = array();

        $storages = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher')->dispatch(
            'In2code\\In2publishCore\\Testing\\Tests\\Fal\\ResourceStorageTest',
            'filterStoragesForDriverTest',
            array(array_unique(array_merge(array_keys($localStorages), array_keys($foreignStorages))))
        );

        foreach ($storages[0] as $uid) {
            if (isset($localStorages[$uid], $foreignStorages[$uid])) {
                $localConfig = $this->getStorageConfiguration($localStorages, $uid);
                $foreignConfig = $this->getStorageConfiguration($foreignStorages, $uid);

                // driver type differences
                if ($localStorages[$uid]['driver'] !== $foreignStorages[$uid]['driver']) {
                    $differentDriver[] = sprintf(
                        'Local: "%s"; Foreign: "%s"; UID: %d',
                        $localStorages[$uid]['name'],
                        $foreignStorages[$uid]['name'],
                        $uid
                    );
                } elseif (isset($localConfig['caseSensitive'], $foreignConfig['caseSensitive'])
                          && (bool)$localConfig['caseSensitive'] !== (bool)$foreignConfig['caseSensitive']
                ) {
                    $differentCaseSens[] = 'Affected storage UID: ' . $uid;
                } elseif ($localConfig !== $foreignConfig) {
                    $differentConfig[] = sprintf(
                        'Storage: [%d] %s',
                        $uid,
                        $localStorages[$uid]['name']
                    );
                }
            }
        }
        if (!empty($differentDriver)) {
            $messages[] = 'fal.different_storage_drivers';
            $messages = array_merge($messages, $differentDriver);
        }
        if (!empty($differentCaseSens)) {
            $messages[] = 'fal.error_case_sensitive_setting';
            $messages = array_merge($messages, $differentCaseSens);
        }
        if (!empty($differentConfig)) {
            $messages[] = 'fal.configuration_differences';
            $messages = array_merge($messages, $differentConfig);
        }
        if ((!empty($differentDriver) || !empty($differentConfig))
            && !ExtensionManagementUtility::isLoaded('in2publish')
        ) {
            $messages[] = 'fal.xsp_premium_notice';
        }

        if (!empty($messages)) {
            return new TestResult('fal.storage_errors', TestResult::ERROR, $messages);
        }
        return new TestResult('fal.storage_okay');
    }

    /**
     * @return array List of test classes that need to pass before this test can be executed
     */
    public function getDependencies()
    {
        return array(
            'In2code\\In2publishCore\\Testing\\Tests\\Database\\LocalDatabaseTest',
            'In2code\\In2publishCore\\Testing\\Tests\\Database\\ForeignDatabaseTest',
        );
    }

    /**
     * @param array $storageConfig
     * @param int $uid
     * @return array
     */
    protected function getStorageConfiguration(array $storageConfig, $uid)
    {
        return $this->flexFormService->convertFlexFormContentToArray($storageConfig[$uid]['configuration']);
    }
}
