export default function UserAvatar({ user, className = '', src }) {
  const photo = src || user?.avatar_url
  const letter = (user?.full_name || '?').charAt(0).toUpperCase()
  if (photo) {
    return <img className={`avatar ${className}`} src={photo} alt="" />
  }
  return <span className={`avatar ${className}`}>{letter}</span>
}
