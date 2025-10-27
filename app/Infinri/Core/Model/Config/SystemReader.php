<?php
declare(strict_types=1);

namespace Infinri\Core\Model\Config;

/**
 * System Configuration Reader
 * Parses system.xml files from all modules
 */
class SystemReader
{
    private array $sections = [];
    private bool $loaded = false;
    
    /**
     * Get all configuration sections
     */
    public function getSections(): array
    {
        if (!$this->loaded) {
            $this->loadSystemXml();
            $this->loaded = true;
        }
        
        return $this->sections;
    }
    
    /**
     * Get specific section
     */
    public function getSection(string $sectionId): ?array
    {
        $sections = $this->getSections();
        return $sections[$sectionId] ?? null;
    }
    
    /**
     * Load system.xml files from all modules
     */
    private function loadSystemXml(): void
    {
        $appDir = dirname(__DIR__, 3); // Go up to app/Infinri
        
        if (!is_dir($appDir)) {
            return;
        }
        
        $modules = scandir($appDir);
        
        foreach ($modules as $module) {
            if ($module === '.' || $module === '..') {
                continue;
            }
            
            $systemFile = $appDir . '/' . $module . '/etc/adminhtml/system.xml';
            
            if (!file_exists($systemFile)) {
                continue;
            }
            
            $this->parseSystemXml($systemFile);
        }
        
        // Sort sections by sortOrder
        uasort($this->sections, function($a, $b) {
            return ($a['sortOrder'] ?? 0) <=> ($b['sortOrder'] ?? 0);
        });
    }
    
    /**
     * Parse system.xml file
     */
    private function parseSystemXml(string $file): void
    {
        $xml = simplexml_load_file($file);
        if ($xml === false) {
            return;
        }
        
        foreach ($xml->system->section as $section) {
            $sectionId = (string)$section['id'];
            
            $this->sections[$sectionId] = [
                'id' => $sectionId,
                'label' => (string)$section->label,
                'tab' => (string)($section->tab ?? 'general'),
                'sortOrder' => (int)($section['sortOrder'] ?? 100),
                'showInDefault' => (string)($section['showInDefault'] ?? '1') === '1',
                'showInWebsite' => (string)($section['showInWebsite'] ?? '1') === '1',
                'showInStore' => (string)($section['showInStore'] ?? '1') === '1',
                'resource' => (string)($section->resource ?? ''),
                'groups' => []
            ];
            
            foreach ($section->group as $group) {
                $groupId = (string)$group['id'];
                
                $this->sections[$sectionId]['groups'][$groupId] = [
                    'id' => $groupId,
                    'label' => (string)$group->label,
                    'sortOrder' => (int)($group['sortOrder'] ?? 100),
                    'showInDefault' => (string)($group['showInDefault'] ?? '1') === '1',
                    'showInWebsite' => (string)($group['showInWebsite'] ?? '1') === '1',
                    'showInStore' => (string)($group['showInStore'] ?? '1') === '1',
                    'fields' => []
                ];
                
                foreach ($group->field as $field) {
                    $fieldId = (string)$field['id'];
                    
                    $this->sections[$sectionId]['groups'][$groupId]['fields'][$fieldId] = [
                        'id' => $fieldId,
                        'label' => (string)$field->label,
                        'type' => (string)($field['type'] ?? 'text'),
                        'sortOrder' => (int)($field['sortOrder'] ?? 100),
                        'showInDefault' => (string)($field['showInDefault'] ?? '1') === '1',
                        'showInWebsite' => (string)($field['showInWebsite'] ?? '1') === '1',
                        'showInStore' => (string)($field['showInStore'] ?? '1') === '1',
                        'comment' => (string)($field->comment ?? ''),
                        'validate' => (string)($field->validate ?? ''),
                        'source_model' => (string)($field->source_model ?? ''),
                    ];
                }
                
                // Sort fields by sortOrder
                uasort($this->sections[$sectionId]['groups'][$groupId]['fields'], function($a, $b) {
                    return $a['sortOrder'] <=> $b['sortOrder'];
                });
            }
            
            // Sort groups by sortOrder
            uasort($this->sections[$sectionId]['groups'], function($a, $b) {
                return $a['sortOrder'] <=> $b['sortOrder'];
            });
        }
    }
}
