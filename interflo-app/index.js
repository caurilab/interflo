/**
 * @format
 */

import { AppRegistry } from 'react-native';
// Initialisation i18n au démarrage (fr par défaut, en fallback — CONVENTIONS.md §2)
import './src/i18n';
import App from './App';
import { name as appName } from './app.json';
import { registerRootComponent } from 'expo';

// Shell bare React Native : cherche le nom du projet (« Interflo »).
AppRegistry.registerComponent(appName, () => App);
// Expo Go : registerRootComponent enregistre « main » (convention Expo) et
// initialise le runtime Expo (gestion d'erreurs, etc.).
registerRootComponent(App);


