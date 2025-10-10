<?php

declare(strict_types=1);

namespace Ease\SQL\Tests;

use Ease\SQL\Orm;

/**
 * Test entity class for ORM tests
 */
class TestEntity
{
    use Orm;

    public string $myTable = 'test_entity';
    public string $keyColumn = 'id';
    public string $nameColumn = 'name';
    public string $createColumn = 'created_at';
    public string $lastModifiedColumn = 'updated_at';
    private array $data = [];
}