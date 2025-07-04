<?php

/**
 * EaseDoctrine main entry point.
 *
 * @author Vitex Software
 * @license MIT
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

namespace Ease\Doctrine;

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Tools\Setup;

/**
 * Class EaseDoctrine.
 *
 * Main class providing FluentPDO-like API using DoctrineORM internally.
 * Supports basic CRUD, query builder, and transactions.
 */
class Engine
{
    /**
     * Default table in SQL (part of identity).
     */
    public string $myTable = '';

    /**
     * Record create time column.
     */
    public ?string $createColumn = null;

    /**
     * Key column name (primary key).
     */
    public ?string $keyColumn = null;

    /**
     * Name column (for display purposes).
     */
    public ?string $nameColumn = null;

    /**
     * Last modified column name.
     */
    public ?string $lastModifiedColumn = null;

    /**
     * @var null|EntityManagerInterface Doctrine EntityManager instance
     */
    protected ?EntityManagerInterface $entityManager = null;

    /**
     * @var array<string, mixed> Doctrine configuration options
     */
    protected array $config;

    /**
     * EaseDoctrine constructor.
     *
     * @param array<string, mixed> $connectionParams Doctrine DB connection params
     * @param array<string, mixed> $config           Doctrine configuration options
     *
     * @throws \Exception
     */
    public function __construct(array $connectionParams, array $config = [])
    {
        try {
            $doctrineConfig = Setup::createAnnotationMetadataConfiguration([
                $config['entityPath'] ?? __DIR__.'/../Entity',
            ], $config['devMode'] ?? true);
            $this->entityManager = EntityManager::create($connectionParams, $doctrineConfig);
            $this->config = $config;
        } catch (DBALException|ORMException $e) {
            throw new \Exception(sprintf(
                _('Failed to initialize Doctrine EntityManager: %s'),
                $e->getMessage(),
            ));
        }
    }

    /**
     * Get Doctrine EntityManager instance.
     */
    public function getEntityManager(): ?EntityManagerInterface
    {
        return $this->entityManager;
    }

    /**
     * Create a new query builder instance.
     */
    public function createQueryBuilder(): \Doctrine\ORM\QueryBuilder
    {
        if (!$this->entityManager) {
            throw new \Exception(_(
                'EntityManager is not initialized.',
            ));
        }

        return $this->entityManager->createQueryBuilder();
    }

    /**
     * Begin a transaction.
     *
     * @throws \Exception
     */
    public function beginTransaction(): void
    {
        try {
            $this->entityManager?->getConnection()->beginTransaction();
        } catch (\Throwable $e) {
            throw new \Exception(sprintf(
                _('Failed to begin transaction: %s'),
                $e->getMessage(),
            ));
        }
    }

    /**
     * Commit the current transaction.
     *
     * @throws \Exception
     */
    public function commit(): void
    {
        try {
            $this->entityManager?->getConnection()->commit();
        } catch (\Throwable $e) {
            throw new \Exception(sprintf(
                _('Failed to commit transaction: %s'),
                $e->getMessage(),
            ));
        }
    }

    /**
     * Rollback the current transaction.
     *
     * @throws \Exception
     */
    public function rollback(): void
    {
        try {
            $this->entityManager?->getConnection()->rollBack();
        } catch (\Throwable $e) {
            throw new \Exception(sprintf(
                _('Failed to rollback transaction: %s'),
                $e->getMessage(),
            ));
        }
    }

    /**
     * Persist (insert or update) an entity.
     *
     * @throws \Exception
     */
    public function save(object $entity): void
    {
        try {
            $this->entityManager?->persist($entity);
            $this->entityManager?->flush();
        } catch (\Throwable $e) {
            throw new \Exception(sprintf(
                _('Failed to save entity: %s'),
                $e->getMessage(),
            ));
        }
    }

    /**
     * Remove (delete) an entity.
     *
     * @throws \Exception
     */
    public function delete(object $entity): void
    {
        try {
            $this->entityManager?->remove($entity);
            $this->entityManager?->flush();
        } catch (\Throwable $e) {
            throw new \Exception(sprintf(
                _('Failed to delete entity: %s'),
                $e->getMessage(),
            ));
        }
    }

    /**
     * Find an entity by its primary key.
     *
     * @throws \Exception
     */
    public function find(string $entityClass, mixed $id): ?object
    {
        try {
            return $this->entityManager?->find($entityClass, $id);
        } catch (\Throwable $e) {
            throw new \Exception(sprintf(
                _('Failed to find entity: %s'),
                $e->getMessage(),
            ));
        }
    }

    /**
     * Get repository for an entity class.
     *
     * @throws \Exception
     */
    public function getRepository(string $entityClass): \Doctrine\ORM\EntityRepository
    {
        try {
            return $this->entityManager?->getRepository($entityClass);
        } catch (\Throwable $e) {
            throw new \Exception(sprintf(
                _('Failed to get repository: %s'),
                $e->getMessage(),
            ));
        }
    }

    /**
     * Set/override object properties.
     *
     * @param array<string, string> $properties
     */
    public function setProperties(array $properties = []): void
    {
        foreach ([
            'myTable', 'keyColumn', 'nameColumn', 'createColumn', 'lastModifiedColumn',
        ] as $property) {
            if (\array_key_exists($property, $properties)) {
                $this->{$property} = $properties[$property];
            }
        }
    }

    /**
     * Returns the name of the currently used SQL table.
     */
    public function getMyTable(): string
    {
        return $this->myTable;
    }

    /**
     * Sets the current working table for SQL.
     */
    public function setmyTable(string $myTable): void
    {
        $this->myTable = $myTable;
    }

    /**
     * Obtain record name if $this->nameColumn is set.
     */
    public function getRecordName(): ?string
    {
        if (empty($this->nameColumn)) {
            return null;
        }

        // Doctrine entity must have a getter for the name column
        $entity = $this->entityManager?->getRepository($this->myTable)->findOneBy([
            $this->keyColumn => $this->getMyKey(),
        ]);

        if ($entity && method_exists($entity, 'get'.ucfirst($this->nameColumn))) {
            return $entity->{'get'.ucfirst($this->nameColumn)}();
        }

        return null;
    }

    /**
     * Get all records from the table.
     *
     * @return array<int, object>
     */
    public function getAll(): array
    {
        return $this->entityManager?->getRepository($this->myTable)->findAll() ?? [];
    }

    /**
     * Get the value of the key column (primary key) for the current entity.
     *
     * @return null|mixed
     */
    public function getMyKey(): mixed
    {
        // This method should be customized per-entity; here we just return null for compatibility
        return null;
    }
}
