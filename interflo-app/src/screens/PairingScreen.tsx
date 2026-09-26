import React, { useState } from 'react';
import { Text, TextInput, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useTranslation } from 'react-i18next';
import Animated, { FadeInUp } from 'react-native-reanimated';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import type { RootStackParamList } from '../navigation/types';
import { DEFAULTS } from '../config/gameConfig';
import { MOTION } from '../config/animations';
import { ApiError } from '../api/client';
import { resolvePairingCode } from '../api/endpoints';
import { BrandLogo } from '../components/BrandLogo';
import { PrimaryButton } from '../components/PrimaryButton';

type Props = NativeStackScreenProps<RootStackParamList, 'Pairing'>;

/**
 * Écran d'appairage (I-14, EX-01, EX-02).
 *
 * QR et code court sont TOUJOURS visibles tous les deux, côte à côte :
 * le code court n'est jamais un secours caché dans un menu (M-5).
 * Scanner un téléviseur depuis un canapé est souvent pénible — sans
 * alternative manuelle visible, on perd des joueurs sur un problème optique.
 *
 * ⚠️ Le code est un pointeur PUBLIC (I-15) : il désigne la chaîne et
 * l'émission, il n'autorise pas à jouer. Il n'est donc JAMAIS traité comme
 * un secret ici : pas de stockage, pas de Bearer — il part en corps de
 * requête de `POST /api/v1/pairing/resolve`, rien de plus.
 */
export function PairingScreen({ navigation }: Props) {
  const { t } = useTranslation();
  const [shortCode, setShortCode] = useState('');
  const [isResolving, setIsResolving] = useState(false);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  const isCodeComplete = shortCode.length === DEFAULTS.shortCodeLength;

  const handleJoin = async () => {
    setIsResolving(true);
    setErrorMessage(null);
    try {
      const resolution = await resolvePairingCode(shortCode);
      // Le pointeur public désigne la session ; l'autorisation de jouer
      // reste côté serveur, sur la fenêtre (I-2, I-15).
      // TODO(I-18) : le code tourne en cours d'antenne — le cas « code
      // mort » est couvert par le 404 ci-dessous ; le comportement d'un
      // joueur déjà appairé (EX-05) est géré côté serveur.
      navigation.navigate('PairingConfirmation', {
        sessionId: resolution.data.session.id,
        tenantName: resolution.data.tenant.name,
        emissionTitle: resolution.data.emission.title,
      });
    } catch (error) {
      if (error instanceof ApiError && error.status === 404) {
        // Réponse générique du serveur : code inconnu ou expiré (I-18).
        setErrorMessage(t('pairing.errorInvalidCode'));
      } else if (error instanceof ApiError && error.isNetworkError) {
        setErrorMessage(t('errors.network'));
      } else {
        setErrorMessage(t('errors.generic'));
      }
    } finally {
      setIsResolving(false);
    }
  };

  return (
    <SafeAreaView className="flex-1 bg-surface">
      <View className="flex-1 px-5 py-6">
        {/* Entrée d'écran douce (D-001 §3) — montée légère en cascade */}
        <Animated.View entering={FadeInUp.duration(300)}>
          <BrandLogo />
          <Text className="mt-6 text-3xl font-extrabold text-white">
            {t('pairing.title')}
          </Text>
          <Text className="mt-2 text-base text-white/75">
            {t('pairing.subtitle')}
          </Text>
        </Animated.View>

        {/* Les deux voies d'appairage, toujours côte à côte (I-14, M-5) */}
        <Animated.View
          entering={FadeInUp.delay(MOTION.stagger.baseMs).duration(320)}
          style={{ marginTop: 32, flex: 1, flexDirection: 'row', gap: 16 }}
        >
          {/* Voie 1 — QR code */}
          <View className="flex-1">
            <View className="aspect-square items-center justify-center rounded-2xl border-2 border-dashed border-brand/60 bg-surface-raised">
              {/* TODO(appairage) : brancher la caméra (ex. react-native-vision-camera)
                  pour scanner le QR affiché à l'antenne (EX-01).
                  Aucun SDK caméra n'est installé à ce stade de scaffolding. */}
              <Text className="px-4 text-center text-sm text-white/60">
                {t('pairing.qrScanZonePlaceholder')}
              </Text>
            </View>
            <Text className="mt-3 text-center text-sm font-semibold text-cream">
              {t('pairing.tvCodeLabel')}
            </Text>
          </View>

          {/* Voie 2 — code court saisi à la main */}
          <View className="flex-1">
            <View className="rounded-2xl border border-white/10 bg-surface-raised p-4">
              <Text className="text-sm font-semibold text-cream">
                {t('pairing.spokenCodeLabel')}
              </Text>
              <TextInput
                value={shortCode}
                onChangeText={(text) => setShortCode(text.toUpperCase())}
                maxLength={DEFAULTS.shortCodeLength}
                autoCapitalize="characters"
                autoCorrect={false}
                keyboardType="default"
                placeholder={'·'.repeat(DEFAULTS.shortCodeLength)}
                placeholderClassName="text-white/25"
                className="mt-3 rounded-xl border border-brand/40 bg-surface-deep px-4 py-3 text-center text-2xl font-extrabold tracking-widest text-cream"
                accessibilityLabel={t('pairing.shortCodeAccessibilityLabel')}
              />
              <View className="mt-4">
                <PrimaryButton
                  label={t('pairing.join')}
                  onPress={handleJoin}
                  disabled={!isCodeComplete}
                  loading={isResolving}
                />
              </View>
            </View>
            <Text className="mt-3 text-center text-sm text-white/60">
              {t('pairing.codeLengthHint', { length: DEFAULTS.shortCodeLength })}
            </Text>
          </View>
        </Animated.View>

        {/* Erreur de résolution (404 / réseau / autre) — message i18n,
            cream chaleureux plutôt que rouge punitif (M-4, esprit D-001) */}
        {errorMessage !== null && (
          <Animated.View entering={FadeInUp.duration(200)}>
            <Text accessibilityLiveRegion="polite" className="mt-4 text-center text-base font-semibold text-cream">
              {errorMessage}
            </Text>
          </Animated.View>
        )}
      </View>
    </SafeAreaView>
  );
}
