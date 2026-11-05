<template>
    <div :class="['alert', `alert--${type}`, modify]">
        <slot />
        {{ message }}
    </div>
</template>

<script>
export default {
    name: 'AppAlert',
    props: {
        type: {
            type: String,
            default: 'primary',
            required: true
        },
        modify: {
            type: String,
            default: '',
            required: false
        },
        message: {
            type: String,
            default: '',
            required: false
        }
    }
};
</script>

<style scoped>

</style>
