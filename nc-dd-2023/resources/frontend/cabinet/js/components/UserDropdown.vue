<template>
    <div
        class="dropdown user-dropdown"
        :class="{ 'is-active': isActive }"
        v-click-out-side="hideDropdown"
    >
        <button
            @click="isActive = !isActive"
            class="dropdown__toggle user-dropdown__toggle"
        >
            <span class="user-dropdown__img">
                <img
                    :src="avatar.url ?? avatar.default"
                    alt="Аватар"
                    width="35"
                    height="35"
                >
            </span>

            <span class="dropdown__title user-dropdown__name">{{ userName }}</span>
        </button>

        <ul v-show="isActive" class="dropdown__menu user-dropdown__menu">
            <li class="dropdown__menu-item">
                <router-link :to="{ name: 'profile' }" @click="hideDropdown">
                    {{ $t('profile') }}
                </router-link>
            </li>

            <li class="dropdown__menu-item">
                <router-link :to="{ name: 'tariffs' }" @click="hideDropdown">
                    {{ $t('tariffs') }}
                </router-link>
            </li>

            <li class="dropdown__menu-item">
                <router-link :to="{ name: 'payments.home' }" @click="hideDropdown">
                    {{ $t('paymentHistory') }}
                </router-link>
            </li>

            <li class="dropdown__menu-item">
                <a href="/auth/logout/exit">{{ $t('logout') }}</a>
            </li>
        </ul>
    </div>
</template>

<script>
import { mapGetters } from 'vuex';
import clickOutSide from "@/directives/clickOutSide";

export default {
    name: 'UserDropdown',
    data() {
        return {
            isActive: false
        }
    },
    directives: { clickOutSide },
    computed:{
        ...mapGetters({
            userName: 'profile/userName',
            avatar:   'profile/avatar',
        })
    },
    methods: {
        hideDropdown() {
            this.isActive = false;
        }
    }
}
</script>
