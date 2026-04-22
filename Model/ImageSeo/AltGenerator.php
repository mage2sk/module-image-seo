<?php
declare(strict_types=1);

namespace Panth\ImageSeo\Model\ImageSeo;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Generates alt/title text from a template with token substitution and a
 * small filter pipeline, plus an optional vision adapter fallback.
 *
 * Templates use tokens like:
 *   {{name}} - {{store}}            for alt
 *   {{name|truncate:120}}           for title
 *
 * Supported tokens: name, sku, store, category.
 * Supported filters: truncate:N, title, strip, default:'x', upper, lower.
 *
 * This is a self-contained renderer so Panth_ImageSeo has no dependency
 * on Panth_AdvancedSEO's TemplateRenderer / TokenRegistry chain.
 */
class AltGenerator
{
    /** Maximum characters for rendered alt/title text. */
    private const MAX_OUTPUT_LENGTH = 250;

    /** Token regex: `{{token(:arg)?(|filter(:arg)?)*}}`. */
    private const TOKEN_PATTERN = '/\{\{\s*([a-zA-Z0-9_\.]+)(?::([a-zA-Z0-9_\-]+))?((?:\s*\|\s*[a-zA-Z0-9_]+(?::[^|}]+)?)*)\s*\}\}/u';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly StoreManagerInterface $storeManager,
        private readonly LoggerInterface $logger,
        private readonly ?VisionAdapterInterface $visionAdapter = null
    ) {
    }

    /**
     * Render alt and title templates for the given entity/context.
     *
     * @param string $altTemplate
     * @param string $titleTemplate
     * @param mixed $entity Typically a ProductInterface, may be null.
     * @param array<string,mixed> $context Entity context for token resolution.
     * @param string|null $absoluteImagePath Optional path for vision fallback.
     * @return array{alt:string,title:string}
     */
    public function generate(
        string $altTemplate,
        string $titleTemplate,
        mixed $entity,
        array $context = [],
        ?string $absoluteImagePath = null
    ): array {
        $alt   = '';
        $title = '';

        try {
            if ($altTemplate !== '') {
                $alt = trim($this->render($altTemplate, $entity, $context));
            }
            if ($titleTemplate !== '') {
                $title = trim($this->render($titleTemplate, $entity, $context));
            }
        } catch (\Throwable $e) {
            $this->logger->warning('[PanthImageSeo] alt template render failed: ' . $e->getMessage());
        }

        if ($alt === ''
            && $this->visionAdapter !== null
            && $absoluteImagePath !== null
            && is_file($absoluteImagePath)
        ) {
            try {
                $vision = $this->visionAdapter->describe($absoluteImagePath, $context);
                if (is_array($vision)) {
                    $alt = (string) ($vision['alt'] ?? $alt);
                    if ($title === '') {
                        $title = (string) ($vision['title'] ?? $alt);
                    }
                }
            } catch (\Throwable $e) {
                $this->logger->warning('[PanthImageSeo] vision adapter failed: ' . $e->getMessage());
            }
        }

        if ($alt === '') {
            $alt = (string) ($context['name']
                ?? (is_object($entity) && method_exists($entity, 'getName') ? (string) $entity->getName() : ''));
        }
        if ($title === '') {
            $title = $alt;
        }
        if (mb_strlen($alt, 'UTF-8') > self::MAX_OUTPUT_LENGTH) {
            $alt = mb_substr($alt, 0, self::MAX_OUTPUT_LENGTH - 3, 'UTF-8') . '...';
        }
        if (mb_strlen($title, 'UTF-8') > self::MAX_OUTPUT_LENGTH) {
            $title = mb_substr($title, 0, self::MAX_OUTPUT_LENGTH - 3, 'UTF-8') . '...';
        }
        return ['alt' => $alt, 'title' => $title];
    }

    /**
     * Render a template string against an entity + context.
     *
     * @param array<string,mixed> $context
     */
    private function render(string $template, mixed $entity, array $context): string
    {
        if ($template === '' || !str_contains($template, '{{')) {
            return $template;
        }
        $output = preg_replace_callback(
            self::TOKEN_PATTERN,
            fn (array $m): string => $this->resolveMatch($m, $entity, $context),
            $template
        );
        if (!is_string($output)) {
            return $template;
        }
        return $this->cleanOutput($output);
    }

    /**
     * @param array<int,string> $match
     * @param array<string,mixed> $context
     */
    private function resolveMatch(array $match, mixed $entity, array $context): string
    {
        $tokenName = strtolower($match[1] ?? '');
        $argument  = ($match[2] ?? '') !== '' ? $match[2] : null;
        $filters   = trim($match[3] ?? '');

        if ($argument === null && str_contains($tokenName, '.')) {
            $parts = explode('.', $tokenName, 2);
            $tokenName = $parts[0];
            $argument  = $parts[1];
        }

        $value = $this->resolveToken($tokenName, $entity, $context);

        if ($filters !== '') {
            $value = $this->applyFilters($value, $filters);
        }
        return $value;
    }

    /**
     * Resolve a single bare token name against the entity / context.
     *
     * @param array<string,mixed> $context
     */
    private function resolveToken(string $token, mixed $entity, array $context): string
    {
        if (isset($context[$token]) && is_scalar($context[$token])) {
            return (string) $context[$token];
        }
        try {
            switch ($token) {
                case 'name':
                    return is_object($entity) && method_exists($entity, 'getName')
                        ? (string) $entity->getName()
                        : '';
                case 'sku':
                    return is_object($entity) && method_exists($entity, 'getSku')
                        ? (string) $entity->getSku()
                        : '';
                case 'store':
                    return (string) $this->storeManager->getStore()->getName();
                case 'category':
                    return $this->resolveCategory($entity);
                default:
                    return '';
            }
        } catch (\Throwable $e) {
            $this->logger->warning('[PanthImageSeo] token resolve failed', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);
            return '';
        }
    }

    /**
     * Best-effort category-name lookup for a product-like entity.
     *
     * Avoids heavy category collection loads; uses getCategory() when the
     * product object already has it attached. Returns '' when no category
     * is readily available.
     */
    private function resolveCategory(mixed $entity): string
    {
        if (!is_object($entity)) {
            return '';
        }
        if (method_exists($entity, 'getCategory')) {
            $category = $entity->getCategory();
            if (is_object($category) && method_exists($category, 'getName')) {
                return (string) $category->getName();
            }
        }
        return '';
    }

    /**
     * Apply a `|filter|filter:arg` pipeline to a value.
     */
    private function applyFilters(string $value, string $filterString): string
    {
        $parts = preg_split('/\s*\|\s*/', ltrim($filterString, '|')) ?: [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            $name = $part;
            $arg  = null;
            if (str_contains($part, ':')) {
                [$name, $arg] = explode(':', $part, 2);
                $name = trim($name);
                $arg  = trim($arg);
                if ($arg !== '' && ($arg[0] === "'" || $arg[0] === '"')) {
                    $arg = trim($arg, "'\"");
                }
            }
            $value = $this->applyFilter(strtolower($name), $value, $arg);
        }
        return $value;
    }

    /**
     * Apply a single filter by name.
     */
    private function applyFilter(string $name, string $value, ?string $arg): string
    {
        switch ($name) {
            case 'truncate':
                $len = max(1, (int) ($arg ?? '60'));
                if (mb_strlen($value, 'UTF-8') > $len) {
                    return rtrim(mb_substr($value, 0, $len - 1, 'UTF-8')) . '…';
                }
                return $value;

            case 'title':
                return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');

            case 'strip':
                $clean = strip_tags($value);
                return trim((string) preg_replace('/\s+/u', ' ', $clean));

            case 'default':
                return $value === '' ? (string) $arg : $value;

            case 'upper':
                return mb_strtoupper($value, 'UTF-8');

            case 'lower':
                return mb_strtolower($value, 'UTF-8');

            default:
                return $value;
        }
    }

    /**
     * Clean up rendered output: remove empty separators, collapse spaces,
     * trim leading/trailing punctuation left by empty tokens.
     */
    private function cleanOutput(string $output): string
    {
        $output = preg_replace('/^\s*[-|]\s*/', '', $output) ?? $output;
        $output = preg_replace('/\s*[-|]\s*$/', '', $output) ?? $output;
        $output = preg_replace('/,\s*,/', ',', $output) ?? $output;
        $output = preg_replace('/^[\s,]+/', '', $output) ?? $output;
        $output = preg_replace('/[\s,]+$/', '', $output) ?? $output;
        $output = preg_replace('/\s*[-]\s*\|\s*/', ' | ', $output) ?? $output;
        $output = preg_replace('/\s*\|\s*[-]\s*/', ' - ', $output) ?? $output;
        $output = preg_replace('/\s{2,}/', ' ', $output) ?? $output;
        return trim($output);
    }
}
