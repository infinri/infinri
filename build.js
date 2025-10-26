#!/usr/bin/env node
/**
 * Dynamic Build Script
 * 
 * Automatically discovers and compiles CSS/JS from all modules
 * Supports multiple areas: base, frontend, adminhtml
 */

const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

const APP_DIR = path.join(__dirname, 'app/Infinri');
const PUB_STATIC = path.join(__dirname, 'pub/static');
const NODE_MODULES = path.join(__dirname, 'node_modules');

// Direct paths to binaries (fixes npx issues on Windows)
const LESSC = path.join(NODE_MODULES, 'less/bin/lessc');
const CLEANCSS = path.join(NODE_MODULES, 'clean-css-cli/bin/cleancss');
const TERSER = path.join(NODE_MODULES, 'terser/bin/terser');

// Areas to compile
const AREAS = ['base', 'frontend', 'adminhtml'];

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
 * Compile CSS from a module for a specific area
 */
function compileCss(moduleName, area) {
    const lessFile = path.join(APP_DIR, moduleName, `view/${area}/web/css/styles.less`);
    
    if (!fs.existsSync(lessFile)) {
        return null;
    }
    
    const outputDir = path.join(PUB_STATIC, 'Infinri', moduleName, area, 'css');
    fs.mkdirSync(outputDir, { recursive: true });
    
    const outputFile = path.join(outputDir, `${moduleName.toLowerCase()}.css`);
    
    console.log(`📦 Compiling CSS: ${moduleName} (${area})`);
    
    try {
        execSync(`node "${LESSC}" "${lessFile}" "${outputFile}"`, { stdio: 'inherit' });
        return { file: outputFile, area };
    } catch (error) {
        console.error(`❌ Failed to compile ${moduleName} (${area})`);
        return null;
    }
}

/**
 * Compile JS from a module for a specific area
 */
function compileJs(moduleName, area) {
    const jsDir = path.join(APP_DIR, moduleName, `view/${area}/web/js`);
    const baseJsDir = path.join(APP_DIR, moduleName, 'view/base/web/js');
    
    const jsFiles = [];
    
    // Add base JS files (only for non-base areas)
    if (area !== 'base' && fs.existsSync(baseJsDir)) {
        jsFiles.push(...fs.readdirSync(baseJsDir)
            .filter(f => f.endsWith('.js'))
            .map(f => path.join(baseJsDir, f)));
    }
    
    // Add area-specific JS files
    if (fs.existsSync(jsDir)) {
        jsFiles.push(...fs.readdirSync(jsDir)
            .filter(f => f.endsWith('.js'))
            .map(f => path.join(jsDir, f)));
    }
    
    if (jsFiles.length === 0) {
        return null;
    }
    
    const outputDir = path.join(PUB_STATIC, 'Infinri', moduleName, area, 'js');
    fs.mkdirSync(outputDir, { recursive: true });
    
    const outputFile = path.join(outputDir, `${moduleName.toLowerCase()}.js`);
    
    console.log(`📦 Compiling JS: ${moduleName} (${area}) - ${jsFiles.length} files`);
    
    // Concatenate all JS files
    const content = jsFiles.map(file => fs.readFileSync(file, 'utf8')).join('\n\n');
    fs.writeFileSync(outputFile, content);
    
    return { file: outputFile, area };
}

/**
 * Merge CSS files by area
 */
function mergeCss(cssFiles, area) {
    console.log(`🔗 Merging CSS from all modules (${area})...`);
    
    const outputDir = path.join(PUB_STATIC, area, 'css');
    fs.mkdirSync(outputDir, { recursive: true });
    
    const merged = path.join(outputDir, 'styles.css');
    const content = cssFiles.map(item => fs.readFileSync(item.file, 'utf8')).join('\n\n');
    fs.writeFileSync(merged, content);
    
    return merged;
}

/**
 * Merge JS files by area
 */
function mergeJs(jsFiles, area) {
    console.log(`🔗 Merging JS from all modules (${area})...`);
    
    const outputDir = path.join(PUB_STATIC, area, 'js');
    fs.mkdirSync(outputDir, { recursive: true });
    
    const merged = path.join(outputDir, 'scripts.js');
    const content = jsFiles.map(item => fs.readFileSync(item.file, 'utf8')).join('\n\n');
    fs.writeFileSync(merged, content);
    
    return merged;
}

/**
 * Minify CSS
 */
function minifyCss(cssFile) {
    console.log('🗜️  Minifying CSS...');
    const output = cssFile.replace('.css', '.min.css');
    try {
        execSync(`node "${CLEANCSS}" -o "${output}" "${cssFile}"`, { stdio: 'inherit' });
        return output;
    } catch (error) {
        console.error('❌ Failed to minify CSS');
        return null;
    }
}

/**
 * Minify JS
 */
function minifyJs(jsFile) {
    console.log('🗜️  Minifying JS...');
    const output = jsFile.replace('.js', '.min.js');
    try {
        execSync(`node "${TERSER}" "${jsFile}" -o "${output}" --compress --mangle`, { stdio: 'inherit' });
        return output;
    } catch (error) {
        console.error('❌ Failed to minify JS');
        return null;
    }
}

/**
 * Main build process
 */
function build() {
    console.log('🚀 Starting dynamic build...\n');
    
    const modules = findModules();
    console.log(`📋 Found ${modules.length} modules: ${modules.join(', ')}\n`);
    
    // Compile for each area
    for (const area of AREAS) {
        console.log(`\n📍 Processing area: ${area}`);
        console.log('─'.repeat(50));
        
        const cssFiles = [];
        const jsFiles = [];
        
        // Compile each module for this area
        for (const module of modules) {
            const css = compileCss(module, area);
            if (css) cssFiles.push(css);
            
            const js = compileJs(module, area);
            if (js) jsFiles.push(js);
        }
        
        // Merge and minify for this area
        if (cssFiles.length > 0) {
            console.log('');
            const mergedCss = mergeCss(cssFiles, area);
            minifyCss(mergedCss);
        } else {
            console.log(`⚠️  No CSS files found for ${area}`);
        }
        
        if (jsFiles.length > 0) {
            console.log('');
            const mergedJs = mergeJs(jsFiles, area);
            minifyJs(mergedJs);
        } else {
            console.log(`⚠️  No JS files found for ${area}`);
        }
    }
    
    console.log('\n✅ Build complete!');
    console.log('\n📦 Output structure:');
    console.log('   pub/static/');
    AREAS.forEach(area => {
        const areaPath = path.join(PUB_STATIC, area);
        if (fs.existsSync(areaPath)) {
            console.log(`   ├── ${area}/`);
            console.log(`   │   ├── css/styles.css & styles.min.css`);
            console.log(`   │   └── js/scripts.js & scripts.min.js`);
        }
    });
}

// Run build
build();
