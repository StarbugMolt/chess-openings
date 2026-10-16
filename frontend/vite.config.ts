import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vitest/config'
import { readFileSync, writeFileSync } from 'node:fs'
import { dependencyNotices } from './scripts/dependencyNotices'

export default defineConfig({
  plugins: [
    vue(),
    {
      name: 'production-dependency-notices',
      apply: 'build',
      generateBundle(_options, bundle) {
        const modules = Object.values(bundle).flatMap((chunk) =>
          chunk.type === 'chunk'
            ? Object.entries(chunk.modules)
                .filter(([, module]) => module.renderedLength > 0)
                .map(([id]) => id)
            : [],
        )
        // The self-hosted font is a CSS asset rather than a module, so append its licence.
        const font = readFileSync(
          new URL('./src/assets/fonts/Archivo-OFL.txt', import.meta.url),
          'utf8',
        )
        const source = `${dependencyNotices(modules)}\n\n--------------------\n\nArchivo variable font, latin and latin-ext subsets from @fontsource-variable/archivo 5.3.0 (OFL-1.1)\n\n${font}`
        this.emitFile({ type: 'asset', fileName: 'third-party-notices.txt', source })
        // Also make the last verified build's notices reachable from the local Vite preview.
        writeFileSync(new URL('./public/third-party-notices.txt', import.meta.url), source)
      },
    },
  ],
  server: {
    allowedHosts: ['frontend', ...(process.env.BROWSER_HOST ? [process.env.BROWSER_HOST] : [])],
    port: 5173,
    strictPort: true,
    watch: { awaitWriteFinish: { stabilityThreshold: 150, pollInterval: 25 } },
    proxy: { '/api': process.env.API_PROXY_TARGET ?? 'http://127.0.0.1:8080' },
  },
  build: { sourcemap: false },
  test: { include: ['tests/unit/**/*.test.ts'] },
})
