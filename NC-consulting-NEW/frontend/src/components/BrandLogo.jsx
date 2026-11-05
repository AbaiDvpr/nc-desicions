export default function BrandLogo({ variant = 'light' }) {
  const src = variant === 'dark' ? '/logo-dark.svg' : '/logo.svg'
  return <img className="brand-logo" src={src} alt="NC-Desicion" width="135" height="30" />
}
