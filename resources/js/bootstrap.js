import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;

window.axios.interceptors.request.use((config) => {
    const t = document.head?.querySelector?.('meta[name="csrf-token"]');
    if (t?.content) {
        config.headers = config.headers ?? {};
        config.headers['X-CSRF-TOKEN'] = t.content;
    }
    return config;
});
