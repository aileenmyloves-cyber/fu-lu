<?php

namespace App\Http\Helpers;

use Exception;
use App\Constants\GlobalConst;
use App\Models\VirtualCardApi;

class CardyFieHelper{

    /**
     * Active API
     */
    public $api;

    /**
     * Store access token
     */
    protected $access_token;

    /**
     * store configuration
     */
    protected array $config;


    public function __construct()
    {
        $this->api = VirtualCardApi::first();
        $this->setConfig();
    }
    /**
     * Set configuration
     */
    public function setConfig()
    {
        $api = $this->api->config;

        if(!$api) throw new Exception("Card Provider Not Found!");

        if($api->cardyfie_mode == GlobalConst::LIVE){
            $base_url = $api->cardyfie_production_url;
        }else{
            $base_url = $api->cardyfie_sandbox_url;
        }
        $config['public_key'] = $api->cardyfie_public_key;
        $config['secret_key'] = $api->cardyfie_secret_key;
        $config['base_url']   = $base_url;
        $config['mode']       = $api->cardyfie_mode;

        $this->config = $config;

        return $this;
    }
    /**
     * Authenticate API access token retrieve
     */
    public function createCustomer(array $data)
    {

        if(!$this->config) $this->setConfig();


        $access_key = $this->config['secret_key'];
        $base_url   = $this->config['base_url'];
        $url        = $base_url."/card-customer/create";


        $form_data = [
            "first_name"     => $data['first_name']         ?? null,
            "last_name"      => $data['last_name']          ?? null,
            "email"          => $data['email']              ?? null,
            "date_of_birth"  => $data['date_of_birth']      ?? null,
            "id_type"        => $data['identity_type']      ?? null,
            "id_number"      => $data['identity_number']    ?? null,
            "id_front_image" => $data['id_front_image']     ?? null,
            "id_back_image"  => $data['id_back_image']      ?? null,
            "user_image"     => $data['user_image']         ?? null,
            "house_number"   => $data['house_number']       ?? null,
            "address_line_1" => $data['address']            ?? null,
            "city"           => $data['city']               ?? null,
            "state"          => $data['state']              ?? null,
            "zip_code"       => $data['zip_code']           ?? null,
            "country"        => $data['country']            ?? null,
            "reference_id"   => $data['reference_id']       ?? null,
        ];




        //start curl request.
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($form_data),
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$access_key}",
                "Accept: application/json",
                "Content-Type: application/json"
            ],
        ]);

        $response = curl_exec($curl);
        $results = json_decode($response,true);


        // handle data
        if(isset($results) && isset($results['type']) && $results['type'] == "success"){

            $data = [
                'status'  => true,
                'message' => $results['message']['success'] ?? "  Customer created successfully",
                'data'    => $results['data'],
            ];

        }else{
            $data = [
                'status'  => false,
                'message' => $results['message']['error'] ?? 'Something went wrong! Please try again.',
                'data'    => [],
            ];
        }

        return $data ?? [];

        curl_close($curl);
    }

    public function getCustomer(string $ulid)
    {

        if(!$this->config) $this->setConfig();

        $access_key = $this->config['secret_key'];
        $base_url = $this->config['base_url'];
        $url =  $base_url."/card-customer/get/".$ulid;

        //start curl request.
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$access_key}",
                "Accept: application/json",
                "Content-Type: application/json"
            ],
        ]);

        $response = curl_exec($curl);
        $results = json_decode($response,true);

        // handle data
        if(isset($results) && isset($results['type']) && $results['type'] == "success"){
            $data = [
                'status'  => true,
                'message' => $results['message']['success'] ?? "Data fetch successfully",
                'data'    => $results['data'],
            ];

        }else{
            $data = [
                'status'  => false,
                'message' => $results['message']['error'] ?? 'Something went wrong! Please try again.',
                'data'    => [],
            ];
        }

        return $data ?? [];

        curl_close($curl);
    }

    public function updateCustomer(array $data,string $ulid)
    {

        if(!$this->config) $this->setConfig();


        $access_key = $this->config['secret_key'];
        $base_url = $this->config['base_url'];
        $url =  $base_url."/card-customer/update/".$ulid;

        $form_data = [
            "first_name"     => $data['first_name']         ?? null,
            "last_name"      => $data['last_name']          ?? null,
            "date_of_birth"  => $data['date_of_birth']      ?? null,
            "id_type"        => $data['identity_type']      ?? null,
            "id_number"      => $data['identity_number']    ?? null,
            "id_front_image" => $data['id_front_image']     ?? null,
            "id_back_image"  => $data['id_back_image']      ?? null,
            "user_image"     => $data['user_image']         ?? null,
            "house_number"   => $data['house_number']       ?? null,
            "address_line_1" => $data['address']            ?? null,
            "city"           => $data['city']               ?? null,
            "state"          => $data['state']              ?? null,
            "zip_code"       => $data['zip_code']           ?? null,
        ];


        //start curl request.
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_POSTFIELDS => json_encode($form_data),
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$access_key}",
                "Accept: application/json",
                "Content-Type: application/json"
            ],
        ]);

        $response = curl_exec($curl);
        $results = json_decode($response,true);



        // handle data
        if(isset($results) && isset($results['type']) && $results['type'] == "success"){
            $data = [
                'status'  => true,
                'message' => $results['message']['success'] ?? "  Customer update successfully",
                'data'    => $results['data'],
            ];

        }else{
            $data = [
                'status'  => false,
                'message' => $results['message']['error'] ?? 'Something went wrong! Please try again.',
                'data'    => [],
            ];
        }

        return $data ?? [];

        curl_close($curl);
    }

    public function cardCurrencies()
    {

        if(!$this->config) $this->setConfig();

        $access_key = $this->config['secret_key'];
        $base_url = $this->config['base_url'];
        $url =  $base_url."/card/currencies";

        //start curl request.
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$access_key}",
                "Accept: application/json",
                "Content-Type: application/json"
            ],
        ]);

        $response = curl_exec($curl);
        $results = json_decode($response,true);

        // handle data
        if(isset($results) && isset($results['type']) && $results['type'] == "success"){
            $data = [
                'status'  => true,
                'message' => $results['message']['success'] ?? "Data fetch successfully",
                'data'    => $results['data'],
            ];

        }else{
            $data = [
                'status'  => false,
                'message' => $results['message']['error'] ?? 'Something went wrong! Please try again.',
                'data'    => [],
            ];
        }

        return $data ?? [];

        curl_close($curl);
    }

    public function issuesCard(array $data)
    {

        if(!$this->config) $this->setConfig();


        $access_key = $this->config['secret_key'];
        $base_url   = $this->config['base_url'];
        $url        = $base_url."/card/issue";

        $form_data = [
            "customer_ulid" => $data['customer_ulid']         ?? null,
            "card_name"     => $data['name_on_card']          ?? null,
            "card_currency" => $data['currency']              ?? null,
            "card_type"     => $data['card_tier']      ?? null,
            "card_provider" => $data['card_type']      ?? null,
            "reference_id"  => $data['reference_id']       ?? null,
        ];


        //start curl request.
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($form_data),
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$access_key}",
                "Accept: application/json",
                "Content-Type: application/json"
            ],
        ]);

        $response = curl_exec($curl);
        $results = json_decode($response,true);

        // handle data
        if(isset($results) && isset($results['type']) && $results['type'] == "success"){

            $data = [
                'status'  => true,
                'message' => $results['message']['success'] ?? "Card Issued Successfully!",
                'data'    => $results['data'],
            ];

        }else{
            $data = [
                'status'  => false,
                'message' => $results['message']['error'] ?? 'Something went wrong! Please try again.',
                'data'    => [],
            ];
        }

        return $data ?? [];

        curl_close($curl);
    }

    public function getCard(string $ulid)
    {

        if(!$this->config) $this->setConfig();

        $access_key = $this->config['secret_key'];
        $base_url = $this->config['base_url'];
        $url =  $base_url."/card/details/".$ulid;

        //start curl request.
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$access_key}",
                "Accept: application/json",
                "Content-Type: application/json"
            ],
        ]);

        $response = curl_exec($curl);
        $results = json_decode($response,true);

        // handle data
        if(isset($results) && isset($results['type']) && $results['type'] == "success"){
            $data = [
                'status'  => true,
                'message' => $results['message']['success'] ?? "Virtual Card Details Fetch Successfully!",
                'data'    => $results['data'],
            ];

        }else{
            $data = [
                'status'  => false,
                'message' => $results['message']['error'] ?? 'Something went wrong! Please try again.',
                'data'    => [],
            ];
        }

        return $data ?? [];

        curl_close($curl);
    }

    public function freeze(string $ulid)
    {
        if(!$this->config) $this->setConfig();

        $access_key = $this->config['secret_key'];
        $base_url = $this->config['base_url'];
        $url =  $base_url."/card/freeze/".$ulid;

        //start curl request.
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$access_key}",
                "Accept: application/json",
                "Content-Type: application/json"
            ],
        ]);

        $response = curl_exec($curl);
        $results = json_decode($response,true);

        // handle data
        if(isset($results) && isset($results['type']) && $results['type'] == "success"){
            $data = [
                'status'  => true,
                'message' => $results['message']['success'] ?? "Card freeze successfully",
                'data'    => $results['data'] ?? [],
            ];

        }else{
            $data = [
                'status'  => false,
                'message' => $results['message']['error'] ?? 'Something went wrong! Please try again.',
                'data'    => [],
            ];
        }

        return $data ?? [];

        curl_close($curl);
    }

    public function unfreeze(string $ulid)
    {
        if(!$this->config) $this->setConfig();

        $access_key = $this->config['secret_key'];
        $base_url   = $this->config['base_url'];
        $url        = $base_url."/card/unfreeze/".$ulid;

        //start curl request.
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$access_key}",
                "Accept: application/json",
                "Content-Type: application/json"
            ],
        ]);

        $response = curl_exec($curl);
        $results = json_decode($response,true);

        // handle data
        if(isset($results) && isset($results['type']) && $results['type'] == "success"){
            $data = [
                'status'  => true,
                'message' => $results['message']['success'] ?? "Card unfreeze successfully",
                'data'    => $results['data'] ?? [],
            ];

        }else{
            $data = [
                'status'  => false,
                'message' => $results['message']['error'] ?? 'Something went wrong! Please try again.',
                'data'    => [],
            ];
        }

        return $data ?? [];

        curl_close($curl);
    }

    public function depositCard(string $ulid, $amount)
    {
        if(!$this->config) $this->setConfig();

        $access_key = $this->config['secret_key'];
        $base_url   = $this->config['base_url'];
        $url        = $base_url."/card/deposit/".$ulid;
        $data = [
            "amount"       => get_amount($amount,null,2)
        ];


        //start curl request.
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$access_key}",
                "Accept: application/json",
                "Content-Type: application/json"
            ],
        ]);

        $response = curl_exec($curl);
        $results = json_decode($response,true);


        // handle data
        if(isset($results) && isset($results['type']) && $results['type'] == "success"){
            $data = [
                'status'  => true,
                'message' => $results['message']['success'] ?? "Card Deposited Successfully!",
                'data'    => $results['data'] ?? [],
            ];

        }else{
            $data = [
                'status'  => false,
                'message' => $results['message']['error'] ?? 'Something went wrong! Please try again.',
                'data'    => [],
            ];
        }

        return $data ?? [];

        curl_close($curl);
    }

    public function withdrawCard(string $ulid, $amount)
    {
        if(!$this->config) $this->setConfig();

        $access_key = $this->config['secret_key'];
        $base_url   = $this->config['base_url'];
        $url        = $base_url."/card/withdraw/".$ulid;
        $data = [
            "amount"       => get_amount($amount,null,2)
        ];


        //start curl request.
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$access_key}",
                "Accept: application/json",
                "Content-Type: application/json"
            ],
        ]);

        $response = curl_exec($curl);
        $results = json_decode($response,true);


        // handle data
        if(isset($results) && isset($results['type']) && $results['type'] == "success"){
            $data = [
                'status'  => true,
                'message' => $results['message']['success'] ?? "Card Withdraw Successfully!",
                'data'    => $results['data'] ?? [],
            ];

        }else{
            $data = [
                'status'  => false,
                'message' => $results['message']['error'] ?? 'Something went wrong! Please try again.',
                'data'    => [],
            ];
        }

        return $data ?? [];

        curl_close($curl);
    }

    public function getCardTransaction(string $ulid)
    {
        if(!$this->config) $this->setConfig();

        $access_key = $this->config['secret_key'];
        $base_url = $this->config['base_url'];
        $url =  $base_url."/card/transactions?card_ulid=".$ulid;

        //start curl request.
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$access_key}",
                "Accept: application/json",
                "Content-Type: application/json"
            ],
        ]);

        $response = curl_exec($curl);
        $results = json_decode($response,true);


        // handle data
        if(isset($results) && isset($results['type']) && $results['type'] == "success"){
            $data = [
                'status'  => true,
                'message' => $results['message']['success'] ?? "Data fetch successfully!",
                'data'    => $results['data'],
            ];

        }else{
            $data = [
                'status'  => false,
                'message' => $results['message']['error'] ?? 'Something went wrong! Please try again.',
                'data'    => [],
            ];
        }

        return $data ?? [];

        curl_close($curl);
    }

     public function closeCard(string $ulid)
    {
        if(!$this->config) $this->setConfig();

        $access_key = $this->config['secret_key'];
        $base_url   = $this->config['base_url'];
        $url        = $base_url."/card/close/".$ulid;

        //start curl request.
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$access_key}",
                "Accept: application/json",
                "Content-Type: application/json"
            ],
        ]);

        $response = curl_exec($curl);
        $results = json_decode($response,true);

        // handle data
        if(isset($results) && isset($results['type']) && $results['type'] == "success"){
            $data = [
                'status'  => true,
                'message' => $results['message']['success'] ?? "Card closed successfully!",
                'data'    => $results['data'] ?? [],
            ];

        }else{
            $data = [
                'status'  => false,
                'message' => $results['message']['error'] ?? 'Something went wrong! Please try again.',
                'data'    => [],
            ];
        }

        return $data ?? [];

        curl_close($curl);
    }

}
