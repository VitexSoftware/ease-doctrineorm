<?php

declare(strict_types=1);

namespace EaseDoctrine\Tests;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;

/**
 * Test configuration helper
 */
class TestConfig
{
    /**
     * Create EntityManager for testing
     */
    public static function createTestEntityManager(): EntityManagerInterface
    {
        // Create SQLite connection in memory
        $connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ]);

        // Create configuration for testing
        $config = ORMSetup::createAttributeMetadataConfiguration(
            [__DIR__],
            true
        );

        return new EntityManager($connection, $config);
    }

    /**
     * Initialize test database schema
     */
    public static function initTestSchema(EntityManagerInterface $em): void
    {
        // Create test_entity table
        $em->getConnection()->executeQuery('
            CREATE TABLE IF NOT EXISTS test_entity (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255),
                email VARCHAR(255),
                active BOOLEAN,
                created_at DATETIME,
                updated_at DATETIME
            )
        ');
    }
}