import { ref } from 'vue';

const items = ref([]);

export function useToast() {
    function add(item) {
        const id = Date.now();
        items.value.push({
            id,
            title: item.title || 'Notification',
            message: item.message || '',
            type: item.type || 'info',
            duration: item.duration || 4000
        });

        if (item.duration !== 0) {
            setTimeout(() => remove(id), item.duration || 4000);
        }
    }

    function remove(id) {
        items.value = items.value.filter(item => item.id !== id);
    }

    return {
        items,
        success: (msg, title = 'Success') => add({ title, message: msg, type: 'success' }),
        error: (msg, title = 'Error') => add({ title, message: msg, type: 'error' }),
        warning: (msg, title = 'Warning') => add({ title, message: msg, type: 'warning' }),
        info: (msg, title = 'Info') => add({ title, message: msg, type: 'info' }),
        remove
    };
}