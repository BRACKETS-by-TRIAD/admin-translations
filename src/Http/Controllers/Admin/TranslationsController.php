<?php

namespace Brackets\AdminTranslations\Http\Controllers\Admin;

use Brackets\AdminTranslations\LanguageLine;
use Illuminate\Database\Eloquent\Builder;
use Brackets\AdminTranslations\Http\Requests\Admin\LanguageLine\IndexLanguageLine;
//use Brackets\AdminTranslations\Http\Requests\Admin\LanguageLine\IndexLanguageLine;
use Illuminate\Http\Response;
use Brackets\Admin\AdminListing;

// FIXME what do we do with this?
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TranslationsController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Display a listing of the resource.
     *
     * @param  IndexLanguageLine $request
     * @return Response|array
     */
    public function index(IndexLanguageLine $request)
    {

        // create and AdminListing instance for a specific model and
        $data = AdminListing::instance(LanguageLine::class)->processRequestAndGet(
        // pass the request with params
            $request,

            // set columns to query
            ['id', 'group', 'key', 'text', 'created_at', 'updated_at'],

            // set columns to searchIn
            ['group', 'key', 'text'],

            function(Builder $query) use ($request) {
                if ($request->has('group')) {
                    $query->whereGroup($request->group);
                }
            }
        );

        if ($request->ajax()) {
            return ['data' => $data];
        }

        return $data;

//        return view('admin.billing.my-movie.index', ['data' => $data]);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  UpdateMyMovie $request
     * @param  MyMovie $myMovie
     * @return Response|array
     */
//    public function update(UpdateMyMovie $request, MyMovie $myMovie)
//    {
//        // Sanitize input
//        $sanitized = $request->only([
//            'title',
//            'perex',
//            'publish_at',
//            'publishh_at',
//            'publishhh_at',
//            'is_top',
//            'is_super_top',
//
//        ]);
//
//        // Update changed values MyMovie
//        $myMovie->update($sanitized);
//
//
//        if ($request->ajax()) {
//            return ['redirect' => url('admin/billing/my-movie')];
//        }
//
//        return redirect('admin/billing/my-movie')
//            ->withSuccess("Updated");
//    }

}
