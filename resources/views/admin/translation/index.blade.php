@extends('brackets/admin::admin.layout.index')

@section('body')

    <translation-listing
            :data="{{ $data->toJson() }}"
            :url="'{{ url('admin/translation') }}'"
            inline-template>

        <div class="row">
            <div class="col">

                <modal name="edit-translation" class="modal--translation" @before-open="beforeModalOpen" v-cloak height="auto" :scrollable="true" :adaptive="true" :pivot-y="0.25">
                    <h4 class="modal-title">Edit translation</h4>
                    <p class="text-center"><strong>Default text:</strong> @{{ translationDefault }}</p>
                    <form @submit.prevent.once="onSubmit">
                        @foreach($locales as $locale)
                            <div class="form-group">
                                <label>{{ strtoupper($locale) }} translation</label>
                                <input type="text" class="form-control" placeholder="Type a translation for '{{ $locale }}' language." v-model="translations.{{ $locale }}">
                            </div>
                        @endforeach
                        <div class="text-center">
                            <button class="modal-submit btn btn-block btn-primary" class="form-control" type="submit">Save translation</button>
                        </div>
                    </form>
                </modal>

                <div class="card">
                    <div class="card-header">
                        <i class="fa fa-align-justify"></i> Translations listing
                    </div>
                    <div class="card-block" v-cloak>
                        <form @submit.prevent="">
                            <div class="row">
                                <div class="col-sm-12 col-md-7 col-xl-5 form-group small-right-gutter-md">
                                    <div class="input-group">
                                        <input class="form-control" placeholder="Search" v-model="search" @keyup.enter="filter('search', $event.target.value)" />
                                        <span class="btn-group input-group-btn">
                                            <button type="button" class="btn btn-primary" @click="filter('search', search)"><i class="fa fa-search"></i>&nbsp; Search</button>
                                        </span>
                                    </div>
                                </div>

                                <div class="col"></div> <!-- dynamic space between -->

                                <div class="col-sm-auto form-group ">
                                    <select class="form-control" v-model="pagination.state.per_page">
                                        <option value="10">10</option>
                                        <option value="25">25</option>
                                        <option value="100">100</option>
                                    </select>
                                </div>

                            </div>
                        </form>

                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th is='sortable' :column="'group'">Group</th>
                                <th is='sortable' :column="'key'">Default</th>
                                <th is='sortable' :column="'text'">English</th>

                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="(item, index) in collection">
                                <td>@{{ item.group }}</td>
                                <td>@{{ item.key }}</td>
                                <td>@{{ item.text.en }}</td>

                                <td>
                                    <div class="row no-gutters">
                                        <div class="col-auto">
                                            <a class="btn btn-sm btn-info" href="#" @click.prevent="editTranslation(item)" title="Edit" role="button"><i class="fa fa-edit"></i></a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>

                        <div class="row" v-if="pagination.state.total > 0">
                            <div class="col-sm">
                                <span class="pagination-caption">Displaying from @{{ pagination.state.from }} to @{{ pagination.state.to }} of total @{{ pagination.state.total }} items.</span>
                            </div>
                            <div class="col-sm-auto">
                                <!-- TODO how to add push state to this pagination so the URL will actually change? we need JS router - do we want it? -->
                                <pagination></pagination>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </translation-listing>

@endsection