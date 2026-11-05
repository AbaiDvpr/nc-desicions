<template>
    <AppProgressBar />
    <AppNav />
    <AppContent />
    <AppMainLoader />
</template>

<script>
import AppProgressBar from '@/components/base/AppProgressBar';
import AppNav from '@/components/base/AppNav';
import AppContent from '@/components/base/AppContent';
import Centrifugo from '@/centrifugo/centrifugo';

export default {
    name: 'App',
    components: {
        AppNav,
        AppContent,
        AppProgressBar
    },
    created() {
        Centrifugo.initialize();
    }
}
</script>
