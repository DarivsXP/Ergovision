import { onMounted, ref, watch } from 'vue';

const STORAGE_KEY = 'ergovision-theme';

function getPreferredDark() {
    if (typeof window === 'undefined') {
        return true;
    }

    const saved = localStorage.getItem(STORAGE_KEY);
    if (saved === 'light') {
        return false;
    }
    if (saved === 'dark') {
        return true;
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches;
}

function applyTheme(isDark) {
    const root = document.documentElement;
    root.classList.toggle('dark', isDark);

    const meta = document.querySelector('meta[name="theme-color"]');
    if (meta) {
        meta.setAttribute('content', isDark ? '#020617' : '#f8fafc');
    }
}

export function useTheme() {
    const isDark = ref(true);

    const setTheme = (dark) => {
        isDark.value = dark;
        localStorage.setItem(STORAGE_KEY, dark ? 'dark' : 'light');
        applyTheme(dark);
    };

    const toggle = () => setTheme(!isDark.value);

    onMounted(() => {
        isDark.value = getPreferredDark();
        applyTheme(isDark.value);
    });

    watch(isDark, (dark) => applyTheme(dark));

    return { isDark, toggle, setTheme };
}
