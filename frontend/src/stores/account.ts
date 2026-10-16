import { defineStore } from 'pinia'
import { ref } from 'vue'
import {
  accountAction,
  accountUser,
  currentUser,
  deleteAccount as deleteAccountRequest,
  type AccountUser,
} from '../api/account'

export const useAccountStore = defineStore('account', () => {
  const user = ref<AccountUser | null>(null)
  const unavailable = ref(false)
  const loaded = ref(false)
  let generation = 0
  let pending: Promise<AccountUser | null> | undefined

  function load(force = false): Promise<AccountUser | null> {
    if (pending) return pending
    if (loaded.value && !force) return Promise.resolve(user.value)
    const task = refresh()
    pending = task
    void task.finally(() => {
      if (pending === task) pending = undefined
    })
    return task
  }

  async function refresh(): Promise<AccountUser | null> {
    const request = ++generation
    try {
      const result = await currentUser()
      if (request === generation) {
        user.value = result
        unavailable.value = false
        loaded.value = true
      }
      return request === generation ? result : user.value
    } catch {
      if (request === generation) {
        user.value = null
        unavailable.value = true
        loaded.value = true
      }
      return user.value
    }
  }

  async function login(email: string, password: string, rememberMe = false): Promise<void> {
    ++generation
    pending = undefined
    const data = await accountAction('login', { email, password, rememberMe })
    user.value = accountUser(data.user)
    unavailable.value = false
    loaded.value = true
  }

  async function logout(): Promise<void> {
    await accountAction('logout', {})
    ++generation
    user.value = null
    unavailable.value = false
    loaded.value = true
  }

  async function deleteAccount(password: string): Promise<void> {
    await deleteAccountRequest(password)
    ++generation
    pending = undefined
    user.value = null
    unavailable.value = false
    loaded.value = true
  }

  return { user, unavailable, loaded, load, login, logout, deleteAccount }
})
