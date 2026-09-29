import { readFile, readdir, rename, writeFile } from 'node:fs/promises';
import path from 'node:path';
import { Formatter } from 'blade-formatter';

const root = path.resolve(import.meta.dirname, '..');
const directory = path.join(root, 'resources/views');
const options = JSON.parse(await readFile(path.join(root, '.bladeformatterrc.json'), 'utf8'));
const write = process.argv.includes('--write');
const files = (await readdir(directory, { recursive: true })).filter((file) => file.endsWith('.blade.php')).sort();
const changes = [];

for (const file of files) {
    const filename = path.join(directory, file);
    const original = await readFile(filename, 'utf8');
    const formatted = await new Formatter(options).formatContent(original);

    if (original !== formatted) {
        changes.push({ filename, formatted });
        console.log(path.relative(root, filename));
    }
}

if (write) {
    for (const { filename, formatted } of changes) {
        // Write beside the source first so a failed write cannot truncate the template.
        const temporary = `${filename}.format-tmp`;
        await writeFile(temporary, formatted, 'utf8');
        await rename(temporary, filename);
    }
} else if (changes.length) {
    process.exitCode = 1;
}

console.log(`${files.length} templates checked; ${changes.length} ${write ? 'formatted' : 'need formatting'}.`);
