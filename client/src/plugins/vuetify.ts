// vuetify.ts
import 'vuetify/styles'
import '@mdi/font/css/materialdesignicons.css'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import * as labs from 'vuetify/labs/components'

export const vuetify = createVuetify({
  components: {
    ...components,
    ...labs,
  },
  directives,
  icons: {
    defaultSet: 'mdi',
  },
  defaults: {
    global: {
      ripple: false,
    },
    VSheet: {
      elevation: 0,
    },
    VSelect: {
      variant: 'outlined',
      menuIcon: 'mdi-chevron-down',
    },
  },
  theme: {
    defaultTheme: 'light',
    themes: {
      light: {
        colors: {
          primary: '#333333',
          secondary: '#4CAF50',
          accent: '#8BC34A',
          error: '#F44336',
          warning: '#FF9800',
          info: '#2196F3',
          success: '#4CAF50',
          surface: '#FFFFFF',
          background: '#F5F5F5',
          'on-surface': '#333333',
        },
      },
    },
  },
})

export default vuetify