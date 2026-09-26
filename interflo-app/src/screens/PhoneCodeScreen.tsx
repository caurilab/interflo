import React, { useState } from 'react';
import { ActivityIndicator, Pressable, Text, TextInput, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useTranslation } from 'react-i18next';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import type { RootStackParamList } from '../navigation/types';
import { DEFAULTS } from '../config/gameConfig';
import { ApiError } from '../api/client';
import { requestPhoneCode, verifyPhoneCode } from '../api/endpoints';
import { attachToSession } from '../session/attach';
import { storeToken } from '../storage/authToken';
import { PrimaryButton } from '../components/PrimaryButton';
// Tokens couleur — source unique (spinner du bouton secondaire)
import colors from '../config/colors';

type Props = NativeStackScreenProps<RootStackParamList, 'PhoneCode'>;

/**
 * Vérification du numéro (I-8), étape 2 : saisie du code OTP reçu par SMS.
 *
 * Succès : le serveur renvoie un token Sanctum — persisté (AsyncStorage,
 * point de départ dev : Keychain viendra) puis attachement immédiat à la
 * session confirmée à l'écran précédent.
 * Erreur 422 : « code invalide ou expiré » (générique, non-énumération).
 */
export function PhoneCodeScreen({ navigation, route }: Props) {
  const { t } = useTranslation();
  const { phone, sessionId, tenantName, emissionTitle } = route.params;
  const [code, setCode] = useState('');
  const [isVerifying, setIsVerifying] = useState(false);
  const [isResending, setIsResending] = useState(false);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [resentNotice, setResentNotice] = useState(false);

  const isCodeComplete = code.length === DEFAULTS.otpLength;

  const handleVerify = async () => {
    setIsVerifying(true);
    setErrorMessage(null);
    setResentNotice(false);
    try {
      const verification = await verifyPhoneCode(phone, code);
      await storeToken(verification.token);

      // Token frais : l'attachement enchaîne sans repasser par le stockage.
      const result = await attachToSession(sessionId, verification.token);
      if (result.status === 'attached') {
        navigation.navigate('Play', { sessionId, tenantName, emissionTitle });
      } else if (result.status === 'phoneRequired') {
        // Ne devrait pas arriver avec un token qui vient d'être émis —
        // repli honnête : retour à la saisie du numéro.
        navigation.navigate('PhoneEntry', { sessionId, tenantName, emissionTitle });
      } else {
        setErrorMessage(
          result.error.isNetworkError ? t('errors.network') : t('errors.generic'),
        );
      }
    } catch (error) {
      if (error instanceof ApiError && error.status === 422) {
        setErrorMessage(t('otp.errorInvalidCode'));
      } else if (error instanceof ApiError && error.status === 429) {
        setErrorMessage(t('errors.tooManyAttempts'));
      } else if (error instanceof ApiError && error.isNetworkError) {
        setErrorMessage(t('errors.network'));
      } else {
        setErrorMessage(t('errors.generic'));
      }
    } finally {
      setIsVerifying(false);
    }
  };

  const handleResend = async () => {
    setIsResending(true);
    setErrorMessage(null);
    setResentNotice(false);
    try {
      await requestPhoneCode(phone);
      setResentNotice(true);
    } catch (error) {
      if (error instanceof ApiError && error.status === 429) {
        setErrorMessage(t('errors.tooManyAttempts'));
      } else if (error instanceof ApiError && error.isNetworkError) {
        setErrorMessage(t('errors.network'));
      } else {
        setErrorMessage(t('errors.generic'));
      }
    } finally {
      setIsResending(false);
    }
  };

  return (
    <SafeAreaView className="flex-1 bg-surface">
      <View className="flex-1 justify-center px-6">
        <Text className="text-center text-3xl font-extrabold text-white">
          {t('otp.title')}
        </Text>
        <Text className="mt-3 text-center text-base leading-6 text-white/75">
          {t('otp.subtitle', { phone })}
        </Text>

        <View className="mt-8 rounded-2xl border border-white/10 bg-surface-raised p-4">
          <TextInput
            value={code}
            onChangeText={(text) => setCode(text.replace(/\D/g, ''))}
            maxLength={DEFAULTS.otpLength}
            keyboardType="number-pad"
            autoComplete="sms-otp"
            placeholder={'·'.repeat(DEFAULTS.otpLength)}
            placeholderClassName="text-white/25"
            className="rounded-xl border border-brand/40 bg-surface-deep px-4 py-3 text-center text-2xl font-extrabold tracking-widest text-cream"
            accessibilityLabel={t('otp.codeAccessibilityLabel')}
          />
          <View className="mt-4">
            <PrimaryButton
              label={t('otp.verify')}
              onPress={handleVerify}
              disabled={!isCodeComplete}
              loading={isVerifying}
            />
          </View>
        </View>

        <Pressable
          onPress={handleResend}
          disabled={isResending}
          accessibilityRole="button"
          className="mt-4 min-h-14 items-center justify-center rounded-xl border border-white/15 bg-surface-raised active:bg-surface-overlay"
        >
          {isResending ? (
            <ActivityIndicator color={colors.white} />
          ) : (
            <Text className="text-lg font-bold text-white/80">{t('otp.resend')}</Text>
          )}
        </Pressable>

        {resentNotice && (
          <Text accessibilityLiveRegion="polite" className="mt-4 text-center text-base text-white/75">
            {t('otp.resent')}
          </Text>
        )}
        {errorMessage !== null && (
          <Text accessibilityLiveRegion="polite" className="mt-4 text-center text-base font-semibold text-cream">
            {errorMessage}
          </Text>
        )}
      </View>
    </SafeAreaView>
  );
}
