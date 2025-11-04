<?php

declare(strict_types=1);

namespace Infinri\Core\Setup\Patch\Data;

use Infinri\Core\Setup\Patch\DataPatchInterface;

/**
 * Install Default Configuration.
 *
 * Installs default system configuration values
 */
class InstallDefaultConfig implements DataPatchInterface
{
    private \PDO $connection;

    public function __construct(\PDO $connection)
    {
        $this->connection = $connection;
    }

    public function apply(): void
    {
        $configs = $this->getDefaultConfigs();

        foreach ($configs as $config) {
            // Check if config already exists
            $stmt = $this->connection->prepare(
                'SELECT config_id FROM core_config_data 
                 WHERE scope = ? AND scope_id = ? AND path = ?'
            );
            $stmt->execute([$config['scope'], $config['scope_id'], $config['path']]);

            if ($stmt->fetchColumn()) {
                continue; // Skip if exists
            }

            // Insert config
            $stmt = $this->connection->prepare(
                'INSERT INTO core_config_data (scope, scope_id, path, value) 
                 VALUES (?, ?, ?, ?)'
            );

            $stmt->execute([
                $config['scope'],
                $config['scope_id'],
                $config['path'],
                $config['value'],
            ]);
        }
    }

    /**
     * Get default configuration values.
     */
    private function getDefaultConfigs(): array
    {
        return [
            // General Settings
            [
                'scope' => 'default',
                'scope_id' => 0,
                'path' => 'general/site/name',
                'value' => 'Infinri',
            ],
            [
                'scope' => 'default',
                'scope_id' => 0,
                'path' => 'general/site/tagline',
                'value' => 'A professional Magento-style framework',
            ],

            // Web Settings
            [
                'scope' => 'default',
                'scope_id' => 0,
                'path' => 'web/url/base',
                'value' => 'http://localhost:8080/',
            ],
            [
                'scope' => 'default',
                'scope_id' => 0,
                'path' => 'web/url/secure_base',
                'value' => 'https://localhost:8080/',
            ],
            [
                'scope' => 'default',
                'scope_id' => 0,
                'path' => 'web/seo/use_rewrites',
                'value' => '1',
            ],

            // Design Settings
            [
                'scope' => 'default',
                'scope_id' => 0,
                'path' => 'design/theme/name',
                'value' => 'Infinri_Theme',
            ],
            [
                'scope' => 'default',
                'scope_id' => 0,
                'path' => 'design/head/default_title',
                'value' => 'Infinri Portfolio',
            ],
            [
                'scope' => 'default',
                'scope_id' => 0,
                'path' => 'design/head/default_description',
                'value' => 'Professional portfolio website built with Infinri framework',
            ],

            // CMS Settings
            [
                'scope' => 'default',
                'scope_id' => 0,
                'path' => 'cms/homepage/page_id',
                'value' => '1',
            ],
            [
                'scope' => 'default',
                'scope_id' => 0,
                'path' => 'cms/no_route/page_id',
                'value' => '2',
            ],

            // Developer Settings
            [
                'scope' => 'default',
                'scope_id' => 0,
                'path' => 'dev/debug/enabled',
                'value' => '1',
            ],
            [
                'scope' => 'default',
                'scope_id' => 0,
                'path' => 'dev/log/enabled',
                'value' => '1',
            ],

            // Theme Footer Settings (matches system.xml structure)
            [
                'scope' => 'default',
                'scope_id' => 0,
                'path' => 'theme_footer/general/enabled',
                'value' => '1',
            ],
            [
                'scope' => 'default',
                'scope_id' => 0,
                'path' => 'theme_footer/general/copyright',
                'value' => '© ' . date('Y') . ' Infinri. All rights reserved.',
            ],
            [
                'scope' => 'default',
                'scope_id' => 0,
                'path' => 'theme_footer/social/social_links',
                'value' => json_encode([
                    [
                        'label' => 'GitHub',
                        'url' => 'https://github.com/infinri',
                        'icon' => 'github',
                        'platform' => 'GitHub',
                    ],
                    [
                        'label' => 'Twitter',
                        'url' => 'https://twitter.com/infinri',
                        'icon' => 'twitter',
                        'platform' => 'Twitter',
                    ],
                    [
                        'label' => 'LinkedIn',
                        'url' => 'https://linkedin.com/company/infinri',
                        'icon' => 'linkedin',
                        'platform' => 'LinkedIn',
                    ],
                ], \JSON_PRETTY_PRINT),
            ],
            [
                'scope' => 'default',
                'scope_id' => 0,
                'path' => 'theme_footer/newsletter/enabled',
                'value' => '0',
            ],
        ];
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
