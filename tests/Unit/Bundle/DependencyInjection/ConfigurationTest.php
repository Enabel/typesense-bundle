<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Unit\Bundle\DependencyInjection;

use Enabel\Typesense\Bundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

final class ConfigurationTest extends TestCase
{
    private Processor $processor;

    protected function setUp(): void
    {
        $this->processor = new Processor();
    }

    public function testItProcessesMinimalConfig(): void
    {
        $config = $this->process([
            'client' => [
                'url' => 'http://localhost:8108',
                'api_key' => 'xyz',
            ],
            'collections' => [
                'App\Entity\Product' => null,
            ],
        ]);

        self::assertSame('http://localhost:8108', $config['client']['url']);
        self::assertSame('xyz', $config['client']['api_key']);
        self::assertNull($config['default_denormalizer']);
        self::assertNull($config['default_data_provider']);
        self::assertArrayHasKey('App\Entity\Product', $config['collections']);
        self::assertNull($config['collections']['App\Entity\Product']['denormalizer']);
        self::assertNull($config['collections']['App\Entity\Product']['data_provider']);
    }

    public function testItProcessesFullConfig(): void
    {
        $config = $this->process([
            'client' => [
                'url' => 'http://ts1:8108',
                'api_key' => 'secret',
            ],
            'default_denormalizer' => 'App\Denormalizer\Custom',
            'default_data_provider' => 'App\Provider\Custom',
            'collections' => [
                'App\Entity\Product' => null,
                'App\Entity\User' => [
                    'denormalizer' => 'App\Denormalizer\UserDenormalizer',
                    'data_provider' => 'App\Provider\UserProvider',
                ],
            ],
        ]);

        self::assertSame('App\Denormalizer\Custom', $config['default_denormalizer']);
        self::assertSame('App\Provider\Custom', $config['default_data_provider']);
        self::assertNull($config['collections']['App\Entity\Product']['denormalizer']);
        self::assertSame('App\Denormalizer\UserDenormalizer', $config['collections']['App\Entity\User']['denormalizer']);
        self::assertSame('App\Provider\UserProvider', $config['collections']['App\Entity\User']['data_provider']);
    }

    public function testAutoIndexDefaultsToTrue(): void
    {
        $config = $this->process([
            'client' => ['url' => 'http://localhost:8108', 'api_key' => 'xyz'],
            'collections' => [],
        ]);

        self::assertTrue($config['auto_index']);
    }

    public function testAutoIndexCanBeDisabled(): void
    {
        $config = $this->process([
            'client' => ['url' => 'http://localhost:8108', 'api_key' => 'xyz'],
            'auto_index' => false,
            'collections' => [],
        ]);

        self::assertFalse($config['auto_index']);
    }

    public function testItRequiresClientUrl(): void
    {
        $this->expectException(\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException::class);

        $this->process([
            'client' => [
                'api_key' => 'xyz',
            ],
            'collections' => [],
        ]);
    }

    public function testItRequiresClientApiKey(): void
    {
        $this->expectException(\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException::class);

        $this->process([
            'client' => [
                'url' => 'http://localhost:8108',
            ],
            'collections' => [],
        ]);
    }

    /**
     * @param array<string, mixed> $configs
     * @return array<string, mixed>
     */
    private function process(array $configs): array
    {
        return $this->processor->processConfiguration(new Configuration(), [$configs]);
    }
}
