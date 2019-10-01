<?php

/**
 * This file is part of the Tarantool Client package.
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
        $info = self::parseFile($filename);

        if (isset($info['tarantool_version'])) {
            [$major, $minor, $patch] = \sscanf($info['tarantool_version'], '%d.%d.%d');
            $requiredVersionId = $major * 10000 + $minor * 100 + $patch;
            if (self::getTarantoolVersionId() < $requiredVersionId) {
                self::markTestSkipped(sprintf('Tarantool >= %s is required.', $info['tarantool_version']));
            }
        }

        $uri = ClientBuilder::createFromEnv()->getUri();

        exec("php $filename $uri", $output, $exitCode);

        self::assertSame(0, $exitCode, implode("\n", $output));

        if (isset($info['output'])) {
            self::assertSame($info['output'], implode("\n", $output));
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
            // ignore classes
            if (strtolower($basename[0]) !== $basename[0]) {
                continue;
            }

            yield [$filename];
        }
    }

    private static function parseFile(string $filename) : array
    {
        $content = file_get_contents($filename);

        $result = [];
        if (preg_match('~@requires\s+?tarantool\s+?(?<version>[.\d]+)~i', $content, $matches)) {
            $result['tarantool_version'] = $matches['version'];
        }
        if (preg_match('~\/\*\s*?OUTPUT\b(.+?)\*\/~s', $content, $matches)) {
            $result['output'] = trim($matches[1]);
        }

        return $result;
    }
}
