/**
 * Deploy theme via SFTP (reads deploy.local.env).
 * Usage: npm run deploy:dev | npm run deploy:prod
 * Target: node scripts/deploy-dev.js [dev|prod]
 */
const crypto = require('crypto');
const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');
const SftpClient = require('ssh2-sftp-client');

const ROOT = path.resolve(__dirname, '..');
const ENV_FILE = path.join(ROOT, 'deploy.local.env');
const TARGET = (process.argv[2] || 'dev').toLowerCase();

if (TARGET !== 'dev' && TARGET !== 'prod') {
	console.error('Usage: node scripts/deploy-dev.js [dev|prod]');
	process.exit(1);
}

const EXCLUDE_DIR_NAMES = new Set([
	'node_modules',
	'.git',
	'.cursor',
	'.sass-cache',
	'.idea',
	'nbproject',
	'scripts',
]);

const EXCLUDE_FILE_NAMES = new Set([
	'deploy.local.env',
	'deploy.local.env.example',
	'_google_prices.json',
	'.DS_Store',
	'Thumbs.db',
]);

const EXCLUDE_FILE_BASENAMES = new Set([
	'package.json',
	'package-lock.json',
	'gulpfile.js',
	'webpack.config.js',
	'prices_generate_json.py',
	'requirements.txt',
]);

/** Build noise — not a source-of-truth change between PCs. */
const DEPLOY_IGNORE_DIRTY = new Set(['assets/dist/js/report.html']);

function loadEnv(filePath) {
	if (!fs.existsSync(filePath)) {
		throw new Error(
			`Missing ${path.basename(filePath)}. Copy deploy.local.env.example and fill in SFTP_* values.`
		);
	}
	const env = {};
	for (const line of fs.readFileSync(filePath, 'utf8').split(/\r?\n/)) {
		const trimmed = line.trim();
		if (!trimmed || trimmed.startsWith('#')) {
			continue;
		}
		const eq = trimmed.indexOf('=');
		if (eq === -1) {
			continue;
		}
		const key = trimmed.slice(0, eq).trim();
		let value = trimmed.slice(eq + 1).trim();
		if (
			(value.startsWith('"') && value.endsWith('"')) ||
			(value.startsWith("'") && value.endsWith("'"))
		) {
			value = value.slice(1, -1);
		}
		env[key] = value;
	}
	// One-off: DEPLOY_GIT_REF=55b8af8 npm run deploy:prod (cmd) or $env:DEPLOY_GIT_REF='55b8af8' (PowerShell)
	[
		'DEPLOY_GIT_REF',
		'DEPLOY_FULL',
		'DEPLOY_GIT_ONLY',
		'DEPLOY_ALLOW_DIRTY',
		'DEPLOY_ALLOW_UNPUSHED',
		'SKIP_BUILD',
		'DRY_RUN',
	].forEach((key) => {
		if (process.env[key] !== undefined) {
			env[key] = process.env[key];
		}
	});
	return env;
}

function envFlag(env, key) {
	return String(env[key] || '').toLowerCase() === 'true';
}

/** Default: upload only git-changed files (not every local≠remote diff). Set DEPLOY_FULL=true for full theme sync. */
function deployGitOnlyEnabled(env) {
	if (envFlag(env, 'DEPLOY_FULL')) {
		return false;
	}
	if (env.DEPLOY_GIT_ONLY !== undefined) {
		return envFlag(env, 'DEPLOY_GIT_ONLY');
	}
	return true;
}

function resolveGitOnlyBaseRef(env, target) {
	if (env.DEPLOY_GIT_REF) {
		return env.DEPLOY_GIT_REF.trim();
	}
	return target === 'prod' ? 'origin/main' : 'origin/dev';
}

function gitRefExists(ref) {
	try {
		execSync(`git rev-parse --verify ${ref}`, { cwd: ROOT, stdio: 'pipe' });
		return true;
	} catch (err) {
		return false;
	}
}

/**
 * Files to upload in git-only mode: commits ahead of base ref, or last commit if already pushed.
 */
function getGitDeployRelativePaths(env, target) {
	let files = [];

	if (gitRefExists(resolveGitOnlyBaseRef(env, target))) {
		const baseRef = resolveGitOnlyBaseRef(env, target);
		try {
			const ahead = execSync(`git rev-list --count ${baseRef}..HEAD`, {
				cwd: ROOT,
				encoding: 'utf8',
			}).trim();
			if (parseInt(ahead, 10) > 0) {
				files = execSync(`git diff --name-only ${baseRef}..HEAD`, {
					cwd: ROOT,
					encoding: 'utf8',
				})
					.split(/\r?\n/)
					.filter(Boolean);
			}
		} catch (err) {
			// fall through to last commit
		}
	}

	if (!files.length) {
		const remote = target === 'prod' ? 'origin/main' : 'origin/dev';
		try {
			const reflogRef = `${remote}@{1}`;
			if (gitRefExists(reflogRef)) {
				const prev = execSync(`git rev-parse ${reflogRef}`, {
					cwd: ROOT,
					encoding: 'utf8',
				}).trim();
				const head = execSync('git rev-parse HEAD', { cwd: ROOT, encoding: 'utf8' }).trim();
				if (prev && prev !== head) {
					files = execSync(`git diff --name-only ${prev}..HEAD`, {
						cwd: ROOT,
						encoding: 'utf8',
					})
						.split(/\r?\n/)
						.filter(Boolean);
				}
			}
		} catch (err) {
			// fall through to last commit
		}
	}

	if (!files.length) {
		files = execSync('git diff-tree --no-commit-id --name-only -r HEAD', {
			cwd: ROOT,
			encoding: 'utf8',
		})
			.split(/\r?\n/)
			.filter(Boolean);
	}

	return [...new Set(files.filter((f) => !shouldSkip(f)))];
}

function cfg(env, target, key) {
	if (target === 'prod') {
		return env[`SFTP_PROD_${key}`];
	}
	return env[`SFTP_${key}`];
}

function shouldSkip(relativePosix) {
	const parts = relativePosix.split('/');
	for (const part of parts) {
		if (EXCLUDE_DIR_NAMES.has(part)) {
			return true;
		}
	}
	// Large theme videos — upload once via SFTP, skip on routine deploy
	if (parts[0] === 'static' && parts[1] === 'video') {
		return true;
	}
	const base = parts[parts.length - 1];
	if (EXCLUDE_FILE_NAMES.has(base) || EXCLUDE_FILE_BASENAMES.has(base)) {
		return true;
	}
	return false;
}

function walkFiles(dir, baseDir, list) {
	for (const name of fs.readdirSync(dir)) {
		const full = path.join(dir, name);
		const rel = path.relative(baseDir, full).split(path.sep).join('/');
		if (shouldSkip(rel)) {
			continue;
		}
		const stat = fs.statSync(full);
		if (stat.isDirectory()) {
			walkFiles(full, baseDir, list);
		} else {
			list.push({ local: full, relative: rel, mtime: stat.mtimeMs, size: stat.size });
		}
	}
}

function buildSftpConfig(env, target) {
	const host = cfg(env, target, 'HOST');
	const port = parseInt(cfg(env, target, 'PORT') || '22', 10);
	const username = cfg(env, target, 'USER');
	const remotePath = cfg(env, target, 'REMOTE_PATH');
	const prefix = target === 'prod' ? 'SFTP_PROD_' : 'SFTP_';

	if (!host || !username || !remotePath) {
		throw new Error(
			`${prefix}HOST, ${prefix}USER, and ${prefix}REMOTE_PATH are required in deploy.local.env`
		);
	}

	if (target === 'prod' && !cfg(env, 'prod', 'PASSWORD') && !cfg(env, 'prod', 'PRIVATE_KEY')) {
		throw new Error('Set SFTP_PROD_PASSWORD or SFTP_PROD_PRIVATE_KEY in deploy.local.env');
	}

	const config = { host, port, username, readyTimeout: 30000 };
	const usePasswordOnly = envFlag(env, target === 'prod' ? 'SFTP_PROD_USE_PASSWORD' : 'SFTP_USE_PASSWORD')
		|| envFlag(env, 'SFTP_USE_PASSWORD');
	const privateKey = cfg(env, target, 'PRIVATE_KEY');
	const password = cfg(env, target, 'PASSWORD');
	const passphrase = cfg(env, target, 'PASSPHRASE');

	if (privateKey && !usePasswordOnly) {
		const keyPath = path.resolve(
			privateKey.replace(/^~/, process.env.USERPROFILE || process.env.HOME || '')
		);
		if (fs.existsSync(keyPath)) {
			config.privateKey = fs.readFileSync(keyPath, 'utf8');
			if (passphrase) {
				config.passphrase = passphrase;
			}
		} else if (!password) {
			throw new Error(`SFTP private key not found: ${keyPath}`);
		}
	}

	if (password) {
		config.password = password;
	}

	if (!config.privateKey && !config.password) {
		throw new Error(`Set ${prefix}PASSWORD or ${prefix}PRIVATE_KEY in deploy.local.env`);
	}

	return { config, remotePath: remotePath.replace(/\\/g, '/').replace(/\/+$/, '') };
}

async function ensureRemoteDir(sftp, remoteDir) {
	const normalized = remoteDir.replace(/\\/g, '/').replace(/\/+$/, '');
	if (!normalized) {
		return;
	}
	try {
		await sftp.mkdir(normalized, true);
	} catch (err) {
		if (err.code !== 4) {
			throw err;
		}
	}
}

function localFileHash(filePath) {
	return crypto.createHash('md5').update(fs.readFileSync(filePath)).digest('hex');
}

async function remoteNeedsUpload(sftp, remoteFile, localMeta) {
	try {
		const stat = await sftp.stat(remoteFile);
		if (stat.size !== localMeta.size) {
			return true;
		}
		const remoteMtime = (stat.modifyTime || stat.mtime || 0) * 1000;
		if (localMeta.mtime > remoteMtime + 1000) {
			return true;
		}
		const remoteData = await sftp.get(remoteFile);
		let remoteBuffer;
		if (Buffer.isBuffer(remoteData)) {
			remoteBuffer = remoteData;
		} else if (Array.isArray(remoteData)) {
			remoteBuffer = Buffer.concat(remoteData);
		} else {
			remoteBuffer = Buffer.from(remoteData);
		}
		const remoteHash = crypto.createHash('md5').update(remoteBuffer).digest('hex');
		return localFileHash(localMeta.local) !== remoteHash;
	} catch (err) {
		const msg = String(err.message || '');
		if (err.code === 2 || /no such file/i.test(msg)) {
			return true;
		}
		throw err;
	}
}

function listDirtyTrackedFiles() {
	const out = execSync('git status --porcelain', { cwd: ROOT, encoding: 'utf8' });
	const files = [];
	for (const line of out.split(/\r?\n/)) {
		if (!line.trim()) {
			continue;
		}
		const file = line.slice(3).trim().replace(/\\/g, '/');
		if (DEPLOY_IGNORE_DIRTY.has(file)) {
			continue;
		}
		// Local build may rewrite source maps without a real code change.
		if (file.startsWith('assets/dist/') && file.endsWith('.map')) {
			continue;
		}
		files.push(file);
	}
	return files;
}

/** Git must be committed and pushed before SFTP (source of truth for home ↔ work). */
function assertGitReadyForDeploy(env, target) {
	if (envFlag(env, 'DEPLOY_ALLOW_DIRTY')) {
		return;
	}

	const dirty = listDirtyTrackedFiles();
	if (dirty.length) {
		throw new Error(
			`Deploy blocked: uncommitted changes (${dirty.join(', ')}). Commit first — git must match what you deploy.`
		);
	}

	try {
		execSync('git fetch origin', { cwd: ROOT, stdio: 'pipe' });
	} catch (err) {
		// offline deploy still possible
	}

	const branch = target === 'prod' ? 'main' : 'dev';
	const remote = `origin/${branch}`;
	if (!gitRefExists(remote)) {
		return;
	}

	const behind = execSync(`git rev-list --count HEAD..${remote}`, {
		cwd: ROOT,
		encoding: 'utf8',
	}).trim();
	if (parseInt(behind, 10) > 0) {
		throw new Error(
			`Deploy blocked: local ${branch} is behind ${remote}. Run: npm run sync:git`
		);
	}

	if (envFlag(env, 'DEPLOY_ALLOW_UNPUSHED')) {
		return;
	}

	const ahead = execSync(`git rev-list --count ${remote}..HEAD`, {
		cwd: ROOT,
		encoding: 'utf8',
	}).trim();
	if (parseInt(ahead, 10) > 0) {
		throw new Error(
			`Deploy blocked: push first (git push origin ${branch}) — ${ahead} commit(s) not on GitHub yet.`
		);
	}
}

async function main() {
	const env = loadEnv(ENV_FILE);
	const dryRun = envFlag(env, 'DRY_RUN');
	const skipBuild = envFlag(env, 'SKIP_BUILD');
	const { config, remotePath } = buildSftpConfig(env, TARGET);

	console.log(`Deploy target: ${TARGET} → ${config.host}`);
	assertGitReadyForDeploy(env, TARGET);
	console.log('Git check OK (committed, pushed, up to date with origin).');

	if (!skipBuild) {
		console.log('Running npm run build...');
		execSync('npm run build', { cwd: ROOT, stdio: 'inherit', shell: true });
	} else {
		console.log('SKIP_BUILD=true — skipping assets build.');
	}

	const gitOnly = deployGitOnlyEnabled(env);
	let files = [];
	walkFiles(ROOT, ROOT, files);

	if (gitOnly) {
		const gitPaths = new Set(getGitDeployRelativePaths(env, TARGET));
		const before = files.length;
		files = files.filter((file) => gitPaths.has(file.relative));
		console.log(
			`DEPLOY_GIT_ONLY: ${files.length} file(s) from git (${before} in theme; set DEPLOY_FULL=true to compare all files with server)`
		);
		if (!files.length) {
			console.log('No deployable files in git diff — nothing to upload.');
		}
	} else {
		console.log(`DEPLOY_FULL: comparing all ${files.length} theme file(s) with server`);
	}
	if (dryRun) {
		console.log('DRY_RUN=true — no upload.');
	}

	const sftp = new SftpClient();
	let uploaded = 0;
	let skipped = 0;

	try {
		console.log(`Connecting to ${config.host}:${config.port} as ${config.username}...`);
		if (!dryRun) {
			await sftp.connect(config);
			await ensureRemoteDir(sftp, remotePath);
		}

		for (const file of files) {
			const remoteFile = `${remotePath}/${file.relative}`.replace(/\/+/g, '/');
			if (dryRun) {
				console.log(`[dry-run] ${file.relative}`);
				continue;
			}

			const remoteDir = path.posix.dirname(remoteFile);
			await ensureRemoteDir(sftp, remoteDir);

			if (await remoteNeedsUpload(sftp, remoteFile, file)) {
				process.stdout.write(`↑ ${file.relative}\n`);
				await sftp.fastPut(file.local, remoteFile);
				uploaded += 1;
			} else {
				skipped += 1;
			}
		}
	} finally {
		if (!dryRun) {
			await sftp.end();
		}
	}

	console.log(`Done. Uploaded: ${uploaded}, unchanged: ${skipped}${dryRun ? ' (dry run)' : ''}`);
	console.log(`Remote: ${remotePath}`);
}

main().catch((err) => {
	console.error(err.message || err);
	process.exit(1);
});
