<?php
declare(strict_types=1);

namespace Panth\ImageSeo\Model\ImageSeo;

/**
 * Pluggable LLM-vision adapter for alt-text generation.
 *
 * Implementations may call OpenAI Vision, Claude Vision, etc. Adapters
 * must be fast-failing and MUST NOT block page rendering — Panth_ImageSeo
 * only calls them from CLI/queue contexts.
 */
interface VisionAdapterInterface
{
    /**
     * Describe the image at the given absolute path.
     *
     * @param string $absoluteImagePath Absolute path to the image on disk.
     * @param array<string,mixed> $context Entity context (name, sku, etc).
     * @return array{alt:string,title?:string}|null
     */
    public function describe(string $absoluteImagePath, array $context = []): ?array;
}
