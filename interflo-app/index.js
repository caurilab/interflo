/**
 * @format
 */

import { AppRegistry } from 'react-native';
// Initialisation i18n au démarrage (fr par défaut, en fallback — CONVENTIONS.md §2)
import './src/i18n';
import App from './App';
import { name as appName } from './app.json';

AppRegistry.registerComponent(appName, () => App);
