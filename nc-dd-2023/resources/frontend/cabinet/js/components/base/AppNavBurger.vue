<template>
    <button
        :class="['burger burger--dark', { 'is-active': isActive }]"
        @click="toggle"
    ><i class="burger__line"></i></button>
</template>

<script>
import { mapGetters } from 'vuex';

export default {
    name: 'AppNavBurger',
    computed: {
        ...mapGetters({ isActive: 'sidebar/isActive' })
    },
    methods: {
        toggle() {
            this.$store.dispatch('sidebar/show', !this.isActive);
        }
    }
}
</script>
