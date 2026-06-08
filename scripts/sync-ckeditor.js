/**
 * Copy free CKEditor 4.22.1 from node_modules → assets/ckeditor
 * Usage: npm run sync-ckeditor
 */
const fs = require('fs');
const path = require('path');

const src = path.join(__dirname, '..', 'node_modules', 'ckeditor4');
const dest = path.join(__dirname, '..', 'assets', 'ckeditor');

function copyDir(from, to) {
    fs.mkdirSync(to, { recursive: true });
    for (const entry of fs.readdirSync(from, { withFileTypes: true })) {
        if (entry.name === 'samples') {
            continue;
        }
        const s = path.join(from, entry.name);
        const d = path.join(to, entry.name);
        if (entry.isDirectory()) {
            copyDir(s, d);
        } else {
            fs.copyFileSync(s, d);
        }
    }
}

if (!fs.existsSync(src)) {
    console.error('Run first: npm install');
    process.exit(1);
}

if (fs.existsSync(dest)) {
    fs.rmSync(dest, { recursive: true, force: true });
}
copyDir(src, dest);

const pkg = JSON.parse(fs.readFileSync(path.join(dest, 'package.json'), 'utf8'));
console.log('Synced CKEditor', pkg.version, '→ assets/ckeditor/');
