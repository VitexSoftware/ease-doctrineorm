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
     * Set object name.
     *
     * @param string $objectName
     * @return string
     */
    public function setObjectName($objectName = '')
    {
        if ($objectName === '') {
            $objectName = $this->getRecordName();
        }
        return parent::setObjectName($objectName);
    }

    /**
     * Add status message.
     *
     * @param string $message
     * @param string $type
     * @return bool
     */
    public function addStatusMessage(string $message, string $type = 'info'): bool
    {
        // This method is overridden by parent classes that have logging capability
        return true;
    }
}