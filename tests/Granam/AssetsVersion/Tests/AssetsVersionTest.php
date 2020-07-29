<?php declare(strict_types=1);

namespace Granam\AssetsVersion\Tests;

use Granam\AssetsVersion\AssetsVersionInjector;
use PHPUnit\Framework\TestCase;

class AssetsVersionInjectorTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideContentWithAssets
     * @param string $content
     * @param string $assetsRootDir
     * @param string $expectedResult
     */
    public function Asset_version_is_added(string $content, string $assetsRootDir, string $expectedResult): void
    {
        static $assetsVersionInjector;
        if (!$assetsVersionInjector) {
            $assetsVersionInjector = new AssetsVersionInjector();
        }
        $contentWithVersions = $assetsVersionInjector->addVersionsToAssetLinks($content, $assetsRootDir);
        self::assertSame($expectedResult, $contentWithVersions);
    }

    public function provideContentWithAssets(): array
    {
        return [
            [
                file_get_contents(__DIR__ . '/stubs/blog.draciodkaz.cz.html'),
                __DIR__ . '/stubs',
                file_get_contents(__DIR__ . '/stubs/expected.blog.draciodkaz.cz.html'),
            ],
        ];
    }
}
