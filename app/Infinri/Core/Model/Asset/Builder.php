<?php

declare(strict_types=1);

namespace Infinri\Core\Model\Asset;

/**
 * Asset Builder
 * 
 * Compiles LESS to CSS and minifies CSS/JS assets using Node.js tools
 * Requires: less, clean-css-cli, terser (installed via npm)
 */
class Builder
{
    /**
     * Path to Node.js binaries
     *
     * @var string
     */
    private string $nodeBinPath;

    /**
     * Base application path
     *
     * @var string
     */
    private string $basePath;

    /**
     * Enable source maps
     *
     * @var bool
     */
    private bool $generateSourceMaps;

    /**
     * Constructor
     *
     * @param string|null $basePath Application base path
     * @param string|null $nodeBinPath Path to node_modules/.bin
     * @param bool $generateSourceMaps Enable source maps
     */
    public function __construct(
        ?string $basePath = null,
        ?string $nodeBinPath = null,
        bool $generateSourceMaps = false
    ) {
        $this->basePath = $basePath ?? dirname(__DIR__, 5);
        $this->nodeBinPath = $nodeBinPath ?? $this->basePath . '/node_modules/.bin';
        $this->generateSourceMaps = $generateSourceMaps;
    }

    /**
     * Compile LESS file to CSS
     *
     * @param string $sourcePath Path to LESS file
     * @param string $outputPath Path to output CSS file
     * @return bool True on success
     * @throws \RuntimeException If compilation fails
     */
    public function compileLess(string $sourcePath, string $outputPath): bool
    {
        if (!file_exists($sourcePath)) {
            throw new \RuntimeException("Source file not found: {$sourcePath}");
        }

        $lessc = $this->nodeBinPath . '/lessc';

        if (!file_exists($lessc)) {
            throw new \RuntimeException("LESS compiler not found. Run: npm install");
        }

        $command = escapeshellcmd($lessc) . ' ' . escapeshellarg($sourcePath) . ' ' . escapeshellarg($outputPath);

        if ($this->generateSourceMaps) {
            $command .= ' --source-map';
        }

        $output = [];
        $returnCode = 0;
        exec($command . ' 2>&1', $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \RuntimeException("LESS compilation failed: " . implode("\n", $output));
        }

        return true;
    }

    /**
     * Minify CSS file
     *
     * @param string $sourcePath Path to CSS file
     * @param string $outputPath Path to output minified CSS
     * @return bool True on success
     * @throws \RuntimeException If minification fails
     */
    public function minifyCss(string $sourcePath, string $outputPath): bool
    {
        if (!file_exists($sourcePath)) {
            throw new \RuntimeException("Source file not found: {$sourcePath}");
        }

        $cleancss = $this->nodeBinPath . '/cleancss';

        if (!file_exists($cleancss)) {
            throw new \RuntimeException("CleanCSS not found. Run: npm install");
        }

        $command = escapeshellcmd($cleancss) . ' -o ' . escapeshellarg($outputPath) . ' ' . escapeshellarg($sourcePath);

        if ($this->generateSourceMaps) {
            $command .= ' --source-map';
        }

        $output = [];
        $returnCode = 0;
        exec($command . ' 2>&1', $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \RuntimeException("CSS minification failed: " . implode("\n", $output));
        }

        return true;
    }

    /**
     * Minify JavaScript file
     *
     * @param string $sourcePath Path to JS file
     * @param string $outputPath Path to output minified JS
     * @return bool True on success
     * @throws \RuntimeException If minification fails
     */
    public function minifyJs(string $sourcePath, string $outputPath): bool
    {
        if (!file_exists($sourcePath)) {
            throw new \RuntimeException("Source file not found: {$sourcePath}");
        }

        $terser = $this->nodeBinPath . '/terser';

        if (!file_exists($terser)) {
            throw new \RuntimeException("Terser not found. Run: npm install");
        }

        $command = escapeshellcmd($terser) . ' ' . escapeshellarg($sourcePath) . ' -o ' . escapeshellarg($outputPath);

        if ($this->generateSourceMaps) {
            $command .= ' --source-map';
        }

        $output = [];
        $returnCode = 0;
        exec($command . ' 2>&1', $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \RuntimeException("JavaScript minification failed: " . implode("\n", $output));
        }

        return true;
    }

    /**
     * Compile and minify LESS file in one step
     *
     * @param string $sourcePath Path to LESS file
     * @param string $outputPath Path to output minified CSS
     * @return bool True on success
     */
    public function buildCss(string $sourcePath, string $outputPath): bool
    {
        $tempCss = sys_get_temp_dir() . '/' . uniqid('infinri_css_') . '.css';

        try {
            // Compile LESS to CSS
            $this->compileLess($sourcePath, $tempCss);

            // Minify CSS
            $this->minifyCss($tempCss, $outputPath);

            return true;
        } finally {
            // Clean up temp file
            if (file_exists($tempCss)) {
                unlink($tempCss);
            }
        }
    }

    /**
     * Check if Node.js tools are installed
     *
     * @return array Status of each tool
     */
    public function checkTools(): array
    {
        return [
            'lessc' => file_exists($this->nodeBinPath . '/lessc'),
            'cleancss' => file_exists($this->nodeBinPath . '/cleancss'),
            'terser' => file_exists($this->nodeBinPath . '/terser'),
        ];
    }

    /**
     * Enable or disable source map generation
     *
     * @param bool $enable Enable source maps
     * @return void
     */
    public function setGenerateSourceMaps(bool $enable): void
    {
        $this->generateSourceMaps = $enable;
    }

    /**
     * Get Node.js bin path
     *
     * @return string Path to node_modules/.bin
     */
    public function getNodeBinPath(): string
    {
        return $this->nodeBinPath;
    }
}
