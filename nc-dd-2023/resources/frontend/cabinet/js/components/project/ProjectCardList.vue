<template>
    <AppAlert
        v-if="isEmpty"
        type="info"
        :message="message"
    />

    <AppAlert
        v-else-if="isError"
        type="danger"
        :message="$t('whoops')"
    />

    <template v-else>
        <div class="projects__header">
            <div class="row">
                <div class="col-12 col-2xl-4">
                    <b class="projects__header-title">{{ $t('nameFinModel') }}</b>
                </div>

                <div class="col-12 col-2xl-2">
                    <b class="projects__header-title">{{ $t('taxRegime') }}</b>
                </div>

                <div class="col-12 col-2xl-3">
                    <b class="projects__header-title">{{ $t('schedule') }}</b>
                </div>

                <div class="col-12 col-2xl-1">
                    <b class="projects__header-title">{{ $t('currency') }}</b>
                </div>

                <div class="col-12 col-2xl-2"></div>
            </div>
        </div>

        <div class="projects__list">
            <ProjectCard
                v-if="projects.length"
                v-for="project in projects"
                :key="project.uuid"
                :project="project"
            />

            <ProjectCardSkeleton
                v-else
                v-for="index in skeletons"
                :key="index"
            />
        </div>
    </template>
</template>

<script>
import ProjectCard from '@/components/project/ProjectCard';
import ProjectCardSkeleton from '@/components/project/ProjectCardSkeleton';
import ProjectFilter from '@/components/project/ProjectFilter.vue';

export default {
    name: 'ProjectCardList',
    components: {
        ProjectCard,
        ProjectCardSkeleton,
        ProjectFilter
    },
    props: {
        projects: {
            type: Array,
            required: true
        },
        isEmpty: {
            type: Boolean,
            required: true
        },
        isError: {
            type: Boolean,
            required: true
        },
        message: {
            type: String,
            required: false,
            default: ''
        },
        skeletons: {
            type: Number,
            required: false,
            default: 6
        }
    }
}
</script>
