<?php

declare(strict_types=1);

namespace Oliverde8\PhpEtlBundle\Tests\Etl\ChainDefinition;

use Oliverde8\Component\PhpEtl\ChainConfig;
use Oliverde8\PhpEtlBundle\Etl\ChainDefinition\CleanupOldExecutionDefinition;
use Oliverde8\PhpEtlBundle\Etl\ChainDefinition\ExampleDefinition;
use Oliverde8\PhpEtlBundle\Etl\ChainDefinition\FlysystemExampleDefinition;
use Oliverde8\PhpEtlBundle\Etl\ChainDefinitionInterface\ChainDefinitionInterface;
use PHPUnit\Framework\TestCase;

/**
 * Guards the built-in V2 (PHP) chain definitions.
 *
 * Every definition must expose a non-empty key and its build() must return a
 * ChainConfig. This is a cheap regression net for two mistakes that are easy to
 * ship in a PHP-configured chain: forgetting to `return` from build() (fatals
 * with a TypeError at execution time) and PHP-version-specific syntax that only
 * parses on the developer's local version.
 */
class ChainDefinitionBuildTest extends TestCase
{
    /**
     * @return iterable<string, array{0: ChainDefinitionInterface}>
     */
    public static function definitionProvider(): iterable
    {
        yield 'example' => [new ExampleDefinition()];
        yield 'cleanup-old-execution' => [new CleanupOldExecutionDefinition()];
        yield 'flysystem-example' => [new FlysystemExampleDefinition()];
    }

    /**
     * @dataProvider definitionProvider
     */
    public function testGetKeyIsNonEmptyString(ChainDefinitionInterface $definition): void
    {
        $this->assertNotEmpty($definition->getKey());
    }

    /**
     * @dataProvider definitionProvider
     */
    public function testBuildReturnsChainConfig(ChainDefinitionInterface $definition): void
    {
        $this->assertInstanceOf(ChainConfig::class, $definition->build());
    }
}
