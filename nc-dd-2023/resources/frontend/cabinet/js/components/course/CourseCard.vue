<template>
    <router-link
        :to="getRoute()"
        :class="['course-card course-card--h-100', { 'is-disabled' : !access }]"
    >
        <div v-if="!access" class="course-card__icon">
            <AppIcon name="lock-closed" modify="icon--size-lg" />
        </div>

        <div class="course-card__media">
            <img
                class="course-card__image"
                :src="`/static/images/cabinet/courses/course-card-${index + 1}.svg`"
                alt="Превью"
            />
        </div>

        <div class="course-card__body">
            <h3 class="course-card__title">{{ course.title }}</h3>

            <div class="course-progress">
                <div class="course-progress__header">
                    <span class="course-progress__title">Ваш прогресс</span>

                    <span class="course-progress__subtitle">
                        <strong>{{ completed }}</strong>/{{ total }} уроков
                    </span>
                </div>

                <div class="course-progress__body">
                    <div class="course-progress__line">
                        <div
                            class="course-progress__fill"
                            :style="{ width: progressPercentage() + '%' }"
                        ></div>
                    </div>
                </div>
            </div>
        </div>
    </router-link>
</template>

<script>
export default {
    name: 'CourseCard',
    props: {
        course: {
            type: Object,
            required: true
        },
        index: {
            type: Number,
            required: true
        },
        access: {
            type: Boolean,
            default: false
        }
    },
    computed: {
        /**
         * Возвращает кол-во завершенных уроков
         *
         * @return {int}
         */
        completed() {
            if (typeof(this.course.lessons) === 'undefined') {
                return 0;
            }

            return this.course.lessons.completed;
        },

        /**
         * Возвращает общее кол-во уроков
         *
         * @return {int}
         */
        total() {
            if (typeof(this.course.lessons) === 'undefined') {
                return 0;
            }

            return this.course.lessons.total;
        },
    },
    methods: {
        /**
         * Обновляет прогресс-бар
         *
         * @returns {number}
         */
        progressPercentage() {
            return (this.completed / this.total) * 100;
        },

        /**
         * Возвращает маршрут
         *
         * @return {Object}
         */
        getRoute() {
            if (this.access) {
                return {
                    name: 'courses.show',
                    params: { id: this.course.id }
                }
            }

            return { name: 'courses.home' }
        }
    }
}
</script>
