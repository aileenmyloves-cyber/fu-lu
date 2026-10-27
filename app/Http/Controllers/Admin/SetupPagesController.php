<?php

namespace App\Http\Controllers\Admin;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Constants\GlobalConst;
use App\Http\Helpers\Response;
use App\Models\Admin\SetupPage;
use App\Models\Admin\SiteSections;
use App\Constants\SiteSectionConst;
use App\Http\Controllers\Controller;
use App\Models\Admin\SetupPageHasSection;
use Illuminate\Support\Facades\Validator;

class SetupPagesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $page_title = __("Setup Pages");
        $type = Str::slug(GlobalConst::SETUP_PAGE);
        $setup_pages = SetupPage::where('type', $type)->get();
        return view('admin.sections.setup-pages.index',compact(
            'page_title',
            'setup_pages',
        ));
    }

    public function statusUpdate(Request $request) {
        $validator = Validator::make($request->all(),[
            'status'                    => 'required|boolean',
            'data_target'               => 'required|string',
        ]);
        if ($validator->stopOnFirstFailure()->fails()) {
            $error = ['error' => $validator->errors()];
            return Response::error($error,null,400);
        }
        $validated = $validator->safe()->all();
        $page_slug = $validated['data_target'];

        $page = SetupPage::where('slug',$page_slug)->first();
        if(!$page) {
            $error = ['error' => [__("Page not found!")]];
            return Response::error($error,null,404);
        }

        try{
            $page->update([
                'status' => ($validated['status'] == true) ? false : true,
            ]);
        }catch(Exception $e) {
            return $e;
            $error = ['error' => [__("Something went wrong! Please try again.")]];
            return Response::error($error,null,500);
        }

        $success = ['success' => [__("Setup Page status updated successfully!")]];
        return Response::success($success,null,200);
    }

    /**
     * Method for view the setup page details page
     * @return view
     */
    public function details($slug){
        $page_title         = __("Setup Page Details");
        $setup_page         = SetupPage::with('sections.section')->where('slug',$slug)->first();


        if(!$setup_page) return back()->with(['error' => [__('Sorry! Setup page not found.')]]);

        $excludedKeys = array_map('strtolower', array_map('trim', SiteSectionConst::notDisplaySections()));


        if ($setup_page && $setup_page->sections->isNotEmpty()) {
            $ordered_sections = collect();

            foreach ($setup_page->sections as $assigned) {
                if ($assigned->section) {
                    $ordered_sections->push($assigned->section);
                }
            }

            $existing_keys = $ordered_sections->pluck('key')->map(fn($k) => strtolower(trim($k)))->toArray();

            $remaining_sections = SiteSections::whereNotIn('key', $existing_keys)
                ->whereNotIn('key', $excludedKeys)
                ->get();

            $site_sections = $ordered_sections
                ->reject(fn($section) => in_array(strtolower(trim($section->key)), $excludedKeys))
                ->merge($remaining_sections);

        } else {
            $site_sections = SiteSections::whereNotIn('key', $excludedKeys)->get();
        }


        return view('admin.sections.setup-pages.details',compact(
            'page_title',
            'setup_page',
            'site_sections'
        ));
    }
    /**
     * Method for store section information
     * @param Illuminate\Http\Request $request $slug
     */
    public function updateSection(Request $request,$slug){
        $setup_page = SetupPage::where('slug', $slug)->first();
        if (!$setup_page) {
            return back()->with(['error' => [__('Sorry! Setup page not found.')]]);
        }

        $validator = Validator::make($request->all(), [
            'sections'   => 'required|array',
            'sections.*' => 'required|string',
            'status'     => 'required|array',
            'status.*'   => 'required|in:0,1',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput($request->all());
        }

        $validated = $validator->validated();

        foreach ($validated['sections'] ?? [] as $index => $section_key) {
            $section = SiteSections::where('key', $section_key)->first();
            if (!$section) {
                continue;
            }

            $position               = $index + 1;
            $status                 = $validated['status'][$index] ?? 1;

            $existing               = SetupPageHasSection::where([
                'setup_page_id'     => $setup_page->id,
                'site_section_id'   => $section->id,
            ])->first();

            if($existing){
                $existing->update([
                    'position' => $position,
                    'status'   => $status,
                ]);
            }else{
                SetupPageHasSection::create([
                    'setup_page_id'   => $setup_page->id,
                    'site_section_id' => $section->id,
                    'position'        => $position,
                    'status'          => $status,
                ]);
            }
        }

        return back()->with(['success' => [__('Sections updated successfully.')]]);
    }

}
