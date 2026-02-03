import axios from 'axios';
import { i18n } from '@/i18n';
import { route } from 'ziggy-js';

export async function switchLocale(l: 'ar' | 'en') {
  // Update DOM immediately
  document.documentElement.lang = l;
  document.documentElement.dir = l === 'ar' ? 'rtl' : 'ltr';
  
  // Update i18n
  i18n.global.locale.value = l;
  
  // Update localStorage for consistency
  if (typeof localStorage !== 'undefined') {
    localStorage.setItem('locale', l);
    localStorage.setItem('direction', l === 'ar' ? 'rtl' : 'ltr');
  }
  
  // Save to backend using axios (better for non-page-changing requests)
  try {
    const response = await axios.post(route('locale.set'), { locale: l });
    console.log('Locale updated successfully:', l);
    return response;
  } catch (error) {
    console.error('Failed to save locale to backend:', error);
    throw error;
  }
}