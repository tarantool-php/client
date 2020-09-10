<?php

/**
 * This file is part of the tarantool/client package.
 *
 * (c) Eugene Leonovich <gen.work@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tarantool\Client\Tests\Integration;

final class ExamplesTest extends TestCase
{
    /**
     * @dataProvider provideExampleData
     */
    public function testExample(string $filename) : void
    {
        $uri = ClientBuilder::createFromEnv()->getUri();

        exec("php $filename $uri", $output, $exitCode);

        $flattenOutput = implode("\n", $output);
        if (0 === strpos($flattenOutput, 'Unfulfilled requirement:')) {
            self::markTestSkipped($flattenOutput);
        }

        self::assertSame(0, $exitCode, $flattenOutput);

        $expectedOutput = self::parseFile($filename);
        if (null !== $expectedOutput) {
            self::assertSame($expectedOutput, $flattenOutput);
        }
    }

    public function provideExampleData() : iterable
    {
        $dir = dirname(__DIR__, 2).'/examples';
        foreach (glob("$dir/{**/*,*}.php", GLOB_BRACE) as $filename) {
            $basename = basename($filename, '.php');
            if ('bootstrap' === $basename) {
                continue;
            }
            // Ignore classes
            if (strtolower($basename[0]) !== $basename[0]) {
                continue;
            }

            yield [$filename];
        }
    }

    private static function parseFile(string $filename) : ?string
    {
        $content = file_get_contents($filename);
        if (preg_match('~\/\*\s*?OUTPUT\b(.+?)\*\/~s', $content, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }
}
