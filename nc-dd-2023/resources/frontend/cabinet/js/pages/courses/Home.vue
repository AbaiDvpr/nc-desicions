<template>
    <h1 class="title-primary">{{ $t($route.meta.title) }}</h1>

    <div class="panel">
        <h2 class="mb-16">Добро пожаловать на образовательную платформу Асель Джунусовой!</h2>

        <p>Готовим к успешному получению грантов до 5 млн тенге.</p>

        <p>
            Присоединяйтесь к нашему курсу и получите уникальные знания и навыки для эффективной подготовки к грантовым программам.
            Наши эксперты помогут вам разработать стратегию, самостоятельно подготовить заявку и повысить ваши шансы на получение финансирования
            до 5 миллионов тенге безвозмездных средств. Начните свой путь к успеху с нашей платформой уже сегодня!
        </p>
    </div>

    <div class="panel course">
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
            <h2 class="mb-16">Доступные курсы ({{ courses.length }})</h2>

            <div class="row course-list">
                <div
                    v-for="(course, index) in courses"
                    class="col-12 col-md-6 col-lg-4"
                    :key="course.id"
                >
                    <CourseCard
                        :course="course"
                        :index="index"
                        :access="courseAccess(course, index)"
                    />
                </div>
            </div>
        </template>
    </div>
</template>

<script>
import API from '@/api/v1/courses';
import CourseCard from '@/components/course/CourseCard';

export default {
    name: 'CourseHome',
    components: {
        CourseCard
    },
    data() {
        return {
            courses:   [],
            isLoading: false,
            isEmpty:   false,
            isError:   false,
        }
    },
    methods: {
        /**
         * Возвращает список модулей
         *
         * @return {Promise<void>}
         */
        async initialize() {
            this.isLoading = true;
            this.isError   = false;

            try {
                this.courses = await API.getModules();
                this.isEmpty = !Boolean(this.courses.length);
            } catch(error) {
                this.isError = true;

                throw error;
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Проверяет завершен ли предыдущий курс
         *
         * @param course
         * @param index
         * @return {boolean}
         */
        courseAccess(course, index) {
            if (index === 0) {
                return true;
            }

            const previousCourse = this.courses[index - 1];

            if (previousCourse && previousCourse.lessons) {
                return previousCourse.lessons.completed === previousCourse.lessons.total;
            }

            return false;
        }
    },
    created() {
        this.initialize();
    }
};
</script>
