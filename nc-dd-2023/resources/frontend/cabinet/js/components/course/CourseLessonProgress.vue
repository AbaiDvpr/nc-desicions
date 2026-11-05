<template>
    <div class="course-progress">
        <h2>Ваш прогресс</h2>

        <div class="course-progress__header">
            <span class="course-progress__title">{{ progressPercentage().toFixed() + '%' }}</span>

            <span class="course-progress__subtitle"><b>{{ completed }}</b>/{{ total }} уроков</span>
        </div>

        <div class="course-progress__body">
            <div class="course-progress__line">
                <div class="course-progress__fill" :style="{ width: progressPercentage() + '%' }"></div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'CourseLessonProgress',
    props: {
        lessons: {
            type: Array,
            required: true
        }
    },
    computed: {
        /**
         * Возвращает кол-во завершенных уроков
         *
         * @return {int}
         */
        completed() {
            let completed = 0;

            this.lessons.forEach((lesson) => {
                if (lesson.completed) {
                    completed++;
                }
            });

            return completed;
        },

        /**
         * Возвращает общее кол-во уроков
         *
         * @return {int}
         */
        total() {
            return this.lessons.length;
        },
    },
    methods: {
        /**
         * Обновляем прогресс-бар
         *
         * @returns {number}
         */
        progressPercentage() {
            return (this.completed / this.total) * 100;
        },
    }
}
</script>
