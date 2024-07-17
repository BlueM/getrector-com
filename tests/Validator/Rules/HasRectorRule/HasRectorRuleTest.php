<?php

declare(strict_types=1);

namespace App\Tests\Validator\Rules\HasRectorRule;

use App\Exception\ShouldNotHappenException;
use App\Tests\AbstractTestCase;
use App\Validation\Rules\HasRectorRule;
use Iterator;
use Nette\Utils\FileSystem;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use Rector\Config\RectorConfig;

final class HasRectorRuleTest extends AbstractTestCase
{
    private HasRectorRule $hasRectorRule;

    protected function setUp(): void
    {
        $rectorConfig = $this->make(RectorConfig::class);
        $this->hasRectorRule = new HasRectorRule($rectorConfig);
    }

    #[DoesNotPerformAssertions]
    #[DataProvider('provideValidData')]
    public function test(string $filePath): void
    {
        $contents = FileSystem::read($filePath);

        $this->hasRectorRule->validate(
            'attribute',
            $contents,
            fn (string $message) => throw new ShouldNotHappenException()
        );
    }

    public static function provideValidData(): Iterator
    {
        yield [__DIR__ . '/Fixture/valid/simple_rule.php'];
        yield [__DIR__ . '/Fixture/valid/valid_config.php'];
    }

    #[DataProvider('provideInvalidData')]
    public function testInvalid(string $filePath): void
    {
        $contents = FileSystem::read($filePath);

        $this->expectException(ShouldNotHappenException::class);
        $this->hasRectorRule->validate(
            'attribute',
            $contents,
            fn (string $message) => throw new ShouldNotHappenException()
        );
    }

    public static function provideInvalidData(): Iterator
    {
        yield [__DIR__ . '/Fixture/invalid/invalid_config.php'];
    }
}
