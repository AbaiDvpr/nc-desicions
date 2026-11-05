<template>
    <h1 class="title-primary">{{ $t('welcome') }}, {{ userName }}!</h1>

    <CourseBanner v-if="courseAccess" />

    <section v-else class="welcome">
        <p class="welcome__text">{{ $t('welcomeText') }}</p>
    </section>

    <section class="projects">
        <h2 class="section-title">{{ $t('latestFinModels') }}</h2>

        <ProjectCardList
            :projects="projects.data"
            :isEmpty="projects.isEmpty"
            :isError="projects.isError"
            :skeletons="projects.params.limit"
            :message="projects.message"
        />
    </section>
</template>

<script>
import { mapGetters } from 'vuex';
import ProjectCardList from '@/components/project/ProjectCardList';
import CreateProject from '@/components/modal/CreateProject';
import CourseBanner from '@/components/course/CourseBanner';

export default {
    name: 'Home',
    components: {
        CourseBanner,
        ProjectCardList,
        CreateProject
    },
    computed: {
        ...mapGetters({
            userName: 'profile/userName',
            projects: 'dashboard/projects',
            courseAccess: 'profile/courseAccess'
        })
    },
    mounted() {
        this.$store.dispatch('dashboard/getProjects');
    }
}
</script>
