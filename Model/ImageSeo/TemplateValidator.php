<?php
declare(strict_types=1);

namespace Panth\ImageSeo\Model\ImageSeo;

use Magento\Framework\Exception\LocalizedException;

class TemplateValidator
{
    public const MAX_LENGTH = 255;

    public const TOKENS = ['name', 'sku', 'store', 'category'];

    public const FILTERS = ['truncate', 'title', 'upper', 'lower', 'strip', 'default'];

    public function validate(string $template, string $label = 'Template'): void
    {
        $template = trim($template);
        if ($template === '') {
            return;
        }

        if (mb_strlen($template, 'UTF-8') > self::MAX_LENGTH) {
            throw new LocalizedException(
                __('%1 must not be longer than %2 characters.', $label, self::MAX_LENGTH)
            );
        }

        $matches = [];
        preg_match_all(AltGenerator::TOKEN_PATTERN, $template, $matches, PREG_SET_ORDER);
        $rest = (string) preg_replace(AltGenerator::TOKEN_PATTERN, '', $template);
        if (str_contains($rest, '{{') || str_contains($rest, '}}')) {
            throw new LocalizedException(
                __('%1 contains an incomplete or malformed token. Use tokens like {{name}} or {{name|truncate:60}}.', $label)
            );
        }

        foreach ($matches as $match) {
            $token = strtolower(explode('.', (string) $match[1], 2)[0]);
            if (!in_array($token, self::TOKENS, true)) {
                throw new LocalizedException(
                    __(
                        '%1 contains the unknown token "%2". Allowed tokens: %3.',
                        $label,
                        $token,
                        implode(', ', self::TOKENS)
                    )
                );
            }
            $this->validateFilters(trim((string) ($match[3] ?? '')), $label);
        }
    }

    private function validateFilters(string $filterString, string $label): void
    {
        if ($filterString === '') {
            return;
        }
        $parts = preg_split('/\s*\|\s*/', ltrim($filterString, '|')) ?: [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            $name = strtolower(trim(explode(':', $part, 2)[0]));
            if (!in_array($name, self::FILTERS, true)) {
                throw new LocalizedException(
                    __(
                        '%1 contains the unknown filter "%2". Allowed filters: %3.',
                        $label,
                        $name,
                        implode(', ', self::FILTERS)
                    )
                );
            }
        }
    }
}
