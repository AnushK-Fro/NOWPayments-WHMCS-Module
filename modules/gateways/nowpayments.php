<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}


function nowpayments_MetaData()
{
    return array(
        'DisplayName' => 'NOWPayments',
        'APIVersion' => '1.2',
    );
}


function nowpayments_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'NOWPayments',
        ),
        'apiKey' => array(
            'FriendlyName' => 'API key',
            'Type' => 'text',
            'Default' => '',
            'Description' => 'Enter your API key here',
        ),
        'ipnSecret' => array(
            'FriendlyName' => 'IPN Secret',
            'Type' => 'text',
            'Default' => '',
            'Description' => 'Enter your IPN Secret here',
        )
    );
}


function nowpayments_link($params)
{
    // Adjust this if you want the fee paid by the user or not
    $fee_paid_by_user = true;

    $origin = $_SERVER['HTTP_ORIGIN'];
    $path = array_filter(explode('/', parse_url($_SERVER['REQUEST_URI'])['path']));
    $logoUrl = $params['systemurl'] . 'modules/gateways/nowpayments/logo.png';
    $ipnUrl = $params['systemurl'] . 'modules/gateways/callback/nowpayments.php';
    if(empty($params['systemurl'])) {
        if(count($path) > 1) {
            array_pop($path);
            $prefix = implode('/', $path);
            $logoUrl = '/' . $prefix . '/modules/gateways/nowpayments/logo.png';
            $ipnUrl = $origin . '/' . $prefix . '/modules/gateways/callback/nowpayments.php';
        } else {
            $logoUrl = '/modules/gateways/nowpayments/logo.png';
            $ipnUrl = $origin . '/modules/gateways/callback/nowpayments.php';
        }
        }

        $orderId = 'WHMCS-' . $params['invoiceid']; // Order ID
        $apiKey = $params['apiKey']; // API Key
        $paymentAmount = $params['amount']; // Payment amount
        $paymentCurrency = mb_strtoupper($params['currency']); // Currency

        // API URL
        $url = 'https://api.nowpayments.io/v1/invoice';

        // Required for USDCSOL Payments, payments over $2,000 do not have working fee calculations
        if (floatval($paymentAmount) > 2000) {
                $fee_paid_by_user = false;
        }

        $data = [
                'price_amount' => $paymentAmount,
                'price_currency' => $paymentCurrency,
                'order_id' => $orderId,
                'ipn_callback_url' => $ipnUrl,
                'is_fixed_rate' => $fee_paid_by_user,
                'is_fee_paid_by_user' => $fee_paid_by_user 
        ];

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'x-api-key: ' . $apiKey
        ));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);

        if (curl_errno($ch)) {

                return 'Error';
        } else {
                $response_data = json_decode($response, true);
        }

        curl_close($ch);

    $htmlOutput = '<a class="btn btn-success btn-sm" id="btnPayNow" data-toggle="modal" data-target="#nowpaymentsModal">Pay Now</a>';
    $htmlOutput .= ' </a>';
    $invoiceUrl = $response_data["invoice_url"];

$htmlOutput .= '
<div class="modal fade" id="nowpaymentsModal" tabindex="-1" role="dialog" aria-labelledby="nowpaymentsModalLabel" aria-h idden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="nowpaymentsModalLabel">NowPayments Information</h5>
      </div>
      <div class="modal-body">
        <b>Please read this notice in full before paying your invoice through the NowPayments cryptocurrency exchange.</b> <br /><br /> NowPayments is a third party crypto exchange and gateway created and operated by ChangeNOW. <b>If any funds get lost or stuck, you may need to contact NowPayments support.</b> We are not liable for any funds that may be lost or stuck through this gateway, only payments that are fully received by us will be counted. Additional fees may be added to cover exchange / transaction fees depending on the coin you choose, and payment must be completed within 20 minutes. NowPayments supports over 200+ cryptocurrency coins and automatically converts them for you, which may make it the most convenient option. <br /><br  />By using this gateway, you agree to NowPayment\'s policies:
                <br /><a href="https://nowpayments.io/doc/tos.pdf?v=1.0">NowPayments Terms of Service</a>
                <br /><a href="https://nowpayments.io/doc/privacy-policy.pdf">NowPayments Privacy Policy</a>
                <br /><a href="https://nowpayments.io/doc/sqs.pdf">NowPayments Service Quality Standards</a>
                <br /><a href="https://nowpayments.io/doc/cookie-policy.pdf">NowPayments Cookie Policy</a>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger btn-sm" data-dismiss="modal">Close</button>
        <a class="btn btn-success btn-sm" id="btnConfirm" href='.$invoiceUrl.'>Confirm</a>
      </div>
    </div>
  </div>
</div>

';
    return $htmlOutput;
}
