<template>
    <div class="title-wrap">
        <AppTitle :title="$route.meta.title" />

        <RequestRefill />
    </div>

    <PaymentsHistory />
</template>

<script>
import RequestRefill from '@/components/modal/RequestRefill';
import PaymentsHistory from '@/components/payments/PaymentsHistory';

export default {
    name: 'PaymentsHome',
    components: {PaymentsHistory, RequestRefill},
}
</script>
