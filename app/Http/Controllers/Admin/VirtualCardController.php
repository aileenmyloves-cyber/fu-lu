<?php

namespace App\Http\Controllers\Admin;

use App\Constants\PaymentGatewayConst;
use App\Exports\VirtualCardTrxExport;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\VirtualCardApi;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;


class VirtualCardController extends Controller
{
    public function cardApi()
    {
        $page_title = __("Setup Virtual Card Api");
        $api = VirtualCardApi::first();
        return view('admin.sections.virtual-card.api',compact(
            'page_title',
            'api',
        ));
    }
    public function cardApiUpdate(Request $request){
        $card_limit_rules = ['required', 'numeric'];

        if ($request->api_method !== 'cardyfie') {
            $card_limit_rules[] = Rule::in([1, 2, 3]);
        }

        $validator = Validator::make($request->all(), [
            'api_method'                          => 'required|in:flutterwave,sudo,stripe,strowallet,cardyfie',
            'flutterwave_secret_key'              => 'required_if:api_method,flutterwave',
            'flutterwave_url'                     => 'required_if:api_method,flutterwave',

            'sudo_api_key'                        => 'required_if:api_method,sudo',
            'sudo_vault_id'                       => 'required_if:api_method,sudo',
            'sudo_url'                            => 'required_if:api_method,sudo',
            'sudo_mode'                           => 'required_if:api_method,sudo',

            'stripe_public_key'                   => 'required_if:api_method,stripe',
            'stripe_secret_key'                   => 'required_if:api_method,stripe',
            'stripe_mode'                         => 'required_if:api_method,stripe',
            'stripe_url'                          => 'required_if:api_method,stripe',

            'strowallet_public_key'               => 'required_if:api_method,strowallet',
            'strowallet_secret_key'               => 'required_if:api_method,strowallet',
            'strowallet_url'                      => 'required_if:api_method,strowallet',
            'strowallet_mode'                     => 'required_if:api_method,strowallet',

            'cardyfie_public_key'                 => 'required_if:api_method,cardyfie',
            'cardyfie_secret_key'                 => 'required_if:api_method,cardyfie',
            'cardyfie_sandbox_url'                => 'required_if:api_method,cardyfie',
            'cardyfie_production_url'             => 'required_if:api_method,cardyfie',
            'cardyfie_webhook_secret'             => 'required_if:api_method,cardyfie',
            'cardyfie_mode'                       => 'required_if:api_method,cardyfie',
            'cardyfie_universal_card_issues_fee'  => 'required_if:api_method,cardyfie',
            'cardyfie_platinum_card_issues_fee'   => 'required_if:api_method,cardyfie',
            'cardyfie_card_deposit_fixed_fee'     => 'required_if:api_method,cardyfie',
            'cardyfie_card_deposit_percent_fee'   => 'required_if:api_method,cardyfie',
            'cardyfie_card_withdraw_fixed_fee'    => 'required_if:api_method,cardyfie',
            'cardyfie_card_withdraw_percent_fee'  => 'required_if:api_method,cardyfie',
            'cardyfie_card_maintenance_fixed_fee' => 'required_if:api_method,cardyfie',
            'cardyfie_min_limit'                  => 'required_if:api_method,cardyfie',
            'cardyfie_max_limit'                  => 'required_if:api_method,cardyfie',
            'cardyfie_daily_limit'                => 'required_if:api_method,cardyfie',
            'cardyfie_monthly_limit'              => 'required_if:api_method,cardyfie',

            'card_details'                        => 'required|string',
            'image'                               => "nullable|mimes:png,jpg,jpeg,webp,svg",
            'card_limit'                          => $card_limit_rules,
        ]);

        if($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        $request->merge(['name'=>$request->api_method]);
        $data = array_filter($request->except('_token','api_method','_method','card_details','image','card_limit'));

        $api               = VirtualCardApi::first();
        $api->card_details = $request->card_details;
        $api->card_limit   = $request->card_limit;
        $api->config       = $data;

        if ($request->hasFile("image")) {
            try {
                $image = get_files_from_fileholder($request, "image");
                $upload_file = upload_files_from_path_dynamic($image, "card-api");
                $api->image = $upload_file;
            } catch (Exception $e) {
                return back()->with(['error' => [__("Ops! Failed To Upload Image.")]]);
            }
        }
        $api->save();

        return back()->with(['success' => [__("Card API Has Been Updated.")]]);
    }

    public function cardApiBalance($provider)
    {
        $api    = VirtualCardApi::first();
        $config = $api->config;
        $data   = null;

        if ($provider == 'flutterwave' && @$config->flutterwave_secret_key) {
            $data = flutterwaveBalance();
        } elseif ($provider == 'sudo' && @$config->sudo_api_key) {
            $data = getSudoBalance();
        } elseif ($provider == 'stripe' && @$config->stripe_secret_key) {
            $data = getIssueBalance();
        } elseif ($provider == 'strowallet' && @$config->strowallet_public_key) {
            $data = get_strowallet_wallet_balance($config->strowallet_public_key, $config->strowallet_url, 'USD');
        }

        if (!$data) {
            return response()->json([
                'status'  => false,
                'message' => __("Api Credentials Not Configured."),
                'amount'  => null,
            ]);
        }

        $message = $data['message'];
        if ($provider == 'strowallet' && $data['status'] != true) {
            $message = __("Expired token - Access Restricted!");
        }

        return response()->json([
            'status'  => $data['status'],
            'message' => $message,
            'amount'  => get_amount($data['balance'] ?? $data['amount'] ?? 0, null, get_wallet_precision()),
        ]);
    }

    //user logs
    public function transactionLogs()
    {
        $page_title = __("Virtual Card Logs");
        $transactions = Transaction::with(
          'user:id,firstname,lastname,email,username,full_mobile',
            'currency:id,name',
        )->where('type', PaymentGatewayConst::VIRTUALCARD)->latest()->paginate(20);

        return view('admin.sections.virtual-card.logs', compact(
            'page_title',
            'transactions'
        ));
    }
    public function exportData(){
        $file_name = now()->format('Y-m-d_H:i:s') . "_Virtual_Card_Logs".'.xlsx';
        return Excel::download(new VirtualCardTrxExport, $file_name);
    }
}
