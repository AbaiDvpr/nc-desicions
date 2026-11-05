const MAX_EDGE = 512
const JPEG_QUALITY = 0.82

export async function compressImage(file) {
  if (!file.type.startsWith('image/')) {
    throw new Error('Можно загрузить только изображение')
  }

  let bitmap
  try {
    bitmap = await createImageBitmap(file)
  } catch {
    throw new Error('Этот формат фото не поддерживается')
  }

  const scale = Math.min(1, MAX_EDGE / Math.max(bitmap.width, bitmap.height))
  const width = Math.max(1, Math.round(bitmap.width * scale))
  const height = Math.max(1, Math.round(bitmap.height * scale))
  const canvas = document.createElement('canvas')
  canvas.width = width
  canvas.height = height
  canvas.getContext('2d').drawImage(bitmap, 0, 0, width, height)
  bitmap.close()

  const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', JPEG_QUALITY))
  if (!blob) throw new Error('Не удалось сжать фото')
  return new File([blob], 'avatar.jpg', { type: 'image/jpeg' })
}
