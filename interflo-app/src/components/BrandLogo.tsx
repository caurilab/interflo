import React from 'react';
import { Image } from 'react-native';
import { useTranslation } from 'react-i18next';

/**
 * Logo horizontal Interflo.
 *
 * Rendu via `Image` sur des PNG @1x/@2x/@3x exportés depuis
 * `brand/logo-horizontal.svg` (rsvg-convert) — choix documenté dans
 * docs/rapports/2026-09-26-logo-interflo.md : pas de dépendance native
 * (react-native-svg) pour un usage purement décoratif.
 *
 * ⚠️ Le wordmark est blanc : ce composant suppose le thème sombre (#101418).
 */

// Ratio du viewBox source (462.92 × 157.97) pour un redimensionnement
// proportionnel quelle que soit la hauteur demandée.
const LOGO_ASPECT_RATIO = 462.92 / 157.97;

type Props = {
  /** Hauteur d'affichage en dp (défaut : 40). */
  height?: number;
};

export function BrandLogo({ height = 40 }: Props) {
  const { t } = useTranslation();
  return (
    <Image
      source={require('../assets/brand/logo-horizontal.png')}
      style={{ height, aspectRatio: LOGO_ASPECT_RATIO }}
      resizeMode="contain"
      accessibilityLabel={t('common.logoAlt')}
    />
  );
}
