import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vitest/config'
import { writeFileSync } from 'node:fs'
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
        const source = dependencyNotices(modules)
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
