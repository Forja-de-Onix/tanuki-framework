<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../models/TodoModel.php';

final class TodoModelTest extends TestCase
{
    protected function setUp(): void
    {
        Database::reset();
        // Empty the table before each test for isolation
        Database::connect()->exec('DELETE FROM todo');
    }

    public function testCreateAndFind(): void
    {
        $id = TodoModel::create([
            'title'       => 'Test task',
            'description' => 'Created from PHPUnit',
            'completed'   => 0,
        ]);

        $this->assertIsInt($id);

        $todo = TodoModel::find($id);
        $this->assertNotNull($todo);
        $this->assertSame('Test task', $todo['title']);
        $this->assertSame('0', (string) $todo['completed']);
    }

    public function testAllOrderedPutsPendingFirst(): void
    {
        TodoModel::create(['title' => 'Done one',    'completed' => 1]);
        TodoModel::create(['title' => 'Pending one', 'completed' => 0]);

        $ordered = TodoModel::allOrdered();

        $this->assertSame('Pending one', $ordered[0]['title']);
    }

    public function testUpdateChangesFields(): void
    {
        $id = TodoModel::create(['title' => 'Original', 'completed' => 0]);

        $ok = TodoModel::update($id, ['title' => 'Updated', 'completed' => 1]);

        $this->assertTrue($ok);
        $todo = TodoModel::find($id);
        $this->assertSame('Updated', $todo['title']);
        $this->assertSame('1', (string) $todo['completed']);
    }

    public function testDeleteRemovesRecord(): void
    {
        $id = TodoModel::create(['title' => 'To delete', 'completed' => 0]);

        $ok = TodoModel::delete($id);

        $this->assertTrue($ok);
        $this->assertNull(TodoModel::find($id));
    }

    public function testInvalidColumnNameIsRejected(): void
    {
        // Regression test for the SQL-injection-via-column-name fix in Model.php
        $this->expectException(\InvalidArgumentException::class);
        TodoModel::where('title; DROP TABLE todo;--', 'x');
    }
}