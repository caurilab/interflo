/**
 * Déclaration de `process` pour Expo (variables publiques `EXPO_PUBLIC_*`,
 * injectées au build) en TypeScript strict, sans dépendre de @types/node.
 */
declare const process: {
  env: {
    EXPO_PUBLIC_API_HOST?: string;
    [key: string]: string | undefined;
  };
};
