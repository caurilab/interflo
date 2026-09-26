/**
 * Persistance du token joueur — Interflo.
 *
 * ⚠️ AsyncStorage = stockage NON chiffré, acceptable en dev uniquement
 * (validé comme point de départ pour cette mission). Le token Sanctum est
 * un identifiant durable : avant production, migrer vers la Keychain iOS /
 * le Keystore Android (ex. react-native-keychain).
 * TODO(sécurité) : bascule Keychain — point à arbitrer avec le PO.
 *
 * ⚠️ Ne JAMAIS stocker ici le code court d'appairage : c'est un pointeur
 * public (I-15), pas un secret — il n'a rien à faire dans ce module.
 */
import AsyncStorage from '@react-native-async-storage/async-storage';

const TOKEN_KEY = 'interflo.playerToken';

/** Lit le token persisté, ou null si absent (jamais levé d'erreur). */
export async function getStoredToken(): Promise<string | null> {
  try {
    return await AsyncStorage.getItem(TOKEN_KEY);
  } catch {
    // Un stockage illisible équivaut à « pas de token » : le joueur
    // repartira par le flux de vérification téléphone (I-8).
    return null;
  }
}

/** Persiste le token Sanctum renvoyé par la vérification du numéro. */
export async function storeToken(token: string): Promise<void> {
  await AsyncStorage.setItem(TOKEN_KEY, token);
}

/** Oublie le token (token rejeté par le serveur : 401/403). */
export async function clearToken(): Promise<void> {
  await AsyncStorage.removeItem(TOKEN_KEY);
}
