import { defineStore } from 'pinia'
import { ref, shallowRef } from 'vue'
import {
  getCatalogueStatus,
  getOpenings,
  type Cached,
  type CataloguePage,
  type CatalogueQuery,
  type CatalogueStatus,
} from '../api/catalogue'

export const useCatalogueStore = defineStore('catalogue', () => {
  const status = shallowRef<CatalogueStatus>()
  const page = shallowRef<CataloguePage>()
  const loading = ref(false)
  const error = ref('')
  let statusCache: Cached<CatalogueStatus> | undefined
  const pages = new Map<string, Cached<CataloguePage>>()
  let generation = 0
  let controller: AbortController | undefined
  let statusGeneration = 0
  let sessionGeneration = 0

  /** Navigation/search boundary only. No timer or watcher can change an active lesson. */
  async function open(query: CatalogueQuery) {
    controller?.abort()
    controller = new AbortController()
    const signal = controller.signal
    const current = ++generation
    const statusRequest = ++statusGeneration
    const key = JSON.stringify(query)
    loading.value = true
    error.value = ''
    try {
      for (let attempt = 0; attempt < 2; attempt++) {
        const latest = await getCatalogueStatus(statusCache, signal)
        if (generation !== current) return
        if (latest.payload.catalogueRevision !== statusCache?.payload.catalogueRevision)
          pages.clear()
        if (statusRequest === statusGeneration) statusCache = latest
        const result = await getOpenings(query, pages.get(key), signal)
        if (generation !== current) return
        // An activation between the two HTTP requests needs a fresh consistent pair.
        if (result.payload.catalogueRevision !== latest.payload.catalogueRevision) {
          statusCache = undefined
          pages.clear()
          continue
        }
        pages.delete(key)
        pages.set(key, result)
        if (pages.size > 20) {
          const oldest = pages.keys().next().value
          if (oldest !== undefined) pages.delete(oldest)
        }
        if (statusRequest === statusGeneration) status.value = latest.payload
        page.value = result.payload
        return
      }
      throw new Error('Catalogue changed during navigation')
    } catch {
      if (generation === current && !signal.aborted) {
        error.value = 'The opening catalogue is unavailable. Please try again.'
      }
    } finally {
      if (generation === current) loading.value = false
    }
  }

  /** Capture once without cancelling a catalogue view request; retain for the whole session. */
  async function forNewSession(): Promise<Readonly<CatalogueStatus>> {
    const request = ++sessionGeneration
    const statusRequest = ++statusGeneration
    const latest = await getCatalogueStatus(statusCache)
    if (request === sessionGeneration && statusRequest === statusGeneration) {
      if (latest.payload.catalogueRevision !== statusCache?.payload.catalogueRevision) pages.clear()
      statusCache = latest
      status.value = latest.payload
    }
    return Object.freeze({ ...latest.payload })
  }

  return { status, page, loading, error, open, forNewSession }
})
