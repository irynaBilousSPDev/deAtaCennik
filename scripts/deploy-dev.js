/**
 * Deploy theme via SFTP (reads deploy.local.env).
 * Usage: npm run deploy:dev | npm run deploy:prod
 * Target: node scripts/deploy-dev.js [dev|prod]
 */
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
	return env;
}

function envFlag(env, key) {
	return String(env[key] || '').toLowerCase() === 'true';
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

async function remoteNeedsUpload(sftp, remoteFile, localMeta) {
	try {
		const stat = await sftp.stat(remoteFile);
		if (stat.size !== localMeta.size) {
			return true;
		}
		const remoteMtime = (stat.modifyTime || stat.mtime || 0) * 1000;
		return localMeta.mtime > remoteMtime + 1000;
	} catch (err) {
		if (err.code === 2) {
			return true;
		}
		throw err;
	}
}

async function main() {
	const env = loadEnv(ENV_FILE);
	const dryRun = envFlag(env, 'DRY_RUN');
	const skipBuild = envFlag(env, 'SKIP_BUILD');
	const { config, remotePath } = buildSftpConfig(env, TARGET);

	console.log(`Deploy target: ${TARGET} → ${config.host}`);

	if (!skipBuild) {
		console.log('Running npm run build...');
		execSync('npm run build', { cwd: ROOT, stdio: 'inherit', shell: true });
	} else {
		console.log('SKIP_BUILD=true — skipping assets build.');
	}

	const files = [];
	walkFiles(ROOT, ROOT, files);
	console.log(`Theme files to consider: ${files.length}`);
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
