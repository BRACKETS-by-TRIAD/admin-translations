@extends('brackets/admin-ui::admin.layout.default')

@section('title', trans('brackets/admin-translations::admin.title'))

@section('body')

    <translation-listing2
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

                <modal name="import-translation" class="modal--translation" v-cloak height="auto" :scrollable="true" :adaptive="true" :pivot-y="0.25">
                    <h4 class="modal-title">{{ trans('brackets/admin-translations::admin.import.title') }}</h4>
                    <div class="modal-body">
                        <div v-show="currentstep == 1">
                            <form>
                            <p class="col-md-12">{{ trans('brackets/admin-translations::admin.import.notice') }}</p>
                            <div class="form-group col-md-12">
                                <div class="file-field">
                                    <div class="btn btn-primary btn-sm float-left">
                                        <span>Choose file</span>
                                        <input type="file" id="file"  name="importFile" ref="file" v-on:change="this.handleImportFileUpload" v-validate="'mimes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet|required'">
                                    </div>
                                    <div class="file-path-wrapper">
                                        <input v-if="importedFile" class="file-path validate" type="text" :placeholder="importedFile.name">
                                        <input v-else class="file-path validate" type="text" placeholder="Upload File">
                                    </div>
                                </div>
                                <span v-if="errors.has('importFile')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('importFile') }}</span>
                            </div>
                            <div class="row col-md-12">
                                <div class="col-md-6">
                                    <p style="margin-top: 5px">{{ trans('brackets/admin-translations::admin.import.language_to_import') }}</p>
                                </div>
                                <div class="col-md-6">
                                    <select class="form-control" v-model="importLanguage" name="importLanguage" ref="import_language" v-validate="'required'">
                                        <option value="">Select language</option>
                                        @foreach($locales as $locale)
                                            <div class="form-group">
                                                <option>{{ strtoupper($locale) }}</option>
                                            </div>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <span v-if="errors.has('importLanguage')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('importLanguage') }}</span>
                                </div>
                            </div>
                            <div class="form-check col-md-12">
                                <input class="form-check-input" type="checkbox" value="" id="onlyMissingLanguages" v-model="onlyMissing" ref="only_missing">
                                <label class="form-check-label" for="onlyMissingLanguages">
                                    {{ trans('brackets/admin-translations::admin.import.do_not_override') }}
                                </label>
                            </div>
                            </form>
                        </div>
                        <div v-show="currentstep == 2" class="col-md-12">
                            <div class="text-center col-md-12">
                                <p>{{ trans('brackets/admin-translations::admin.import.conflict_notice_we_have_found') }} @{{ numberOfFoundTranslations }}
                                    {{ trans('brackets/admin-translations::admin.import.conflict_notice_translations_to_be_imported') }} @{{ numberOfTranslationsToReview }}
                                    {{ trans('brackets/admin-translations::admin.import.conflict_notice_differ') }}
                                </p>
                            </div>

                            <table class="table table-hover">
                                <thead>
                                <tr>
                                    <th>{{ trans('brackets/admin-translations::admin.fields.group') }}</th>
                                    <th>{{ trans('brackets/admin-translations::admin.fields.default') }}</th>
                                    <th>{{ trans('brackets/admin-translations::admin.fields.current_value') }}</th>
                                    <th>{{ trans('brackets/admin-translations::admin.fields.imported_value') }}</th>
                                    <th style="display: none;"></th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="(item, index) in conflicts" v-if="conflicts[index].has_conflict">
                                    <td style="word-break: break-all">@{{ conflicts[index].group }}</td>
                                    <td style="word-break: break-all">@{{ conflicts[index].default }}</td>
                                    <td style="word-break: break-all">
                                        <input type="radio" v-bind:value="true" v-model="conflicts[index].checkedCurrent" :id="'current-' + index + '0'" :name="'current-' + index" v-validate="'required'">
                                        <label class="form-check-label" :for="'current-' + index + '0'">
                                            @{{ conflicts[index].current_value }}
                                        </label>
                                    </td>
                                    <td style="word-break: break-all">
                                        <input type="radio" v-bind:value="false" v-model="conflicts[index].checkedCurrent" :id="'current-' + index + '1'" :name="'current-' + index">
                                        <label class="form-check-label" :for="'current-' + index + '1'">
                                            @{{ conflicts[index][importLanguage.toLowerCase()] }}
                                        </label>
                                    </td>
                                    <td style="display: none;"></td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-show="currentstep == 3">
                            <div class="text-center col-md-12">
                                <p> @{{numberOfSuccessfullyImportedLanguages}} {{ trans('brackets/admin-translations::admin.import.sucesfully_notice') }} @{{numberOfSuccessfullyUpdatedLanguages}} {{ trans('brackets/admin-translations::admin.import.sucesfully_notice_update') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" v-if="!this.lastStep" class="btn btn-primary col-md-2" :disabled="errors.any()" @click.prevent="nextStep()">Next</button>
                    </div>
                </modal>

                <modal name="export-translation" class="modal--translation" v-cloak height="auto" :scrollable="true" :adaptive="true" :pivot-y="0.25">
                    <h4 class="modal-title">{{ trans('brackets/admin-translations::admin.index.export') }}</h4>
                    <div class="text-center">
                        <form @submit.prevent.once="onSubmitExport">
                            <p class="text-left">{{ trans('brackets/admin-translations::admin.export.notice') }}</p>
                            <div class="row col-md-12">
                                <label>{{ trans('brackets/admin-translations::admin.export.language_to_export') }}</label>
                                <select class="form-control" v-model="exportLanguage" name="exportLanguage" v-validate="'required'">
                                    <option value="">Select language</option>
                                    @foreach($locales as $locale)
                                        <div class="form-group">
                                            <option>{{ strtoupper($locale) }}</option>
                                        </div>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-12">
                                <span v-if="errors.has('exportLanguage')" class="form-control-feedback form-text" v-cloak>@{{ errors.first('exportLanguage') }}</span>
                            </div>
                            <br>
                            <input class="form-check-input" type="checkbox" id="exportChecked" v-model="templateChecked">
                            <label class="form-check-label text-left" for="exportChecked">
                                {{ trans('brackets/admin-translations::admin.export.export_reference_language') }}
                            </label>
                            <div class="row col-md-12" v-if="templateChecked">
                                <label>{{ trans('brackets/admin-translations::admin.export.reference_langauge') }}</label>
                                <select class="form-control" v-model="templateLanguage">
                                    <option value="">Select language</option>
                                    @foreach($locales as $locale)
                                        <div class="form-group">
                                            <option>{{ strtoupper($locale) }}</option>
                                        </div>
                                    @endforeach
                                </select>
                            </div>
                            <button class="modal-submit btn btn-block btn-primary col-md-2 float-right" class="form-control" type="submit"><i class="fa fa-file-excel-o"></i> {{ trans('brackets/admin-translations::admin.btn.export') }}</button>
                        </form>
                    </div>
                </modal>

                <div class="card">
                    <div class="card-header">
                        <i class="fa fa-align-justify"></i> {{ trans('brackets/admin-translations::admin.index.title') }}
                        <a class="btn btn-primary btn-sm pull-right m-b-0 ml-2" href="#" @click.prevent="showImport()" role="button"><i class="fa fa-upload"></i>&nbsp; {{ trans('brackets/admin-translations::admin.btn.import') }}</a>
                        <a class="btn btn-primary btn-sm pull-right m-b-0 ml-2" href="#" @click.prevent="showExport()" role="button"><i class="fa fa-file-excel-o"></i>&nbsp; {{ trans('brackets/admin-translations::admin.btn.export') }}</a>
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
    </translation-listing2>

@endsection