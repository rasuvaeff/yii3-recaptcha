<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha\Tests;

use Stringable;
use Yiisoft\Translator\CategorySource;
use Yiisoft\Translator\TranslatorInterface;

/**
 * @internal
 */
final class FakeTranslator implements TranslatorInterface
{
    public int $callCount = 0;

    public ?string $lastMessage = null;

    public function __construct(
        private readonly string $return = 'translated',
        private readonly bool $throw = false,
    ) {}

    #[\Override]
    public function addCategorySources(CategorySource ...$categories): static
    {
        return $this;
    }

    #[\Override]
    public function setLocale(string $locale): static
    {
        return $this;
    }

    #[\Override]
    public function getLocale(): string
    {
        return 'en';
    }

    #[\Override]
    public function translate(
        string|Stringable $id,
        array $parameters = [],
        ?string $category = null,
        ?string $locale = null,
    ): string {
        $this->callCount++;
        $this->lastMessage = (string) $id;

        if ($this->throw) {
            throw new \RuntimeException('FakeTranslator::translate was called but should not have been');
        }

        return $this->return;
    }

    #[\Override]
    public function withDefaultCategory(string $category): static
    {
        return $this;
    }

    #[\Override]
    public function withLocale(string $locale): static
    {
        return $this;
    }
}
