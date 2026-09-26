import { BrowserRouter, Route, Routes } from 'react-router'
import HomePage from './pages/HomePage'
import HostConsolePage from './pages/HostConsolePage'
import QuestionAgentPage from './pages/QuestionAgentPage'

/**
 * Racine de l'application : deux consoles de direct + une page de sélection.
 *
 * Aucune route d'API n'existe encore (docs/10-contrat-api-console-animateur.md
 * : « AUCUNE ROUTE N'EST DÉFINIE »). Ces routes sont des routes de navigation
 * locales uniquement — elles n'appellent rien.
 */
export default function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/" element={<HomePage />} />
        <Route path="/animateur" element={<HostConsolePage />} />
        <Route path="/agent-questions" element={<QuestionAgentPage />} />
      </Routes>
    </BrowserRouter>
  )
}
