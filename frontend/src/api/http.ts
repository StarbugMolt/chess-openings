/** Session CSRF is read immediately before each mutation; signing in rotates it. */
export async function csrfPost(
  path: string,
  body: object,
  read: (response: Response) => Promise<Record<string, unknown>>,
): Promise<Record<string, unknown>> {
  return read(await csrfRequest(path, body, read))
}

/** Keep confirmation credentials in a bounded JSON body, never in a URL. */
export async function csrfRequest(
  path: string,
  body: object,
  readCsrf: (response: Response) => Promise<Record<string, unknown>>,
  method: 'POST' | 'DELETE' = 'POST',
): Promise<Response> {
  const csrf = await readCsrf(
    await fetch('/api/auth/csrf', { credentials: 'same-origin', cache: 'no-store' }),
  )
  if (typeof csrf.csrfToken !== 'string') throw new Error('Refresh the page and try again.')
  return fetch(path, {
    method,
    credentials: 'same-origin',
    cache: 'no-store',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf.csrfToken },
    body: JSON.stringify(body),
  })
}
