const MAX_EDGE = 512
const JPEG_QUALITY = 0.82

export async function compressImage(file, { maxEdge = MAX_EDGE, quality = JPEG_QUALITY, name = 'avatar.jpg' } = {}) {
  if (!file.type.startsWith('image/')) {
    throw new Error('Можно загрузить только изображение')
  }

  let bitmap
  try {
    bitmap = await createImageBitmap(file)
  } catch {
    throw new Error('Этот формат фото не поддерживается')
  }

  const scale = Math.min(1, maxEdge / Math.max(bitmap.width, bitmap.height))
  const width = Math.max(1, Math.round(bitmap.width * scale))
  const height = Math.max(1, Math.round(bitmap.height * scale))
  const canvas = document.createElement('canvas')
  canvas.width = width
  canvas.height = height
  const context = canvas.getContext('2d')
  context.fillStyle = '#fff'
  context.fillRect(0, 0, width, height)
  context.drawImage(bitmap, 0, 0, width, height)
  bitmap.close()

  const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', quality))
  if (!blob) throw new Error('Не удалось сжать фото')
  return new File([blob], name, { type: 'image/jpeg' })
}
