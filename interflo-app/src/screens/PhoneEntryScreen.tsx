import React, { useState } from 'react';
import { Text, TextInput, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useTranslation } from 'react-i18next';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import type { RootStackParamList } from '../navigation/types';
import { ApiError } from '../api/client';
import { requestPhoneCode } from '../api/endpoints';
import { PrimaryButton } from '../components/PrimaryButton';

type Props = NativeStackScreenProps<RootStackParamList, 'PhoneEntry'>;

/**
 * Vérification du numéro (I-8), étape 1 : saisie du numéro.
 *
 * Format international avec indicatif (E.164, ex. +33612345678) —
 * la validation définitive est côté serveur (422), l'écran ne fait que
 * normaliser (espaces retirés) et exiger le « + » initial.
 *
 * Succès : 204 vide, identique que le numéro soit connu ou non
 * (non-énumération) — l'écran passe à la saisie du code dans tous les cas.
 */
export function PhoneEntryScreen({ navigation, route }: Props) {
  const { t } = useTranslation();
  const [phone, setPhone] = useState('');
  const [isSending, setIsSending] = useState(false);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  // E.164 minimal côté client : « + » suivi de chiffres (8 à 15 chiffres).
  const normalizedPhone = phone.replace(/[\s.-]/g, '');
  const isPhonePlausible = /^\+\d{8,15}$/.test(normalizedPhone);

  const handleSendCode = async () => {
    setIsSending(true);
    setErrorMessage(null);
    try {
      await requestPhoneCode(normalizedPhone);
      navigation.navigate('PhoneCode', { ...route.params, phone: normalizedPhone });
    } catch (error) {
      if (error instanceof ApiError && error.status === 422) {
        setErrorMessage(t('phone.errorInvalidPhone'));
      } else if (error instanceof ApiError && error.status === 429) {
        setErrorMessage(t('errors.tooManyAttempts'));
      } else if (error instanceof ApiError && error.isNetworkError) {
        setErrorMessage(t('errors.network'));
      } else {
        setErrorMessage(t('errors.generic'));
      }
    } finally {
      setIsSending(false);
    }
  };

  return (
    <SafeAreaView className="flex-1 bg-surface">
      <View className="flex-1 justify-center px-6">
        <Text className="text-center text-3xl font-extrabold text-white">
          {t('phone.title')}
        </Text>
        <Text className="mt-3 text-center text-base leading-6 text-white/75">
          {t('phone.subtitle')}
        </Text>

        <View className="mt-8 rounded-2xl border border-white/10 bg-surface-raised p-4">
          <Text className="text-sm font-semibold text-cream">
            {t('phone.phoneLabel')}
          </Text>
          <TextInput
            value={phone}
            onChangeText={setPhone}
            keyboardType="phone-pad"
            autoComplete="tel"
            autoCorrect={false}
            placeholder={t('phone.phonePlaceholder')}
            placeholderClassName="text-white/25"
            className="mt-3 rounded-xl border border-brand/40 bg-surface-deep px-4 py-3 text-center text-2xl font-extrabold tracking-wider text-cream"
            accessibilityLabel={t('phone.phoneAccessibilityLabel')}
          />
          <View className="mt-4">
            <PrimaryButton
              label={t('phone.sendCode')}
              onPress={handleSendCode}
              disabled={!isPhonePlausible}
              loading={isSending}
            />
          </View>
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
