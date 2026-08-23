<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class BaselineTest extends TestCase
{
    public function testSanity(): void
    {
        $this->assertTrue(true);
    }

    public function testPhpVersionIsAtLeast82(): void
    {
        $this->assertTrue(PHP_VERSION_ID >= 80200);
    }
}
