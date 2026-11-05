<template>
    <ul class="course-playlist">
        <li>
            <h2 class="mb-16">Программа модуля</h2>
        </li>

        <li
            v-for="lesson in lessons"
            :key="lesson.id"
            :class="['course-playlist__item', getStatus(lesson)]"
            @click="selectLesson(lesson.id)"
        >
            <div class="course-playlist__item-icon">
                <AppIcon :name="getStatusIcon(lesson)" modify="icon--lg" />
            </div>

            <div class="course-playlist__item-body">
                <h3 class="course-playlist__item-title">{{ lesson.title }}</h3>
            </div>
        </li>
    </ul>
</template>

<script>
import {scrollToTop} from '@/helpers/helpers';

export default {
    name: 'CourseLessonPlaylist',
    emits: ['selectLesson'],
    props: {
        lessons: {
            type: Array,
            required: true
        },
        selectedLesson: {
            type: Object,
            required: true
        },
    },
    methods: {
        /**
         * Выбирает урок
         *
         * @param  id
         * @return {void}
         */
        selectLesson(id) {
            if (id !== this.selectedLesson.id) {
                this.$emit('selectLesson', id);
            }

            scrollToTop();
        },

        /**
         * Получает класс айтема плейлиста
         *
         * @param lesson
         * @returns {string}
         */
        getStatus(lesson) {
            let status = 'is-locked';

            if (lesson.id === this.selectedLesson.id) {
                status = 'is-active';

                return status;
            }

            if (lesson.completed) {
                status = 'is-done';

                return status;
            }

            return status;
        },

        /**
         * Получает имя иконки айтема плейлиста
         *
         * @param lesson
         * @returns {string}
         */
        getStatusIcon(lesson) {
            let icon = 'lock-closed';

            if (lesson.id === this.selectedLesson.id) {
                icon = 'play';

                return icon;
            }

            if (lesson.completed) {
                icon = 'check';

                return icon;
            }

            return icon;
        }
    },
}
</script>
