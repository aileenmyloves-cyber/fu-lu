<?php

use App\Constants\GlobalConst;
use App\Models\StrowalletVirtualCard;
use GuzzleHttp\Client;
use App\Models\VirtualCardApi;


function parse_error($response, $fallback = "Something went wrong. Please try again.")
{
    $message = $response['message'] ?? $fallback;

    if (is_array($message)) {
        // {property, constraints} format
        if (isset($message[0]) && is_array($message[0]) && array_key_exists('property', $message[0])) {
            return array_map(function ($item) {
                $property    = $item['property'] ?? '';
                $constraints = isset($item['constraints']) ? implode(', ', array_values($item['constraints'])) : '';
                return $property . ': ' . $constraints;
            }, $message);
        }

        // field => [errors] format (e.g. ["public_key" => ["The public key field is required."]])
        $errors = [];
        foreach ($message as $fieldErrors) {
            foreach ((array) $fieldErrors as $error) {
                $errors[] = $error;
            }
        }
        return $errors ?: [$fallback];
    }

    return [$message ?? "Something went wrong! Please try again."];
}

function stro_wallet_create_user($formData,$public_key,$base_url,$idImage,$userPhoto){
    $client = new Client();

    $response               = $client->request('POST', $base_url.'create-user/', [
        'headers'           => [
            'accept'        => 'application/json',
        ],
        'form_params'       => [
            'public_key'    => $public_key,
            'houseNumber'   => $formData['house_number'],
            'firstName'     => $formData['first_name'],
            'lastName'      => $formData['last_name'],

            'idNumber'      => rand(123456789,987654321),
            'customerEmail' => $formData['customer_email'],
            'phoneNumber'   => $formData['phone'],
            'dateOfBirth'   => $formData['date_of_birth'],
            'idImage'       => $idImage??"",
            'userPhoto'     => $userPhoto??"",
            'line1'         => $formData['address'],
            'state'         => 'Accra',
            'zipCode'       => $formData['zip_code'],
            'city'          => 'Accra',
            'country'       => 'Ghana',
            'idType'        => 'PASSPORT',
        ],
    ]);

    $result         = $response->getBody();
    $decodedResult  = json_decode($result, true);


    if(isset($decodedResult['success']) && $decodedResult['success'] == true ){
        $data =[
            'status'        => true,
            'message'       => "Create Customer Successfully.",
            'data'          => $decodedResult['response'],
        ];
    }else{
        $data =[
            'status'        => false,
            'message'       => $decodedResult['message'] ?? 'Something is wrong! Contact With Admin',
            'data'          => null,
        ];
    }

    return $data;

}
function create_stowallet_cardkyc($formData,$public_key,$base_url,$idImage,$mode = null){
    $params = [
        'public_key'              => $public_key,
        'first_name'              => $formData['first_name'],
        'last_name'               => $formData['last_name'],
        'id_type'                 => $formData['id_type'],
        'id_number'               => $formData['id_number'],
        'email'                   => $formData['email'],
        'dial_code'               => $formData['phone_code'],
        'phone_number'            => $formData['phone'],
        'date_of_birth'           => $formData['dob'],
        'place_of_birth'          => $formData['dob'],
        'id_front_image'          => base64_encode($idImage) ?? "",
        'line1'                   => $formData['line1'],
        'state'                   => $formData['state'],
        'postal_code'             => $formData['postal_code'],
        'city'                    => $formData['city'],
        'country'                 => $formData['country'],
        'expected_monthly_volume' => 10000,
        'annual_salary'           => 10000,
        'account_purpose'         => $formData['account_purpose'],
        'employment_status'       => $formData['employment_status'],
        'occupation'              => $formData['occupation'],
    ];

    $mode = $mode ?? (VirtualCardApi::first()->config->strowallet_mode ?? GlobalConst::SANDBOX);
    if ($mode === GlobalConst::SANDBOX) {
        $params['mode'] = 'sandbox';
    }


    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL            => $base_url . 'cardkyc/?' . http_build_query($params),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING       => '',
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST  => 'POST',
        CURLOPT_HTTPHEADER     => [
            'accept: application/json',
        ],
    ]);

    $result = curl_exec($curl);
    $err    = curl_error($curl);
    curl_close($curl);

    if ($err) {
        return [
            'status'  => false,
            'message' => 'Something is wrong! Contact With Admin',
            'data'    => null,
        ];
    }

    $decodedResult = json_decode($result, true);

    if(isset($decodedResult['success']) && $decodedResult['success'] == true ){
        $data =[
            'status'        => true,
            'message'       => "Create Customer Successfully.",
            'data'          => $decodedResult['data'] ?? [],
        ];
    }else{
        $data =[
            'status'        => false,
            'message'       => $decodedResult['message'] ?? 'Something is wrong! Contact With Admin',
            'data'          => null,
        ];
    }

    return $data;

}
//get customer api response
function get_customer($public_key,$base_url,$customerId,$customerEmail){

    $url = $base_url . 'getcardholder/?public_key=' . urlencode($public_key) . '&customerId=' . urlencode($customerId) . '&customerEmail=' . urlencode($customerEmail);
    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
        ],
    ]);

    $response = curl_exec($curl);
    $result = json_decode($response,true);

    if(isset($result) && isset($result['success']) && $result['success'] == true){
        $data =[
            'status' => true,
            'message' => "Customer Get SuccessFully",
            'data' => $result['data'] ?? [],
        ];
    }else{
        $data =[
            'status' => false,
            'message' => $result['message']??__("Something went wrong! Please try again."),
            'data' => [],
        ];
    }
    return $data;

    // Close cURL session
    curl_close($curl);

}
//update customer api
function update_customer($formData,$public_key,$base_url,$idImage,$userPhoto,$customer){

    $ch = curl_init();
    // Set the URL
    $url = $base_url . 'updateCardCustomer/?public_key=' . urlencode($public_key) . '&customerId=' . urlencode($customer->customerId) .'&firstName=' .urlencode($formData['first_name']) . '&lastName=' . urlencode($formData['last_name']) . '&idImage=' . urlencode($idImage) . '&userPhoto=' . urlencode($userPhoto);

    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return response instead of outputting
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT'); // Set request method to PUT
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json' // Set headers
    ]);

    // Execute cURL request
    $response = curl_exec($ch);
    $result     = json_decode($response,true);

    if(isset($result) && isset($result['success']) && $result['success'] == true){
        $data =[
            'status' => true,
            'message' => "Customer updated successfully",
            'data' => $result['response'] ?? [],
        ];
    }else{
        $data =[
            'status' => false,
            'message' => $result['message']??__("Something went wrong! Please try again."),
            'data' => [],
        ];
    }
    return $data;

    curl_close($ch);


}
// create virtual card for strowallet
function create_strowallet_virtual_card($public_key,$base_url,$formData){

    $method = VirtualCardApi::first();
    $mode = $method->config->strowallet_mode ?? GlobalConst::SANDBOX;
    $data = [
        'public_key'  => $public_key,
        'name'        => $formData['name'],
        'customer_id' => $formData['customer_id'] ?? "",
        'amount'      => $formData['card_amount'],
    ];

    if ($mode === GlobalConst::SANDBOX) {
        $data['mode'] = "sandbox";
    }


    $curl = curl_init();

    curl_setopt_array($curl, [
    CURLOPT_URL => $base_url."create-nfc-card?".http_build_query($data, '', '&', PHP_QUERY_RFC3986),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_HTTPHEADER => [
        "accept: application/json",
        "content-type: application/json",
    ],
    ]);
    $response = curl_exec($curl);

    curl_close($curl);
    $result  = json_decode($response, true);

    if(isset($result['success']) && $result['success'] == true ){
        $data =[
            'status'        => true,
            'message'       => "Create Card Successfully.",
            'data'          => $result['response'],
        ];
    }elseif(isset($result) && isset($result['error'])){
        $data =[
            'status'        => false,
            'message'       => "Contact With Strowallet Account Administration, ".$result['error']??"",
            'data'          => null,
        ];

    }else{
        $data =[
            'status'        => false,
            'message'       => $result['message'] ?? "",
            'data'          => null,
        ];
    }

    return $data;
}

// card details
function card_details($card_id,$public_key,$base_url){

    $method = VirtualCardApi::first();
    $mode = $method->config->strowallet_mode??GlobalConst::SANDBOX;
    $query =[
        'public_key' => $public_key,
        'card_id'    => $card_id
    ];

    if ($mode === GlobalConst::SANDBOX) {
        $query['mode'] = "sandbox";
    }

    $client = new Client();

    try {
        $response = $client->request('GET', $base_url . "fetch-nfccard-detail/", [
            'query'       => $query,
            'headers'     => [
                'accept' => 'application/json',
            ],
            'timeout'     => 30,
            'http_errors' => false,
        ]);

        $result = json_decode($response->getBody()->getContents(), true);
    } catch (\Exception $e) {
        $result = null;
    }


    if(isset($result['success']) && $result['success'] == true ){
        $data =[
            'status'        => true,
            'message'       => "Card Details Retrieved Successfully.",
            'data'          => $result['response'],
        ];
    }else{
        $data =[
            'status'        => false,
            'message'       => $result['message'] ?? 'Your Card Is Pending!Please Contact With Admin',
            'data'          => null,
        ];
    }

    return $data;
}

// card status change (active / frozen)
function change_strowallet_card_status($card_id,$status,$public_key,$base_url){

    $method = VirtualCardApi::first();
    $mode   = $method->config->strowallet_mode ?? GlobalConst::SANDBOX;
    $query  = [
        'public_key' => $public_key,
        'card_id'    => $card_id,
        'status'     => $status,
    ];

    if ($mode === GlobalConst::SANDBOX) {
        $query['mode'] = "sandbox";
    }

    $client = new Client();

    try {
        $response = $client->request('POST', $base_url . "nfc-cards/status", [
            'query'           => $query,
            'headers'         => [
                'accept' => 'application/json',
            ],
            'timeout'         => 30,
            'http_errors'     => false,
            // keep POST if the server redirects (e.g. trailing slash), instead of turning it into GET
            'allow_redirects' => ['strict' => true],
        ]);

        $result = json_decode($response->getBody()->getContents(), true);
    } catch (\Exception $e) {
        $result = null;
    }


    if(isset($result['success']) && $result['success'] == true){
        return [
            'status'  => true,
            'message' => $result['message'] ?? "Card status updated successfully.",
            'data'    => $result,
        ];
    }

    return [
        'status'  => false,
        'message' => $result['message'] ?? 'Something went wrong! Please try again.',
        'data'    => null,
    ];
}
// strowallet/ziiropay account (wallet) balance; host is taken from the admin base url
function get_strowallet_wallet_balance($public_key, $base_url, $currency = "USD"){
    $parts  = parse_url($base_url);
    $origin = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '') . (isset($parts['port']) ? ':' . $parts['port'] : '');

    $client = new Client();

    try {
        $response = $client->request('GET', $origin . '/api/wallet/balance/' . strtoupper($currency) . '/', [
            'query'       => ['public_key' => $public_key],
            'headers'     => [
                'accept' => 'application/json',
            ],
            'timeout'     => 30,
            'http_errors' => false,
        ]);

        $result = json_decode($response->getBody()->getContents(), true);
    } catch (\Exception $e) {
        $result = null;
    }

    if(isset($result['balance'])){
        return [
            'status'  => true,
            'message' => __("SuccessFully Fetch Account Balance"),
            'balance' => $result['balance'],
        ];
    }

    return [
        'status'  => false,
        'message' => $result['message'] ?? __("Something went wrong! Please try again."),
        'balance' => 0,
    ];
}
function strowalletBalance(){
    $currency_code = get_default_currency_code();
    $method = VirtualCardApi::first();
    $publicKey =  $method->config->strowallet_public_key;
    $url = 'https://strowallet.com/api/wallet/balance/'.$currency_code.'/?public_key='  . $publicKey;

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: application/json'
    ));

    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode(  $response,true);
    if(isset($result['balance']) ){
        $data =[
            'status'        => true,
            'message'       => __("SuccessFully Fetch Account Balance"),
            'balance'          => $result['balance'],
        ];
    }else{
        $data =[
            'status'        => false,
            'message'       => $result['message']??'',
            'balance'          => 0
        ];
    }
    return $data;
}
function updateStroWalletCardBalance($user,$card_id,$response){
    $card = StrowalletVirtualCard::where('user_id',$user->id)->where('card_id',$card_id)->first();
    $card->balance = $response['data']['card_detail']['balance']??$card->balance;
    $card->save();
    return  $card->balance??0;
}
// withdraw amount from strowallet card
function withdraw_virtual_card($public_key, $base_url, $card_id, $amount){

    $method = VirtualCardApi::first();
    $mode = $method->config->strowallet_mode ?? GlobalConst::SANDBOX;
    $data = [
        'public_key' => $public_key,
        'card_id'    => $card_id,
        'amount'     => $amount,
        'type'       => 'withdraw',
    ];


    if ($mode === GlobalConst::SANDBOX) {
        $data['mode'] = "sandbox";
    }

    $client = new Client();

    try {
        $response = $client->request('POST', $base_url . "fund-withdraw-nfccard/", [
            'query'           => $data,
            'headers'         => [
                'accept' => 'application/json',
            ],
            'timeout'         => 30,
            'http_errors'     => false,
            'allow_redirects' => ['strict' => true],
        ]);

        $result = json_decode($response->getBody()->getContents(), true);
    } catch (\Exception $e) {
        $result = null;
    }


    if(isset($result['success']) && $result['success'] == true ){
        $data =[
            'status'        => true,
            'message'       => "Card withdrawal request submitted successfully.",
            'data'          => $result['response'],
        ];
    }elseif(isset($result) && isset($result['error'])){
        $data =[
            'status'        => false,
            'message'       => $result['message'] ?? "",
            'data'          => null,
        ];

    }else{
        $data =[
            'status'        => false,
            'message'       => $result['message'] ?? "",
            'data'          => null,
        ];
    }

    return $data;
}
