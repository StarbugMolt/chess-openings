import { readFileSync, readdirSync } from 'node:fs'
import { join } from 'node:path'

/** Derive notices from rendered production modules, including transitive Vue runtime packages. */
export function dependencyNotices(moduleIds: Iterable<string>): string {
  const packages = new Map<string, string>()
  for (const id of moduleIds) {
    const separator = '/node_modules/'
    const index = id.lastIndexOf(separator)
    if (index < 0) continue
    const relative = id.slice(index + separator.length)
    const name = relative.match(/^((?:@[^/]+\/)?[^/]+)/)?.[1]
    if (name) packages.set(name, id.slice(0, index + separator.length) + name)
  }
  if (!packages.size) throw new Error('No bundled dependencies found for licence notices.')
  const notices = [...packages]
    .sort(([a], [b]) => a.localeCompare(b))
    .map(([name, directory]) => {
      const metadata = JSON.parse(readFileSync(join(directory, 'package.json'), 'utf8')) as {
        version: string
        license: string
      }
      const licence = readdirSync(directory).find((file) => /^licen[cs]e(?:\..*)?$/i.test(file))
      if (!licence) throw new Error(`Missing licence text for bundled package ${name}`)
      return `${name} ${metadata.version} (${metadata.license})\n\n${readFileSync(join(directory, licence), 'utf8')}`
    })
  return `Chess Openings production dependency notices\nProject source: https://github.com/Starbugstone/chess-openings\n\n${notices.join('\n\n--------------------\n\n')}`
}
