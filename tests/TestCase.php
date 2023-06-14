<?php
/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

declare(strict_types=1);

namespace tests;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the base class for all yii framework unit tests.
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * Clean up after test case.
     */
    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        $logger = Yii::getLogger();
        $logger->flush();
    }

    /**
     * Clean up after test.
     * By default, the application created with [[mockApplication]] will be destroyed.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->destroyApplication();
    }

    /**
     * Destroys application in Yii::$app by setting it to null.
     */
    protected function destroyApplication(): void
    {
        if (Yii::$app && Yii::$app->has('session', true)) {
            Yii::$app->session->close();
        }
        Yii::$app = null;
    }

    protected function getVendorPath(): string
    {
        return dirname(__DIR__) . '/vendor';
    }

    /**
     * Populates Yii::$app with a new application
     * The application will be destroyed on tearDown() automatically.
     * @param array $config The application configuration, if needed
     * @param string $appClass name of the application class to create
     */
    protected function mockApplication(array $config = [], string $appClass = '\yii\console\Application'): void
    {
        new $appClass(
            ArrayHelper::merge([
                'id' => 'testapp',
                'basePath' => __DIR__,
                'vendorPath' => $this->getVendorPath(),
            ], $config)
        );
    }

    protected function mockWebApplication($config = [], string $appClass = '\yii\web\Application'): void
    {
        new $appClass(
            ArrayHelper::merge([
                'id' => 'testapp',
                'basePath' => __DIR__,
                'vendorPath' => $this->getVendorPath(),
                'aliases' => [
                    '@bower' => '@vendor/bower-asset',
                    '@npm' => '@vendor/npm-asset',
                ],
                'components' => [
                    'request' => [
                        'cookieValidationKey' => 'wefJDF8sfdsfSDefwqdxj9oq',
                        'scriptFile' => __DIR__ . '/index.php',
                        'scriptUrl' => '/index.php',
                        'isConsoleRequest' => false,
                    ],
                    'assetManager' => [
                        'bundles' => [
                            'yii\grid\GridViewAsset' => false,
                            'yii\web\JqueryAsset' => false,
                        ],
                    ],
                ],
            ], $config)
        );
    }
}
