export default {
    methods: {
        /**
         * Возвращает числовую строку, отформатированную в соответствии с локалью
         *
         * @param   value
         * @returns {string}
         */
        numberFormat(value) {
            return Number(value).toLocaleString(['ru']);
        },
    }
}
