<template>
    <div
        :class="['notifier', { 'has-notifiers': notifications.length }]"
        v-click-out-side="hideDropdown"
    >
        <div :class="['dropdown notifier__dropdown', { 'is-active': isActive }]"
        >
            <button
                class="dropdown__toggle notifier__toggle"
                @click="isActive = !isActive"
            >
                <AppIcon name="notification" />

                <span v-if="notifications.length" class="notifier__bage"></span>
            </button>

            <div
                v-show="isActive"
                class="dropdown__menu notifier__menu"
            >
                <AppLoader v-if="isLoading" />

                <span v-else-if="isEmpty" class="notifier__text">
                    {{ $t('notificationsEmpty') }}
                </span>

                <span v-else-if="isError" class="notifier__text notifier__text--danger">
                    {{ $t('whoops') }}
                </span>

                <ul v-else>
                    <li
                        v-for="{ id, data } in notifications"
                        :key="id"
                        :class="[
                            'dropdown__menu-item notifier__menu-item',
                            `is-${data.alert}`,
                            { 'is-disabled': isDisabled(id) }
                        ]"
                        @click="setAsRead(id)"
                    >{{ data.message }}</li>
                </ul>
            </div>
        </div>
    </div>
</template>


<script>
import { mapGetters } from 'vuex';
import clickOutSide from '@/directives/clickOutSide';

export default {
    name: 'NotifierDropdown',
    data() {
        return {
            selectedIds: [],
            isActive: false
        }
    },
    computed: {
        ...mapGetters({
            notifications: 'notification/notifications',
            isLoading:     'notification/isLoading',
            isError:       'notification/isError',
            isEmpty:       'notification/isEmpty',
        })
    },
    directives: { clickOutSide },
    methods: {
        /**
         * Скрывает дропдаун
         *
         * @return {void}
         */
        hideDropdown() {
            this.isActive = false;
        },

        /**
         * Проверяет есть ли данное уведомление в списке выбранных
         *
         * @param   id
         * @return {boolean}
         */
        isDisabled(id) {
            return this.selectedIds.includes(id);
        },

        /**
         * Помечает уведомление как прочитанное
         *
         * @param id
         * @return {void}
         */
        setAsRead(id) {
           this.selectedIds.push(id);

            this.$store
                .dispatch('notification/setAsRead', { id })
                .finally(() => {
                    this.selectedIds = this.selectedIds.filter(item => item !== id)
                });
       }
    },
    created() {
        this.$store.dispatch('notification/getList');
    }
}
</script>
