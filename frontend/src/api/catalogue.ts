export interface CatalogueStatus {
  catalogueRevision: number
  sourceSha: string | null
  updatedAt: string | null
  entryCount: number
}

export interface OpeningSummary {
  id: string
  sourceId: string
  eco: string
  name: string
  sequenceCount?: number
  sequences?: { pgn: string; eco: string }[]
}

export interface CataloguePage {
  catalogueRevision: number
  items: OpeningSummary[]
  page: number
  limit: number
  total: number
}

export interface Cached<T> {
  payload: T
  etag: string | null
}

export interface CatalogueQuery {
  search: string
  eco: string
  page: number
}

function record(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null
}
function count(value: unknown): value is number {
  return typeof value === 'number' && Number.isSafeInteger(value) && value >= 0
}
function status(value: unknown): value is CatalogueStatus {
  return (
    record(value) &&
    count(value.catalogueRevision) &&
    count(value.entryCount) &&
    (value.sourceSha === null ||
      (typeof value.sourceSha === 'string' && /^[a-f0-9]{40}$/.test(value.sourceSha))) &&
    (value.updatedAt === null || typeof value.updatedAt === 'string')
  )
}
function page(value: unknown): value is CataloguePage {
  return (
    record(value) &&
    count(value.catalogueRevision) &&
    count(value.total) &&
    count(value.page) &&
    value.page > 0 &&
    count(value.limit) &&
    value.limit > 0 &&
    value.limit <= 50 &&
    Array.isArray(value.items) &&
    value.items.length <= value.limit &&
    value.items.every(
      (item: unknown) =>
        record(item) &&
        typeof item.id === 'string' &&
        /^[1-9][0-9]*$/.test(item.id) &&
        typeof item.sourceId === 'string' &&
        /^[a-f0-9]{64}$/.test(item.sourceId) &&
        typeof item.eco === 'string' &&
        /^[A-E][0-9]{2}$/.test(item.eco) &&
        typeof item.name === 'string' &&
        (item.sequenceCount === undefined ||
          (count(item.sequenceCount) && item.sequenceCount > 0)) &&
        (item.sequences === undefined ||
          (Array.isArray(item.sequences) &&
            item.sequences.length <= 3 &&
            item.sequences.every(
              (sequence: unknown) =>
                record(sequence) &&
                typeof sequence.pgn === 'string' &&
                typeof sequence.eco === 'string',
            ))),
    )
  )
}

async function request<T>(
  url: string,
  validate: (value: unknown) => value is T,
  cached?: Cached<T>,
  signal?: AbortSignal,
): Promise<Cached<T>> {
  const headers: Record<string, string> = { Accept: 'application/json' }
  if (cached?.etag) headers['If-None-Match'] = cached.etag
  const response = await fetch(url, {
    headers,
    credentials: 'omit',
    cache: 'no-cache',
    ...(signal ? { signal } : {}),
  })
  if (response.status === 304 && cached) return cached
  if (!response.ok) throw new Error('Catalogue request failed')
  const payload: unknown = await response.json()
  if (!validate(payload)) throw new Error('Invalid catalogue response')
  return { payload, etag: response.headers.get('ETag') }
}

export function getCatalogueStatus(cached?: Cached<CatalogueStatus>, signal?: AbortSignal) {
  return request('/api/openings/catalogue-status', status, cached, signal)
}

export function getOpenings(
  query: CatalogueQuery,
  cached?: Cached<CataloguePage>,
  signal?: AbortSignal,
) {
  const params = new URLSearchParams({
    search: query.search,
    eco: query.eco,
    page: String(query.page),
    limit: '20',
  })
  return request(`/api/openings?${params}`, page, cached, signal)
}
