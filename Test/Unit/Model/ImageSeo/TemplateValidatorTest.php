<?php
declare(strict_types=1);

namespace Panth\ImageSeo\Test\Unit\Model\ImageSeo;

use Magento\Framework\Exception\LocalizedException;
use Panth\ImageSeo\Model\ImageSeo\TemplateValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TemplateValidatorTest extends TestCase
{
    public static function validTemplates(): array
    {
        return [
            'empty' => [''],
            'whitespace' => ['   '],
            'plain text' => ['Our product photo'],
            'default alt' => ['{{name}} - {{store}}'],
            'all tokens' => ['{{name}} {{sku}} {{store}} {{category}}'],
            'filters chained' => ["{{name|lower|title|truncate:40}} | {{category|default:'our catalogue'}}"],
            'upper and strip' => ['[{{sku|upper}}] {{name|strip}}'],
            'spaces inside' => ['{{ name | truncate:80 }}'],
            'case insensitive' => ['{{NAME|UPPER}}'],
            'dotted token' => ['{{name.short}}'],
            'max length' => [str_repeat('a', 255)],
        ];
    }

    #[DataProvider('validTemplates')]
    public function testValidTemplatesPass(string $template): void
    {
        (new TemplateValidator())->validate($template);
        $this->addToAssertionCount(1);
    }

    public static function invalidTemplates(): array
    {
        return [
            'unknown token' => ['{{nmae}} - {{store}}', 'unknown token "nmae"'],
            'unknown filter' => ['{{name|shout}}', 'unknown filter "shout"'],
            'unknown filter after valid' => ['{{name|lower|bogus:3}}', 'unknown filter "bogus"'],
            'unclosed token' => ['{{name - {{store}}', 'incomplete or malformed token'],
            'stray closing' => ['name}} - {{store}}', 'incomplete or malformed token'],
            'empty token' => ['{{}}', 'incomplete or malformed token'],
            'too long' => [str_repeat('a', 256), 'must not be longer than 255 characters'],
        ];
    }

    #[DataProvider('invalidTemplates')]
    public function testInvalidTemplatesThrowWithClearMessage(string $template, string $expected): void
    {
        try {
            (new TemplateValidator())->validate($template, 'Alt Text Template');
            $this->fail('Expected LocalizedException');
        } catch (LocalizedException $e) {
            $this->assertStringContainsString($expected, $e->getMessage());
            $this->assertStringStartsWith('Alt Text Template', $e->getMessage());
        }
    }

    public function testUnknownTokenMessageListsAllowedTokens(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Allowed tokens: name, sku, store, category.');
        (new TemplateValidator())->validate('{{price}}');
    }

    public function testUnknownFilterMessageListsAllowedFilters(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Allowed filters: truncate, title, upper, lower, strip, default.');
        (new TemplateValidator())->validate('{{name|reverse}}');
    }

    public function testDefaultLabelIsUsed(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Template contains the unknown token "foo"');
        (new TemplateValidator())->validate('{{foo}}');
    }
}
