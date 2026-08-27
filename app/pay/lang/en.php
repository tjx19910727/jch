<?php

return [
    'init_payment_success' => 'Successfully initiated payment',
    'init_payment_fail' => 'Failed to initiate payment',
    'request_params_require' => 'Request parameter cannot be empty',
    'cancel_payment_success' => 'Revocation of payment successful',
    'cancel_payment_fail' => 'Revoking payment failed',
    'StrategyPayee' =>
        [
            'payee_config_no_json' => 'The recipient\'s configuration information format is incorrect, not in JSON format',
            'payee_config_no_data' => 'Check for no recipient configuration information',
        ],
    'pay_type_not_in_scope' => 'The payment method is not within the allowed range',
    'VWx' =>
        [
            'unKnow_code' => '',
        ],
    'VOrderPay' =>
        [
            'order_no_data' => 'No order information found',
            'machine_no_data' => 'No device information found',
            'unKnow_auth_code' => 'Unable to identify payment code type',
            'unKnow_pay_type' => 'Undefined payment type',
            'update_order_pay_info_fail' => 'Failed to Order',
            'pay_status3' => 'The order has been successfully paid',
            'pay_exception' => 'Pay exception',
            'auth_code_not_match_pay_type' => 'The payment code does not match the payment type of the order',
            'subcar_mix_mixed_goods' => 'Online and offline goods cannot be placed in the same cart',
            'subcar_mix_order_goods_empty' => 'The order has no goods details for selecting a payee strategy',
            'subcar_mix_payee_empty' => 'No payee strategy is configured for this goods type',
            'mall_no_data' => 'No valid mall information found',
            'mall_machine_no_data' => 'No mall and device relationship information found',
            'mall_disable_points_payment' => 'The mall has disabled points payment',
            'machine_disable_points_payment' => 'The device has disabled points payment',

        ],
    'VJdCashier' =>
        [
            'app_id_require' => 'Application ID cannot be empty',
            'agentNum_require' => 'The agent number cannot be empty',
            'customerNum_require' => 'Merchant ID cannot be empty',
            'shopNum_require' => 'The store number cannot be empty',
            'accessKey_require' => 'The public key cannot be empty',
            'secretKey_require' => 'Private key cannot be empty',
            'bill_account_require' => 'The recipient\'s sub account cannot be empty',
        ],
    'VAliPay' =>
        [
            'cert_not_exit' => 'The certificate file does not exist',
            'app_id_require' => 'Application ID cannot be empty',
            'pid_require' => 'Merchant account (PID] cannot be empty',
            'private_key_path_require' => 'The private key path cannot be empty',
            'ali_public_key_path_require' => 'The public key certificate path of Alipay platform cannot be empty',
            'ali_root_cert_path_require' => 'Alipay root certificate path cannot be empty',
            'app_public_key_path_require' => 'The path of the application public key certificate cannot be empty',
        ],
    'VTrip' =>
        [
            'appId_require' => 'AppId cannot be empty',
            'appSecret_require' => 'AppSecret cannot be empty',
            'baseUrl_require' => 'Request address cannot be empty',
        ],

    "VPos" => [
        "check_sign_fail" => "Signature verification failed.",
        "order_no_data" => "No order information found.",


        "machine_id_require" => "The equipment number cannot be empty.",
        "msg_id_require" => "The message ID cannot be empty.",
        "msg_id_unique" => "The message ID already exists. Please re-report.",
        "timestamp_require" => "The timestamp cannot be left blank.",
        "signKey_require" => "The signature key does not exist.",
        "sign_require" => "The add-on signature key does not exist. Signature cannot be empty.",
        "timestamp_checkTimestamp_overdue" => "Time stamp has expired. Please update the time.",

        "payment_type_require" => "The payment type cannot be left blank.",
        "payment_status_require" => "The payment result cannot be left blank.",
        "trade_no_require" => "Payment result cannot be empty. Order number cannot be empty.",
        "mch_no_require" => "Payment result cannot be empty. Order number cannot be empty. Transaction order number cannot be empty.",
        "payment_data_format" => "The payment provider data must be a valid JSON object string.",
    ],
];
