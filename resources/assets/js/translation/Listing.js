import BaseListing from 'components/Listing/BaseListing';

Vue.component('translation-listing', {

    mixins: [BaseListing],

    props: {
        label: {
            type: String,
            default: function() {
                return 'All groups';
            }
        }
    },

    data(){
        return {

            scanning: false,

            filters: {
                group: null
            },

            translationId: null,
            translationDefault: '',
            translations: {}
        }
    },

    computed: {
        filteredGroup() {
            return this.filters.group === null ? this.label : this.filters.group;
        },
    },

    methods: {

        rescan(url) {
            this.scanning = true;
            axios.post(url)
                .then(response => {
                    this.scanning = false;
                    this.loadData(true);
                }, error => {
                    this.scanning = false;
                    this.$notify({ type: 'error', title: 'Error!', text: 'An error has occured.'});
                });
        },

        filterGroup(group) {
            this.filters.group = group;
            this.loadData(true);
        },

        resetGroup() {
            this.filters.group = null;
            this.loadData(true);
        },

        editTranslation (item) {
            this.$modal.show('edit-translation', item);
        },
        beforeModalOpen ({params}) {
            this.translationId = params.id;
            this.translationDefault = params.key;
            this.translations = {};
            for (const key of Object.keys(params.text)) {
                this.translations[key] = params.text[key];
            }
        },
        onSubmit() {
            let url = '/admin/translation/'+this.translationId;
            let data = {
                text: this.translations
            };

            axios.post(url, data).then(response => {
                this.$modal.hide('edit-translation');
                this.$notify({ type: 'success', title: 'Success!', text: 'Item successfully changed.'});
                this.loadData();
            }, error => {
                this.$notify({ type: 'error', title: 'Error!', text: 'An error has occured.'});
            });
        }
    }
});