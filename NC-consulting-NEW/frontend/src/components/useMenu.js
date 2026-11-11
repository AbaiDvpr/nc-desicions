import { useEffect, useRef, useState } from 'react'

export function useMenu() {
  const [open, setOpen] = useState(false)
  const [placement, setPlacement] = useState('bottom')
  const rootRef = useRef(null)

  useEffect(() => {
    if (!open) return undefined
    const onPointer = (event) => {
      if (!rootRef.current?.contains(event.target)) setOpen(false)
    }
    const onKey = (event) => {
      if (event.key === 'Escape') setOpen(false)
    }
    document.addEventListener('mousedown', onPointer)
    document.addEventListener('keydown', onKey)
    return () => {
      document.removeEventListener('mousedown', onPointer)
      document.removeEventListener('keydown', onKey)
    }
  }, [open])

  const toggle = () => {
    const rect = rootRef.current?.getBoundingClientRect()
    if (rect) {
      const below = window.innerHeight - rect.bottom
      setPlacement(below < 320 && rect.top > below ? 'top' : 'bottom')
    }
    setOpen((current) => !current)
  }

  const close = () => setOpen(false)

  return { open, placement, rootRef, toggle, close }
}
