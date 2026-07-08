<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Lib\HsCodes;
use PHPUnit\Framework\TestCase;

final class HsCodesTest extends TestCase
{
    public function test_tree_has_chapters_with_children(): void
    {
        $tree = HsCodes::tree();

        $this->assertNotEmpty($tree);
        $this->assertArrayHasKey('children', $tree[0]);
        $this->assertNotEmpty($tree[0]['children']);
    }

    public function test_valid_code_from_tree_is_recognized(): void
    {
        $tree = HsCodes::tree();
        $firstHeadingCode = $tree[0]['children'][0]['code'];

        $this->assertTrue(HsCodes::isValidCode($firstHeadingCode));
    }

    public function test_unknown_code_is_rejected(): void
    {
        $this->assertFalse(HsCodes::isValidCode('9999'));
        $this->assertFalse(HsCodes::isValidCode('not-a-code'));
    }
}
