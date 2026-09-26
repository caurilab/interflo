import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
// i18n initialisé AVANT le rendu : aucun texte en dur, fr par défaut (PO §2)
import './i18n'
import './index.css'
import './styles/main.scss'
import App from './App.tsx'

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <App />
  </StrictMode>,
)
