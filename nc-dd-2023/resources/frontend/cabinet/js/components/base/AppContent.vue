<template>
    <div class="app__content">
        <AppHeader />

        <main class="main">
            <router-view :key="locale"></router-view>
        </main>
    </div>
</template>

<script>
import { mapGetters } from 'vuex';
import AppHeader from '@/components/base/AppHeader';

export default {
    name: 'AppContent',
    components:{
        AppHeader
    },
    computed: {
        ...mapGetters({
            locale: 'lang/locale'
        })
    }
}
</script>
