import React from 'react';
import { ActivityIndicator, Pressable, Text } from 'react-native';
import { GradientView } from './GradientView';
// Tokens couleur — source unique (spinner sur dégradé)
import colors from '../config/colors';

type PrimaryButtonProps = {
  /** Libellé i18n du bouton. */
  label: string;
  onPress: () => void;
  /** Désactivé : rendu en magenta profond atténué, aucun geste possible. */
  disabled?: boolean;
  /** Chargement : spinner sombre sur le dégradé, geste bloqué. */
  loading?: boolean;
  /**
   * gradient : CTA principal en dégradé d'énergie D-001 (défaut).
   * ghost : action secondaire — carte sombre, liseré cream discret.
   */
  variant?: 'gradient' | 'ghost';
};

/**
 * Bouton d'action principal — dégradé d'énergie D-001 (#d96034 → #d6007d).
 *
 * Style gaming : dégradé franc, texte cream très gras, état pressé net
 * (assombrissement immédiat, sans animation). Corporate : min-h-14, zone
 * large, libellé toujours lisible (cream sur magenta = contraste correct
 * sur fond sombre).
 */
export function PrimaryButton({
  label,
  onPress,
  disabled = false,
  loading = false,
  variant = 'gradient',
}: PrimaryButtonProps) {
  const isInactive = disabled || loading;

  if (variant === 'ghost') {
    return (
      <Pressable
        onPress={onPress}
        disabled={isInactive}
        accessibilityRole="button"
        className="min-h-14 items-center justify-center rounded-xl border border-white/15 bg-surface-raised active:bg-surface-overlay"
      >
        {loading ? (
          <ActivityIndicator color={colors.white} />
        ) : (
          <Text className="text-lg font-bold text-white/80">{label}</Text>
        )}
      </Pressable>
    );
  }

  return (
    <Pressable
      onPress={onPress}
      disabled={isInactive}
      accessibilityRole="button"
      className="active:opacity-80"
    >
      <GradientView
        // Inactif : magenta profond uni (liseré visible, pas d'appel au geste)
        from={isInactive ? colors.brand.dark : colors.energy.from}
        to={isInactive ? colors.brand.dark : colors.energy.to}
        style={{ borderRadius: 14, opacity: disabled ? 0.45 : 1 }}
        contentStyle={{ minHeight: 56, alignItems: 'center', justifyContent: 'center' }}
      >
        {loading ? (
          <ActivityIndicator color={colors.surface.deep} />
        ) : (
          <Text className="text-lg font-extrabold tracking-wide text-white">
            {label}
          </Text>
        )}
      </GradientView>
    </Pressable>
  );
}
