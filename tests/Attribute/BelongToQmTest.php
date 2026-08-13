<?php

declare(strict_types=1);

namespace Prolyfix\QmBundle\Tests\Attribute;

use PHPUnit\Framework\TestCase;
use Prolyfix\QmBundle\Attribute\BelongToQm;

final class BelongToQmTest extends TestCase
{
    public function testIsAClassLevelAttribute(): void
    {
        $reflectionClass = new \ReflectionClass(BelongToQm::class);
        $attributes = $reflectionClass->getAttributes(\Attribute::class);

        $this->assertCount(1, $attributes);

        /** @var \Attribute $instance */
        $instance = $attributes[0]->newInstance();
        $this->assertSame(\Attribute::TARGET_CLASS, $instance->flags);
    }

    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf(BelongToQm::class, new BelongToQm());
    }
}
