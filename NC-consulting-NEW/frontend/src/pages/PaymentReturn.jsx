import { useEffect, useState } from 'react'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import { api } from '../api.js'
import { useAuth } from '../auth/AuthContext.jsx'

export default function PaymentReturn() {
  const [params] = useSearchParams()
  const navigate = useNavigate()
  const { setUser } = useAuth()
  const [error, setError] = useState('')
  const invoiceId = params.get('invoice')

  useEffect(() => {
    let active = true
    if (!invoiceId) {
      setError('Счёт не найден')
      return undefined
    }

    const check = async () => {
      for (let attempt = 0; attempt < 5; attempt += 1) {
        try {
          const invoice = await api.syncInvoice(invoiceId)
          if (!active) return
          if (invoice.paid) {
            const fresh = await api.me()
            if (active) setUser(fresh)
            const tariff = invoice.service !== 'Пополнение счёта'
            navigate(tariff ? '/cabinet/tariff' : '/cabinet/balance', {
              replace: true,
              state: { notice: tariff ? `Тариф «${invoice.service}» подключён` : 'Баланс пополнен' },
            })
            return
          }
        } catch (err) {
          if (active) setError(err.message)
          return
        }
        await new Promise((resolve) => setTimeout(resolve, 1500))
      }
      if (active) setError('Оплата ещё не подтвердилась. Если деньги списались, откройте страницу через минуту.')
    }

    check()
    return () => {
      active = false
    }
  }, [invoiceId, navigate, setUser])

  return (
    <div className="page tariff-buy">
      <header className="page__header">
        <h1>Оплата</h1>
      </header>
      {error ? <div className="alert alert--error">{error}</div> : <p className="muted">Проверяем оплату…</p>}
      <p><Link to="/cabinet/tariff">К моему тарифу</Link></p>
    </div>
  )
}
