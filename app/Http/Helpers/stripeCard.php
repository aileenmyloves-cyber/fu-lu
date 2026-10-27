<?php


use App\Models\VirtualCardApi;
use Illuminate\Support\Collection;


function createConnectAccount($request_data){
    $method = VirtualCardApi::first();
    $apiKey = $method->config->stripe_secret_key;
    $countries = get_all_countries();
    $currency = $request_data['currency'] ?? get_default_currency_code();
    $country = Collection::make($countries)->first(function ($item) use ($currency) {
        if($currency == "GBP"){
            return $item->currency_code === $currency && $item->name === "United Kingdom";
        }else{
            return $item->currency_code === $currency;
        }
    });
    try{
        $stripe = new \Stripe\StripeClient($apiKey);
        $result= $stripe->accounts->create([
           'country' => $country->iso2 ?? 'US',
            'capabilities' => [
                'transfers' => ['requested' => true],
                'card_issuing' => ['requested' => true],
                'treasury' => ['requested' => true],
            ],

            'controller' => [
            'stripe_dashboard' => ['type' => 'none'],
            'fees' => ['payer' => 'application'],
            'losses' => ['payments' => 'application'],
            'requirement_collection' => 'application',
            ]
        ]);
        $data =[
            'status'        => true,
            'message'       => "Connected Account Created",
            'data'          => $result,

        ];
    }catch(Exception $e){
        $data =[
            'status'        => false,
            'message'       => $e->getMessage()." [Please Contact With Stripe Support]",
            'data'          => null,
        ];

    }
    return $data;
}
function createFinancialAccount($request_data, $c_account) {

    // Retrieve API key
    $method     = VirtualCardApi::first();
    $apiKey     = $method->config->stripe_secret_key;
    $base_url   = $method->config->stripe_url;

    // Get currency
    $currency = strtolower($request_data['currency']) ?? strtolower(get_default_currency_code());
    try {
        $data = [
          'supported_currencies' => [$currency],
            'features[card_issuing][requested]' => 'true',
            'features[deposit_insurance][requested]' => 'true',
            'features[financial_addresses][aba][requested]' => 'true',
            'features[inbound_transfers][ach][requested]' => 'true',
            'features[intra_stripe_flows][requested]' => 'true',
            'features[outbound_payments][ach][requested]' => 'true',
            'features[outbound_payments][us_domestic_wire][requested]' => 'true',
            'features[outbound_transfers][ach][requested]' => 'true',
            'features[outbound_transfers][us_domestic_wire][requested]' => 'true',
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $base_url.'/treasury/financial_accounts');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); // Convert to x-www-form-urlencoded format
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Stripe-Account: ' . $c_account,
            'Content-Type: application/x-www-form-urlencoded' // Required by Stripe API
        ]);


        $response = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($response, true);

        return [
            'status'  => true,
            'message' => "Financial Account Created Successfully",
            'data'    => $result,
        ];

    } catch (Exception $e) {
        return [
            'status'  => false,
            'message' => $e->getMessage() . " [Please Contact Stripe Support]",
            'data'    => null,
        ];
    }
}
function createCardHolders($user,$c_account,$request_data){
    $client_ip = request()->ip() ?? false;
    $method = VirtualCardApi::first();
    $apiKey = $method->config->stripe_secret_key;
    $countries = get_all_countries();
    $currency = $request_data['currency'] ?? get_default_currency_code();
    $country = Collection::make($countries)->first(function ($item) use ($currency) {
        if($currency == "GBP"){
            return $item->currency_code === $currency && $item->name === "United Kingdom";
        }else{
            return $item->currency_code === $currency;
        }
    });

    try{
        $stripe = new \Stripe\StripeClient( $apiKey);
        $result = $stripe->issuing->cardholders->create(
            [

                'name' => $user->fullname,
                'email' => $user->email,
                'phone_number' =>  $user->full_mobile,
                'status' => 'active',
                'type' => 'individual',
                'individual' => [
                    'card_issuing' => [
                        'user_terms_acceptance' => [
                            'date' => time(),
                            'ip' => $client_ip,
                            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                        ],
                    ],
                    'first_name' => $user->firstname,
                    'last_name' => $user->lastname,
                    'dob' => ['day' => 1, 'month' => 11, 'year' => 1981],
                ],
                'billing' => [
                    'address' => [
                        'line1' => $user->address->address,
                        'city' => $user->address->city,
                        'state' => $user->address->state,
                        'postal_code' => $user->address->zip,
                        'country' => $country->iso2,
                    ],
                ],
            ],
            ['stripe_account' => $c_account]
        );
        $data =[
            'status'        => true,
            'message'       => "Card Holder Created",
            'data'          => $result,

        ];
    }catch(Exception $e){
        $data =[
            'status'        => false,
            'message'       => $e->getMessage()." [Please Contact With Stripe Support]",
            'data'          => null,
        ];

    }
    return $data;
}
function createVirtualCard($card_holder_id,$c_account,$f_account,$request_data){

    $method = VirtualCardApi::first();
    $secretKey = $method->config->stripe_secret_key;
    $cardholderId = $card_holder_id;
    $currency = $request_data['currency'] ?? get_default_currency_code();


   try{
    $stripe = new \Stripe\StripeClient($secretKey);
    $result = $stripe->issuing->cards->create(
        [
            'cardholder'        => $cardholderId,
            'currency'          => strtolower($currency),
            'financial_account' => $f_account,
            'type'              => 'virtual',
            'status'            => 'active',
        ],
        ['stripe_account' => $c_account]
    );
        $data =[
            'status'        => true,
            'message'       => "Card Created",
            'data'          => $result,

        ];
    }catch(Exception $e){
        $data =[
            'status'        => false,
            'message'       => $e->getMessage()." [Please Contact With Stripe Support]",
            'data'          => null,
        ];

    }
    return $data;

}
function cardActiveInactive($card_holder_id,$status){
    $method = VirtualCardApi::first();
    $secretKey = $method->config->stripe_secret_key;
    $cardholderId = $card_holder_id;
    $cardId = $cardholderId;
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $method->config->stripe_url.'/issuing/cards/' . $cardId);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'status' => $status,
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $secretKey,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    $result = json_decode($response,true);

    if(isset($result['error'])){
        $data = [
            'status'  => false,
            'message'  => $result['error']['message']??"Somethings Is Wrong!",
            'data'  => [],
        ];
    }else{
        $data = [
            'status'  => true,
            'message'  => "Card Updated Successfully",
            'data'  => $result,
        ];
    }
  return $data;
}
function getSensitiveData($cardId){
    $method = VirtualCardApi::first();
    $apiKey = $method->config->stripe_secret_key;
    $cardId = $cardId;
    try{
        $stripe = new \Stripe\StripeClient($apiKey);
        $result = $stripe->issuing->cards->retrieve(
            $cardId,
            ['expand' => ['number', 'cvc']]
        );
        $data =[
            'status'        =>true,
            'message'       =>"Got Sensitive Data Successfully",
            'number'        =>$result->number,
            'cvc'           =>$result->cvc,
        ];
    }catch(Exception $e){
        $data =[
            'status'        =>false,
            'message'       =>"Something Is Wrong, please Contact With Owner",
            'number'        => "",
            'cvc'           =>"",
        ];
    }
    return $data;

}
function getIssueBalance(){
    $method = VirtualCardApi::first();
    $apiKey = $method->config->stripe_secret_key;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $method->config->stripe_url.'/balance');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response,true);

    if(isset($result['error'])){
        $data = [
            'status'  => false,
            'message' =>__("Something went wrong! Please try again."),
            'amount'  => 0.0,
        ];
    }else{
        $data = [
            'status'  => true,
            'message'  => __("SuccessFully Fetch Account Balance"),
            'amount'  => $result['available'][0]['amount']/100 ?? 0,
        ];
    }
    return $data;

}
function getStripeCardTransactions($cardId){
    $method = VirtualCardApi::first();
    $apiKey = $method->config->stripe_secret_key;
    $stripe = new \Stripe\StripeClient($apiKey);
    $cardId = $cardId;
    try{
        $transactions = $stripe->issuing->transactions->all([
            'card' => $cardId,
        ]);
        $data =[
            'status'        =>true,
            'data'          =>$transactions['data']
        ];
    }catch(Exception $e){
        $data =[
            'status'        =>false,
            'data'          => []
        ];
    }
    return $data;

}
function transfer($request_data,$c_account){
    $method = VirtualCardApi::first();
    $secretKey = $method->config->stripe_secret_key;
    $stripe = new \Stripe\StripeClient($secretKey);

    try{
        $result = $stripe->transfers->create([
            'amount' => $request_data['card_amount'],
            'currency' => strtolower($request_data['currency'] ?? get_default_currency_code()),
            'destination' => $c_account,
        ]);
        $data =[
            'status'        => true,
            'message'       => "Transfer Done",
            'data'          => $result,

        ];
    }catch(Exception $e){
        $data =[
            'status'        => false,
            'message'       => $e->getMessage()." [Please Contact With Stripe Support]",
            'data'          => null,
        ];

    }
    return $data;
}
function updateAccount($user,$c_account,$request_data){
    $method = VirtualCardApi::first();
    $secretKey = $method->config->stripe_secret_key;
    $client_ip = request()->ip() ?? false;
    $countries = get_all_countries();
    $currency = $request_data['currency'] ?? get_default_currency_code();
    $country = Collection::make($countries)->first(function ($item) use ($currency) {
        if($currency == "GBP"){
            return $item->currency_code === $currency && $item->name === "United Kingdom";
        }else{
            return $item->currency_code === $currency;
        }
    });

    try{
    $stripe = new \Stripe\StripeClient( $secretKey);
    $result = $stripe->accounts->update(
        $c_account,
        [
            // 'tos_acceptance' => [
            // 'date' => time(),
            // 'ip' =>  $client_ip,
            // ],
            'business_type' => 'individual', // Set the business type
            'email' => $user->email,
            'individual' => [
                'first_name'    => $user->firstname,
                'last_name'     => $user->lastname,

                'dob' => [
                    'day' => '01',
                    'month' => '11',
                    'year' => '1981',
                ],
                'address' => [
                    'line1'         => $user->address->address,
                    'city'          => $user->address->city,
                    'state'         => $user->address->state,
                    'postal_code'   => $user->address->zip,
                    'country'       => $country->iso2,
                ],
                'ssn_last_4' => '',
            ],
            // 'business_profile' => [
            //     'mcc' => '', // Merchant Category Code
            //     'url' => '',
            // ],
            // 'external_account' => [
            //     'object' => 'bank_account',
            //     'country' => 'US',
            //     'currency' => 'usd',
            //     'routing_number' => '',
            //     'account_number' => '',
            //     'account_holder_name' => '',
            //     'account_holder_type' => '',
            // ]
        ]
        );
        $data =[
            'status'        => true,
            'message'       => "Account Updated",
            'data'          => $result,

        ];
    }catch(Exception $e){
        $data =[
            'status'        => false,
            'message'       => $e->getMessage()." [Please Contact With Stripe Support]",
            'data'          => null,
        ];

    }
    return $data;
}
/************************Start Test Mode Card Helper*******************************/
function test_cardHolder($user,$request_data){
    $client_ip = request()->ip() ?? false;
    $method = VirtualCardApi::first();
    $apiKey = $method->config->stripe_secret_key;
    $countries = get_all_countries();
    $currency = $request_data['currency'] ?? get_default_currency_code();
    $country = Collection::make($countries)->first(function ($item) use ($currency) {
        if($currency == "GBP"){
            return $item->currency_code === $currency && $item->name === "United Kingdom";
        }else{
            return $item->currency_code === $currency;
        }
    });

    try{
        $stripe = new \Stripe\StripeClient( $apiKey);
        $result = $stripe->issuing->cardholders->create(
            [
                'name'          => $user->fullname,
                'email'         => $user->email,
                'phone_number'  =>  $user->full_mobile,
                'status'        => 'active',
                'type'          => 'individual',
                'individual' => [
                    'card_issuing' => [
                        'user_terms_acceptance' => [
                            'date'          => time(),
                            'ip'            => $client_ip,
                            'user_agent'    => $_SERVER['HTTP_USER_AGENT'],
                        ],
                    ],
                    'first_name'    => $user->firstname,
                    'last_name'     => $user->lastname,
                    'dob' => ['day' => 1, 'month' => 11, 'year' => 1989],
                ],
                'billing' => [
                    'address' => [
                        'line1'         => $user->address->address,
                        'city'          => $user->address->city,
                        'state'         => $user->address->state,
                        'postal_code'   => $user->address->zip,
                        'country'       => $country->iso2,
                    ],
                ],
            ]

        );

        $data =[
            'status'        => true,
            'message'       => "Card Holder Created",
            'data'          => $result,

        ];
    }catch(Exception $e){
        $data =[
            'status'        => false,
            'message'       => $e->getMessage()." [Please Contact With Stripe Support]",
            'data'          => null,
        ];

    }
    return $data;


 }

 function create_test_card($card_holder_id,$request_data){
    $method = VirtualCardApi::first();
    $secretKey = $method->config->stripe_secret_key;
    $cardholderId = $card_holder_id;

   try{
        $stripe = new \Stripe\StripeClient($secretKey);
        $result = $stripe->issuing->cards->create(
            [
                'cardholder'=> $cardholderId,
                'currency'  => strtolower($request_data['currency']) ?? strtolower(get_default_currency_code()),
                'type'      => 'virtual',
                'status'    => 'active',
            ]

        );
        $data =[
            'status'        => true,
            'message'       => "Card Created Successful",
            'data'          => $result,

        ];
    }catch(Exception $e){
        $data =[
            'status'        => false,
            'message'       => $e->getMessage()." [Please Contact With Stripe Support]",
            'data'          => null,
        ];

    }

    return $data;

}
/************************End Test Mode Card Helper*********************************/
