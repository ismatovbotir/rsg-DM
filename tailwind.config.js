/** Tailwind CLI build (no Vite): `npm run build` writes public/css/pda.css and public/css/admin.css; both are committed. */
export default {
    content: ['./resources/views/**/*.blade.php', './app/Livewire/**/*.php', './app/Support/**/*.php'],
    theme: {
        extend: {
            colors: {
                brand: { DEFAULT: '#e51932', dark: '#b8102a', soft: '#fdecef', navy: '#24262b' }, // RSG logo red
                ink: { DEFAULT: '#1b2430', muted: '#5b6676', faint: '#8a95a5' },
                line: '#e4e8ee',
                ok: { DEFAULT: '#2e8b57', bg: '#e6f4ec', text: '#1f6b43' },
                bad: { DEFAULT: '#c0392b', bg: '#fdeaea', text: '#a32727' },
            },
        },
    },
    plugins: [],
};
