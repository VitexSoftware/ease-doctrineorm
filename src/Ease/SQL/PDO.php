<?php

declare(strict_types=1);

/**
 * This file is part of the EaseDoctrine package
 *
 * https://github.com/VitexSoftware/php-ease-doctrineorm
 *
 * (c) Vítězslav Dvořák <http://vitexsoftware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ease\SQL;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\EntityManagerInterface;

/**
 * PDO-compatible connection provider for Doctrine DBAL.
 * 
 * This class provides a singleton pattern for database connections
 * compatible with the FluentPDO style of getting connections.
 */
class PDO
{
    /**
     * @var array<string, EntityManagerInterface>
     */
    private static array $instances = [];

    /**
     * @var array<string, \Doctrine\DBAL\Connection>
     */
    private static array $connections = [];

    /**
     * Get shared instance of EntityManager.
     *
     * @param string|array|null $options Connection options or DSN string
     * @param string $name Connection name for multiple connections
     * 
     * @return EntityManagerInterface
     */
    public static function getInstance($options = null, string $name = 'default'): EntityManagerInterface
    {
        if (!isset(self::$instances[$name])) {
            self::$instances[$name] = self::connect($options, $name);
        }

        return self::$instances[$name];
    }

    /**
     * Get shared instance of Doctrine Connection.
     *
     * @param string|array|null $options Connection options or DSN string
     * @param string $name Connection name for multiple connections
     * 
     * @return \Doctrine\DBAL\Connection
     */
    public static function getConnectionInstance($options = null, string $name = 'default'): \Doctrine\DBAL\Connection
    {
        if (!isset(self::$connections[$name])) {
            $entityManager = self::getInstance($options, $name);
            self::$connections[$name] = $entityManager->getConnection();
        }

        return self::$connections[$name];
    }

    /**
     * Create a new database connection.
     *
     * @param string|array|null $options Connection options
     * @param string $name Connection name
     * 
     * @return EntityManagerInterface
     */
    private static function connect($options, string $name): EntityManagerInterface
    {
        $connectionParams = self::parseConnectionOptions($options);
        
        // Setup Doctrine
        $config = ORMSetup::createAttributeMetadataConfiguration(
            paths: [__DIR__ . '/../../Entity'],
            isDevMode: true,
        );

        // Create EntityManager
        $connection = DriverManager::getConnection($connectionParams, $config);
        return new EntityManager($connection, $config);
    }

    /**
     * Parse connection options from various formats.
     *
     * @param string|array|null $options
     * @return array
     */
    private static function parseConnectionOptions($options): array
    {
        if (is_array($options)) {
            return $options;
        }

        if (is_string($options)) {
            // Parse DSN string
            $parsed = parse_url($options);
            if ($parsed === false) {
                throw new \InvalidArgumentException('Invalid DSN: ' . $options);
            }

            $connectionParams = [
                'driver' => self::getDoctrineDriver($parsed['scheme'] ?? 'mysql'),
                'host' => $parsed['host'] ?? 'localhost',
                'port' => $parsed['port'] ?? null,
                'dbname' => ltrim($parsed['path'] ?? '', '/'),
                'user' => $parsed['user'] ?? null,
                'password' => $parsed['pass'] ?? null,
            ];

            // Handle query parameters
            if (isset($parsed['query'])) {
                parse_str($parsed['query'], $queryParams);
                $connectionParams = array_merge($connectionParams, $queryParams);
            }

            return array_filter($connectionParams);
        }

        // Use environment variables or defaults
        return [
            'driver' => self::getDoctrineDriver($_ENV['DB_TYPE'] ?? 'mysql'),
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => isset($_ENV['DB_PORT']) ? (int)$_ENV['DB_PORT'] : null,
            'dbname' => $_ENV['DB_DATABASE'] ?? 'test',
            'user' => $_ENV['DB_USERNAME'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
        ];
    }

    /**
     * Map database type to Doctrine driver.
     *
     * @param string $type
     * @return string
     */
    private static function getDoctrineDriver(string $type): string
    {
        $map = [
            'mysql' => 'pdo_mysql',
            'pgsql' => 'pdo_pgsql',
            'postgresql' => 'pdo_pgsql',
            'sqlite' => 'pdo_sqlite',
            'sqlite3' => 'pdo_sqlite',
            'mssql' => 'pdo_sqlsrv',
            'sqlsrv' => 'pdo_sqlsrv',
        ];

        return $map[strtolower($type)] ?? 'pdo_mysql';
    }

    /**
     * Singleton - prevent cloning.
     */
    private function __clone() {}

    /**
     * Singleton - prevent construction.
     */
    private function __construct() {}
}