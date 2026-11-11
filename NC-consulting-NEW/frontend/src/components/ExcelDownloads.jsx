import { finModelFiles } from '../api.js'
import { ExcelIcon } from './FileIcons.jsx'

const VARIANTS = [['excel', 'Фин модель']]

export default function ExcelDownloads({ id, small = false }) {
  const files = finModelFiles(id)
  const className = small ? 'btn btn--small btn--excel' : 'btn btn--excel'
  return VARIANTS.map(([key, label]) => (
    <a key={key} href={files[key]} download className={className}>
      <ExcelIcon />
      {label}
    </a>
  ))
}
