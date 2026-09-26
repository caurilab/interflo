/**
 * Types de navigation — pile racine de l'application joueur.
 *
 * Le parcours reflète le cycle de vie d'une session (INTERFLO_PRODUCT.md §13.4)
 * et le flux d'appairage du contrat backend (rapport 2026-09-26) :
 * appairage (code court / QR) → confirmation → vérification téléphone (I-8)
 * si pas de token → attachement → jeu.
 *
 * ⚠️ Tout le jeu (attente, question, feedback, locked, fin) vit dans UNE
 * SEULE route `Play` : les transitions sont pilotées par l'état serveur
 * (`play/state`), pas par la navigation — la machine à états rend la vue
 * adéquate, aucune race de navigation possible avec le polling.
 */

/** Données de session issues du pointeur public (resolve / attach). */
export type SessionPointerParams = {
  /** Identifiant serveur de la session (session.id du pointeur). */
  sessionId: number;
  /** Nom de la chaîne (tenant.name) — affichage uniquement. */
  tenantName: string;
  /** Titre de l'émission (emission.title) — affichage uniquement. */
  emissionTitle: string;
};

export type RootStackParamList = {
  /** Appairage à une émission : QR + code court, toujours les deux (I-14). */
  Pairing: undefined;
  /**
   * Confirmation de l'émission résolue avant attachement :
   * « c'est bien cette chaîne / cette émission ? ».
   */
  PairingConfirmation: SessionPointerParams;
  /** Vérification du numéro (I-8), étape 1 : saisie du numéro E.164. */
  PhoneEntry: SessionPointerParams;
  /** Vérification du numéro (I-8), étape 2 : saisie du code OTP reçu par SMS. */
  PhoneCode: SessionPointerParams & { phone: string };
  /**
   * Jeu élimination : machine à états pilotée par play/state (idle /
   * waiting / question / answered / locked / finished). Hors fenêtre,
   * l'application est inerte pour le joueur (I-2).
   */
  Play: SessionPointerParams;
};
