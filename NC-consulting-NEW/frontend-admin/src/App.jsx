import { useEffect, useState } from 'react'
import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom'
import { api } from './api.js'
import AdminLayout from './layouts/AdminLayout.jsx'
import BpJobs from './pages/BpJobs.jsx'
import BpTemplates from './pages/BpTemplates.jsx'
import Companies from './pages/Companies.jsx'
import FemRates from './pages/FemRates.jsx'
import GovPrograms from './pages/GovPrograms.jsx'
import Home from './pages/Home.jsx'
import Login from './pages/Login.jsx'
import Models from './pages/Models.jsx'
import Refills from './pages/Refills.jsx'
import Tariffs from './pages/Tariffs.jsx'
import TaskBoard from './pages/TaskBoard.jsx'
import UserView from './pages/UserView.jsx'
import Users from './pages/Users.jsx'

export default function App() {
  const [admin, setAdmin] = useState(undefined)

  useEffect(() => {
    api.me().then(setAdmin).catch(() => setAdmin(null))
  }, [])

  if (admin === undefined) return <div className="boot">Загрузка…</div>

  return (
    <BrowserRouter>
      <Routes>
        <Route path="/login" element={admin ? <Navigate to="/" replace /> : <Login onLogin={setAdmin} />} />
        <Route element={admin ? <AdminLayout admin={admin} onLogout={() => setAdmin(null)} /> : <Navigate to="/login" replace />}>
          <Route index element={<Home />} />
          <Route path="tasks" element={<TaskBoard />} />
          <Route path="users" element={<Users />} />
          <Route path="users/:id" element={<UserView />} />
          <Route path="models" element={<Models />} />
          <Route path="companies" element={<Companies />} />
          <Route path="tariffs" element={<Tariffs />} />
          <Route path="refills" element={<Refills />} />
          <Route path="bp" element={<BpTemplates />} />
          <Route path="bp/jobs" element={<BpJobs />} />
          <Route path="fem-rates" element={<FemRates />} />
          <Route path="gov-programs" element={<GovPrograms />} />
        </Route>
        <Route path="*" element={<Navigate to="/" replace />} />
      </Routes>
    </BrowserRouter>
  )
}
