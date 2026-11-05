<template>
    <button
        :class="['btn', type, modify, { 'is-loading': isLoading }]"
        :disabled="disabled"
        @click="method()"
    >
        <slot name="icon"></slot>
        <span><slot /></span>
        <span v-if="isLoading" class="loader"></span>
    </button>
</template>

<script>
export default {
    name: 'AppButtonLoader',
    props: {
        type: {
            type: String,
            default: 'btn--primary'
        },
        modify: {
            type: String,
            default: ''
        },
        disabled: {
            type: Boolean,
            required: false,
            default: false
        },
        isLoading: {
            type: Boolean,
            required: false,
            default: false
        },
        method: {
            type: Function,
            required: true
        }
    }
}
</script>
