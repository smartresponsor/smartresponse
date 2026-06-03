/* Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
 * Interfacing provider baseline tokens.
 *
 * This file is intentionally static and safe to load from every Interfacing
 * document shell. Ant Design ProComponents and PrimeReact runtime adapters can
 * consume the same neutral values without requiring a React mount before the
 * base shell is styled.
 */
window.InterfacingProviderDesignBaseline = Object.freeze({
  version: '2026.05.provider-neutral.2',
  antDesign: Object.freeze({
    token: Object.freeze({
      fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji"',
      fontFamilyCode: 'SFMono-Regular, Consolas, "Liberation Mono", Menlo, Courier, monospace',
      fontSize: 14,
      fontSizeSM: 12,
      fontSizeLG: 16,
      lineHeight: 1.5714285714,
      fontWeightStrong: 600,
      borderRadius: 8,
      borderRadiusSM: 6,
      borderRadiusLG: 10,
      paddingXXS: 4,
      paddingXS: 8,
      paddingSM: 12,
      padding: 16,
      paddingLG: 24,
      marginXXS: 4,
      marginXS: 8,
      marginSM: 12,
      margin: 16,
      marginLG: 24,
      controlHeight: 32,
      controlHeightLG: 36,
      colorPrimary: '#2563eb'
    })
  }),
  primeReact: Object.freeze({
    cssVariables: Object.freeze({
      '--font-family': 'var(--interfacing-provider-font-family)',
      '--font-size': 'var(--interfacing-provider-font-size)',
      '--text-color': 'var(--interfacing-provider-text-color)',
      '--text-color-secondary': 'var(--interfacing-provider-text-muted)',
      '--primary-color': 'var(--interfacing-provider-primary)',
      '--primary-color-text': '#ffffff',
      '--surface-ground': 'var(--interfacing-provider-bg)',
      '--surface-card': 'var(--interfacing-provider-surface)',
      '--surface-border': 'var(--interfacing-provider-border)',
      '--inline-spacing': 'var(--interfacing-provider-gap-sm)',
      '--border-radius': 'var(--interfacing-provider-radius)',
      '--focus-ring': '0 0 0 3px rgba(37, 99, 235, 0.16)'
    })
  })
});
