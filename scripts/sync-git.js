/**
 * Pull latest dev + main from GitHub (no SFTP).
 * Usage: npm run sync:git
 */
const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const ROOT = path.resolve(__dirname, '..');

function run(cmd) {
	return execSync(cmd, { cwd: ROOT, encoding: 'utf8', stdio: 'pipe' }).trim();
}

function runInherit(cmd) {
	execSync(cmd, { cwd: ROOT, stdio: 'inherit', shell: true });
}

function dirtyFiles() {
	const out = run('git status --porcelain');
	return out
		? out
				.split(/\r?\n/)
				.map((line) => line.slice(3).trim())
				.filter(Boolean)
		: [];
}

function main() {
	console.log('Sync local from GitHub (no deploy)...\n');

	const dirty = dirtyFiles();
	if (dirty.length) {
		console.warn('Warning: uncommitted local changes:');
		dirty.forEach((f) => console.warn(`  - ${f}`));
		console.warn('Stash or commit before pull if pull fails.\n');
	}

	runInherit('git fetch origin');

	runInherit('git checkout dev');
	runInherit('git pull origin dev');

	const lockBefore = fs.existsSync(path.join(ROOT, 'package-lock.json'))
		? fs.readFileSync(path.join(ROOT, 'package-lock.json'), 'utf8')
		: '';

	runInherit('git checkout main');
	runInherit('git pull origin main');

	runInherit('git checkout dev');

	const devHash = run('git rev-parse --short dev');
	const mainHash = run('git rev-parse --short main');
	const originDev = run('git rev-parse --short origin/dev');
	const originMain = run('git rev-parse --short origin/main');

	console.log('\n---');
	console.log(`dev:  ${devHash} (origin/dev: ${originDev})`);
	console.log(`main: ${mainHash} (origin/main: ${originMain})`);

	if (devHash !== originDev || mainHash !== originMain) {
		console.warn('\nBranches may still diverge — check: git status');
	} else {
		console.log('\nLocal matches origin/dev and origin/main.');
	}

	const lockAfter = fs.existsSync(path.join(ROOT, 'package-lock.json'))
		? fs.readFileSync(path.join(ROOT, 'package-lock.json'), 'utf8')
		: '';

	if (lockBefore !== lockAfter) {
		console.log('\npackage-lock.json changed — run: npm ci');
	}

	console.log('\nServer (dev.akademiata.pl) is NOT updated by sync. Use /deploy-dev when you need SFTP.');
}

main();
