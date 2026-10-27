<?php

namespace App\Http\Controllers\Admin;

use Exception;
use App\Models\Trade;
use App\Models\UserWallet;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Exports\TradeTrxExport;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Constants\PaymentGatewayConst;
use Illuminate\Support\Facades\Validator;

class TradeController extends Controller
{
    public function index()
    {
        $page_title = __("All Logs");
        $transactions = Transaction::with(
            'user:id,firstname,email,username,mobile',
        )->where('type', PaymentGatewayConst::TRADE)->orderBy('id', 'desc')->paginate(20);

        return view('admin.sections.trade.index', compact(
            'page_title',
            'transactions'
        ));
    }


    public function pending()
    {
        $page_title = __("Pending Logs");
        $transactions = Transaction::with([
            'user:id,firstname,email,username,mobile',
            'trade'
        ])
        ->where('type', PaymentGatewayConst::TRADE)
        //
        ->whereHas('trade', function ($query) {
            $query->where('status', '2'); // Replace 'active' with your desired status value
        })
        ->orderBy('id', 'desc')
        ->paginate(20);

        return view('admin.sections.trade.index', compact(
            'page_title',
            'transactions'
        ));
    }


    public function logDetails($id){
        $data = Transaction::where('id',$id)->with(
            'user:id,firstname,email,username,full_mobile,image',
            'currency:id,name,alias,payment_gateway_id,currency_code,rate',
        )->where('type', PaymentGatewayConst::TRADE)->first();

        $page_title = __("Trade details for").'  '.$data->trx_id;
        return view('admin.sections.trade.details', compact(
            'page_title',
            'data'
        ));
    }


    public function complete()
    {
        $page_title = "Complete Logs";
        $transactions = Transaction::with(
            'user:id,firstname,email,username,mobile','trade'
        )->where('type', PaymentGatewayConst::TRADE)
        ->whereHas('trade', function($q){
            $q->where('status', 6);
        })->orderBy('id', 'desc')
        ->where('status', 1)
        ->paginate(20);

        return view('admin.sections.trade.index', compact(
            'page_title',
            'transactions'
        ));
    }

    public function ongoingLogs()
    {
        $page_title = __("Ongoing Logs");
        $transactions = Transaction::with(
            'user:id,firstname,email,username,mobile','trade'
        )->where('type', PaymentGatewayConst::TRADE)
        ->whereHas('trade', function($q){
            $q->where('status', 1);
        })->orderBy('id', 'desc')
        ->where('status', 1)
        ->paginate(20);
        return view('admin.sections.trade.index', compact(
            'page_title',
            'transactions'
        ));
    }


    public function canceled()
    {
        $page_title = __("Canceled Logs");
        $transactions = Transaction::with(
            'user:id,firstname,email,username,mobile',
        )->where('type', PaymentGatewayConst::TRADE)->orderBy('id', 'desc')->where('status', 4)->paginate(20);
        return view('admin.sections.trade.index', compact(
            'page_title',
            'transactions'
        ));
    }

    public function canceledRequest()
    {
        $page_title = __("Cancel Request Logs");
        $transactions = Transaction::with(
            'user:id,firstname,email,username,mobile',
        )->where('type', PaymentGatewayConst::TRADE)->orderBy('id', 'desc')->where('status', 7)->paginate(20);
        return view('admin.sections.trade.index', compact(
            'page_title',
            'transactions'
        ));
    }

     /**
     * This method for approved add money
     * @method PUT
     * @param Illuminate\Http\Request $request
     * @return Illuminate\Http\Request Response
     */
    public function approved(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);

        if($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = Transaction::with('trade')->where('id',$request->id)->where('status',7)->where('type', PaymentGatewayConst::TRADE)->first();

        $trade = Trade::with('saleCurrency')->findOrFail($data->trade->id);

        $wallet = UserWallet::where('user_id', $trade->user_id)->where('currency_id', $trade->currency_id)->first();
        try{
            $wallet->balance = $wallet->balance + $data->request_amount;
            $wallet->save();
            // update trade
            $trade->status = 8;
            $trade->save();
            //update transaction
            $data->status = 8;
            $data->save();

            return redirect()->back()->with(['success' => [__('Trade closed request approved successfully')]]);
        }catch(Exception $e){
            return back()->with(['error' => [$e->getMessage()]]);
        }
    }

    /**
     * This method for reject add money
     * @method PUT
     * @param Illuminate\Http\Request $request
     * @return Illuminate\Http\Request Response
     */
    public function rejected(Request $request){
        $validator = Validator::make($request->all(),[
            'id' => 'required|integer',
            'reject_reason' => 'required|string',
        ]);
        if($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        $data = Transaction::with('trade')->where('id',$request->id)->where('status',7)->where('type', PaymentGatewayConst::TRADE)->first();
        $trade = Trade::findOrFail($data->trade->id);
        $reject['status'] = 4;
        $reject['reject_reason'] = $request->reject_reason;
        try{
            $data->fill($reject)->save();
             // update trade
             $trade->status = 4;
             $trade->save();
            return redirect()->back()->with(['success' => [__('Trade request canceled successfully')]]);
        }catch(Exception $e){
            return back()->with(['error' => [$e->getMessage()]]);
        }
    }




    public function exportData(){
        $file_name = now()->format('Y-m-d_H:i:s') . "_marketplace_Logs".'.xlsx';
        return Excel::download(new TradeTrxExport,$file_name);
    }




}
