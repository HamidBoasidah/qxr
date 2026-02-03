export type Direction = 'ltr' | 'rtl'

const STORAGE_KEY = 'direction'

export function getSavedDirection(): Direction {
  if (typeof window === 'undefined') return 'ltr'
  const saved = localStorage.getItem(STORAGE_KEY)
  return (saved === 'rtl' || saved === 'ltr') ? (saved as Direction) : 'ltr'
}

export function applyDirection(dir: Direction) {
  if (typeof document === 'undefined') return
  document.documentElement.setAttribute('dir', dir)
  // Also toggle a helper class for any custom CSS using `.rtl ...`
  if (dir === 'rtl') {
    document.documentElement.classList.add('rtl')
  } else {
    document.documentElement.classList.remove('rtl')
  }
}

export function toggleDirection(): Direction {
  const current = getSavedDirection()
  const next: Direction = current === 'rtl' ? 'ltr' : 'rtl'
  localStorage.setItem(STORAGE_KEY, next)
  applyDirection(next)
  return next
}

export function getCurrentDirection(): Direction {
  if (typeof document === 'undefined') return 'ltr'
  const attr = document.documentElement.getAttribute('dir') as Direction | null
  return attr === 'rtl' ? 'rtl' : 'ltr'
}
