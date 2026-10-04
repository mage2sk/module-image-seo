<?php
declare(strict_types=1);

namespace Panth\ImageSeo\Test\Unit\Model\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Panth\ImageSeo\Model\Config\Backend\Template;
use Panth\ImageSeo\Model\ImageSeo\TemplateValidator;
use PHPUnit\Framework\TestCase;

class TemplateTest extends TestCase
{
    private function model(TemplateValidator $validator, array $data): Template
    {
        $context = $this->createStub(Context::class);
        $context->method('getEventDispatcher')->willReturn($this->createStub(ManagerInterface::class));

        return new Template(
            $context,
            $this->createStub(Registry::class),
            $this->createStub(ScopeConfigInterface::class),
            $this->createStub(TypeListInterface::class),
            $validator,
            null,
            null,
            $data
        );
    }

    public function testValidTemplateIsTrimmedAndSaved(): void
    {
        $model = $this->model(new TemplateValidator(), ['value' => '  {{name}} - {{store}}  ']);

        $this->assertSame($model, $model->beforeSave());
        $this->assertSame('{{name}} - {{store}}', $model->getValue());
    }

    public function testInvalidTemplateIsRejectedWithFieldLabel(): void
    {
        $model = $this->model(new TemplateValidator(), [
            'value' => '{{nmae}}',
            'field_config' => ['label' => 'Alt Text Template'],
        ]);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Alt Text Template contains the unknown token "nmae"');
        $model->beforeSave();
    }

    public function testValidatorReceivesTrimmedValueAndDefaultLabel(): void
    {
        $validator = $this->createMock(TemplateValidator::class);
        $validator->expects($this->once())->method('validate')->with('{{sku}}', 'Template');

        $this->model($validator, ['value' => " {{sku}}\n"])->beforeSave();
    }

    public function testEmptyValueIsAllowed(): void
    {
        $model = $this->model(new TemplateValidator(), ['value' => null]);

        $model->beforeSave();
        $this->assertSame('', $model->getValue());
    }
}
