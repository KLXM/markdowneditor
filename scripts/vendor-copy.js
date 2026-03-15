/**
 * vendor-copy.js
 * Copies EasyMDE dist files from node_modules to assets/vendor/easymde.
 *
 * Usage:  npm run build
 */

const fs = require('fs');
const path = require('path');

const src = path.resolve(__dirname, '..', 'node_modules', 'easymde', 'dist');
const dest = path.resolve(__dirname, '..', 'assets', 'vendor', 'easymde');

// Ensure destination exists
fs.mkdirSync(dest, { recursive: true });

const files = [
    'easymde.min.js',
    'easymde.min.css',
];

let copied = 0;

for (const file of files) {
    const srcFile = path.join(src, file);
    const destFile = path.join(dest, file);

    if (!fs.existsSync(srcFile)) {
        console.error(`  ✗  ${file} not found in ${src}`);
        continue;
    }

    fs.copyFileSync(srcFile, destFile);
    const size = (fs.statSync(destFile).size / 1024).toFixed(1);
    console.log(`  ✓  ${file} (${size} KB)`);
    copied++;
}

console.log(`\n  ${copied}/${files.length} files copied to assets/vendor/easymde/`);
