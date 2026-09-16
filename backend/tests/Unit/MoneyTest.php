<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_to_tl(): void
    {
        $this->assertSame(95, Money::toTl(9500));
        $this->assertSame(95.5, Money::toTl(9550));
        $this->assertSame(0, Money::toTl(0));
    }

    public function test_from_tl(): void
    {
        $this->assertSame(9500, Money::fromTl(95));
        $this->assertSame(9550, Money::fromTl('95.5'));
        $this->assertSame(1999, Money::fromTl(19.99));
        $this->assertSame(0, Money::fromTl('abc'));
        $this->assertSame(0, Money::fromTl(null));
        $this->assertSame(0, Money::fromTl(-5));
    }
}
