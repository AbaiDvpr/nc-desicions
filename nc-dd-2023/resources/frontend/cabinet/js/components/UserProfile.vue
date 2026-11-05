<template>
    <div class="nav__profile">
        <div class="nav__profile-img">
            <img
                :src="avatar.url ?? avatar.default"
                alt="Аватар профайла"
                width="79"
                height="79"
            >
        </div>

        <div class="nav__profile-content">
            <span class="nav__profile-item">
                ID: <strong class="color-primary">{{ cabinetId }}</strong>
            </span>

            <span class="nav__profile-item">
                Тариф: <strong class="color-primary">{{ tariffName }}</strong>
            </span>

            <span class="nav__profile-item">
                Баланс: <strong class="color-primary">{{ numberFormat(balance) }}</strong>
            </span>
        </div>
    </div>
</template>

<script>
import { mapGetters } from 'vuex';

export default {
    name: 'UserProfile',
    computed: {
        ...mapGetters({
            cabinetId:  'profile/cabinetId',
            tariff:     'profile/tariff',
            balance:    'profile/balance',
            avatar:     'profile/avatar',
            fullAccess: 'profile/fullAccess',
        }),

        /**
         * Наименование тарифа
         *
         * @return {string}
         */
        tariffName() {
            return this.fullAccess ? 'Unlimited' : this.tariff.name;
        }
    }
}
</script>
