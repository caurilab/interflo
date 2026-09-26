module.exports = {
  presets: [
    // NativeWind v4 : jsxImportSource « nativewind » (doc officielle, mode framework-less)
    ['module:@react-native/babel-preset', { jsxImportSource: 'nativewind' }],
    'nativewind/babel',
  ],
  plugins: [
    // Requis par react-native-reanimated v4 (le plugin vit dans react-native-worklets)
    'react-native-worklets/plugin',
    // laravel-echo (transport temps réel) expédie des blocs statiques de
    // classe (ES2022) : transformés pour Metro/Hermes.
    '@babel/plugin-transform-class-static-block',
  ],
};
