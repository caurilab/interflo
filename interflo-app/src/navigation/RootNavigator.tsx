import React from 'react';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import type { RootStackParamList } from './types';
// Tokens couleur partagés avec tailwind.config.js (source unique)
import colors from '../config/colors';
import { PairingScreen } from '../screens/PairingScreen';
import { PairingConfirmationScreen } from '../screens/PairingConfirmationScreen';
import { PhoneEntryScreen } from '../screens/PhoneEntryScreen';
import { PhoneCodeScreen } from '../screens/PhoneCodeScreen';
import { PlayScreen } from '../screens/PlayScreen';

const Stack = createNativeStackNavigator<RootStackParamList>();

/**
 * Navigateur racine — parcours joueur.
 *
 * Flux d'appairage (contrat backend 2026-09-26) :
 * Pairing (code court / QR) → PairingConfirmation → attach direct si token
 * persisté, sinon PhoneEntry → PhoneCode (I-8) → attach → Play.
 *
 * Au-delà de l'appairage, la navigation ne pilote plus le jeu : `Play`
 * héberge la machine à états alimentée par `GET /api/v1/play/state`
 * (transport PROVISOIRE par polling, en attente de l'ADR temps réel) —
 * ouverture de fenêtre (I-3), verdict (I-29), verrouillage élimination
 * (EX-32), fin de partie (I-28) sont des changements d'état rendus, pas
 * des transitions de pile.
 */
export function RootNavigator() {
  return (
    <Stack.Navigator
      initialRouteName="Pairing"
      screenOptions={{
        headerShown: false,
        contentStyle: { backgroundColor: colors.surface.DEFAULT },
      }}
    >
      <Stack.Screen name="Pairing" component={PairingScreen} />
      <Stack.Screen name="PairingConfirmation" component={PairingConfirmationScreen} />
      <Stack.Screen name="PhoneEntry" component={PhoneEntryScreen} />
      <Stack.Screen name="PhoneCode" component={PhoneCodeScreen} />
      <Stack.Screen name="Play" component={PlayScreen} />
    </Stack.Navigator>
  );
}
