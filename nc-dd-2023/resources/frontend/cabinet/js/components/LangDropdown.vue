<template>
    <div v-if="Object.keys(locales).length" class="lang-switcher">
        <div
            class="dropdown lang-switcher__dropdown"
            :class="{ 'is-active': isActive }"
            v-click-out-side="hideDropdown"
        >
            <button
                class="dropdown__toggle lang-switcher__toggle"
                @click="isActive = !isActive"
            >{{ locales[$i18n.locale] }}</button>

            <ul
                v-show="isActive"
                class="dropdown__menu lang-switcher__menu"
            >
                <template  v-for="(locale, localeKey) in locales" :key="localeKey">
                    <li
                        v-if="$i18n.locale !== localeKey"
                        class="dropdown__menu-item lang-switcher__menu-item"
                        @click="changeLocale(localeKey)"
                    >{{ locale }}</li>
                </template>
            </ul>
        </div>
    </div>
</template>

<script>
import { mapGetters } from 'vuex';
import clickOutSide from "@/directives/clickOutSide";

export default {
    name: 'LangDropdown',
    data() {
        return {
            isActive: false
        }
    },
    computed: {
        ...mapGetters({
            locale: 'lang/locale',
            locales: 'lang/locales'
        })
    },
    directives: { clickOutSide },
    methods: {
        /**
         * Скрывает панель
         *
         * @returns {void}
         */
        hideDropdown() {
            this.isActive = false;
        },

        /**
         *
         * @param   locale
         * @returns {void}
         */
        changeLocale(locale) {
            if (this.$i18n.locale !== locale) {
                this.$store.dispatch('lang/setLocale', { locale, routeName: this.$route.meta.title });
                this.hideDropdown();
            }
        }
    }
}
</script>

