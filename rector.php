<?php

declare(strict_types=1);

use Rasuvaeff\RectorNamedLiterals\AddNameToLiteralArgumentRector;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\CodingStyle\Rector\FuncCall\FunctionFirstClassCallableRector;
use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\DeadCode\Rector\Concat\RemoveConcatAutocastRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withPhpSets(php83: true)
    ->withPreparedSets(deadCode: true, codeQuality: true)
    ->withRules([AddNameToLiteralArgumentRector::class])
    // Rewrites `$x !== null` into `$x instanceof Some\Long\ClassName` on a
    // property whose declared type already says which class it is: a fully
    // qualified name inline, stating nothing the type declaration did not, and
    // reading worse at every place it appears.
    ->withSkip([
        FlipTypeControlToUseExclusiveTypeRector::class,
        // `array_filter($codes, 'is_string')` narrows list<mixed> to
        // list<string> for psalm; `is_string(...)` does not, and the
        // declared return type stops being provable.
        FunctionFirstClassCallableRector::class,
        // Drops the `(string)` in front of preg_replace() in a
        // concatenation. The concatenation would coerce, but preg_replace
        // returns ?string and concatenating null is deprecated — the cast
        // is the thing that says the null was considered.
        RemoveConcatAutocastRector::class,
        // `(static function () use ($params) { return require …di.php; })()`
        // becomes an arrow function, and the `use ($params)` goes with it.
        // An arrow function captures only the variables its body mentions
        // syntactically — and this one's consumer is the required file, so
        // $params stops existing inside di.php. Verified: the rewrite makes
        // the wiring test emit "Undefined variable $params".
        ClosureToArrowFunctionRector::class => [__DIR__ . '/tests/ConfigWiringTest.php'],
    ]);