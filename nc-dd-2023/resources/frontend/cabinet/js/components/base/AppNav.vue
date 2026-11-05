<template>
    <nav :class="['nav', { 'is-active': isActive, 'is-compact': isCompact }]">
        <AppNavLogo />
        <UserProfile />
        <AppNavMenu />

        <button class="nav__btn-compact btn" @click="toggleCompact">
            <AppIcon name="sidebar-left" />
        </button>
    </nav>
</template>

<script>
import { mapGetters } from 'vuex';
import AppNavLogo from '@/components/base/AppNavLogo';
import AppNavMenu from '@/components/base/AppNavMenu';
import UserProfile from '@/components/UserProfile';

export default {
    name: 'AppNav',
    components: {
        AppNavLogo,
        AppNavMenu,
        UserProfile
    },
    computed: {
        ...mapGetters({
            isActive: 'sidebar/isActive',
            isCompact: 'sidebar/isCompact'
        })
    },
    methods: {
        toggleCompact() {
            this.$store.dispatch('sidebar/compact', !this.isCompact);
        }
    },
    created() {
        if (window.innerWidth < 992) {
            this.$store.dispatch('sidebar/show', false);
        }
    }
}
</script>
