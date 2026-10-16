import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { initializeLocale } from './i18n'
import App from './App.vue'
import { router } from './router'
import './style.css'

initializeLocale()

createApp(App).use(createPinia()).use(router).mount('#app')
