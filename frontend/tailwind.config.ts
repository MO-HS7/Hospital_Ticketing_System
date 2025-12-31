import type { Config } from 'tailwindcss'
import plugin from 'tailwindcss/plugin'

export default {
    content: [
        './components/**/*.{js,vue,ts}',
        './layouts/**/*.vue',
        './pages/**/*.vue',
        './plugins/**/*.{js,ts}',
        './app.vue',
        './error.vue',
    ],
    darkMode: 'class',
    theme: {
        extend: {
            // Font families
            fontFamily: {
                sans: ['Inter', 'Cairo', 'system-ui', 'sans-serif'],
                arabic: ['Cairo', 'Inter', 'system-ui', 'sans-serif'],
            },
            // Color palette (light/dark aware)
            colors: {
                primary: {
                    50: '#eff6ff',
                    100: '#dbeafe',
                    200: '#bfdbfe',
                    300: '#93c5fd',
                    400: '#60a5fa',
                    500: '#3b82f6',
                    600: '#2563eb',
                    700: '#1d4ed8',
                    800: '#1e40af',
                    900: '#1e3a8a',
                    950: '#172554',
                },
                surface: {
                    light: '#ffffff',
                    dark: '#0f172a',
                },
                background: {
                    light: '#f8fafc',
                    dark: '#1e293b',
                },
            },
            // Spacing scale
            spacing: {
                '18': '4.5rem',
                '22': '5.5rem',
            },
            // Border radius
            borderRadius: {
                'xl': '1rem',
                '2xl': '1.5rem',
                '3xl': '2rem',
            },
            // Box shadows
            boxShadow: {
                'soft': '0 2px 15px -3px rgba(0, 0, 0, 0.07), 0 10px 20px -2px rgba(0, 0, 0, 0.04)',
                'card': '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1)',
                'elevated': '0 10px 40px -10px rgba(0, 0, 0, 0.15)',
            },
            // Animation
            animation: {
                'fade-in': 'fadeIn 0.3s ease-out',
                'slide-up': 'slideUp 0.3s ease-out',
                'slide-in-right': 'slideInRight 0.3s ease-out',
                'pulse-soft': 'pulseSoft 2s infinite',
            },
            keyframes: {
                fadeIn: {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                slideUp: {
                    '0%': { opacity: '0', transform: 'translateY(10px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                slideInRight: {
                    '0%': { opacity: '0', transform: 'translateX(20px)' },
                    '100%': { opacity: '1', transform: 'translateX(0)' },
                },
                pulseSoft: {
                    '0%, 100%': { opacity: '1' },
                    '50%': { opacity: '0.7' },
                },
            },
        },
    },
    plugins: [
        plugin(function ({ addVariant }) {
            addVariant('rtl', '[dir="rtl"] &')
            addVariant('ltr', '[dir="ltr"] &')
        }),
    ],
} satisfies Config
