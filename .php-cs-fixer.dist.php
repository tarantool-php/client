<?php

declare(strict_types=1);

namespace Tarantool\Client;

use PhpCsFixer\Config;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\Fixer\ConstantNotation\NativeConstantInvocationFixer;
use PhpCsFixer\Fixer\FunctionNotation\NativeFunctionInvocationFixer;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;

final class FilterableFixer implements ConfigurableFixerInterface
{
    private ConfigurableFixerInterface $fixer;
    private string $pathRegex;

    public function __construct(ConfigurableFixerInterface $fixer, string $pathRegex)
    {
        $this->fixer = $fixer;
        $this->pathRegex = $pathRegex;
    }

    public function configure(array $configuration) : void
    {
        $this->fixer->configure($configuration);
    }

    public function getConfigurationDefinition() : FixerConfigurationResolverInterface
    {
        return $this->fixer->getConfigurationDefinition();
    }

    public function isCandidate(Tokens $tokens) : bool
    {
        return $this->fixer->isCandidate($tokens);
    }

    public function isRisky() : bool
    {
        return $this->fixer->isRisky();
    }

    public function fix(\SplFileInfo $file, Tokens $tokens) : void
    {
        $this->fixer->fix($file, $tokens);
    }

    public function getName() : string
    {
        return (new \ReflectionClass($this))->getShortName().'/'.$this->fixer->getName();
    }

    public function getPriority() : int
    {
        return $this->fixer->getPriority();
    }

    public function getDefinition() : FixerDefinitionInterface
    {
        return $this->fixer->getDefinition();
    }

    public function supports(\SplFileInfo $file) : bool
    {
        if (1 !== preg_match($this->pathRegex, $file->getRealPath())) {
            return false;
        }

        return $this->fixer->supports($file);
    }
};

$header = <<<EOF
This file is part of the tarantool/client package.

(c) Eugene Leonovich <gen.work@gmail.com>

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
EOF;

return (new Config())
    ->setUsingCache(false)
    ->setRiskyAllowed(true)
    ->registerCustomFixers([
        new FilterableFixer(new NativeConstantInvocationFixer(), '/\bsrc\b/'),
        new FilterableFixer(new NativeFunctionInvocationFixer(), '/\bsrc\b/'),
    ])
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'array_syntax' => ['syntax' => 'short'],
        'binary_operator_spaces' => ['operators' => ['=' => null, '=>' => null]],
        'class_attributes_separation' => ['elements' => ['method' => 'one', 'property' => 'none']],
        'declare_strict_types' => true,
        'integer_literal_case' => false,
        'native_constant_invocation' => false,
        'native_function_invocation' => false,
        'FilterableFixer/native_constant_invocation' => true,
        'FilterableFixer/native_function_invocation' => ['include' => ['@all']],
        'no_useless_else' => true,
        'no_useless_return' => true,
        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
            'imports_order' => ['class', 'function', 'const'],
        ],
        'phpdoc_align' => false,
        'phpdoc_order' => true,
        'phpdoc_to_comment' => false,
        'phpdoc_separation' => false, // do not separate @param and @psalm-param
        'return_type_declaration' => ['space_before' => 'one'],
        'strict_comparison' => true,
        'header_comment' => [
            'comment_type' => 'PHPDoc',
            'header' => $header,
            'location' => 'after_open',
            'separate' => 'both',
        ],
    ])
;
