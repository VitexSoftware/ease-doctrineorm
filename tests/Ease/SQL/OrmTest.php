<?php

declare(strict_types=1);

namespace Ease\SQL\Tests;

use Ease\SQL\Orm;
use PHPUnit\Framework\TestCase;
use Doctrine\ORM\EntityManager;

/**
 * Test case for the Orm trait.
 */
class OrmTest extends TestCase
{

    private TestEntity $entity;
    private EntityManager $em;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an in-memory SQLite database for testing
        $connectionParams = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        // Create schema
        $this->em = EntityManager::create($connectionParams, Setup::createAnnotationMetadataConfiguration([__DIR__], true));
        $this->em->getConnection()->executeQuery('
            CREATE TABLE test_entity (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255),
                email VARCHAR(255),
                active BOOLEAN,
                created_at DATETIME,
                updated_at DATETIME
            )
        ');

        $this->entity = new TestEntity();
    }

    public function testGetColumnsFromSQL(): void
    {
        // Insert test data
        $this->em->getConnection()->executeQuery("
            INSERT INTO test_entity (name, email, active) 
            VALUES ('John', 'john@example.com', 1)
        ");

        $result = $this->entity->getColumnsFromSQL(
            ['name', 'email'],
            ['active' => true]
        );

        $this->assertCount(1, $result);
        $this->assertEquals('John', $result[0]['name']);
        $this->assertEquals('john@example.com', $result[0]['email']);
    }

    public function testGetDataFromSQL(): void
    {
        // Insert test data
        $this->em->getConnection()->executeQuery("
            INSERT INTO test_entity (name, email) 
            VALUES ('Jane', 'jane@example.com')
        ");

        $result = $this->entity->getDataFromSQL(1);
        
        $this->assertNotEmpty($result);
        $this->assertEquals('Jane', $result[0]['name']);
    }

    public function testLoadFromSQL(): void
    {
        // Insert test data
        $this->em->getConnection()->executeQuery("
            INSERT INTO test_entity (name, email) 
            VALUES ('Alice', 'alice@example.com')
        ");

        $rowCount = $this->entity->loadFromSQL(['name' => 'Alice']);
        
        $this->assertEquals(1, $rowCount);
        $data = $this->entity->getData();
        $this->assertEquals('alice@example.com', $data['email']);
    }

    public function testSaveToSQL(): void
    {
        $data = [
            'name' => 'Bob',
            'email' => 'bob@example.com',
            'active' => true
        ];

        $id = $this->entity->saveToSQL($data);
        
        $this->assertNotNull($id);
        
        // Verify the data was saved
        $saved = $this->em->getConnection()->fetchAssociative('SELECT * FROM test_entity WHERE id = ?', [$id]);
        $this->assertEquals('Bob', $saved['name']);
    }

    public function testUpdateToSQL(): void
    {
        // Insert initial data
        $this->em->getConnection()->executeQuery("
            INSERT INTO test_entity (name, email) 
            VALUES ('Charlie', 'charlie@example.com')
        ");

        $updateData = ['email' => 'charles@example.com'];
        $rowsUpdated = $this->entity->updateToSQL($updateData, ['name' => 'Charlie']);
        
        $this->assertEquals(1, $rowsUpdated);
        
        // Verify the update
        $updated = $this->em->getConnection()->fetchAssociative('SELECT * FROM test_entity WHERE name = ?', ['Charlie']);
        $this->assertEquals('charles@example.com', $updated['email']);
    }

    public function testDeleteFromSQL(): void
    {
        // Insert test data
        $this->em->getConnection()->executeQuery("
            INSERT INTO test_entity (name, email) 
            VALUES ('Dave', 'dave@example.com')
        ");

        $result = $this->entity->deleteFromSQL(['name' => 'Dave']);
        
        $this->assertTrue($result);
        
        // Verify the deletion
        $count = $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM test_entity WHERE name = ?', ['Dave']);
        $this->assertEquals(0, $count);
    }

    public function testRecordExists(): void
    {
        // Insert test data
        $this->em->getConnection()->executeQuery("
            INSERT INTO test_entity (name, email) 
            VALUES ('Eve', 'eve@example.com')
        ");

        $this->assertTrue($this->entity->recordExists('Eve'));
        $this->assertFalse($this->entity->recordExists('NonExistent'));
    }

    public function testDbsync(): void
    {
        $data = [
            'name' => 'Frank',
            'email' => 'frank@example.com'
        ];

        $success = $this->entity->dbsync($data);
        
        $this->assertTrue($success);
        
        // Verify data was saved and reloaded
        $this->assertEquals('Frank', $this->entity->getData()['name']);
    }

    public function testDbreload(): void
    {
        // Insert test data
        $this->em->getConnection()->executeQuery("
            INSERT INTO test_entity (name, email) 
            VALUES ('Grace', 'grace@example.com')
        ");

        $this->entity->loadFromSQL(1);
        
        // Update directly in database
        $this->em->getConnection()->executeQuery("
            UPDATE test_entity SET email = 'grace.new@example.com' WHERE id = 1
        ");

        $reloaded = $this->entity->dbreload();
        
        $this->assertTrue($reloaded);
        $this->assertEquals('grace.new@example.com', $this->entity->getData()['email']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Clean up: drop the test table
        $this->em->getConnection()->executeQuery('DROP TABLE IF EXISTS test_entity');
    }
}