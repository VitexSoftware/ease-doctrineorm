EaseDoctrine
===========

A drop-in replacement for FluentPDO, powered by DoctrineORM. This library allows you to migrate existing applications from FluentPDO to DoctrineORM with minimal code changes.

Features
--------

- FluentPDO-like API for easy migration
- Internally uses DoctrineORM for database operations
- Supports multiple database platforms (as supported by DoctrineORM)
- Complete CRUD operations with FluentPDO-compatible methods:
    - `getColumnsFromSQL()`: Fetch specific columns with conditions
    - `getDataFromSQL()`: Load records by ID
    - `loadFromSQL()`: Load data with conditions
    - `saveToSQL()`: Insert or update records
    - `deleteFromSQL()`: Remove records
    - `recordExists()`: Check record existence
- Query builder and transaction support
- Table and column property compatibility (`myTable`, `keyColumn`, `nameColumn`, etc.)
- Compatible with PHP 8.4+
- PSR-12 compliant
- Secure, extensible, and well-tested

Installation
------------

.. code-block:: bash

    composer require vitexsoftware/ease-doctrineorm

Usage Example
-------------

.. code-block:: php

    use Ease\Doctrine\Engine;

    $connectionParams = [
        'driver' => 'pdo_sqlite',
        'path' => __DIR__ . '/var/data.sqlite',
    ];
    $config = [
        'entityPath' => __DIR__ . '/src/Entity',
        'devMode' => true,
    ];
    $easeDoctrine = new Engine($connectionParams, $config);
    $easeDoctrine->setmyTable('User');
    $easeDoctrine->keyColumn = 'id';
    $easeDoctrine->nameColumn = 'username';

    // Basic operations
    $allUsers = $easeDoctrine->getAll();
    $userName = $easeDoctrine->getRecordName();

    // FluentPDO-compatible operations
    // Load specific columns with conditions
    $users = $easeDoctrine->getColumnsFromSQL(
        ['username', 'email'],
        ['active' => true],
        'username',
        null,
        10
    );

    // Load by ID
    $user = $easeDoctrine->getDataFromSQL(123);

    // Save (insert or update)
    $userData = ['username' => 'john_doe', 'email' => 'john@example.com'];
    $userId = $easeDoctrine->saveToSQL($userData);

    // Check existence
    if ($easeDoctrine->recordExists('john_doe')) {
        echo 'User exists!';
    }

    // Delete
    $easeDoctrine->deleteFromSQL(['id' => 123]);

    // Load with conditions
    $activeUsers = $easeDoctrine->loadFromSQL(['active' => true]);

Detailed API Documentation
----------------------

The library provides several FluentPDO-compatible methods for database operations:

``getColumnsFromSQL(array $columnsList, $conditions = null, $orderBy = null, $indexBy = null, $limit = null)``
    Fetch specific columns with conditions, ordering, and limits.

``getDataFromSQL($itemID, array $columnsList = ['*'])``
    Load a record by its ID, optionally selecting specific columns.

``loadFromSQL($itemID)``
    Load data for specified conditions or ID.

``saveToSQL($data = null)``
    Save (insert or update) data to the database.

``updateToSQL($data = null, $conditions = [])``
    Update existing records matching conditions.

``insertToSQL($data = null)``
    Insert a new record.

``deleteFromSQL($data = null)``
    Delete records matching conditions.

``recordExists($data)``
    Check if a record exists. Can check by ID, name, or conditions.

``dbreload()``
    Reload current record from database.

``dbsync(array $data = [])``
    Save data and reload from database.

Testing
-------

.. code-block:: bash

    ./vendor/bin/phpunit --bootstrap vendor/autoload.php tests

License
-------

MIT License

Author
------

Vitex Software <info@vitexsoftware.cz>
