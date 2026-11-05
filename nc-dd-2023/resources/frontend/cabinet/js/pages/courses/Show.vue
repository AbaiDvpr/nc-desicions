<template>
    <div class="title-wrap">
        <router-link class="btn btn--primary btn--size-md" :to="{ name: 'courses.home' }">
            <AppIcon name="arrow-left" />
        </router-link>

        <h1 class="title-primary">Содержимое обучающего курса</h1>
    </div>

    <AppLoader v-if="isLoading" />

    <AppAlert
        v-else-if="isEmpty"
        type="info"
        :message="$t('noData')"
    />

    <AppAlert
        v-else-if="isError"
        type="danger"
        :message="$t('whoops')"
    />

    <template v-else>
        <CourseEula v-if="!hasCourseEula" />

        <div class="row">
            <div class="col-12 col-xl-7">
                <CourseLessonPlayer
                    :id="selectedLesson.videoID"
                    :autoplay="videoAutoplay"
                />

                <div class="panel course-content">
                    <div class="course-info">
                        <div class="course-info__author">
                            Автор курса: <a href="javascript:;">Джунусова Асель</a>
                        </div>

                        <AppRating
                            :rating="selectedLesson.rating"
                            :votes="selectedLesson.votes"
                            :is-voted="selectedLesson.voted"
                            @update="setRateLesson"
                        />

                        <div v-if="false" class="course-info__level">
                            <AppIcon name="cap" modify="icon--sm" />
                            Легкий
                        </div>

                        <div v-if="false" class="course-info__students">
                            <AppIcon name="user-group" modify="icon--sm" />
                            1200 учеников
                        </div>
                    </div>

                    <h2 class="course-content__title">{{ selectedLesson.title }}</h2>

                    <div class="course-content__description" v-html="selectedLesson.description"></div>

                    <div class="course-content__controls">
                        <a
                            v-if="selectedLesson.linkAdditional"
                            class="btn btn--primary"
                            :href="selectedLesson.linkAdditional"
                            target="_blank"
                            rel="noreferrer nofollow"
                        >Дополнительные материалы</a>

                        <button @click="setCompleted" class="btn btn--success btn--course-done">
                            <span v-if="!isLastLesson">Следующий урок</span>
                            <span v-else>Завершить модуль</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-5">
                <div class="panel course-sidebar">
                    <CourseLessonProgress :lessons="lessons" />

                    <CourseLessonPlaylist
                        :lessons="lessons"
                        :selectedLesson="selectedLesson"
                        @selectLesson="selectLesson"
                    />
                </div>
            </div>
        </div>
    </template>
</template>

<script>
import { mapGetters } from 'vuex';
import API from '@/api/v1/courses';
import CourseLessonPlayer from '@/components/course/CourseLessonPlayer';
import CourseLessonPlaylist from '@/components/course/CourseLessonPlaylist';
import CourseLessonProgress from '@/components/course/CourseLessonProgress';
import CourseEula from '@/components/modal/CourseEula';
import AppRating from '@/components/base/AppRating';
import {scrollToTop} from '@/helpers/helpers';
import notyf from '@/plugins/notyf';

export default {
    name: 'CourseShow',
    components: {
        AppRating,
        CourseEula,
        CourseLessonPlayer,
        CourseLessonPlaylist,
        CourseLessonProgress,
    },
    data() {
        return {
            module:         {},
            lessons:        [],
            selectedLesson: {},
            isLoading:      false,
            isEmpty:        false,
            isError:        false,
        };
    },
    computed: {
        ...mapGetters({
            hasCourseEula: 'profile/courseEula'
        }),

        /**
         * @return {string}
         */
        moduleId() {
            return this.$route.params.id;
        },

        /**
         * @returns {boolean}
         */
        videoAutoplay() {
            return this.hasCourseEula;
        },

        /**
         * @return {boolean}
         */
        isCompleted() {
            return Boolean(this.selectedLesson.completed);
        },

        /**
         * @return {boolean}
         */
        isLastLesson() {
            return this.selectedLesson.id === this.lessons[this.lessons.length - 1].id;
        },
    },
    methods: {
        /**
         * Возвращает уроки модуля
         *
         * @return {Promise<void>}
         */
        async initialize() {
            this.isLoading = true;
            this.isError   = false;

            try {
                this.module  = await API.getOneModule(this.moduleId);
                this.lessons = await API.getLessons(this.moduleId);
                this.isEmpty = !Boolean(this.lessons.length);

                if (!this.isEmpty) {
                    this.selectedLesson = this.lessons[0];

                    this.showEulaModal();
                }
            } catch(error) {
                this.isError = true;

                throw error;
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Выбирать определенный урок
         *
         * @param  id
         * @return {void}
         */
        selectLesson(id) {
            this.selectedLesson = this.lessons.find(item => item.id === id);
        },

        /**
         * Показать модальное окно соглашения
         *
         * @return {void}
         */
        showEulaModal() {
            if (!this.hasCourseEula) {
                this.showModal('modal-course-eula');
            }
        },

        /**
         * Возвращает следующий урок
         *
         * @return {Object|null}
         */
        getNextLesson() {
            const index = this.lessons.findIndex(item => item.id === this.selectedLesson.id);

            const nextLesson = this.lessons[index + 1];

            if (typeof(nextLesson) === "undefined") {
                return null;
            }

            return nextLesson;
        },

        /**
         * Устанавливает оценку урока
         *
         * @param value
         * @return {Promise<void>}
         */
        async setRateLesson(value) {
            this.$store.dispatch('loader/setLoading', true);

            try {
                const { rating, voted, votes } = await API.setRateLesson(
                    this.moduleId,
                    this.selectedLesson.id,
                    {
                        rate: value,
                    }
                );

                const index = this.lessons.findIndex(item => item.id === this.selectedLesson.id);

                this.lessons[index] = {
                    ...this.lessons[index],
                    rating,
                    voted,
                    votes
                };
                this.selectedLesson = this.lessons[index];

                notyf.success(this.$i18n.t('successVoted'));
            } catch(error) {
                notyf.error(this.$i18n.t('whoops'));

                throw error;
            } finally {
                this.$store.dispatch('loader/setLoading', false);
            }
        },

        /**
         * Установить урок как завершенный
         *
         * @return {Promise<void>}
         */
        async setCompleted() {
            if (this.isCompleted && this.isLastLesson) {
                this.$router.push({ name: 'courses.home' });

                return;
            }

            if (this.isCompleted) {
                this.selectedLesson = this.getNextLesson();

                scrollToTop();

                return;
            }

            this.isLoading = true;
            this.isError   = false;

            try {
                await API.setCompletedLesson(this.moduleId, this.selectedLesson.id);

                const index = this.lessons.findIndex(item => item.id === this.selectedLesson.id);

                this.lessons[index] = {
                    ...this.lessons[index],
                    completed: true
                };

                this.selectedLesson = this.getNextLesson();

                if (!this.selectedLesson) {
                    this.$router.push({name: 'courses.home'})
                }

            } catch(error) {
                this.isError = true;

                throw error;
            } finally {
                this.isLoading = false;
            }
        }
    },
    created() {
        this.initialize();
    }
};
</script>

