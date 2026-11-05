<template>
    <div v-if="isLoading" class="loader-overlay">
        <div class="loader"></div>
    </div>
</template>

<script>
import { mapState } from 'vuex';

export default {
    name: 'AppMainLoader',
    computed: {
        ...mapState({
            isLoading: state => state.loader.isLoading
        })
    }
}
</script>
