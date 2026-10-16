import { csrfPost, csrfRequest } from './http'

export interface AccountUser {
  id: string
  email: string
  verified: boolean
  createdAt: string
  /** Display hint only; the server enforces ROLE_ADMIN on every admin endpoint. */
  admin: boolean
}

export class AccountError extends Error {
  constructor(
    message: string,
    readonly status: number,
    readonly retryAfter?: number,
  ) {
    super(message)
  }
}

function object(value: unknown): Record<string, unknown> {
  if (!value || typeof value !== 'object' || Array.isArray(value)) {
    throw new Error('The account service returned an unexpected response.')
  }
  return value as Record<string, unknown>
}

async function responseData(response: Response): Promise<Record<string, unknown>> {
  let data: Record<string, unknown>
  try {
    data = object(await response.json())
  } catch {
    throw new AccountError('The account service is unavailable. Please try again.', response.status)
  }
  if (!response.ok) {
    throw new AccountError(
      typeof data.error === 'string' ? data.error : 'Please try again.',
      response.status,
      response.status === 429 ? retryAfter(response.headers.get('Retry-After')) : undefined,
    )
  }
  return data
}

export function retryAfter(value: string | null): number | undefined {
  if (!value) return undefined
  const seconds = /^\d+$/.test(value) ? Number(value) : (Date.parse(value) - Date.now()) / 1000
  return Number.isFinite(seconds) && seconds > 0 ? Math.ceil(seconds) : undefined
}

export function accountUser(value: unknown): AccountUser {
  const user = object(value)
  if (
    typeof user.id !== 'string' ||
    !/^[1-9]\d*$/.test(user.id) ||
    typeof user.email !== 'string' ||
    typeof user.verified !== 'boolean' ||
    typeof user.createdAt !== 'string' ||
    typeof user.admin !== 'boolean'
  ) {
    throw new Error('The account service returned an unexpected user.')
  }
  return {
    id: user.id,
    email: user.email,
    verified: user.verified,
    createdAt: user.createdAt,
    admin: user.admin,
  }
}

export async function currentUser(): Promise<AccountUser | null> {
  const response = await fetch('/api/me', { credentials: 'same-origin', cache: 'no-store' })
  if (response.status === 401) return null
  return accountUser((await responseData(response)).user)
}

export type AccountAction =
  | 'login'
  | 'logout'
  | 'register'
  | 'request-reset'
  | 'request-verification'
  | 'verify-email'
  | 'reset-password'

export async function accountAction(
  action: AccountAction,
  body: Record<string, string | boolean>,
): Promise<Record<string, unknown>> {
  return csrfPost(`/api/auth/${action}`, body, responseData)
}

export async function exportAccountData(password: string): Promise<Blob> {
  const response = await csrfRequest('/api/me/data-export', { password }, responseData)
  if (!response.ok) await responseData(response)
  if (!/^application\/json(?:;|$)/i.test(response.headers.get('Content-Type') ?? '')) {
    throw new AccountError('The download is unavailable. Please try again.', response.status)
  }
  return response.blob()
}

export async function deleteAccount(password: string): Promise<void> {
  const data = await responseData(
    await csrfRequest('/api/me', { password }, responseData, 'DELETE'),
  )
  if (data.deleted !== true) {
    throw new AccountError('Could not confirm account deletion. Please try again.', 502)
  }
}
