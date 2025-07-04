EaseDoctrine
===========

A drop-in replacement for FluentPDO, powered by DoctrineORM. This library allows you to migrate existing applications from FluentPDO to DoctrineORM with minimal code changes.

Features
--------

- FluentPDO-like API for easy migration
- Internally uses DoctrineORM for database operations
- Supports multiple database platforms (as supported by DoctrineORM)
- Basic CRUD operations, query builder, and transaction support
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
    $allUsers = $easeDoctrine->getAll();
    $userName = $easeDoctrine->getRecordName();

    // Use $easeDoctrine for CRUD, queries, transactions, etc.

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
