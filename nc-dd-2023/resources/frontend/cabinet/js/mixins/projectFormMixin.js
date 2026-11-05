import { numberConfig, percentConfig } from '@/plugins/numberFormat';

export default {
    data() {
        return {
            numberConfig,
            percentConfig,
        }
    },
    methods: {
        /**
         * Возвращает базовый url для формы
         *
         * @return {string}
         */
        getBaseUrl() {
            return location.href.replace('#/models', '/projects');
        },

        /**
         * Возвращает имя компонента формы
         *
         * @param  name
         * @return {string}
         */
        getNameComponent(name) {
            return name[0].toUpperCase() + name.slice(1) + 'Component';
        },

        /**
         * Возвращает числовую строку, отформатированную в соответствии с локалью
         *
         * @param  value
         * @return {string}
         */
        numberFormat(value) {
            return Number(value).toLocaleString(['ru','en']);
        }
    }
}
