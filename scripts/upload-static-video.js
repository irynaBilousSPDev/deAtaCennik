/**
 * Upload gitignored static/video/*.mp4 (one-time or after new video).
 * Usage: node scripts/upload-static-video.js [dev|prod]
 */
const fs = require('fs');
const path = require('path');
const SftpClient = require('ssh2-sftp-client');

const ROOT = path.resolve(__dirname, '..');
const ENV_FILE = path.join(ROOT, 'deploy.local.env');
const TARGET = (process.argv[2] || 'dev').toLowerCase();
const VIDEO_DIR = path.join(ROOT, 'static', 'video');
const LEGACY_REMOTE_VIDEOS = ['ATAMISTRZEMSWIATA1.mp4'];

function loadEnv(filePath) {
	if (!fs.existsSync(filePath)) {
		throw new Error(`Missing ${path.basename(filePath)}. Copy deploy.local.env.example and fill SFTP values.`);
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

function cfg(env, target, key) {
	const prefix = target === 'prod' ? 'SFTP_PROD_' : 'SFTP_';
	return env[`${prefix}${key}`] || env[key];
}

function buildSftpConfig(env, target) {
	const host = cfg(env, target, 'HOST');
	const port = parseInt(cfg(env, target, 'PORT') || '22', 10);
	const username = cfg(env, target, 'USER');
	const remotePath = cfg(env, target, 'REMOTE_PATH');
	const prefix = target === 'prod' ? 'SFTP_PROD_' : 'SFTP_';

	if (!host || !username || !remotePath) {
		throw new Error(`${prefix}HOST, ${prefix}USER, and ${prefix}REMOTE_PATH are required.`);
	}

	const config = { host, port, username, readyTimeout: 120000 };
	const password = cfg(env, target, 'PASSWORD');
	const privateKey = cfg(env, target, 'PRIVATE_KEY');
	const passphrase = cfg(env, target, 'PASSPHRASE');

	if (privateKey) {
		const keyPath = path.resolve(privateKey.replace(/^~/, process.env.USERPROFILE || process.env.HOME || ''));
		if (fs.existsSync(keyPath)) {
			config.privateKey = fs.readFileSync(keyPath, 'utf8');
			if (passphrase) {
				config.passphrase = passphrase;
			}
		}
	}
	if (password) {
		config.password = password;
	}
	if (!config.privateKey && !config.password) {
		throw new Error(`Set ${prefix}PASSWORD or ${prefix}PRIVATE_KEY.`);
	}

	return {
		config,
		remotePath: remotePath.replace(/\\/g, '/').replace(/\/+$/, ''),
	};
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

async function main() {
	if (TARGET !== 'dev' && TARGET !== 'prod') {
		throw new Error('Usage: node scripts/upload-static-video.js [dev|prod]');
	}
	if (!fs.existsSync(VIDEO_DIR)) {
		throw new Error(`Missing folder: static/video/`);
	}

	const videos = fs.readdirSync(VIDEO_DIR).filter((name) => /\.(mp4|webm)$/i.test(name));
	if (!videos.length) {
		throw new Error('No .mp4/.webm files in static/video/ — add ATAMISTRZEMSWIATA.mp4 locally.');
	}

	const env = loadEnv(ENV_FILE);
	const { config, remotePath } = buildSftpConfig(env, TARGET);
	const sftp = new SftpClient();

	console.log(`Upload static/video → ${TARGET} (${config.host})`);

	try {
		await sftp.connect(config);
		const remoteVideoDir = `${remotePath}/static/video`.replace(/\/+/g, '/');
		await ensureRemoteDir(sftp, remoteVideoDir);

		for (const name of videos) {
			const local = path.join(VIDEO_DIR, name);
			const remote = `${remoteVideoDir}/${name}`;
			const sizeMb = (fs.statSync(local).size / (1024 * 1024)).toFixed(1);
			process.stdout.write(`↑ static/video/${name} (${sizeMb} MB)\n`);
			await sftp.fastPut(local, remote);
		}

		for (const legacy of LEGACY_REMOTE_VIDEOS) {
			const remote = `${remoteVideoDir}/${legacy}`;
			if (await sftp.exists(remote)) {
				await sftp.delete(remote);
				process.stdout.write(`✕ removed static/video/${legacy}\n`);
			}
		}
	} finally {
		await sftp.end();
	}

	console.log('Done.');
}

main().catch((err) => {
	console.error(err.message || err);
	process.exit(1);
});
