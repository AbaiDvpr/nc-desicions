export default {
    methods: {
        /**
         * Показывает боковой popup
         *
         * @param   name
         * @returns {void}
         */
        showSidePopup(name) {
            this.$store.dispatch('sidePopup/setPopup', name);
        },

        /**
         * Скрывает боковой popup
         *
         * @returns {void}
         */
        hideSidePopup() {
            this.$store.dispatch('sidePopup/setPopup', null);
        }
    }
}
