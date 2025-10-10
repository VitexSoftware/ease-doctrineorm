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

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Ease\Brick;
use Ease\Doctrine\Engine as DoctrineEngine;

/**
 * FluentPDO-compatible wrapper for Doctrine ORM.
 *
 * This class provides a compatibility layer to use Doctrine ORM
 * with FluentPDO-style API for easy migration.
 */
class Engine extends Brick
{
    use Orm;

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
    public string $keyColumn = 'id';

    /**
     * Name column (for display purposes).
     */
    public string $nameColumn = '';

    /**
     * Last modified column name.
     */
    public ?string $lastModifiedColumn = null;

    /**
     * @var DoctrineEngine Doctrine engine instance
     */
    protected DoctrineEngine $doctrineEngine;

    /**
     * @var EntityManagerInterface|null
     */
    protected ?EntityManagerInterface $entityManager = null;

    /**
     * @var \Doctrine\DBAL\Connection|null
     */
    protected ?\Doctrine\DBAL\Connection $connection = null;

    /**
     * @var array|null Data storage
     */
    public ?array $data = [];

    /**
     * Database object.
     *
     * @param mixed $identifier
     * @param array $options    'autoload'=>false prevent initial autoloading, keyColumn,myTable,createColumn,lastModifiedColumn,nameColumn
     */
    public function __construct($identifier = null, $options = [])
    {
        parent::__construct($identifier, $options);
        $this->setUp($options);

        // Initialize EntityManager if not already set
        if ($this->entityManager === null) {
            $this->entityManager = PDO::getInstance();
        }

        if (\array_key_exists('autoload', $options) && ($options['autoload'] === true)) {
            $this->loadIdentifier($identifier);
        } else {
            $this->useIdentifier($identifier);
        }

        $this->setObjectName();
    }

    /**
     * Get FluentPDO instance (compatibility method).
     *
     * @return FluentQuery
     */
    public function getFluentPDO(): FluentQuery
    {
        if (!isset($this->fluent)) {
            $this->fluent = new FluentQuery($this);
        }

        return $this->fluent;
    }

    /**
     * Get Doctrine connection.
     *
     * @return \Doctrine\DBAL\Connection
     */
    public function getConnection(): \Doctrine\DBAL\Connection
    {
        if ($this->connection === null) {
            $this->connection = $this->getEntityManager()->getConnection();
        }

        return $this->connection;
    }

    /**
     * Get Entity Manager.
     *
     * @return EntityManagerInterface
     */
    public function getEntityManager(): EntityManagerInterface
    {
        if ($this->entityManager === null) {
            // This should be injected from the application
            throw new \RuntimeException('Entity Manager not initialized. Please set it using setEntityManager()');
        }

        return $this->entityManager;
    }

    /**
     * Set Entity Manager.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function setEntityManager(EntityManagerInterface $entityManager): void
    {
        $this->entityManager = $entityManager;
        $this->connection = $entityManager->getConnection();
    }

    /**
     * Properties to keep.
     *
     * @return array<string>
     */
    public function __sleep(): array
    {
        return [
            'myTable',
            'keyColumn',
            'nameColumn',
            'createColumn',
            'lastModifiedColumn',
            'data',
        ];
    }

    /**
     * Use Given value as identifier.
     *
     * @param mixed $identifier
     */
    public function useIdentifier($identifier): void
    {
        switch ($this->howToProcess($identifier)) {
            case 'values':
                $this->takeData($identifier);

                break;
            case 'reuse':
                $this->takeData($identifier->getData());

                break;
            case 'name':
                $this->setDataValue($this->nameColumn, $identifier);

                break;
            case 'id':
                $this->setMyKey($identifier);

                break;

            default:
                break;
        }
    }

    /**
     * Load record using identifier.
     *
     * @param mixed $identifier
     */
    public function loadIdentifier($identifier): void
    {
        switch ($this->howToProcess($identifier)) {
            case 'values':
                $this->loadFromSQL($identifier);

                break;
            case 'reuse':
                $this->takeData($identifier->getData());

                break;
            case 'name':
                $this->loadFromSQL([$this->nameColumn => $identifier]);

                break;
            case 'id':
            case 'uuid':
                $this->loadFromSQL($identifier);

                break;

            default:
                break;
        }
    }

    /**
     * @param \Ease\SQL\Engine $identifier
     *
     * @return string id|name|values|reuse|unknown
     */
    public function howToProcess($identifier)
    {
        $recognizedAs = 'unknown';

        switch (\gettype($identifier)) {
            case 'integer':
            case 'double':
                if ($this->getKeyColumn()) {
                    $recognizedAs = 'id';
                }

                break;
            case 'string':
                if (!empty($this->nameColumn)) {
                    $recognizedAs = 'name';
                } elseif (\Ease\Functions::isUuid($identifier)) {
                    $recognizedAs = 'uuid';
                }

                break;
            case 'array':
                $recognizedAs = 'values';

                break;
            case 'object':
                if ($identifier instanceof self) {
                    $recognizedAs = 'reuse';
                }

                break;

            default:
            case 'boolean':
            case 'NULL':
                $recognizedAs = 'unknown';

                break;
        }

        return $recognizedAs;
    }

    /**
     * Prove that record is present in DB.
     *
     * @param array|int|string $identifier
     *
     * @return bool Record was found ?
     */
    public function recordExist($identifier = null)
    {
        return $this->listingQuery()->where(null === $identifier ? [$this->getKeyColumn() => $this->getMyKey()] : $identifier)->count() !== 0;
    }

    /**
     * Obtain record name if $this->nameColumn is set.
     *
     * @return string
     */
    public function getRecordName()
    {
        return empty($this->nameColumn) ? '' : strval($this->getDataValue($this->nameColumn));
    }

    /**
     * Set record name if $this->nameColumn is set.
     *
     * @param string $recordName
     *
     * @return bool
     */
    public function setRecordName($recordName)
    {
        return empty($this->nameColumn) ? false : $this->setDataValue($this->nameColumn, $recordName);
    }

    /**
     * Returns the name of the currently used SQL table.
     *
     * @return string
     */
    public function getMyTable()
    {
        return $this->myTable;
    }

    /**
     * Sets the current working table for SQL.
     *
     * @param string $myTable
     */
    public function setmyTable($myTable)
    {
        $this->myTable = $myTable;
    }

    /**
     * Get all records from the table.
     *
     * @return array
     */
    public function getAll()
    {
        return $this->listingQuery()->fetchAll();
    }

    /**
     * Get specific columns from all records.
     *
     * @param string|array $columns
     *
     * @return array
     */
    public function getAllFromSQL($columns = null)
    {
        $query = $this->listingQuery();

        if ($columns) {
            $query->select($columns);
        }

        return $query->fetchAll();
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
     * Get key column name.
     *
     * @return string
     */
    public function getKeyColumn()
    {
        return $this->keyColumn;
    }

    /**
     * Set key column name.
     *
     * @param string $keyColumn
     */
    public function setKeyColumn(string $keyColumn): void
    {
        $this->keyColumn = $keyColumn;
    }

    /**
     * Get the value of the key column (primary key).
     *
     * @param array|null $data
     * @return mixed
     */
    public function getMyKey(?array $data = [])
    {
        if (!empty($data)) {
            return $data[$this->keyColumn] ?? null;
        }
        return $this->getDataValue($this->keyColumn);
    }

    /**
     * Set the value of the key column.
     *
     * @param mixed $myKeyValue
     *
     * @return bool
     */
    public function setMyKey($myKeyValue)
    {
        return $this->setDataValue($this->keyColumn, $myKeyValue);
    }

    /**
     * Create listing query.
     *
     * @return FluentQuery
     */
    public function listingQuery()
    {
        return $this->getFluentPDO()->from($this->myTable);
    }

    /**
     * Insert data into the database.
     *
     * @param array $data
     *
     * @return int|false
     */
    public function insertToSQL(array $data = [])
    {
        if (empty($data)) {
            $data = $this->getData();
        }

        try {
            $result = $this->getFluentPDO()->insertInto($this->myTable, $data)->execute();

            if ($result) {
                $this->setMyKey($result);
            }

            return $result;
        } catch (\Exception $e) {
            $this->addStatusMessage($e->getMessage(), 'error');

            return false;
        }
    }

    /**
     * Update data in the database.
     *
     * @param array $data
     *
     * @return bool
     */
    public function updateToSQL(array $data = [])
    {
        if (empty($data)) {
            $data = $this->getData();
        }

        try {
            $result = $this->getFluentPDO()
                ->update($this->myTable, $data, $this->getMyKey())
                ->where($this->keyColumn, $this->getMyKey())
                ->execute();

            return $result !== false;
        } catch (\Exception $e) {
            $this->addStatusMessage($e->getMessage(), 'error');

            return false;
        }
    }

    /**
     * Delete record from database.
     *
     * @param mixed $id
     *
     * @return bool
     */
    public function deleteFromSQL($id = null)
    {
        if ($id === null) {
            $id = $this->getMyKey();
        }

        try {
            $result = $this->getFluentPDO()
                ->deleteFrom($this->myTable)
                ->where($this->keyColumn, $id)
                ->execute();

            return $result !== false;
        } catch (\Exception $e) {
            $this->addStatusMessage($e->getMessage(), 'error');

            return false;
        }
    }

    /**
     * Load data from SQL.
     *
     * @param mixed $identifier
     *
     * @return int
     */
    public function loadFromSQL($identifier = null)
    {
        if (is_null($identifier)) {
            $identifier = $this->getMyKey();
        }

        $conditions = is_array($identifier) ? $identifier : [$this->keyColumn => $identifier];

        $data = $this->listingQuery()->where($conditions)->fetch();

        if ($data) {
            $this->takeData($data);

            return 1;
        }

        return 0;
    }

    /**
     * Save data to SQL (insert or update).
     *
     * @param array $data
     *
     * @return bool
     */
    public function saveToSQL(array $data = [])
    {
        if (empty($data)) {
            $data = $this->getData();
        }

        if ($this->getMyKey() && $this->recordExist()) {
            return $this->updateToSQL($data);
        } else {
            return $this->insertToSQL($data) !== false;
        }
    }

    /**
     * Get PDO instance for compatibility.
     *
     * @param array $options
     * @return \PDO
     */
    public function getPdo($options = [])
    {
        return $this->getConnection()->getNativeConnection();
    }

    /**
     * Database property for phinx compatibility.
     */
    public function __get($name)
    {
        if ($name === 'database') {
            return $this->getConnection()->getDatabase();
        }
        
        return null;
    }
}
