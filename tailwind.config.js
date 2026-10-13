/** @type {import('tailwindcss').Config} */
module.exports = {
    darkMode: 'class',
    content: ['./index.php', './api/**/*.php', './views/**/*.php'],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'sans-serif'],
            },
            colors: {
                teal: {
                    50: '#f0fdfa',
                    100: '#ccfbf1',
                    500: '#14b8a6',
                    600: '#0d9488',
                    700: '#0f766e',
                    800: '#115e59',
                    900: '#134e4a',
                },
                // Soft dark surfaces (not pure black) for dark mode.
                night: {
                    900: '#14181e',
                    800: '#242b33',
                    700: '#2c343d',
                    600: '#3b444f',
                },
            },
        },
    },
};
