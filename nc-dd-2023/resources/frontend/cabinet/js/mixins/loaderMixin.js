export default {
    methods: {
        /**
         * Показывает/скрывает глобальный loader
         *
         * @param   state
         * @returns {void}
         */
        setLoading(state) {
            this.$store.dispatch('loader/setLoading', state);
        }
    }
}
