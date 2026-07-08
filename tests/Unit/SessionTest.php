<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Session;
use PHPUnit\Framework\TestCase;

final class SessionTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        Session::start();
    }

    public function test_set_and_get(): void
    {
        Session::set('foo', 'bar');

        $this->assertSame('bar', Session::get('foo'));
        $this->assertTrue(Session::has('foo'));
    }

    public function test_remove(): void
    {
        Session::set('to_remove', 1);
        Session::remove('to_remove');

        $this->assertFalse(Session::has('to_remove'));
    }

    public function test_flash_is_consumed_after_read(): void
    {
        Session::flash('notice', 'hello');

        $this->assertSame('hello', Session::flash('notice'));
        $this->assertNull(Session::flash('notice'));
    }

    public function test_csrf_token_is_stable_and_verifiable(): void
    {
        $token = Session::csrfToken();

        $this->assertSame($token, Session::csrfToken());
        $this->assertTrue(Session::verifyCsrfToken($token));
        $this->assertFalse(Session::verifyCsrfToken('wrong-token'));
    }
}
