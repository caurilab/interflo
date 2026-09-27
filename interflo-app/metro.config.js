const { getDefaultConfig } = require('expo/metro-config');
const { withNativeWind } = require('nativewind/metro');

/**
 * Metro configuration (Expo).
 *
 * NativeWind v4 : withNativeWind traite global.css (doc officielle).
 * Expo : getDefaultConfig d'expo/metro-config (sérialiseur attendu par
 * `expo start` / `expo export`).
 *
 * @type {import('expo/metro-config').MetroConfig}
 */
const config = getDefaultConfig(__dirname);

module.exports = withNativeWind(config, { input: './global.css' });


