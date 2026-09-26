import React, { useState } from 'react';
import { View, Text } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useTranslation } from 'react-i18next';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import type { RootStackParamList } from '../navigation/types';
import { attachToSession } from '../session/attach';
import { PrimaryButton } from '../components/PrimaryButton';

type Props = NativeStackScreenProps<RootStackParamList, 'PairingConfirmation'>;

/**
 * Écran de confirmation — « c'est bien cette émission ? ».
 *
 * Le pointeur public (I-15) ne donne que chaîne + émission + session :
 * on les montre au joueur AVANT l'attachement, pour qu'un code mal
 * transcrit (ou pris en photo ailleurs) n'embarque personne dans la
 * mauvaise émission.
 *
 * Attachement (`POST /api/v1/sessions/attach`) :
 * - token persisté valide → directement l'attente ;
 * - pas de token, ou 401/403 → flux de vérification du numéro (I-8).
 */
export function PairingConfirmationScreen({ navigation, route }: Props) {
  const { t } = useTranslation();
  const { sessionId, tenantName, emissionTitle } = route.params;
  const [isAttaching, setIsAttaching] = useState(false);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  const handleParticipate = async () => {
    setIsAttaching(true);
    setErrorMessage(null);
    const result = await attachToSession(sessionId);
    setIsAttaching(false);

    if (result.status === 'attached') {
      navigation.navigate('Play', { sessionId, tenantName, emissionTitle });
    } else if (result.status === 'phoneRequired') {
      navigation.navigate('PhoneEntry', { sessionId, tenantName, emissionTitle });
    } else {
      setErrorMessage(
        result.error.isNetworkError ? t('errors.network') : t('errors.generic'),
      );
    }
  };

  return (
    <SafeAreaView className="flex-1 bg-surface">
      <View className="flex-1 justify-center px-6">
        <Text className="text-center text-3xl font-extrabold text-white">
          {t('pairingConfirmation.title')}
        </Text>

        {/* Identité de l'émission issue du pointeur public (R-11) */}
        <View className="mt-8 rounded-2xl border border-brand/40 bg-surface-raised p-5">
          <Text className="text-sm font-semibold uppercase tracking-wide text-cream">
            {t('pairingConfirmation.channelLabel')}
          </Text>
          <Text className="mt-1 text-2xl font-extrabold text-white">{tenantName}</Text>
          <Text className="mt-4 text-sm font-semibold uppercase tracking-wide text-cream">
            {t('pairingConfirmation.emissionLabel')}
          </Text>
          <Text className="mt-1 text-2xl font-extrabold text-white">{emissionTitle}</Text>
        </View>

        {/* Zones tactiles larges et espacées (petits écrans, M-1) */}
        <View className="mt-8">
          <PrimaryButton
            label={t('pairingConfirmation.participate')}
            onPress={handleParticipate}
            loading={isAttaching}
          />
        </View>
        <View className="mt-4">
          <PrimaryButton
            label={t('pairingConfirmation.cancel')}
            onPress={() => navigation.goBack()}
            disabled={isAttaching}
            variant="ghost"
          />
        </View>

        {errorMessage !== null && (
          <Text accessibilityLiveRegion="polite" className="mt-4 text-center text-base font-semibold text-cream">
            {errorMessage}
          </Text>
        )}
      </View>
    </SafeAreaView>
  );
}
