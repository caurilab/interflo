// Interflo — test de charge du pic de soumissions (D-002 §3 / §7.1)
//
// Chaque VU simule UN joueur qui soumet UNE réponse pendant une fenêtre.
// On mesure la latence de soumission et le taux d'acceptation au pic.
//
// ⚠️ Prérequis : la manche cible doit être OUVERTE et les joueurs déjà
// servis (served_at posé). Le plus simple : lancer la partie via la console
// animateur, ou brancher un jeu de données semé (voir README).
//
// Usage :
//   k6 run \
//     --env BASE_URL=https://api.interflo.test \
//     --env ROUND_ID=1 \
//     --env TOKEN=<bearer-sanctum> \
//     ops/load-test/k6-answer-peak.js
//
// Paliers (D-002 §7.1) : 1 000 → 10 000 → 100 000 VU. Aucun chiffre de ce
// fichier n'est une promesse : les critères de passage sont à fixer à la
// première exécution.

import http from 'k6/http';
import { check } from 'k6';

const BASE_URL = __ENV.BASE_URL || 'https://api.interflo.test';
const ROUND_ID = __ENV.ROUND_ID || '1';
const TOKEN = __ENV.TOKEN || '';

export const options = {
  scenarios: {
    peak: {
      executor: 'ramping-vus',
      startVUs: 0,
      stages: [
        { duration: '30s', target: 1000 },   // palier 1 — validation
        { duration: '30s', target: 10000 },  // palier 2 — dimensionnement
        { duration: '30s', target: 100000 }, // palier 3 — cible V1 (100 000)
      ],
      gracefulStop: '10s',
    },
  },
  thresholds: {
    // À fixer à la première exécution (D-002 §7.1) — valeurs indicatives.
    http_req_duration: ['p(95)<500'],
    http_req_failed: ['rate<0.01'],
  },
};

export default function () {
  const url = `${BASE_URL}/api/v1/rounds/${ROUND_ID}/answer`;
  const payload = JSON.stringify({
    answer_index: Math.floor(Math.random() * 4), // I-4 : 4 propositions
    // Horodatage du geste (I-6), borné côté serveur — un client de charge
    // fournit un timestamp plausible (ni trop tôt, ni dans le futur).
    client_timestamp: Date.now() - Math.floor(Math.random() * 3000),
  });

  const res = http.post(url, payload, {
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      Authorization: `Bearer ${TOKEN}`,
    },
  });

  // Statuts acceptés : 200 (verdict), 409 (déjà répondu — unicité Redis),
  // 403 (rejet hors fenêtre/verrou — comportement nominal au pic).
  check(res, {
    'soumission traitée': (r) => r.status === 200 || r.status === 409 || r.status === 403,
  });
}
