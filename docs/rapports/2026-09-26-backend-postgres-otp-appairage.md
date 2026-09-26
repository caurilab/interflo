# Rapport — Bascule PostgreSQL, vérification téléphone, appairage

> **Date** : 2026-09-26 — **Agent** : interflo-backend
> **Périmètre** : mission du 2026-09-26 (bascule PostgreSQL + mécanique d'appairage + vérification du numéro de téléphone). Tables PROVISOIRES uniquement, chacune marquée « PROVISOIRE — en attente de la décision sur le modèle de données (docs/07 §5) ». Aucune table de jeu, pas de Stancl Tenancy.

---

## Fichiers créés / modifiés

**Créés**
- Migrations (7, PROVISOIRES) : `players`, `phone_verification_challenges`, `tenants`, `emissions`, `game_sessions`, `pairing_codes`, `player_session`
- Modèles : `Player` (Authenticatable + HasApiTokens), `PhoneVerificationChallenge`, `Tenant`, `Emission`, `GameSession`, `PairingCode`, `PlayerSession`
- Factories + seeders idempotents (garde-fou production) pour chaque modèle
- `app/Contracts/SmsSender.php` + `app/Services/Sms/LogSmsSender.php`
- Services : `PhoneVerificationService`, `PairingCodeService`, `SessionAttachmentService`
- Form Requests : `Phone/RequestPhoneCodeRequest`, `Phone/VerifyPhoneCodeRequest`, `Pairing/ResolvePairingCodeRequest`, `Sessions/AttachSessionRequest`
- Controllers : `Api/PhoneVerificationController`, `Api/PairingController`, `Api/SessionAttachmentController`
- Resources : `PlayerResource`, `PairingResolutionResource`
- Middleware : `EnsurePhoneIsVerified` (alias `phone.verified`)
- Commande : `interflo:rotate-pairing-codes` (planifiée `everyMinute`)
- `lang/fr/interflo.php` + `lang/en/interflo.php` (les deux locales partent ensemble)
- Tests : `PhoneVerificationTest`, `PairingTest`, `SessionAttachmentTest`

**Modifiés**
- `.env`, `.env.example` : PostgreSQL par défaut (`interflo` / `houdini`), sqlite en repli commenté
- `phpunit.xml` : tests sur PostgreSQL base `interflo_test`
- `config/interflo.php` : 5 nouvelles valeurs OTP (ci-dessous)
- `routes/api.php` (créé par `install:api`, rempli), `routes/console.php`, `bootstrap/app.php`, `app/Providers/AppServiceProvider.php`, `database/seeders/DatabaseSeeder.php`, `tests/Pest.php`
- `composer.json` : + `laravel/sanctum`

**Bases** : `interflo` et `interflo_test` créées sur le PostgreSQL local ; migrations appliquées sur les deux.

## Tests

**36 verts, 0 régression, 509 assertions** (`php artisan test --compact`). Couverture : flux OTP complet (succès, faux, expiré, tentatives dépassées, throttles heure + minute), non-énumération, stockage hashé, E.164, résolution (valide / expirée / inconnue / casse / fenêtre future), rotation (ancien code mort, joueur attaché conservé), alphabet, commande planifiée, attach (401 / 403 / succès / I-17 / I-16 / idempotence / unicité en base), détache.

`vendor/bin/pint` exécuté (⚠️ `--dirty` indisponible : le projet n'est pas un dépôt Git ; Pint a été ciblé sur les dossiers touchés).

## Contrat exposé (pour le mobile)

| Route | Accès | Succès | Erreurs |
|---|---|---|---|
| `POST /api/v1/players/phone/request-code` `{phone}` | public, throttle 10/h/numéro | `204` vide (identique numéro connu ou non) | `422 phone` (E.164), `429` |
| `POST /api/v1/players/phone/verify` `{phone, code}` | public, throttle 10/min/numéro | `200 {token, player:{id, phone, phone_verified_at}}` | `422 code` générique (« Code invalide ou expiré »), `429` |
| `POST /api/v1/pairing/resolve` `{code}` | public | `200 {data:{tenant:{name}, emission:{title}, session:{id, status}}}` | `404` générique (« Code inconnu ou expiré ») |
| `POST /api/v1/sessions/attach` `{session_id}` | Bearer Sanctum + téléphone vérifié | `200` même forme que resolve | `401`, `403` (« numéro doit être vérifié »), `422 session_id` (inexistante ou terminée) |
| `DELETE /api/v1/sessions/attach` | Bearer Sanctum + téléphone vérifié | `204` (idempotent) | `401`, `403` |

Le pointeur de `resolve` ne contient **que** `tenant.name`, `emission.title`, `session.id`, `session.status` — ni slug, ni références BOS, ni horodatages (R-9, R-11).

## Valeurs paramétrables introduites (points de départ NON VALIDÉS)

| Clé (`config/interflo.php`, env `INTERFLO_*`) | Défaut |
|---|---|
| `otp_length` | 6 |
| `otp_ttl_seconds` | 300 |
| `otp_max_attempts` | 5 |
| `otp_request_throttle_per_hour` | 10 |
| `otp_verify_throttle_per_minute` | 10 *(introduite par le backend, non demandée — filet additionnel)* |

## Hypothèses (à arbitrer)

1. **Bascule PostgreSQL ordonnée alors que le choix reste « à confirmer, pas à recopier par défaut »** (docs/04 §3.1). Consigné comme hypothèse dans `.env` / `.env.example`. ⚠️ Le psql local est en réalité **18.6**, pas 17.9.
2. **Aucun provider SMS choisi** : contrat `App\Contracts\SmsSender` + implémentation `LogSmsSender` (log). Brancher un provider = une implémentation + une ligne de binding.
3. **EX-05 implémentée bien que NON validée** : à la rotation du code, le joueur déjà appairé **garde** sa session. Signalé au PO.
4. **I-17 garanti en base** : index unique partiel PostgreSQL `ON player_session (player_id) WHERE detached_at IS NULL`, complété par le détachement en service. Choix documenté dans la migration.
5. **Rotation cadencée sous la minute impossible avec le scheduler Laravel** (granularité 1 min vs 15-30 s visés) : commande planifiée `everyMinute` = mécanique de secours ; la vraie cadence dépend de l'architecture temps réel non conçue (docs/04 §5). La validité par timestamps reste correcte entre-temps.
6. **`otp_verify_throttle_per_minute`** ajoutée sans demande explicite (« rate limiting sur les deux routes ») — valeur à arbitrer.
7. **Réponse `verify`** : `200 {token, player}` (non spécifié par la mission) ; `204` pour `request-code`.
8. **`player_session` en nom de table singulier** (exigence de mission), modèle `PlayerSession` avec `$table` explicite.
