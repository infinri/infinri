#!/usr/bin/env node
/**
 * Dynamic Build Script
 * 
 * Automatically discovers and compiles CSS/JS from all modules
 * No need to hardcode module names in package.json
 */

const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

const APP_DIR = path.join(__dirname, 'app/Infinri');
const PUB_STATIC = path.join(__dirname, 'pub/static');

/**
 * Find all modules in app/Infinri
 */
function findModules() {
    if (!fs.existsSync(APP_DIR)) {
        console.error('❌ app/Infinri directory not found');
        return [];
    }
    
    return fs.readdirSync(APP_DIR, { withFileTypes: true })
        .filter(dirent => dirent.isDirectory())
        .map(dirent => dirent.name);
}

/**
 * Compile CSS from a module
 */
function compileCss(moduleName) {
    const lessFile = path.join(APP_DIR, moduleName, 'view/frontend/web/css/styles.less');
    
    if (!fs.existsSync(lessFile)) {
        return false;
    }
    
    const outputDir = path.join(PUB_STATIC, 'Infinri', moduleName, 'css');
    fs.mkdirSync(outputDir, { recursive: true });
    
    const outputFile = path.join(outputDir, `${moduleName.toLowerCase()}.css`);
    
    console.log(`📦 Compiling CSS: ${moduleName}`);
    execSync(`npx lessc "${lessFile}" "${outputFile}"`, { stdio: 'inherit' });
    
    return outputFile;
}

/**
 * Compile JS from a module
 */
function compileJs(moduleName) {
    const jsDir = path.join(APP_DIR, moduleName, 'view/frontend/web/js');
    const baseJsDir = path.join(APP_DIR, moduleName, 'view/base/web/js');
    
    const jsFiles = [];
    
    // Add base JS files
    if (fs.existsSync(baseJsDir)) {
        jsFiles.push(...fs.readdirSync(baseJsDir)
            .filter(f => f.endsWith('.js'))
            .map(f => path.join(baseJsDir, f)));
    }
    
    // Add frontend JS files
    if (fs.existsSync(jsDir)) {
        jsFiles.push(...fs.readdirSync(jsDir)
            .filter(f => f.endsWith('.js'))
            .map(f => path.join(jsDir, f)));
    }
    
    if (jsFiles.length === 0) {
        return false;
    }
    
    const outputDir = path.join(PUB_STATIC, 'Infinri', moduleName, 'js');
    fs.mkdirSync(outputDir, { recursive: true });
    
    const outputFile = path.join(outputDir, `${moduleName.toLowerCase()}.js`);
    
    console.log(`📦 Compiling JS: ${moduleName} (${jsFiles.length} files)`);
    
    // Concatenate all JS files
    const content = jsFiles.map(file => fs.readFileSync(file, 'utf8')).join('\n\n');
    fs.writeFileSync(outputFile, content);
    
    return outputFile;
}

/**
 * Merge all CSS files
 */
function mergeCss(cssFiles) {
    console.log('🔗 Merging CSS from all modules...');
    
    const outputDir = path.join(PUB_STATIC, 'frontend/css');
    fs.mkdirSync(outputDir, { recursive: true });
    
    const merged = path.join(outputDir, 'styles.css');
    const content = cssFiles.map(file => fs.readFileSync(file, 'utf8')).join('\n\n');
    fs.writeFileSync(merged, content);
    
    return merged;
}

/**
 * Merge all JS files
 */
function mergeJs(jsFiles) {
    console.log('🔗 Merging JS from all modules...');
    
    const outputDir = path.join(PUB_STATIC, 'frontend/js');
    fs.mkdirSync(outputDir, { recursive: true });
    
    const merged = path.join(outputDir, 'scripts.js');
    const content = jsFiles.map(file => fs.readFileSync(file, 'utf8')).join('\n\n');
    fs.writeFileSync(merged, content);
    
    return merged;
}

/**
 * Minify CSS
 */
function minifyCss(cssFile) {
    console.log('🗜️  Minifying CSS...');
    const output = cssFile.replace('.css', '.min.css');
    execSync(`npx cleancss -o "${output}" "${cssFile}"`, { stdio: 'inherit' });
    return output;
}

/**
 * Minify JS
 */
function minifyJs(jsFile) {
    console.log('🗜️  Minifying JS...');
    const output = jsFile.replace('.js', '.min.js');
    execSync(`npx terser "${jsFile}" -o "${output}" --compress --mangle`, { stdio: 'inherit' });
    return output;
}

/**
 * Main build process
 */
function build() {
    console.log('🚀 Starting dynamic build...\n');
    
    const modules = findModules();
    console.log(`📋 Found ${modules.length} modules: ${modules.join(', ')}\n`);
    
    const cssFiles = [];
    const jsFiles = [];
    
    // Compile each module
    for (const module of modules) {
        const css = compileCss(module);
        if (css) cssFiles.push(css);
        
        const js = compileJs(module);
        if (js) jsFiles.push(js);
    }
    
    console.log('');
    
    // Merge and minify
    if (cssFiles.length > 0) {
        const mergedCss = mergeCss(cssFiles);
        minifyCss(mergedCss);
    }
    
    if (jsFiles.length > 0) {
        const mergedJs = mergeJs(jsFiles);
        minifyJs(mergedJs);
    }
    
    console.log('\n✅ Build complete!');
}

// Run build
build();
