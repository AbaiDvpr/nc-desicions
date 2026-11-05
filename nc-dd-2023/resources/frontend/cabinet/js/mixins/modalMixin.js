export default {
    methods: {
        /**
         * Показывает модальные окна
         *
         * @param   name
         * @returns {void}
         */
        showModal(name) {
            this.$store.dispatch('modal/setModal', name);
        },

        /**
         * Скрывает модальные окна
         *
         * @returns {void}
         */
        hideModal() {
            this.$store.dispatch('modal/setModal', null);
        }
    }
}
