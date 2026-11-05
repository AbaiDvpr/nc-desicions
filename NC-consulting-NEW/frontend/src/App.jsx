import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom'
import { AuthProvider } from './auth/AuthContext.jsx'
import { ProtectedRoute, PublicOnlyRoute } from './auth/guards.jsx'
import CabinetLayout from './layouts/CabinetLayout.jsx'
import About from './pages/About.jsx'
import Companies from './pages/Companies.jsx'
import CompanyCreate from './pages/CompanyCreate.jsx'
import CompanySection from './pages/CompanySection.jsx'
import CompanySettings from './pages/CompanySettings.jsx'
import CompanyView from './pages/CompanyView.jsx'
import Counterparties from './pages/Counterparties.jsx'
import CounterpartyForm from './pages/CounterpartyForm.jsx'
import Balance from './pages/Balance.jsx'
import PaymentReturn from './pages/PaymentReturn.jsx'
import FinModelCreate from './pages/FinModelCreate.jsx'
import FinModels from './pages/FinModels.jsx'
import Home from './pages/Home.jsx'
import Login from './pages/Login.jsx'
import Profile from './pages/Profile.jsx'
import Register from './pages/Register.jsx'
import Tariff from './pages/Tariff.jsx'
import TariffBuy from './pages/TariffBuy.jsx'
import Tariffs from './pages/Tariffs.jsx'

function App() {
  return (
    <AuthProvider>
      <BrowserRouter>
        <Routes>
          <Route element={<PublicOnlyRoute />}>
            <Route path="/login" element={<Login />} />
            <Route path="/register" element={<Register />} />
          </Route>

          <Route element={<ProtectedRoute />}>
            <Route path="/cabinet" element={<CabinetLayout />}>
              <Route index element={<Home />} />
              <Route path="profile" element={<Profile />} />
              <Route path="about" element={<About />} />
              <Route path="tariff" element={<Tariff />} />
              <Route path="tariffs" element={<Tariffs />} />
              <Route path="tariffs/:id" element={<TariffBuy />} />
              <Route path="balance" element={<Balance />} />
              <Route path="payment/return" element={<PaymentReturn />} />
              <Route path="companies" element={<Companies />} />
              <Route path="companies/new" element={<CompanyCreate />} />
              <Route path="companies/:id" element={<CompanyView />} />
              <Route path="companies/:id/profile" element={<CompanySettings />} />
              <Route path="companies/:id/:section" element={<CompanySection />} />
              <Route path="counterparties" element={<Counterparties />} />
              <Route path="counterparties/new" element={<CounterpartyForm />} />
              <Route path="counterparties/:id" element={<CounterpartyForm />} />
              <Route path="models" element={<FinModels />} />
              <Route path="models/new" element={<FinModelCreate />} />
            </Route>
          </Route>

          <Route path="*" element={<Navigate to="/cabinet" replace />} />
        </Routes>
      </BrowserRouter>
    </AuthProvider>
  )
}

export default App
