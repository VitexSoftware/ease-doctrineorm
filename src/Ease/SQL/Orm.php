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

/**
 * Orm trait - provides ORM-like functionality for SQL Engine.
 */
trait Orm
{
    /**
     * @var FluentQuery|null
     */
    public ?FluentQuery $fluent = null;

    /**
     * Server Host or IP.
     */
    public ?string $server = null;

    /**
     * DB Login.
     */
    public ?string $dbLogin = null;

    /**
     * DB password.
     */
    public ?string $dbPass = null;

    /**
     * Database to connect by default.
     */
    public string $database = '';

    /**
     * Database port.
     */
    public ?string $port = null;

    /**
     * Type of used database.
     *
     * @var string mysql|pgsql|..
     */
    public string $dbType = '';

    /**
     * Default connection settings.
     *
     * @var array<string, string>|string
     */
    public $dbSettings = [];

    /**
     * Default connection setup.
     *
     * @var array<string, string>
     */
    public array $connectionSetup = [];

    /**
     * Last error info.
     */
    public array $errorInfo = [];

    /**
     * Error code.
     */
    protected int $errorNumber;

    /**
     * Only one row returned?
     */
    private bool $multipleteResult;

    /**
     * Setup object properties based on options.
     *
     * @param array $options
     */
    public function setUp(array $options = []): void
    {
        if (array_key_exists('myTable', $options)) {
            $this->myTable = $options['myTable'];
        }
        if (array_key_exists('keyColumn', $options)) {
            $this->keyColumn = $options['keyColumn'];
        }
        if (array_key_exists('nameColumn', $options)) {
            $this->nameColumn = $options['nameColumn'];
        }
        if (array_key_exists('createColumn', $options)) {
            $this->createColumn = $options['createColumn'];
        }
        if (array_key_exists('lastModifiedColumn', $options)) {
            $this->lastModifiedColumn = $options['lastModifiedColumn'];
        }
    }

    /**
     * Get data value.
     *
     * @param string $columnName
     * @return mixed
     */
    public function getDataValue($columnName)
    {
        return $this->data[$columnName] ?? null;
    }

    /**
     * Set data value.
     *
     * @param string $columnName
     * @param mixed $value
     * @return bool
     */
    public function setDataValue(string $columnName, $value): bool
    {
        $this->data[$columnName] = $value;
        return true;
    }

    /**
     * Get all data.
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data ?? [];
    }

    /**
     * Take data.
     *
     * @param array $data
     * @return int Count of taken items
     */
    public function takeData(array $data): int
    {
        $this->data = array_merge($this->data ?? [], $data);
        return count($data);
    }

    /**
     * Add status message.
     *
     * @param mixed $message
     * @param string $type
     * @param mixed $caller
     * @return mixed
     */
    public function addStatusMessage($message, $type = 'info', $caller = null)
    {
        // This method is overridden by parent classes that have logging capability
        return parent::addStatusMessage($message, $type, $caller);
    }

    /**
     * Get database columns values by conditions.
     *
     * @param array<string> $columnsList column names listing
     * @param array<string, mixed>|int|string $conditions conditions or ID
     * @param array|string $orderBy sort by
     * @param string $indexBy result keys by row keys
     * @param int $limit maximum number of results
     *
     * @return array<string, mixed>
     */
    public function getColumnsFromSQL(
        array $columnsList,
        $conditions = null,
        $orderBy = null,
        $indexBy = null,
        $limit = null
    ) {
        $em = \Doctrine\ORM\EntityManager::getEntityManager();
        $qb = $em->createQueryBuilder();
        
        $qb->select(implode(',', $columnsList))
           ->from($this->getMyTable(), 'e');

        if ($conditions) {
            if (is_array($conditions)) {
                foreach ($conditions as $field => $value) {
                    $qb->andWhere("e.$field = :$field")
                       ->setParameter($field, $value);
                }
            } else {
                $qb->andWhere('e.id = :id')
                   ->setParameter('id', $conditions);
            }
        }

        if ($orderBy) {
            if (is_array($orderBy)) {
                foreach ($orderBy as $field => $direction) {
                    $qb->addOrderBy("e.$field", $direction);
                }
            } else {
                $qb->orderBy("e.$orderBy");
            }
        }

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        $query = $qb->getQuery();
        $result = $query->getResult();

        if ($indexBy && !empty($result)) {
            $indexed = [];
            foreach ($result as $row) {
                $indexed[$row[$indexBy]] = $row;
            }
            return $indexed;
        }

        return $result;
    }

    /**
     * Load data from database by ID.
     *
     * @param int $itemID record key
     * @param array $columnsList columns to select
     *
     * @return array Results
     */
    public function getDataFromSQL($itemID = null, array $columnsList = ['*'])
    {
        $em = \Doctrine\ORM\EntityManager::getEntityManager();
        $qb = $em->createQueryBuilder();

        $select = $columnsList[0] === '*' ? 'e' : implode(',', $columnsList);
        
        $qb->select($select)
           ->from($this->getMyTable(), 'e')
           ->where('e.id = :id')
           ->setParameter('id', $itemID);

        return $qb->getQuery()->getResult();
    }

    /**
     * Load data from database for the current ID.
     *
     * @param array|int $itemID Record key or conditions array
     *
     * @return int|null Number of rows loaded
     */
    public function loadFromSQL($itemID)
    {
        $em = \Doctrine\ORM\EntityManager::getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->select('e')
           ->from($this->getMyTable(), 'e');

        if (is_array($itemID)) {
            foreach ($itemID as $field => $value) {
                $qb->andWhere("e.$field = :$field")
                   ->setParameter($field, $value);
            }
        } else {
            $qb->where('e.id = :id')
               ->setParameter('id', $itemID);
        }

        $result = $qb->getQuery()->getResult();
        $this->multipleteResult = (count($result) > 1);

        if ($this->multipleteResult) {
            $results = [];
            foreach ($result as $data) {
                $this->takeData((array) $data);
                $results[] = $this->getData();
            }
            $this->data = $results;
        } else {
            if (!empty($result)) {
                $this->takeData((array) current($result));
            }
        }

        return !empty($this->data) ? count($this->data) : null;
    }

    /**
     * Reload current record from Database.
     *
     * @return bool
     */
    public function dbreload()
    {
        return (bool) $this->loadFromSQL([$this->getKeyColumn() => $this->getMyKey()]);
    }

    /**
     * Insert current data into Database and load actual record data back.
     *
     * @param array<string, mixed> $data Initial data to save
     *
     * @return bool Operation success
     */
    public function dbsync(array $data = []): bool
    {
        return $this->saveToSQL($data ?: $this->getData()) && $this->dbreload();
    }

    /**
     * Update record in database.
     *
     * @param array<string, mixed> $data Data to save
     * @param array<string, mixed> $conditions Update conditions
     *
     * @return int Number of rows updated
     */
    public function updateToSQL($data = null, $conditions = [])
    {
        if ($data === null) {
            $data = $this->getData();
        }

        $em = \Doctrine\ORM\EntityManager::getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->update($this->getMyTable(), 'e');

        foreach ($data as $field => $value) {
            $qb->set("e.$field", ":$field")
               ->setParameter($field, $value);
        }

        if (!empty($conditions)) {
            foreach ($conditions as $field => $value) {
                $qb->andWhere("e.$field = :where_$field")
                   ->setParameter("where_$field", $value);
            }
        } else {
            $keyColumn = $this->getKeyColumn();
            if (isset($data[$keyColumn])) {
                $qb->where("e.$keyColumn = :id")
                   ->setParameter('id', $data[$keyColumn]);
            }
        }

        if (isset($this->lastModifiedColumn) && !isset($data[$this->lastModifiedColumn])) {
            $qb->set("e.{$this->lastModifiedColumn}", ':lastModified')
               ->setParameter('lastModified', date('Y-m-d H:i:s'));
        }

        return $qb->getQuery()->execute();
    }

    /**
     * Save data to database.
     *
     * @param array<string, mixed> $data Data to save
     *
     * @return int|null ID of record or null on failure
     */
    public function saveToSQL($data = null)
    {
        if ($data === null) {
            $data = $this->getData();
        }

        $keyColumn = $this->getKeyColumn();
        $key = isset($data[$keyColumn]) ? $data[$keyColumn] : null;

        if ($key) {
            return $this->updateToSQL($data);
        } else {
            return $this->insertToSQL($data);
        }
    }

    /**
     * Insert new record to database.
     *
     * @param array<string, mixed>|null $data Data to insert
     *
     * @return int|null ID of new record or null on failure
     */
    public function insertToSQL($data = null)
    {
        if ($data === null) {
            $data = $this->getData();
        }

        if ($this->createColumn && !isset($data[$this->createColumn])) {
            $data[$this->createColumn] = date('Y-m-d H:i:s');
        }

        try {
            $em = \Doctrine\ORM\EntityManager::getEntityManager();
            $className = $this->getMyTable();
            $entity = new $className();

            foreach ($data as $field => $value) {
                $setter = 'set' . ucfirst($field);
                if (method_exists($entity, $setter)) {
                    $entity->$setter($value);
                }
            }

            $em->persist($entity);
            $em->flush();

            $getter = 'get' . ucfirst($this->getKeyColumn());
            if (method_exists($entity, $getter)) {
                $insertId = $entity->$getter();
                $this->setMyKey($insertId);
                return $insertId;
            }
        } catch (\Exception $exc) {
            $this->addStatusMessage($exc->getMessage(), 'error');
            throw $exc;
        }

        return null;
    }

    /**
     * Delete record from database.
     *
     * @param array|int|null $data Data or ID to delete
     *
     * @return bool Success
     */
    public function deleteFromSQL($data = null)
    {
        if ($data === null) {
            $data = $this->getData();
        }

        try {
            $em = \Doctrine\ORM\EntityManager::getEntityManager();
            $qb = $em->createQueryBuilder();

            $qb->delete($this->getMyTable(), 'e');

            if (is_array($data)) {
                foreach ($data as $field => $value) {
                    $qb->andWhere("e.$field = :$field")
                       ->setParameter($field, $value);
                }
            } else {
                $qb->where('e.id = :id')
                   ->setParameter('id', $data);
            }

            return (bool) $qb->getQuery()->execute();
        } catch (\Exception $exc) {
            $this->addStatusMessage($exc->getMessage(), 'error');
            throw $exc;
        }
    }

    /**
     * Check if record exists.
     *
     * @param array|int|string $data int for ID column, string for nameColumn, array for conditions
     *
     * @return bool
     */
    public function recordExists($data): bool
    {
        $em = \Doctrine\ORM\EntityManager::getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->select('COUNT(e.id)')
           ->from($this->getMyTable(), 'e');

        switch (gettype($data)) {
            case 'string':
                $qb->where("e.{$this->nameColumn} = :value")
                   ->setParameter('value', $data);
                break;
            case 'integer':
                $qb->where("e.{$this->keyColumn} = :value")
                   ->setParameter('value', $data);
                break;
            default:
                foreach ($data as $field => $value) {
                    $qb->andWhere("e.$field = :$field")
                       ->setParameter($field, $value);
                }
                break;
        }

        return (bool) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Get table name.
     *
     * @return string
     */
    public function getMyTable()
    {
        return $this->myTable;
    }

    /**
     * Set table name.
     *
     * @param string $tablename
     */
    public function setMyTable($tablename): void
    {
        $this->myTable = $tablename;
    }

    /**
     * Basic Query to return all records.
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function listingQuery()
    {
        $em = \Doctrine\ORM\EntityManager::getEntityManager();
        return $em->createQueryBuilder()
            ->select('e')
            ->from($this->getMyTable(), 'e');
    }
}