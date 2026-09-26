module.exports = {
  preset: '@react-native/jest-preset',
  // ⚠️ pnpm : les chemins réels passent par node_modules/.pnpm/<pkg>/node_modules/<pkg>.
  // Le motif par défaut du preset RN ne reconnaît que node_modules/<pkg> à plat,
  // ce qui excluait les setup files du preset de la transformation babel.
  // Le segment .pnpm est inclus DANS le lookahead (hors, le backtracking
  // le sauterait et ignorerait quand même les fichiers autorisés).
  transformIgnorePatterns: [
    'node_modules/(?!(\\.pnpm/[^/]+/node_modules/)?((jest-)?react-native|@react-native(-community)?|@react-native-async-storage|nativewind|react-native-worklets|react-native-reanimated)/)',
  ],
};
