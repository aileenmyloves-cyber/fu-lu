<?php

namespace App\Http\Controllers\Api\Global;

use App\Constants\SiteSectionConst;
use App\Http\Controllers\Controller;
use App\Http\Helpers\Api\Helpers;
use App\Models\Admin\SiteSections;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Contact;
use App\Models\Newsletter;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GlobalController extends Controller
{

    public function sendContactMessage(Request $request){
        $validator = Validator::make($request->all(),[
            'name'    => 'required|string',
            'email'   => 'required|email',
            'mobile'  => 'required',
            'subject' => 'required|string',
            'message' => 'required|string',
        ]);

        $user = auth()->user();

        if($validator->fails()){

            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }

        $validated = $validator->validated();

        try {
            Contact::create($validated);
        } catch (Exception $e) {
            $error = ['error'=>[__('Something went wrong! Please try again')]];
            return Helpers::error($error);
        }

        $message =  ['success'=>[__('Message send successfully!')]];
        return Helpers::onlySuccess($message);
    }

    public function sendSubscribeRequest(Request $request){
        $validator = Validator::make($request->all(),[
           'name' => 'required|string|max:100',
           'email'    => 'required|string|email|max:255|unique:newsletters,email',
        ]);


        if($validator->fails()){
            $error = ['error' => [$validator->errors()->all()]];
            return Helpers::validation($error);
        }

        $validated               = $validator->validated();
        $validated['created_at'] = now();

        try {
            Newsletter::create($validated);
        } catch (Exception $e) {
           $error = ['error'=>[__('Something went wrong! Please try again')]];
            return Helpers::error($error);
        }

        $message =  ['success'=>[__('Subscription successful!')]];
        return Helpers::onlySuccess($message);
    }

    public function getSiteSectionData(Request $request){

        $section_slug  = \Illuminate\Support\Str::slug(SiteSectionConst::FOOTER_SECTION);
        $social_media  = SiteSections::getData($section_slug)->first();
        $items = (array) $social_media->value->items;
        $social_icons = [];
        foreach ($items as $item) {
            $social_icons[] = (array) $item->language;
        }

        $announcement_categories = BlogCategory::active()->latest()->get();
        $announcement_categories->makeHidden(['status','editData','admin_id']);

        if(isset($announcement_categories)){
            $category = [];
            foreach ($announcement_categories ?? [] as $value) {
                $name =  $value->name;
                $category[] = [
                    'id'      => $value->id,
                    'name'    => $name,
                    'data'    => $value->data,
                    'slug'    => $value->slug,
                    'has_announcements'    => (int)count($value->announcements),
                ];
            }

        }else{
            $category = [];
        }

        $announcements = Blog::active()->orderBy('id',"DESC")->get();
        $announcements->makeHidden(['status','editData','admin_id','short_title','tags']);


        $data =[
            'social_icons'            => $social_icons,
            'announcement_categories' => $category,
            'announcements'           => $announcements,
            'base_url'                => get_asset_url(),
            'default_image'           => files_asset_path_basename("default"),
            'image_path'              => files_asset_path_basename("blog"),

        ];


        $message =  ['success'=>[__('Section Information!')]];
        return Helpers::success($data, $message);

    }
}