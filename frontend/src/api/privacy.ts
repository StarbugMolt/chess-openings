export interface PrivacyNotice {
  version: 1
  publisher: string
  contactEmail: string
  emailProvider: string
  hostingProvider: 'o2switch'
  hostingBackupDays: number
  deploymentBackupDays: number
  applicationLogDays: number
  unverifiedAccountDays: number
  development: boolean
  configured: boolean
  missing: ('publisher' | 'contactEmail' | 'emailProvider')[]
}

export function privacyNotice(value: unknown): PrivacyNotice {
  if (!value || typeof value !== 'object' || Array.isArray(value))
    throw new Error('Invalid privacy notice.')
  const data = value as Record<string, unknown>
  if (
    data.version !== 1 ||
    typeof data.publisher !== 'string' ||
    data.publisher.length > 200 ||
    typeof data.contactEmail !== 'string' ||
    data.contactEmail.length > 254 ||
    (data.contactEmail !== '' && !/^[^\s<>@]+@[^\s<>@]+\.[^\s<>@]+$/.test(data.contactEmail)) ||
    typeof data.emailProvider !== 'string' ||
    data.emailProvider.length > 300 ||
    data.emailProvider.includes('://') ||
    data.hostingProvider !== 'o2switch' ||
    typeof data.development !== 'boolean' ||
    typeof data.configured !== 'boolean' ||
    !Array.isArray(data.missing) ||
    new Set(data.missing).size !== data.missing.length ||
    data.missing.some((key) => !['publisher', 'contactEmail', 'emailProvider'].includes(key))
  )
    throw new Error('Invalid privacy notice.')
  for (const key of [
    'hostingBackupDays',
    'deploymentBackupDays',
    'applicationLogDays',
    'unverifiedAccountDays',
  ]) {
    const days = data[key]
    if (
      typeof days !== 'number' ||
      !Number.isSafeInteger(days) ||
      days < (key === 'unverifiedAccountDays' ? 0 : 1) ||
      days > 3650
    )
      throw new Error('Invalid privacy retention period.')
  }
  const missing = ['publisher', 'contactEmail', 'emailProvider'].filter((key) => data[key] === '')
  if (
    missing.length !== data.missing.length ||
    missing.some((key) => !(data.missing as unknown[]).includes(key)) ||
    data.configured !== (missing.length === 0)
  )
    throw new Error('Inconsistent privacy configuration.')
  return {
    version: 1,
    publisher: data.publisher,
    contactEmail: data.contactEmail,
    emailProvider: data.emailProvider,
    hostingProvider: 'o2switch',
    hostingBackupDays: data.hostingBackupDays as number,
    deploymentBackupDays: data.deploymentBackupDays as number,
    applicationLogDays: data.applicationLogDays as number,
    unverifiedAccountDays: data.unverifiedAccountDays as number,
    development: data.development,
    configured: data.configured,
    missing: data.missing as PrivacyNotice['missing'],
  }
}

export async function getPrivacyNotice(signal?: AbortSignal): Promise<PrivacyNotice> {
  const response = await fetch('/api/privacy', {
    credentials: 'omit',
    cache: 'no-store',
    ...(signal ? { signal } : {}),
  })
  if (!response.ok) throw new Error('Privacy details are temporarily unavailable.')
  return privacyNotice(await response.json())
}
