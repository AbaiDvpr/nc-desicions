<template>
    <h1 class="title-primary">{{ $t(title) }}</h1>
</template>

<script>
export default {
    name: 'AppTitle',
    props: {
        title: {
            type: String,
            required: true
        }
    }
};
</script>
