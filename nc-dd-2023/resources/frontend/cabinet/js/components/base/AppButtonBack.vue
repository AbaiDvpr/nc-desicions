<template>
    <button class="btn btn--primary btn--size-md" @click="goBack">
        <AppIcon name="arrow-left" />
    </button>
</template>

<script>
export default {
    name: 'AppButtonBack',
    methods: {
        /**
         * Возвращает на предыдущую страницу
         *
         * @return {void}
         */
        goBack() {
            if (history.state.back) {
                history.back();

                return;
            }

            this.$router.push({ name: 'home' });
        }
    }
};
</script>
