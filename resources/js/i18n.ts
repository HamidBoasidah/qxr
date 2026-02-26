import { createI18n } from 'vue-i18n';
import en from '@/locales/en.json';
import ar from '@/locales/ar.json';
import { getSavedDirection } from '@/utils/direction';

// Get initial locale from Inertia shared data if available, otherwise from localStorage
function getInitialLocale(): string {
  // Check if we have Inertia page data with locale
  if (typeof window !== 'undefined' && window.page?.props?.locale) {
    return window.page.props.locale;
  }

  // Fallback to localStorage direction mapping, default to Arabic
  const dir = getSavedDirection();
  return dir === 'ltr' ? 'en' : 'ar';
}

const initialLocale = getInitialLocale();

export const i18n = createI18n({
  legacy: false,
  locale: initialLocale,
  fallbackLocale: 'ar',
  messages: { en, ar },
});

export function setHtmlLang(locale: string) {
  if (typeof document !== 'undefined') {
    document.documentElement.setAttribute('lang', locale);
  }
}
