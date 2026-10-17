import { readFile, mkdir, writeFile } from 'node:fs/promises';
import { createSign } from 'node:crypto';
import { dirname, resolve } from 'node:path';

const credentialPath = process.argv[2];
const outputPath = process.argv[3] || 'storage/app/private/legacy-firebase-export.json';

if (!credentialPath) {
    throw new Error('Indica la ruta del archivo de cuenta de servicio.');
}

const credentials = JSON.parse(await readFile(resolve(credentialPath), 'utf8'));
if (credentials.type !== 'service_account' || !credentials.private_key || !credentials.client_email) {
    throw new Error('El archivo no es una cuenta de servicio valida.');
}

const base64Url = (value) => Buffer.from(value)
    .toString('base64')
    .replaceAll('+', '-')
    .replaceAll('/', '_')
    .replace(/=+$/u, '');

const now = Math.floor(Date.now() / 1000);
const header = base64Url(JSON.stringify({ alg: 'RS256', typ: 'JWT' }));
const claim = base64Url(JSON.stringify({
    iss: credentials.client_email,
    scope: 'https://www.googleapis.com/auth/cloud-platform',
    aud: 'https://oauth2.googleapis.com/token',
    iat: now,
    exp: now + 3600,
}));
const unsignedJwt = `${header}.${claim}`;
const signer = createSign('RSA-SHA256');
signer.update(unsignedJwt);
signer.end();
const assertion = `${unsignedJwt}.${base64Url(signer.sign(credentials.private_key))}`;

const tokenResponse = await fetch('https://oauth2.googleapis.com/token', {
    method: 'POST',
    headers: { 'content-type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({
        grant_type: 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        assertion,
    }),
});

if (!tokenResponse.ok) {
    throw new Error(`No se pudo autenticar la cuenta de servicio (${tokenResponse.status}).`);
}

const { access_token: accessToken } = await tokenResponse.json();
const authorizedJson = async (url) => {
    const response = await fetch(url, { headers: { authorization: `Bearer ${accessToken}` } });
    if (!response.ok) {
        const detail = await response.text();
        throw new Error(`Firebase respondio ${response.status} para ${new URL(url).pathname}: ${detail.slice(0, 500)}`);
    }

    return response.json();
};

const decodeFirestoreValue = (value = {}) => {
    if ('nullValue' in value) return null;
    if ('booleanValue' in value) return value.booleanValue;
    if ('integerValue' in value) return Number(value.integerValue);
    if ('doubleValue' in value) return value.doubleValue;
    if ('timestampValue' in value) return value.timestampValue;
    if ('stringValue' in value) return value.stringValue;
    if ('bytesValue' in value) return value.bytesValue;
    if ('referenceValue' in value) return value.referenceValue;
    if ('geoPointValue' in value) return value.geoPointValue;
    if ('arrayValue' in value) return (value.arrayValue.values || []).map(decodeFirestoreValue);
    if ('mapValue' in value) return decodeFirestoreFields(value.mapValue.fields || {});
    return null;
};

const decodeFirestoreFields = (fields) => Object.fromEntries(
    Object.entries(fields || {}).map(([key, value]) => [key, decodeFirestoreValue(value)]),
);

const listPages = async (initialUrl, collectionKey) => {
    const records = [];
    let pageToken = null;

    do {
        const url = new URL(initialUrl);
        if (pageToken) url.searchParams.set('pageToken', pageToken);
        const page = await authorizedJson(url);
        records.push(...(page[collectionKey] || []));
        pageToken = page.nextPageToken || null;
    } while (pageToken);

    return records;
};

const projectId = credentials.project_id;
const authUsers = await listPages(
    `https://identitytoolkit.googleapis.com/v1/projects/${encodeURIComponent(projectId)}/accounts:batchGet?maxResults=1000`,
    'users',
);
const authByUid = new Map(authUsers.map((user) => [user.localId, user]));

const firestoreBase = `https://firestore.googleapis.com/v1/projects/${encodeURIComponent(projectId)}/databases/(default)/documents`;
const userDocuments = await listPages(`${firestoreBase}/users?pageSize=300`, 'documents');
const users = [];
let messageCount = 0;

for (const document of userDocuments) {
    const uid = document.name.split('/').at(-1);
    const authUser = authByUid.get(uid) || {};
    const fields = decodeFirestoreFields(document.fields);
    const messageDocuments = await listPages(
        `${firestoreBase}/users/${encodeURIComponent(uid)}/mensajes?pageSize=300`,
        'documents',
    );
    const messages = messageDocuments.map((messageDocument) => ({
        firebase_id: messageDocument.name.split('/').at(-1),
        created_at: messageDocument.createTime || null,
        updated_at: messageDocument.updateTime || null,
        ...decodeFirestoreFields(messageDocument.fields),
    }));
    messageCount += messages.length;

    users.push({
        firebase_uid: uid,
        email: fields.email || authUser.email || null,
        name: fields.name || authUser.displayName || authUser.email || 'Usuario',
        email_verified: Boolean(authUser.emailVerified),
        disabled: Boolean(authUser.disabled),
        auth_created_at: authUser.createdAt || authUser.createdAtMillis || null,
        last_login_at: authUser.lastLoginAt || authUser.lastLoginAtMillis || null,
        firestore_created_at: fields.createdAt || document.createTime || null,
        role: fields.role || 'user',
        profile_photo: fields.fperfil || fields.photoURL || authUser.photoUrl || null,
        preferences: fields.preferences || null,
        header_color: fields.headerColor || null,
        messages,
    });
}

for (const authUser of authUsers) {
    if (users.some((user) => user.firebase_uid === authUser.localId)) continue;
    users.push({
        firebase_uid: authUser.localId,
        email: authUser.email || null,
        name: authUser.displayName || authUser.email || 'Usuario',
        email_verified: Boolean(authUser.emailVerified),
        disabled: Boolean(authUser.disabled),
        auth_created_at: authUser.createdAt || authUser.createdAtMillis || null,
        last_login_at: authUser.lastLoginAt || authUser.lastLoginAtMillis || null,
        firestore_created_at: null,
        role: 'user',
        profile_photo: authUser.photoUrl || null,
        preferences: null,
        header_color: null,
        messages: [],
    });
}

users.sort((left, right) => String(left.email || left.firebase_uid).localeCompare(String(right.email || right.firebase_uid)));
const exportData = {
    source: 'firebase',
    project_id: projectId,
    exported_at: new Date().toISOString(),
    users,
};

const resolvedOutput = resolve(outputPath);
await mkdir(dirname(resolvedOutput), { recursive: true });
await writeFile(resolvedOutput, `${JSON.stringify(exportData, null, 2)}\n`, { mode: 0o600 });

process.stdout.write(JSON.stringify({
    project: projectId,
    auth_users: authUsers.length,
    firestore_users: userDocuments.length,
    exported_users: users.length,
    messages: messageCount,
    output: resolvedOutput,
}));
