import { Component, State } from 'src/core/shopware';
import template from './sw-property-assignment.html.twig';
import './sw-property-assignment.html.less';

Component.register('sw-property-assignment', {
    template,

    data() {
        return {
            groups: [],
            displayTree: false,
            displaySearch: false
        };
    },

    props: {
        options: {
            type: Object, // requires an Association store
            required: true
        }
    },

    computed: {
        groupStore() {
            return State.getStore('configuration_group');
        }
    },

    mounted() {
        this.mountedComponent();
    },

    methods: {
        mountedComponent() {
            const params = { page: 1, limit: 500 };

            return this.options.getList(params).then(() => {
                this.groupOptions(this.options);
                this.$emit('options-loaded');
            });
        },

        onSelectOption(selection) {
            const item = selection.item;

            if (selection.selected === true) {
                if (!this.options.hasId(item.id)) {
                    const newOption = this.options.create(item.id);
                    newOption.setData(item);
                    newOption.isLocal = true;
                }

                // In case the entity was already created but was deleted before
                this.options.store[item.id].isDeleted = false;

                this.groupOptions(this.options);

                return;
            }

            const assigned = this.options.getById(item.id);
            this.deleteOption(assigned);
        },

        deleteOption(option) {
            option.delete();
            this.groupOptions(this.options);
            this.$refs.searchField.addOptionCount();
        },

        groupOptions(options) {
            let groupedData = {};

            options.forEach((option) => {
                if (option.isDeleted) {
                    return;
                }

                const groupId = option.groupId;
                let grouped = groupedData[groupId];

                if (grouped) {
                    grouped.options.push(option);
                    return;
                }

                grouped = { };

                if (option.group.id) {
                    grouped.group = option.group;
                } else {
                    grouped.group = this.groupStore.getById(groupId);
                    grouped.group.isLocal = false;
                }
                groupedData[groupId] = grouped;
                grouped.options = [];
                grouped.options.push(option);
            });

            groupedData = Object.values(groupedData);

            groupedData.sort((a, b) => {
                return a.group.id.localeCompare(b.group.id);
            });

            this.groups = groupedData;
        }
    }
});
