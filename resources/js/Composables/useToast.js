// resources/js/Composables/useToast.js
import { ref } from 'vue';

const items = ref([]);

export function useToast() {
    
    function add(item) {
        const id = Date.now();
        const notification = {
            id,
            title: item.title || 'Notification',
            message: item.message || '',
            type: item.type || 'info', // success, error, warning, info
            duration: item.duration || 4000
        };
        
        items.value.push(notification);

        if (notification.duration > 0) {
            setTimeout(() => {
                remove(id);
            }, notification.duration);
        }
    }

    function remove(id) {
        items.value = items.value.filter(item => item.id !== id);
    }

    return {
        items,
        add,
        remove,
        success: (msg, title = 'Success') => add({ title, message: msg, type: 'success' }),
        error: (msg, title = 'Error') => add({ title, message: msg, type: 'error' }),
        warning: (msg, title = 'Warning') => add({ title, message: msg, type: 'warning' }),
        info: (msg, title = 'Info') => add({ title, message: msg, type: 'info' })
    };
}