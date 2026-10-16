import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import HomeView from './views/HomeView.vue'
import NotFoundView from './views/NotFoundView.vue'
import { useAccountStore } from './stores/account'
import { nextTick } from 'vue'

const routes: RouteRecordRaw[] = [{ path: '/', name: 'home', component: HomeView }]
routes.push({ path: '/learn', name: 'learn', component: () => import('./views/LearnView.vue') })
routes.push({
  path: '/learn/:id',
  name: 'guided-lesson',
  component: () => import('./views/GuidedLessonView.vue'),
  meta: { requiresAuth: true },
})
routes.push({
  path: '/openings',
  name: 'catalogue',
  component: () => import('./views/CatalogueView.vue'),
})
routes.push({
  path: '/train/:id',
  name: 'variation-training',
  component: () => import('./views/VariationTrainingView.vue'),
  meta: { requiresAuth: true },
})

for (const name of [
  'login',
  'register',
  'forgot-password',
  'resend-verification',
  'verify-email',
  'reset-password',
]) {
  routes.push({ path: `/${name}`, name, component: () => import('./views/AuthView.vue') })
}
routes.push({
  path: '/privacy',
  name: 'privacy',
  component: () => import('./views/PrivacyView.vue'),
})
routes.push({
  path: '/account',
  name: 'account',
  component: () => import('./views/AccountView.vue'),
  meta: { requiresAuth: true },
})
routes.push({
  path: '/admin',
  name: 'admin',
  component: () => import('./views/AdminView.vue'),
  // Display gate only: every /api/admin/* endpoint independently requires ROLE_ADMIN.
  meta: { requiresAuth: true, requiresAdmin: true },
})
routes.push({
  path: '/progress',
  name: 'practice-progress',
  component: () => import('./views/ProgressView.vue'),
  meta: { requiresAuth: true },
})

if (import.meta.env.DEV) {
  routes.push({
    path: '/dev/board',
    name: 'board-lab',
    component: () => import('./views/BoardLabView.vue'),
  })
}

routes.push({ path: '/:pathMatch(.*)*', name: 'not-found', component: NotFoundView })

const titles: Record<string, string> = {
  home: 'Learn chess openings',
  learn: 'Guided lessons',
  'guided-lesson': 'Opening lesson',
  'variation-training': 'Variation training',
  catalogue: 'Opening reference',
  login: 'Sign in',
  register: 'Create an account',
  'forgot-password': 'Reset your password',
  'resend-verification': 'Resend verification email',
  'verify-email': 'Verify your email',
  'reset-password': 'Choose a new password',
  account: 'Your account',
  'practice-progress': 'Your practice',
  privacy: 'Account privacy',
  admin: 'Admin',
  'board-lab': 'Developer board',
  'not-found': 'Page not found',
}
for (const route of routes)
  route.meta = { ...route.meta, title: titles[String(route.name)] ?? 'Chess Openings' }

export const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior: (to, from, saved) => {
    if (saved) return saved
    if (to.hash) return { el: to.hash }
    // Catalogue controls retain focus and scroll while changing their query.
    if (to.name === 'catalogue' && from.name === 'catalogue') return false
    if (to.name === 'admin' && from.name === 'admin') return false
    return { top: 0 }
  },
})

router.beforeEach(async (to, from) => {
  const account = useAccountStore()
  if (to.meta.requiresAuth) {
    const hadUser = !!account.user
    if (!(await account.load(true)))
      return {
        name: 'login',
        query: {
          next: to.fullPath,
          ...(account.unavailable
            ? { session: 'unavailable' }
            : hadUser && from.name === 'login'
              ? { session: 'cookies' }
              : {}),
        },
      }
    // The guard reloaded /api/me above, so a revoked admin is redirected on navigation.
    if (to.meta.requiresAdmin && !account.user?.admin) return { name: 'learn' }
  }
  if (['login', 'register'].includes(String(to.name)) && (await account.load()))
    return { name: 'learn' }
  return true
})

router.afterEach(async (to, from, failure) => {
  if (failure) return
  document.title = `${String(to.meta.title)} · Chess Openings`
  if (to.path === from.path) return
  await nextTick()
  const main = document.getElementById('main-content')
  main?.setAttribute('tabindex', '-1')
  main?.focus({ preventScroll: true })
})

// An open tab can reference a lazy chunk from an older release. Reload at most once
// for that destination; deployments also retain previous asset files.
router.onError((error, to) => {
  if (
    !/Failed to fetch dynamically imported module|Importing a module script failed/.test(
      String(error),
    )
  )
    return
  const key = `chunk-retry:${to.fullPath}`
  try {
    if (!sessionStorage.getItem(key)) {
      sessionStorage.setItem(key, '1')
      location.assign(to.fullPath)
    }
  } catch {
    /* Storage may be disabled; keep the current page. */
  }
})
