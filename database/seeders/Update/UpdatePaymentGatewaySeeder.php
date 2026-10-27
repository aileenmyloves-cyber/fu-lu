<?php

namespace Database\Seeders\Update;

use Illuminate\Database\Seeder;
use App\Models\Admin\PaymentGateway;
use App\Constants\PaymentGatewayConst;

/**
 * Updates `input_fields` only, only for the MANUAL Add Money and Money Out
 * (Withdraw) gateways, so each gateway asks for the information that
 * actually represents it. Receiving-method gateways and AUTOMATIC gateways
 * are left untouched. Safe to re-run.
 *
 */
class UpdatePaymentGatewaySeeder extends Seeder
{
    public function run()
    {
        $updates = [
            PaymentGatewayConst::add_money_slug() => [
                // Add Money: user pays externally, then submits proof of payment.
                'jazzcash'  => [
                    $this->text('Sender Mobile Number', 'sender_mobile_number', 7, 20),
                    $this->text('Transaction ID', 'transaction_id', 4, 40),
                    $this->file('Payment Screenshot', 'payment_screenshot'),
                ],
                'easypaisa' => [
                    $this->text('Sender Mobile Number', 'sender_mobile_number', 7, 20),
                    $this->text('Transaction ID', 'transaction_id', 4, 40),
                    $this->file('Payment Screenshot', 'payment_screenshot'),
                ],
                'upi' => [
                    $this->text('Sender UPI ID', 'sender_upi_id', 5, 50),
                    $this->text('UTR / Transaction Reference Number', 'utr_number', 4, 40),
                    $this->file('Payment Screenshot', 'payment_screenshot'),
                ],
                'phonepe' => [
                    $this->text('Sender Mobile Number', 'sender_mobile_number', 7, 20),
                    $this->text('UTR / Transaction ID', 'utr_transaction_id', 4, 40),
                    $this->file('Payment Screenshot', 'payment_screenshot'),
                ],
                'khalti' => [
                    $this->text('Sender Khalti ID / Mobile Number', 'sender_khalti_id', 5, 40),
                    $this->text('Transaction ID', 'transaction_id', 4, 40),
                    $this->file('Payment Screenshot', 'payment_screenshot'),
                ],
                'bitcoin' => [
                    $this->text('Sender Wallet Address', 'sender_wallet_address', 20, 100),
                    $this->text('Transaction Hash / TXID', 'transaction_hash', 20, 100),
                    $this->file('Payment Screenshot', 'payment_screenshot', false),
                ],
            ],
            PaymentGatewayConst::money_out_slug() => [
                // Money Out: user provides their own receiving account, no proof needed.
                'jazzcash' => [
                    $this->text('Receiver Mobile Number', 'receiver_mobile_number', 7, 20),
                    $this->text('Account Holder Name', 'account_holder_name', 3, 60),
                ],
                'easypaisa' => [
                    $this->text('Receiver Mobile Number', 'receiver_mobile_number', 7, 20),
                    $this->text('Account Holder Name', 'account_holder_name', 3, 60),
                ],
                'upi' => [
                    $this->text('UPI ID', 'upi_id', 5, 50),
                    $this->text('Account Holder Name', 'account_holder_name', 3, 60),
                ],
                'phonepe' => [
                    $this->text('Registered Mobile Number', 'registered_mobile_number', 7, 20),
                    $this->text('Account Holder Name', 'account_holder_name', 3, 60),
                ],
                'khalti' => [
                    $this->text('Khalti ID / Mobile Number', 'khalti_id', 5, 40),
                    $this->text('Account Holder Name', 'account_holder_name', 3, 60),
                ],
                'bitcoin' => [
                    $this->text('Receiving Wallet Address', 'receiving_wallet_address', 20, 100),
                ],
            ],
        ];

        foreach ($updates as $slug => $gateways) {
            foreach ($gateways as $alias => $inputFields) {
                PaymentGateway::manual()
                    ->where('slug', $slug)
                    ->where('alias', $alias)
                    ->update([
                        'input_fields' => json_encode($inputFields),
                    ]);
            }
        }
    }

    protected function text(string $label, string $name, $min = 0, $max = 30, bool $required = true)
    {
        return [
            'type'          => 'text',
            'label'         => $label,
            'name'          => $name,
            'required'      => $required,
            'validation'    => [
                'min'       => (string) $min,
                'max'       => (string) $max,
                'mimes'     => [],
                'options'   => [],
                'required'  => $required,
            ],
        ];
    }

    protected function file(string $label, string $name, bool $required = true, $maxSizeMb = 10)
    {
        return [
            'type'          => 'file',
            'label'         => $label,
            'name'          => $name,
            'required'      => $required,
            'validation'    => [
                'min'       => 0,
                'max'       => (string) $maxSizeMb,
                'mimes'     => ['jpg', 'png', 'jpeg', 'webp'],
                'options'   => [],
                'required'  => $required,
            ],
        ];
    }
}