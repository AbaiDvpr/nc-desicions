<template>
    <div class="project-card mb-0">
        <div class="row">
            <div class="col-12 col-2xl-4">
                <span class="project-card__title">
                    <i :class="['status', isCompleted ? 'status--done' : 'status--progress']"></i>
                    {{ form }}
                    {{ name }}
                </span>
            </div>

            <div class="col-12 col-2xl-2">
                <span class="project-card__text">{{ taxRegime }}</span>
            </div>

            <div class="col-12 col-2xl-3">
                <span class="project-card__text">{{ schedule }}</span>
            </div>

            <div class="col-12 col-2xl-1">
                <span class="project-card__text">{{ currency }}</span>
            </div>

            <div class="col-12 col-2xl-2">
                <ul class="project-card__controls">
                    <li>
                        <router-link
                            :to="{ name: 'models.show', params: { uuid } }"
                            :class="['btn btn--primary btn--size-sm', isPossibleToCorrect ? '' : 'is-disabled' ]"
                        >
                            <AppIcon name="edit" />
                        </router-link>
                    </li>

                    <li>
                        <a
                            :class="['btn btn--success btn--size-sm', isCompleted ? '' : 'is-disabled' ]"
                            :href="downloadLink"
                            download
                        >
                            <AppIcon name="excel" />
                        </a>
                    </li>

                    <li>
                        <a
                            class="btn btn--success btn--size-sm is-disabled"
                            href="#"
                        >
                            <AppIcon name="word" />
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'ProjectCard',
    props: {
        project: {
            type: Object,
            required: true
        }
    },
    computed: {
        uuid() {
            return this.project.uuid;
        },
        name() {
            return this.project.name ?? this.$i18n.t('notFilled');
        },
        form() {
            return this.project.form;
        },
        taxRegime() {
            return this.project.taxRegime ?? this.$i18n.t('notFilled');
        },
        schedule() {
            return this.project.schedule ?? this.$i18n.t('notFilled');
        },
        currency() {
            return this.project.currency ? this.project.currency : this.$i18n.t('notFilled');
        },
        isCompleted() {
            return this.project.isCompleted;
        },
        isPossibleToCorrect() {
            return this.project.isPossibleToCorrect;
        },
        downloadLink() {
            return this.project.downloadLink;
        }
    }
}
</script>
