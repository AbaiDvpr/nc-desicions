<template>
    <svg :class="['icon', `icon--${name}`, modify]">
        <use :xlink:href="`/static/images/sprites/cabinet.svg#${name}`"></use>
    </svg>
</template>

<script>
export default {
    name: 'AppIcon',
    props: {
        name: {
            type: String,
            required: true
        },
        modify: {
            type: String,
            required: false
        }
    }
};
</script>
