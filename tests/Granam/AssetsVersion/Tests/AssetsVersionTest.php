<?php declare(strict_types=1);

namespace Granam\AssetsVersion\Tests;

use Granam\AssetsVersion\AssetsVersionInjector;
use Granam\AssetsVersion\Exceptions\AssetsVersionParsingException;
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
        $assetsVersionInjector = new AssetsVersionInjector();
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

    /**
     * @test
     */
    public function Exception_is_thrown_by_default_on_problem(): void
    {
        $nonExistingFile = uniqid(preg_replace('~^.*::~', '', __METHOD__) . '_', true);
        $uniqueAdditionalInfo = uniqid('some info', true);
        $assetsVersionInjector = new AssetsVersionInjector();

        $this->expectException(AssetsVersionParsingException::class);
        $this->expectErrorMessageMatches(sprintf('~%s.* %s$~', preg_quote($nonExistingFile, '~'), preg_quote($uniqueAdditionalInfo, '~')));
        $assetsVersionInjector->addVersionsToAssetLinks(<<<HTML
<link type="text/css" href="$nonExistingFile">
HTML
            , __DIR__, $uniqueAdditionalInfo);
    }
}
