import './bootstrap';
import Alpine from 'alpinejs';

Alpine.data('themeManager', () => ({
    theme: 'light',
    dropdownOpen: false,

    init() {
        if (document.documentElement.classList.contains('pink')) {
            this.theme = 'pink';
        } else if (document.documentElement.classList.contains('dark')) {
            this.theme = 'dark';
        } else {
            this.theme = 'light';
        }
    },

    setTheme(newTheme) {
        this.theme = newTheme;
        this.dropdownOpen = false;

        document.documentElement.classList.remove('dark', 'pink');
        if (newTheme === 'dark') {
            document.documentElement.classList.add('dark');
        } else if (newTheme === 'pink') {
            document.documentElement.classList.add('pink');
        }

        localStorage.setItem('theme', newTheme);
    },

    cycleTheme() {
        const sequence = ['light', 'dark', 'pink'];
        const nextIdx = (sequence.indexOf(this.theme) + 1) % sequence.length;
        this.setTheme(sequence[nextIdx]);
    }
}));

window.Alpine = Alpine;

Alpine.start();
