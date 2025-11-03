<?php
declare(strict_types=1);

namespace Infinri\Admin\Block\System;

use Infinri\Core\App\Request;
use Infinri\Core\Block\Template;
use Infinri\Core\Model\Config as ConfigModel;
use Infinri\Core\Model\Config\SystemReader;
use Infinri\Core\Model\ObjectManager;

/**
 * System Configuration Block
 */
class Config extends Template
{
    private ?SystemReader $systemReader = null;
    private ?ConfigModel $config = null;
    private ?Request $request = null;

    /**
     * Get current section ID
     */
    public function getCurrentSection(): string
    {
        return $this->getRequest()->getParam('section', 'general');
    }

    /**
     * Get all sections
     */
    public function getSections(): array
    {
        return $this->getSystemReader()->getSections();
    }

    /**
     * Get current section data
     */
    public function getSectionData(): ?array
    {
        return $this->getSystemReader()->getSection($this->getCurrentSection());
    }

    /**
     * Get configuration value
     */
    public function getConfigValue(string $path): mixed
    {
        return $this->getConfigModel()->getValue($path);
    }

    /**
     * Get field options from source model
     */
    public function getFieldOptions(string $sourceModel): array
    {
        if (empty($sourceModel)) {
            return [];
        }

        try {
            $source = new $sourceModel();
            return $source->toOptionArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getSystemReader(): SystemReader
    {
        if ($this->systemReader === null) {
            $this->systemReader = ObjectManager::getInstance()->create(SystemReader::class);
        }
        return $this->systemReader;
    }

    private function getConfigModel(): ConfigModel
    {
        if ($this->config === null) {
            $this->config = ObjectManager::getInstance()->create(ConfigModel::class);
        }
        return $this->config;
    }

    private function getRequest(): Request
    {
        if ($this->request === null) {
            $this->request = ObjectManager::getInstance()->get(Request::class);
        }
        return $this->request;
    }
}
