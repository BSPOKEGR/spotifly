// Password is supplied via the environment (mongosh: process.env, legacy shell: _getEnv).
const appPwd = (typeof process !== 'undefined' && process.env.WEATHER_SRV_MONGO_APP_PASSWORD) ||
    (typeof _getEnv === 'function' && _getEnv('WEATHER_SRV_MONGO_APP_PASSWORD'));
if (!appPwd) {
    throw new Error('WEATHER_SRV_MONGO_APP_PASSWORD must be set');
}
db.getSiblingDB('openagridb');
db.createUser({
    user: 'openagri',
    pwd: appPwd,
    roles: [
        {
            role: 'readWrite',
            db: 'openagridb',
        },
    ],
});
db.points.createIndex(
    { "location": "2dsphere" }
  );