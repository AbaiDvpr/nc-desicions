<template>
    <FullCalendar :options="options" ref="fullCalendar">
            <template v-slot:eventContent='arg'>
                <div
                    class="fc-event__item"
                >
                    <b
                        v-if="arg.event.extendedProps.sum"
                        class="fc-event__item-title"
                    >{{ arg.event.extendedProps.sum }}</b>

                    <a
                        v-if="arg.event.extendedProps.link"
                        :href="arg.event.extendedProps.link"
                        class="fc-event__item-description"
                    >{{ arg.event.title }}</a>

                    <p
                        v-else
                        class="fc-event__item-description"
                    >{{ arg.event.title }}</p>
                </div>
            </template>
        </FullCalendar>
</template>

<script>
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid'
import interactionPlugin from '@fullcalendar/interaction';
import ruLocale from '@fullcalendar/core/locales/ru';
import kkLocale from '@fullcalendar/core/locales/kk';
import screenMixin from '@/mixins/screenMixin';
import {mapGetters} from 'vuex';
import {getFormatDate} from '@/plugins/dayjs';

const SCREEN_WIDTH_MD = 768;
const SCREEN_WIDTH_XL = 1366;

export default {
    name: 'EventCalendar',
    mixins: [screenMixin],
    components: {
        FullCalendar,
    },
    props: {
        data: {
            type: Array,
            default: []
        }
    },
    data() {
        return {
            options: {
                selectable: false,
                plugins: [dayGridPlugin, interactionPlugin, timeGridPlugin],
                initialView: 'dayGridMonth',
                locales: [ruLocale, kkLocale],
                titleFormat: date => {
                    date = new Date(date.date.year, date.date.month, date.date.day);

                    return getFormatDate(date, 'MMMM, YYYY');
                },
                weekends: true,
                dayMaxEvents: 2,
                events: this.data
            },
        };
    },
    computed: {
        ...mapGetters({
            locale: 'lang/locale'
        })
    },
    watch: {
        windowWidth(value) {
            this.changeView(value);
        },
    },
    methods: {
        /**
         * Меняет вид календаря
         *
         * @param value
         * @return {void}
         */
        changeView(value) {
            const calendarApi = this.$refs.fullCalendar.getApi();

            if (value < SCREEN_WIDTH_MD) {
                this.options.dayMaxEvents = false;
                calendarApi.changeView('dayGridDay');

                return;
            }

            if (value > SCREEN_WIDTH_MD && value < SCREEN_WIDTH_XL) {
                this.options.dayMaxEvents = 1;
                calendarApi.changeView('dayGridWeek');

                return;
            }

            this.options.dayMaxEvents = 2;
            calendarApi.changeView('dayGridMonth');
        },
    },
    created() {
        this.options.locale = this.locale;
    },
    mounted() {
        this.changeView(this.windowWidth);
    }
};
</script>

