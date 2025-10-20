<?php
declare(strict_types=1);

namespace Infinri\Core\Block;

/**
 * JS Block
 * 
 * Renders a proper <script> tag for JavaScript files
 * More secure and maintainable than raw HTML in layout XML
 */
class Js extends AbstractBlock
{
    /**
     * @var string Script src
     */
    private string $src = '';
    
    /**
     * @var bool Defer attribute
     */
    private bool $defer = true;
    
    /**
     * @var bool Async attribute
     */
    private bool $async = false;
    
    /**
     * Set script src
     *
     * @param string $src
     * @return $this
     */
    public function setSrc(string $src): self
    {
        $this->src = $src;
        return $this;
    }
    
    /**
     * Get script src
     *
     * @return string
     */
    public function getSrc(): string
    {
        // Check data array first (from XML)
        $dataSrc = $this->getData('src');
        if ($dataSrc !== null) {
            return $dataSrc;
        }
        
        return $this->src;
    }
    
    /**
     * Set defer attribute
     *
     * @param bool $defer
     * @return $this
     */
    public function setDefer(bool $defer): self
    {
        $this->defer = $defer;
        return $this;
    }
    
    /**
     * Get defer attribute
     *
     * @return bool
     */
    public function getDefer(): bool
    {
        $dataDefer = $this->getData('defer');
        if ($dataDefer !== null) {
            return filter_var($dataDefer, FILTER_VALIDATE_BOOLEAN);
        }
        
        return $this->defer;
    }
    
    /**
     * Set async attribute
     *
     * @param bool $async
     * @return $this
     */
    public function setAsync(bool $async): self
    {
        $this->async = $async;
        return $this;
    }
    
    /**
     * Get async attribute
     *
     * @return bool
     */
    public function getAsync(): bool
    {
        $dataAsync = $this->getData('async');
        if ($dataAsync !== null) {
            return filter_var($dataAsync, FILTER_VALIDATE_BOOLEAN);
        }
        
        return $this->async;
    }
    
    /**
     * Render script tag
     *
     * @return string
     */
    public function toHtml(): string
    {
        $src = $this->getSrc();
        
        if (empty($src)) {
            return '';
        }
        
        $attributes = [
            'src' => htmlspecialchars($src, ENT_QUOTES, 'UTF-8'),
        ];
        
        if ($this->getDefer()) {
            $attributes['defer'] = 'defer';
        }
        
        if ($this->getAsync()) {
            $attributes['async'] = 'async';
        }
        
        $attributeString = [];
        foreach ($attributes as $key => $value) {
            if ($value === $key) {
                // Boolean attribute (defer, async)
                $attributeString[] = $key;
            } else {
                $attributeString[] = sprintf('%s="%s"', $key, $value);
            }
        }
        
        return sprintf('<script %s></script>', implode(' ', $attributeString));
    }
}
