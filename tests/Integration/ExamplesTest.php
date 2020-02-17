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
        self::checkExampleRequirements($info['requirements']);

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

        $result = ['requirements' => []];
        if (preg_match_all('~@requires\s+?(\w+?)\s+?(.+?)\s*?$~mi', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $result['requirements'][strtolower($match[1])] = $match[2];
            }
        }
        if (preg_match('~\/\*\s*?OUTPUT\b(.+?)\*\/~s', $content, $matches)) {
            $result['output'] = trim($matches[1]);
        }

        return $result;
    }

    private static function checkExampleRequirements(array $requirements) : void
    {
        if (isset($requirements['tarantool'])) {
            if (version_compare(self::getTarantoolVersion(), $requirements['tarantool'], '<')) {
                self::markTestSkipped(sprintf('Tarantool >= %s is required.', $requirements['tarantool']));
            }
        }

        if (isset($requirements['extension']) && !extension_loaded($requirements['extension'])) {
            self::markTestSkipped(sprintf('Extension %s is required.', $requirements['extension']));
        }

        if (isset($requirements['function']) && !\function_exists($requirements['function'])) {
            $pieces = \explode('::', $requirements['function']);
            if ((2 !== \count($pieces)) || !\class_exists($pieces[0]) || !\method_exists($pieces[0], $pieces[1])) {
                self::markTestSkipped(sprintf('Function %s is required.', $requirements['function']));
            }
        }
    }
}
