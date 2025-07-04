<?php

/**
 * PHPUnit tests for EaseDoctrine main class.
 */

declare(strict_types=1);

/**
 * This file is part of the Easedoctrineorm package
 *
 * https://github.com/VitexSoftware/php-ease-doctrineorm
 *
 * (c) Vítězslav Dvořák <http://vitexsoftware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ease\Doctrine\Tests;

use Ease\Doctrine\Engine;
use EaseDoctrine\EaseDoctrine;
use Exception;
use PHPUnit\Framework\TestCase;

/**
 * Class EaseDoctrineTest.
 *
 * Basic tests for EaseDoctrine initialization and API.
 */
class EngineTest extends TestCase
{
    /**
     * Test EaseDoctrine initialization with valid config.
     */
    public function testInitialization(): void
    {
        $connectionParams = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];
        $config = [
            'entityPath' => __DIR__,
            'devMode' => true,
        ];
        $easeDoctrine = new Engine($connectionParams, $config);
        $this->assertNotNull($easeDoctrine->getEntityManager());
    }

    /**
     * Test EaseDoctrine initialization with invalid config throws exception.
     */
    public function testInitializationFailure(): void
    {
        $this->expectException(\Exception::class);
        $connectionParams = [
            'driver' => 'pdo_invalid',
        ];
        $config = [
            'entityPath' => __DIR__,
            'devMode' => true,
        ];
        new Engine($connectionParams, $config);
    }

    /**
     * Test query builder creation.
     */
    public function testQueryBuilder(): void
    {
        $connectionParams = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];
        $config = [
            'entityPath' => __DIR__,
            'devMode' => true,
        ];
        $easeDoctrine = new Engine($connectionParams, $config);
        $qb = $easeDoctrine->createQueryBuilder();
        $this->assertInstanceOf(\Doctrine\ORM\QueryBuilder::class, $qb);
    }

    /**
     * Test transaction methods: beginTransaction, commit, and rollback.
     */
    public function testTransactionMethods(): void
    {
        $connectionParams = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];
        $config = [
            'entityPath' => __DIR__,
            'devMode' => true,
        ];
        $easeDoctrine = new Engine($connectionParams, $config);
        $easeDoctrine->beginTransaction();
        $easeDoctrine->commit();
        $easeDoctrine->beginTransaction();
        $easeDoctrine->rollback();
        $this->assertTrue(true); // If no exception, test passes
    }

    /**
     * Test setProperties and getter methods.
     */
    public function testSetPropertiesAndGetters(): void
    {
        $connectionParams = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];
        $config = [
            'entityPath' => __DIR__,
            'devMode' => true,
        ];
        $easeDoctrine = new Engine($connectionParams, $config);
        $easeDoctrine->setProperties([
            'myTable' => 'User',
            'keyColumn' => 'id',
            'nameColumn' => 'username',
            'createColumn' => 'created_at',
            'lastModifiedColumn' => 'updated_at',
        ]);
        $this->assertEquals('User', $easeDoctrine->getMyTable());
        $easeDoctrine->setmyTable('Account');
        $this->assertEquals('Account', $easeDoctrine->getMyTable());
        $this->assertEquals('id', $easeDoctrine->keyColumn);
        $this->assertEquals('username', $easeDoctrine->nameColumn);
        $this->assertEquals('created_at', $easeDoctrine->createColumn);
        $this->assertEquals('updated_at', $easeDoctrine->lastModifiedColumn);
    }

    /**
     * Test getAll method returns an array.
     */
    public function testGetAllReturnsArray(): void
    {
        $connectionParams = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];
        $config = [
            'entityPath' => __DIR__,
            'devMode' => true,
        ];
        $easeDoctrine = new Engine($connectionParams, $config);
        $easeDoctrine->setmyTable('User');
        $result = $easeDoctrine->getAll();
        $this->assertIsArray($result);
    }
}
