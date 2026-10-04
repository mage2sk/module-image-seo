<?php
declare(strict_types=1);

namespace Panth\ImageSeo\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class MigrateConfigPaths implements DataPatchInterface
{
    private const LEGACY_PREFIX = 'panth_seo/image/';
    private const NEW_PREFIX    = 'panth_image_seo/image/';

    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup
    ) {
    }

    public function apply(): self
    {
        $connection = $this->moduleDataSetup->getConnection();
        $table = $this->moduleDataSetup->getTable('core_config_data');

        $legacyRows = $connection->fetchAll(
            $connection->select()
                ->from($table, ['config_id', 'scope', 'scope_id', 'path'])
                ->where('path LIKE ?', self::LEGACY_PREFIX . '%')
        );

        foreach ($legacyRows as $row) {
            $newPath = self::NEW_PREFIX . substr((string) $row['path'], strlen(self::LEGACY_PREFIX));
            $exists = $connection->fetchOne(
                $connection->select()
                    ->from($table, ['config_id'])
                    ->where('scope = ?', (string) $row['scope'])
                    ->where('scope_id = ?', (int) $row['scope_id'])
                    ->where('path = ?', $newPath)
                    ->limit(1)
            );
            if ($exists !== false && $exists !== null) {
                continue;
            }
            $connection->update(
                $table,
                ['path' => $newPath],
                ['config_id = ?' => (int) $row['config_id']]
            );
        }

        return $this;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
