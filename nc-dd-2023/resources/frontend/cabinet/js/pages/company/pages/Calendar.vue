<template>
    <div class="title-wrap">
        <AppButtonBack />

        <h1 class="title-primary">{{ $t('calendar') }}</h1>
    </div>

    <div class="panel">
        <EventCalendar :data="data" />
    </div>
</template>

<script>
import {defineAsyncComponent} from 'vue';
import AppLoader from '@/components/base/AppLoader';
import AppButtonBack from '@/components/base/AppButtonBack';

export default {
    name: 'CompanyPageCalendar',
    components: {
        AppButtonBack,
        EventCalendar: defineAsyncComponent({
            loader: () => import(/* webpackChunkName: "EventCalendar" */ '@/components/EventCalendar'),
            loadingComponent: AppLoader
        }),
    },
    data() {
        return {
            data: [
                {
                    title: 'Списание в счёт оплаты тарифного плана Базовый Списание в счёт оплаты тарифного плана Базовый Списание в счёт оплаты тарифного плана Базовый',
                    sum: '100 000 т',
                    date: '2023-09-19',
                    link: '#',
                    className: 'fc-event--red',
                },
                {
                    title: 'Списание в счёт оплаты тарифного плана Базовый Списание в счёт оплаты тарифного плана Базовый Списание в счёт оплаты тарифного плана Базовый',
                    sum: '100 000 т',
                    date: '2023-09-20',
                    link: '#',
                    className: 'fc-event--green',
                },
                {
                    title: 'Списание в счёт оплаты тарифного плана Базовый Списание в счёт оплаты тарифного плана Базовый Списание в счёт оплаты тарифного плана Базовый',
                    sum: '100 000 т',
                    date: '2023-09-19',
                    link: '#',
                    className: 'fc-event--aqua',
                },
                {
                    title: 'Списание в счёт оплаты тарифного плана Базовый Списание в счёт оплаты тарифного плана Базовый Списание в счёт оплаты тарифного плана Базовый',
                    sum: null,
                    date: '2023-09-19',
                    link: '#',
                    className: 'fc-event--purple',
                }
            ]
        }
    }
}
</script>


