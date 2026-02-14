<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    align: { type: String, default: 'right' },
    width: { type: String, default: '48' },
    contentClasses: { type: String, default: 'py-1 bg-slate-900' },
});

const open = ref(false);
const dropdownRef = ref(null);

// Close when clicking anywhere on the document
const closeDropdown = (e) => {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
        open.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', closeDropdown);
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') open.value = false;
    });
});

onUnmounted(() => {
    document.removeEventListener('click', closeDropdown);
});

const widthClass = computed(() => ({ '48': 'w-48' }[props.width.toString()] || 'w-48'));

const alignmentClasses = computed(() => {
    if (props.align === 'left') return 'ltr:origin-top-left start-0';
    if (props.align === 'right') return 'ltr:origin-top-right end-0';
    return 'origin-top';
});
</script>

<template>
    <div class="relative" ref="dropdownRef">
        <div @click.stop="open = !open">
            <slot name="trigger" />
        </div>

        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-show="open"
                class="absolute z-[120] mt-2 rounded-2xl shadow-2xl"
                :class="[widthClass, alignmentClasses]"
                @click="open = false"
            >
                <div class="rounded-2xl border border-white/10 ring-0 overflow-hidden shadow-2xl" :class="contentClasses">
                    <slot name="content" />
                </div>
            </div>
        </Transition>
    </div>
</template>