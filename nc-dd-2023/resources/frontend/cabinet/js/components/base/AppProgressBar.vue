<template>
    <div v-if="isShow" class="progress-loader">
        <div :class="['progress-loader__fill', {'is-loading': isLoading, 'is-loaded': isLoaded}]"></div>
    </div>
</template>

<script>
import {mapGetters} from 'vuex';

export default {
    name: 'AppProgressBar',
    computed: {
        ...mapGetters({
            isShow: 'progressBar/isShow',
            isLoaded: 'progressBar/isLoaded',
            isLoading: 'progressBar/isLoading',
        }),
    },
};
</script>
