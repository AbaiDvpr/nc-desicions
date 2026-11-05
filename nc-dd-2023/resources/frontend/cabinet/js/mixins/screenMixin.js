export const SCREEN_WIDTH_MD = 768;

export default {
    data() {
        return {
            windowWidth: window.innerWidth
        }
    },
    methods: {
        /**
         * Обработчик resize
         *
         * @returns {void}
         */
        onResize() {
            this.windowWidth = window.innerWidth;
        }
    },
    mounted() {
        window.addEventListener('resize', this.onResize);
    },
    beforeUnmount() {
        window.removeEventListener('resize', this.onResize);
    }
}
