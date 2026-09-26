import './global.css';

import React from 'react';
import { StatusBar } from 'react-native';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { NavigationContainer, DefaultTheme } from '@react-navigation/native';
import type { Theme } from '@react-navigation/native';
import { RootNavigator } from './src/navigation/RootNavigator';
// Tokens couleur partagés avec tailwind.config.js (source unique)
import colors from './src/config/colors';

/**
 * Interflo — application joueur.
 *
 * Le parcours de jeu au format élimination est câblé de bout en bout
 * (appairage → OTP → jeu → feedback → éliminé → fin de partie) contre
 * l'API réelle. Transport : polling HTTP provisoire (session 4), en
 * attente de la couche temps réel (D-002, proposée).
 *
 * TODO(I-43) : le SDK d'empreinte audio ACRCloud se branchera ici plus tard
 * (format buzzer uniquement, mode mesuré désactivé au démarrage — I-25).
 * Le micro ne s'écoutera jamais en continu (I-22) et son refus n'empêchera
 * jamais de jouer (I-23, M-6).
 */

// Thème sombre D-001 : salon éclairé par la télévision (06-ux-ui.md §2.2),
// primaire magenta de marque pour les accents de navigation.
const interfloTheme: Theme = {
  ...DefaultTheme,
  dark: true,
  colors: {
    ...DefaultTheme.colors,
    background: colors.surface.DEFAULT,
    card: colors.surface.DEFAULT,
    text: colors.white,
    primary: colors.brand.DEFAULT,
  },
};

export default function App() {
  return (
    <SafeAreaProvider>
      <StatusBar barStyle="light-content" />
      <NavigationContainer theme={interfloTheme}>
        <RootNavigator />
      </NavigationContainer>
    </SafeAreaProvider>
  );
}
