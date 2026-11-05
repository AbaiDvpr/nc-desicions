<template>
    <div :class="['custom-select', { 'is-disabled': this.isDisabled }]" v-click-out-side="close">
        <div class="custom-select__head" @click="toggle">
          <span class="custom-select__text custom-select__text--overflow">
              <template v-if="selectedOption">
                  {{ selectedOption.title }}
              </template>

              <template v-else-if="this.placeholder">
                  {{ this.placeholder }}
              </template>
          </span>
        </div>

        <div v-show="isOpen" class="custom-select__body">
            <input
                v-if="showSearch"
                type="text"
                v-model.trim="search"
                ref="searchInput"
                @keydown.enter.prevent="addNewOption"
                class="custom-select__input"
            />
            <ul class="custom-select__list">
                <li
                    v-for="(option, index) in filteredOptions"
                    :key="index"
                    :class="[
                        'custom-select__list-item',
                        {'is-marked': selectedOption ? selectedOption.value === option.value : false }
                    ]"
                    @click="selectOption(option)"
                ><span class="custom-select__text">{{ option.title }}</span></li>

                <li
                    v-if="search.length"
                    class="custom-select__list-item is-marked"
                    @click="addNewOption"

                ><span class="custom-select__text">{{ search }}</span></li>
            </ul>

            <div v-if="$slots.button" class="custom-select__button-wrap">
                <slot name="button"></slot>
            </div>

            <div v-if="this.isLoading" class="loader-block">
                <div class="loader"></div>
            </div>

            <span v-if="!options.length" class="custom-select__empty">{{ noResult }}</span>
        </div>
    </div>
</template>

<script>
import clickOutSide from '@/directives/clickOutSide';

export default {
    name: 'AppCustomSelect',
    emits: ['update:modelValue', 'add-option', 'input-search' , 'change'],
    props: {
        options: {
            type: Array,
            required: true
        },
        placeholder: {
            type: String,
            required: false,
            default: 'Выберите значение из списка'
        },
        noResult: {
            type: String,
            required: false,
            default: 'Нет результатов'
        },
        hasFilter: {
            type: Boolean,
            required: false,
            default: true
        },
        showSearch: {
            type: Boolean,
            required: false,
            default: true
        },
        isDisabled: {
            type: Boolean,
            required: false,
            default: false
        },
        isLoading: {
            type: Boolean,
            required: false,
            default: false
        },
        modelValue: {
            type: [String, Number, null],
            required: true
        }
    },
    data() {
        return {
            search: '',
            selectedOption: null,
            isOpen: false
        };
    },
    directives: { clickOutSide },
    computed: {
        /**
         * Возвращает отфильтрованный список на основе поиска
         *
         * @returns {*[]}
         */
        filteredOptions() {
            if (this.hasFilter) {
                return this.options.filter(option =>
                    option.title.toLowerCase().startsWith(this.search.toLowerCase())
                );
            } else {
                return this.options;
            }
        },
    },
    watch: {
        search: function(value) {
            this.$emit('input-search', value);
        }
    },
    methods: {
        /**
         * Скрывает список
         *
         * @returns {void}
         */
        close() {
            this.isOpen = false;
        },

        /**
         * Очищаем форму поиска
         *
         * @returns {void}
         */
        clearSearch() {
            this.search = '';
            this.isOpen = false;
        },

        /**
         * Показывает/скрывает список
         *
         * @returns {void}
         */
        toggle() {
            this.isOpen = !this.isOpen;

            if (this.isOpen && this.showSearch) {
                setTimeout(() => this.$refs.searchInput.focus(),0);
            }
        },

        /**
         * Выбирает опцию
         *
         * @param option
         * @returns {void}
         */
        selectOption(option) {
            this.$emit('update:modelValue', option.value);
            this.selectedOption = {...option};
            this.isOpen = false;
            this.$emit('change', option.value);
        },

        /**
         * Проверяет на наличие совпадения
         *
         * @returns {boolean}
         */
        isOptionExist() {
            return this.options.some(
                option => option.title.toLowerCase() === this.search.toLowerCase()
            );
        },

        /**
         * Добавляет новую опцию
         *
         * @returns {void}
         */
        addNewOption() {
            if (this.isOptionExist() || !this.search.length ) return;

            const newOption = this.search;

            this.$emit('add-option', newOption);
            this.$emit('change', newOption);
            this.clearSearch();
        },

        /**
         * Устанавливает значение по умолчанию
         *
         * @returns {void}
         */
        setSelectedOption() {
            if (this.modelValue) {
                const index = this.options.findIndex(item => item.value === this.modelValue);

                this.selectedOption = this.options[index];
            }
        }
    },
    mounted() {
        this.setSelectedOption();
    },
    updated() {
        this.setSelectedOption();
    }
};
</script>
