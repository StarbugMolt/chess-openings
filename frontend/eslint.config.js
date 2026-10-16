import js from '@eslint/js'
import vue from 'eslint-plugin-vue'
import globals from 'globals'
import tseslint from 'typescript-eslint'
import prettier from 'eslint-config-prettier'

export default tseslint.config(
  {
    ignores: [
      'dist/**',
      'node_modules/**',
      'playwright-report/**',
      'test-results/**',
      '.test-artifacts/**',
    ],
  },
  js.configs.recommended,
  ...tseslint.configs.recommended,
  ...vue.configs['flat/recommended'],
  {
    languageOptions: {
      parserOptions: { parser: tseslint.parser, extraFileExtensions: ['.vue'] },
    },
    rules: { 'vue/multi-word-component-names': 'off' },
  },
  { files: ['src/**/*.{ts,vue}'], languageOptions: { globals: globals.browser } },
  {
    files: ['*.{js,ts}', 'scripts/**', 'tests/**'],
    languageOptions: { globals: { ...globals.node, ...globals.browser } },
  },
  prettier,
)
