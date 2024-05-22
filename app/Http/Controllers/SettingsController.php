<?php

namespace App\Http\Controllers;

use App\Http\Middleware\RedirectIfNotAdmin;
use App\Http\Middleware\RedirectIfNotParmittedMultiple;
use App\Models\Language;
use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Jackiedo\DotenvEditor\Facades\DotenvEditor;

class SettingsController extends Controller {
    public function __construct(){
//        $this->middleware(RedirectIfNotParmittedMultiple::class.':global,smtp');
        $this->middleware(RedirectIfNotAdmin::class.':global,smtp');
    }

    private function configExist($array){
        $hasValue = true;
        $envLoad = DotenvEditor::load();
        $keys = $envLoad->getKeys($array);
        foreach ($keys as $key){
            if(!$key['value']){
                $hasValue = false;
                break;
            }
        }
        return $hasValue;
    }

    public function index(){
        $settings = Setting::orderBy('id')->get();
        $settingData = [];
        foreach ($settings as $setting){
            $settingData[$setting['slug']] = ['id' => $setting->id, 'name' => $setting->name, 'slug' => $setting->slug, 'type' => $setting->type, 'value' => $setting->value];
            if($setting->type === 'json'){
                $settingData[$setting['slug']]['value'] = $setting->value? json_decode($setting->value, true): null;
            }
        }
        $customCss = File::get(public_path('css/custom.css'));
        $settingData['custom_css'] = ['slug' => 'custom_css', 'name' => 'Custom CSS', 'value' => $customCss];
        return Inertia::render('Settings/Index', [
            'title' => 'Global Settings',
            'settings' => $settingData,
            'languages' => Language::orderBy('name')
                ->get()
                ->map
                ->only('code', 'name'),
        ]);
    }

    public function update(){
        $requests = Request::all();

        if (config('app.demo')) {
            return Redirect::back()->with('error', 'Updating global settings are not allowed for the live demo.');
        }

        $requests['enable_registration'] = (int) $requests['enable_registration'];

        if(!empty($requests['custom_css'])){
            Storage::disk('public_path')->put('css/custom.css', $requests['custom_css']);
        }

        array_splice($requests, array_search($requests['custom_css'], array_values($requests)), 1);
        $jsonFields = ['hide_ticket_fields'];

        $requestsData =  ['app_name' => $requests['app_name'], 'enable_registration' => $requests['enable_registration'], 'default_language' => $requests['default_language'], 'email_notifications' => $requests['email_notifications']];

        foreach ($requestsData as $requestKey => $requestValue){
            $setting = Setting::where('slug', $requestKey)->first();
            if(isset($setting)){
                $setting->value = $setting->type == 'json' ? json_encode($requestValue) : $requestValue;
                $setting->save();
            }else{
                Setting::create([
                    'slug' => $requestKey,
                    'name' => ucfirst(str_replace('_', ' ', $requestKey)),
                    'type' => in_array($requestKey, $jsonFields)? 'json' : 'text',
                    'value' => in_array($requestKey, $jsonFields)? json_encode($requestValue) : $requestValue,
                ]);
            }
        }

        if(Request::file('logo') && !empty(Request::file('logo'))){
            Request::file('logo')->storeAs('/', 'logo.png', ['disk' => 'image']);
        }

        if(Request::file('logo_white') && !empty(Request::file('logo_white'))){
            Request::file('logo_white')->storeAs('/', 'logo_white.png', ['disk' => 'image']);
        }

        if(Request::file('favicon')){
            Request::file('favicon')->storeAs('/', 'favicon.png', ['disk' => 'public_path']);
        }

        if(!empty($requests['default_language'])){
            $env = DotenvEditor::load();
            $env->setKey('LOCALE', $requests['default_language']);
            $env->save();
        }

        return Redirect::route('global')->with('success', 'Settings updated.');
    }

    public function smtp(){
        $demo = config('app.demo');
        $env = DotenvEditor::load();
        $keys = $env->getKeys(['MAIL_HOST','MAIL_PORT','MAIL_USERNAME','MAIL_PASSWORD','MAIL_ENCRYPTION','MAIL_FROM_ADDRESS','MAIL_FROM_NAME']);
        return Inertia::render('Settings/Smtp', [
            'title' => 'SMTP Settings',
            'keys' => $keys,
            'demo' => boolval($demo),
        ]);
    }

    public function updateSmtp(){
        if (config('app.demo')) {
            return Redirect::back()->with('error', 'Updating SMTP setup is not allowed for the live demo.');
        }

        $mailVariables = Request::validate([
            'MAIL_HOST' => ['required'],
            'MAIL_PORT' => ['required'],
            'MAIL_USERNAME' => ['required'],
            'MAIL_PASSWORD' => ['required'],
            'MAIL_ENCRYPTION' => ['required'],
            'MAIL_FROM_ADDRESS' => ['nullable', 'email'],
            'MAIL_FROM_NAME' => ['nullable'],
        ]);
        $this->setEnvVariables($mailVariables);
        return Redirect::back()->with('success', 'SMTP configuration updated!');
    }

    private function setEnvVariables($data) {
        $env = DotenvEditor::load();
        foreach ($data as $data_key => $data_value){
            $env->setKey($data_key, $data_value);
        }
        $env->save();
    }

    public function clearCache($slug){
        // php artisan optimize && php artisan cache:clear && php artisan route:cache && php artisan view:clear && php artisan config:cache
        $slugArray = [
            'config' => 'config:cache', 'optimize' => 'optimize', 'cache' => 'cache:clear',
            'route' => 'route:cache', 'view' => 'view:clear'
        ];
        if(isset($slugArray[$slug])){
            Artisan::call($slugArray[$slug]);
        }elseif($slug == 'all'){
            Artisan::call('optimize');
            Artisan::call('cache:clear');
            Artisan::call('route:cache');
            Artisan::call('view:clear');
            Artisan::call('config:cache');
            Artisan::call('clear-compiled');
        }
        return response()->json(['success'=>true]);
    }
}
