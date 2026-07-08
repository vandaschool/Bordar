<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Lib\SqlSplitter;
use PHPUnit\Framework\TestCase;

final class SqlSplitterTest extends TestCase
{
    public function test_splits_two_simple_statements(): void
    {
        $statements = SqlSplitter::statements("CREATE TABLE a (id INT);\nCREATE TABLE b (id INT);");

        $this->assertCount(2, $statements);
    }

    public function test_semicolon_inside_a_line_comment_does_not_split_the_statement(): void
    {
        $sql = "CREATE TABLE a (\n"
            . "    id INT,\n"
            . "    -- note: status is X; becomes Y later\n"
            . "    name VARCHAR(10)\n"
            . ");";

        $statements = SqlSplitter::statements($sql);

        $this->assertCount(1, $statements);
        $this->assertStringNotContainsString('--', $statements[0]);
        $this->assertStringContainsString('name VARCHAR(10)', $statements[0]);
    }

    public function test_empty_and_whitespace_only_chunks_are_dropped(): void
    {
        $statements = SqlSplitter::statements("CREATE TABLE a (id INT);   \n\n;  ;\n");

        $this->assertCount(1, $statements);
    }
}
