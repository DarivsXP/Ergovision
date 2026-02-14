import { ref } from 'vue';

// State is defined OUTSIDE the function so it persists across all components
const items = ref([]);

export function useToast() {
    /**
     * Internal function to add a toast
     */
    const add = ({ title, message, type = 'info', duration = 4000 }) => {
        const id = Date.now();

        items.value.push({
            id,
            title,
            message,
            type,
        });

        // Auto-remove after duration
        if (duration > 0) {
            setTimeout(() => {
                remove(id);
            }, duration);
        }
    };

    /**
     * Remove a specific toast by ID
     */
    const remove = (id) => {
        items.value = items.value.filter((item) => item.id !== id);
    };

    // Helper methods for easy access
    return {
        items, // The reactive array
        success: (message, title = 'Success') => add({ title, message, type: 'success' }),
        error: (message, title = 'Error') => add({ title, message, type: 'error' }),
        warning: (message, title = 'Warning') => add({ title, message, type: 'warning' }),
        info: (message, title = 'Info') => add({ title, message, type: 'info' }),
        remove,
    };
}