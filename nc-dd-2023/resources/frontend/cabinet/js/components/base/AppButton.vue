<template>
    <button v-if="!link" :class="['btn', type, modify]" :disabled="disabled">
        <slot />
    </button>
    <router-link v-else :to="to" :class="['btn', type, modify]">
        <slot />
    </router-link>
</template>

<script>
export default {
    name: 'AppButton',
    props: {
        type: {
            type: String,
            default: 'btn--primary'
        },
        modify: {
            type: String,
            default: ''
        },
        link: {
            type: Boolean,
            required: false,
            default: false
        },
        to: {
            type: Object,
            required: false,
            default: { name: 'home' }
        },
        disabled: {
            type: Boolean,
            required: false,
            default: false
        }
    }
}
</script>
