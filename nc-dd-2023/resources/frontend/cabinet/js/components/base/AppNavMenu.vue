<template>
    <ul class="nav__menu">
        <li v-for="item in nav" :key="item.to.name">
            <router-link
                class="nav__menu-link"
                :to="item.to"
                :title="$t(item.name)"
            >
                <AppIcon :name="item.icon" />

                <span class="nav__menu-link-text">{{ $t(item.name) }}</span>
            </router-link>
<!--            <a v-if="item.to.name === 'home'" class="badge" href="#">75</a>-->
        </li>
    </ul>
</template>
<script>
import { mapGetters } from 'vuex';
import nav from '@/config/nav';
import {getNavByFilteredAccess, getNavByFilteredUserEmail} from '@/helpers/helpers';

export default {
    name: 'AppNavMenu',
    computed: {
        ...mapGetters({
            userEmail: 'profile/userEmail'
        }),

        nav() {
            let data = [];

            data = getNavByFilteredUserEmail(nav, this.userEmail);
            data = getNavByFilteredAccess(data);

            return data;
        }
    }
}
</script>
