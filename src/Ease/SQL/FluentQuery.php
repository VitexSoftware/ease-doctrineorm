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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;

/**
 * FluentPDO-style query builder using Doctrine DBAL.
 *
 * Provides FluentPDO-compatible API using Doctrine DBAL QueryBuilder internally.
 */
class FluentQuery
{
    /**
     * @var Engine
     */
    protected Engine $engine;

    /**
     * @var Connection
     */
    protected Connection $connection;

    /**
     * @var QueryBuilder|null
     */
    protected ?QueryBuilder $queryBuilder = null;

    /**
     * @var string
     */
    protected string $table = '';

    /**
     * @var string
     */
    protected string $queryType = 'select';

    /**
     * @var array
     */
    protected array $whereConditions = [];

    /**
     * @var array
     */
    protected array $orderBy = [];

    /**
     * @var int|null
     */
    protected ?int $limit = null;

    /**
     * @var int|null
     */
    protected ?int $offset = null;

    /**
     * @var array
     */
    protected array $values = [];

    /**
     * Constructor.
     *
     * @param Engine $engine
     */
    public function __construct(Engine $engine)
    {
        $this->engine = $engine;
        $this->connection = $engine->getConnection();
    }

    /**
     * Create SELECT query.
     *
     * @param string $table
     * @param mixed $primaryKey
     *
     * @return self
     */
    public function from(string $table, $primaryKey = null): self
    {
        $this->reset();
        $this->table = $table;
        $this->queryType = 'select';
        $this->queryBuilder = $this->connection->createQueryBuilder();
        $this->queryBuilder->select('*')->from($table);

        if ($primaryKey !== null) {
            $keyColumn = $this->engine->keyColumn ?? 'id';
            $this->where($keyColumn, $primaryKey);
        }

        return $this;
    }

    /**
     * Create INSERT query.
     *
     * @param string $table
     * @param array $values
     *
     * @return self
     */
    public function insertInto(string $table, array $values = []): self
    {
        $this->reset();
        $this->table = $table;
        $this->queryType = 'insert';
        $this->values = $values;

        return $this;
    }

    /**
     * Create UPDATE query.
     *
     * @param string $table
     * @param array $set
     * @param mixed $primaryKey
     *
     * @return self
     */
    public function update(string $table, array $set = [], $primaryKey = null): self
    {
        $this->reset();
        $this->table = $table;
        $this->queryType = 'update';
        $this->queryBuilder = $this->connection->createQueryBuilder();
        $this->queryBuilder->update($table);

        foreach ($set as $column => $value) {
            $this->queryBuilder->set($column, '?', $value);
        }

        if ($primaryKey !== null) {
            $keyColumn = $this->engine->keyColumn ?? 'id';
            $this->where($keyColumn, $primaryKey);
        }

        return $this;
    }

    /**
     * Create DELETE query.
     *
     * @param string $table
     * @param mixed $primaryKey
     *
     * @return self
     */
    public function deleteFrom(string $table, $primaryKey = null): self
    {
        $this->reset();
        $this->table = $table;
        $this->queryType = 'delete';
        $this->queryBuilder = $this->connection->createQueryBuilder();
        $this->queryBuilder->delete($table);

        if ($primaryKey !== null) {
            $keyColumn = $this->engine->keyColumn ?? 'id';
            $this->where($keyColumn, $primaryKey);
        }

        return $this;
    }

    /**
     * Add WHERE condition.
     *
     * @param string|array $column
     * @param mixed $value
     *
     * @return self
     */
    public function where($column, $value = null): self
    {
        if (is_array($column)) {
            foreach ($column as $col => $val) {
                $this->where($col, $val);
            }
        } else {
            if ($this->queryBuilder) {
                $paramName = 'param_' . count($this->whereConditions);
                $this->queryBuilder->andWhere("$column = :$paramName")
                    ->setParameter($paramName, $value);
            }
            $this->whereConditions[$column] = $value;
        }

        return $this;
    }

    /**
     * Set SELECT columns.
     *
     * @param string|array $columns
     *
     * @return self
     */
    public function select($columns): self
    {
        if ($this->queryBuilder && $this->queryType === 'select') {
            $this->queryBuilder->select($columns);
        }

        return $this;
    }

    /**
     * Add ORDER BY.
     *
     * @param string $column
     * @param string $direction
     *
     * @return self
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        if ($this->queryBuilder && $this->queryType === 'select') {
            $this->queryBuilder->orderBy($column, $direction);
        }
        $this->orderBy[$column] = $direction;

        return $this;
    }

    /**
     * Set LIMIT.
     *
     * @param int $limit
     *
     * @return self
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        if ($this->queryBuilder && $this->queryType === 'select') {
            $this->queryBuilder->setMaxResults($limit);
        }

        return $this;
    }

    /**
     * Set OFFSET.
     *
     * @param int $offset
     *
     * @return self
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        if ($this->queryBuilder && $this->queryType === 'select') {
            $this->queryBuilder->setFirstResult($offset);
        }

        return $this;
    }

    /**
     * Execute query and fetch single row.
     *
     * @return array|false
     */
    public function fetch()
    {
        if ($this->queryType !== 'select' || !$this->queryBuilder) {
            return false;
        }

        try {
            $result = $this->queryBuilder->executeQuery();
            return $result->fetchAssociative();
        } catch (\Exception $e) {
            $this->engine->addStatusMessage($e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Execute query and fetch all rows.
     *
     * @return array
     */
    public function fetchAll(): array
    {
        if ($this->queryType !== 'select' || !$this->queryBuilder) {
            return [];
        }

        try {
            $result = $this->queryBuilder->executeQuery();
            return $result->fetchAllAssociative();
        } catch (\Exception $e) {
            $this->engine->addStatusMessage($e->getMessage(), 'error');
            return [];
        }
    }

    /**
     * Execute query and return count of rows.
     *
     * @return int
     */
    public function count(): int
    {
        if ($this->queryType !== 'select' || !$this->queryBuilder) {
            return 0;
        }

        try {
            // Clone the query builder to avoid modifying the original
            $countQuery = clone $this->queryBuilder;
            $countQuery->select('COUNT(*) as count');
            $countQuery->setMaxResults(null);
            $countQuery->setFirstResult(null);

            $result = $countQuery->executeQuery();
            $row = $result->fetchAssociative();

            return (int) ($row['count'] ?? 0);
        } catch (\Exception $e) {
            $this->engine->addStatusMessage($e->getMessage(), 'error');
            return 0;
        }
    }

    /**
     * Execute the query.
     *
     * @return mixed
     */
    public function execute()
    {
        try {
            switch ($this->queryType) {
                case 'insert':
                    return $this->executeInsert();
                case 'update':
                    return $this->executeUpdate();
                case 'delete':
                    return $this->executeDelete();
                case 'select':
                    return $this->fetchAll();
                default:
                    throw new \RuntimeException("Unknown query type: {$this->queryType}");
            }
        } catch (\Exception $e) {
            $this->engine->addStatusMessage($e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Execute INSERT query.
     *
     * @return int|false
     */
    protected function executeInsert()
    {
        if (empty($this->values)) {
            return false;
        }

        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->insert($this->table);

        $index = 0;
        foreach ($this->values as $column => $value) {
            $queryBuilder->setValue($column, '?')
                ->setParameter($index++, $value);
        }

        $result = $queryBuilder->executeStatement();

        if ($result > 0) {
            return (int) $this->connection->lastInsertId();
        }

        return false;
    }

    /**
     * Execute UPDATE query.
     *
     * @return int|false
     */
    protected function executeUpdate()
    {
        if (!$this->queryBuilder) {
            return false;
        }

        return $this->queryBuilder->executeStatement();
    }

    /**
     * Execute DELETE query.
     *
     * @return int|false
     */
    protected function executeDelete()
    {
        if (!$this->queryBuilder) {
            return false;
        }

        return $this->queryBuilder->executeStatement();
    }

    /**
     * Reset query state.
     *
     * @return void
     */
    protected function reset(): void
    {
        $this->queryBuilder = null;
        $this->table = '';
        $this->queryType = 'select';
        $this->whereConditions = [];
        $this->orderBy = [];
        $this->limit = null;
        $this->offset = null;
        $this->values = [];
    }
}