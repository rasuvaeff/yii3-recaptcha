<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha\Tests\Support;

/**
 * Sorts the attributes inside `<input>` tags so markup assertions do not depend
 * on the attribute output order, which differs across yiisoft/html versions.
 */
trait NormalizesHtml
{
    private static function normalizeInputAttributes(string $html): string
    {
        return (string) preg_replace_callback(
            '/<input\b([^>]*)>/',
            static function (array $matches): string {
                preg_match_all('/\s+([\w:-]+)(="[^"]*")?/', $matches[1], $attributeMatches, PREG_SET_ORDER);
                $attributes = array_map(
                    static fn(array $attribute): string => $attribute[1] . ($attribute[2] ?? ''),
                    $attributeMatches,
                );
                sort($attributes);

                return '<input' . ($attributes === [] ? '' : ' ' . implode(' ', $attributes)) . '>';
            },
            $html,
        );
    }
}
