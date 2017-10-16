@extends('brackets/admin-ui::admin.layout.default')

@section('title', trans('brackets/admin-translations::admin.title'))

@section('body')

    <translation-listing
            :data="{{ $data->toJson() }}"
            :url="'{{ url('admin/translations') }}'"
            :label="'{{ trans('brackets/admin-translations::admin.index.all_groups') }}'"
            inline-template>

        <div class="row">
            <div class="col">

                <modal name="edit-translation" class="modal--translation" @before-open="beforeModalOpen" v-cloak height="auto" :scrollable="true" :adaptive="true" :pivot-y="0.25">
                    <h4 class="modal-title">{{ trans('brackets/admin-translations::admin.index.edit') }}</h4>
                    <p class="text-center"><strong>{{ trans('brackets/admin-translations::admin.index.default_text') }}:</strong> @{{ translationDefault }}</p>
                    <form @submit.prevent.once="onSubmit">
                        @foreach($locales as $locale)
                            <div class="form-group">
                                <label>{{ strtoupper($locale) }} {{ trans('brackets/admin-translations::admin.index.translation') }}</label>
                                <input type="text" class="form-control" placeholder="{{ trans('brackets/admin-translations::admin.index.translation_for_language', ['locale' => $locale]) }}" v-model="translations.{{ $locale }}">
                            </div>
                        @endforeach
                        <div class="text-center">
                            <button class="modal-submit btn btn-block btn-primary" class="form-control" type="submit">{{ trans('brackets/admin-ui::admin.btn.save') }} {{ trans('brackets/admin-translations::admin.index.translation') }}</button>
                        </div>
                    </form>
                </modal>

                <div class="card">
                    <div class="card-header">
                        <i class="fa fa-align-justify"></i> {{ trans('brackets/admin-translations::admin.index.title') }}
                        {{-- Consider, if rescan button should be visible in production, because in production rescanning should be part of the deploy process --}}
                        <a class="btn btn-primary btn-sm pull-right m-b-0" href="{{ url('admin/translations/rescan') }}" @click.prevent="rescan('{{ url('admin/translations/rescan') }}')" role="button"><i class="fa" :class="scanning ? 'fa-spinner' : 'fa-eye'"></i>&nbsp; {{ trans('brackets/admin-translations::admin.btn.re_scan') }}</a>
                    </div>
                    <div class="card-block" v-cloak>
                        <form @submit.prevent="">
                            <div class="row justify-content-md-between">
                                <div class="col col-lg-7 col-xl-5 form-group">
                                    <div class="input-group">
                                        <div class="btn-group input-group-btn input-group-btn--search-filter">
                                            <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                @{{ this.filteredGroup }}
                                            </button>
                                            <div class="dropdown-menu">
                                                <a class="dropdown-item" href="#" @click.prevent="resetGroup">{{ trans('brackets/admin-translations::admin.index.all_groups') }}</a>
                                                @foreach($groups as $group)
                                                    <a class="dropdown-item" href="#" @click.prevent="filterGroup('{{ $group }}')">{{ $group }}</a>
                                                @endforeach
                                            </div>
                                        </div>
                                        <input class="form-control" placeholder="{{ trans('brackets/admin-ui::admin.placeholder.search') }}" v-model="search" @keyup.enter="filter('search', $event.target.value)" />
                                        <span class="btn-group input-group-btn">
                                            <button type="button" class="btn btn-primary" @click="filter('search', search)"><i class="fa fa-search"></i>&nbsp; {{ trans('brackets/admin-ui::admin.btn.search') }}</button>
                                        </span>
                                    </div>
                                </div>

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
                                <th is='sortable' :column="'group'">{{ trans('brackets/admin-translations::admin.fields.group') }}</th>
                                <th is='sortable' :column="'key'">{{ trans('brackets/admin-translations::admin.fields.default') }}</th>
                                <th is='sortable' :column="'text'">{{ mb_strtoupper((isset(Auth::user()->language) && in_array(Auth::user()->language, config('translatable.locales'))) ? Auth::user()->language : 'en' ) }}</th>

                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="(item, index) in collection">
                                <td>@{{ item.group }}</td>
                                <td>@{{ item.key }}</td>
                                <td>{{'{{'}} item.text.{{ (isset(Auth::user()->language) && in_array(Auth::user()->language, config('translatable.locales'))) ? Auth::user()->language : 'en' }} }}</td>

                                <td>
                                    <div class="row no-gutters">
                                        <div class="col-auto">
                                            <a class="btn btn-sm btn-info" href="#" @click.prevent="editTranslation(item)" title="{{ trans('brackets/admin-ui::admin.btn.edit') }}" role="button"><i class="fa fa-edit"></i></a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>

                        <div class="row" v-if="pagination.state.total > 0">
                            <div class="col-sm">
                                <span class="pagination-caption">{{ trans('brackets/admin-ui::admin.pagination.overview') }}</span>
                            </div>
                            <div class="col-sm-auto">
                                <!-- TODO how to add push state to this pagination so the URL will actually change? we need JS router - do we want it? -->
                                <pagination></pagination>
                            </div>
                        </div>

	                    <div class="no-items-found" v-if="!collection.length > 0">
		                    <i class="icon-magnifier"></i>
		                    <h3>{{ trans('brackets/admin-translations::admin.index.no_items') }}</h3>
		                    <p>{{ trans('brackets/admin-translations::admin.index.try_changing_items') }}</p>
                            <a class="btn btn-primary" href="{{ url('admin/translations/rescan') }}" @click.prevent="rescan('{{ url('admin/translations/rescan') }}')" role="button"><i class="fa fa-eye"></i>&nbsp; {{ trans('brackets/admin-translations::admin.btn.re_scan') }}</a>
	                    </div>
                    </div>
                </div>
            </div>
        </div>
    </translation-listing>

@endsection