<template>
    <div class="wrap">
        <div class="stepper__delimeter">{{ field.title }}</div>
    </div>
</template>

<script>
export default {
    name: 'DelimiterComponent',
    props: {
        name: {
            type: String
        },
        field: {
            type: Object
        },
        errors: {
            type: Object
        }
    }
}
</script>
