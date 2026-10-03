<?php
declare(strict_types=1);

namespace Panth\ImageSeo\Test\Unit\Setup\Patch\Data;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Panth\ImageSeo\Setup\Patch\Data\MigrateConfigPaths;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class MigrateConfigPathsTest extends TestCase
{
    public function testExistingTargetRowIsKeptAndLegacyRowIsLeftAlone(): void
    {
        $select = $this->createStub(Select::class);
        $select->method('from')->willReturnSelf();
        $select->method('where')->willReturnSelf();
        $select->method('limit')->willReturnSelf();

        $connection = $this->createMock(AdapterInterface::class);
        $connection->method('select')->willReturn($select);
        $connection->method('fetchAll')->willReturn([
            ['config_id' => 10, 'scope' => 'default', 'scope_id' => 0, 'path' => 'panth_seo/image/alt_template'],
            ['config_id' => 11, 'scope' => 'stores', 'scope_id' => 1, 'path' => 'panth_seo/image/title_template'],
        ]);
        $connection->method('fetchOne')->willReturnOnConsecutiveCalls('99', false);
        $connection->expects($this->once())
            ->method('update')
            ->with(
                'core_config_data',
                ['path' => 'panth_image_seo/image/title_template'],
                ['config_id = ?' => 11]
            )
            ->willReturn(1);

        $setup = $this->createStub(ModuleDataSetupInterface::class);
        $setup->method('getConnection')->willReturn($connection);
        $setup->method('getTable')->willReturn('core_config_data');

        (new MigrateConfigPaths($setup))->apply();
    }

    public function testNoLegacyRowsDoesNothing(): void
    {
        $select = $this->createStub(Select::class);
        $select->method('from')->willReturnSelf();
        $select->method('where')->willReturnSelf();

        $connection = $this->createMock(AdapterInterface::class);
        $connection->method('select')->willReturn($select);
        $connection->method('fetchAll')->willReturn([]);
        $connection->expects($this->never())->method('update');

        $setup = $this->createStub(ModuleDataSetupInterface::class);
        $setup->method('getConnection')->willReturn($connection);
        $setup->method('getTable')->willReturn('core_config_data');

        (new MigrateConfigPaths($setup))->apply();
    }
}
