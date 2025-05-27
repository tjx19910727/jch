<?php

return [
    'Login' =>
        [
            'time_require' => 'Time cannot be empty',
            'login_id_require' => 'WeChat ID cannot be empty',
            'time_over' => 'QR code timeout, please scan the code again',
            'wxLogin_no_data' => 'Check for login information without scanning code',
            'wxOfficial_no_data' => 'No configuration information of official account',
            'status2' => 'The current QR code has already been operated, please scan the code again',
            'status3' => 'Login successful, please do not retry',
            'code_used' => 'The current authorization code has already been used. Please scan the code again for authorization',
            'login_success' => 'Login succeeded',
            'login_fail' => 'Login failed',
            'openid_can_not_empty' => 'Openid cannot be empty',
            'login_id_can_not_empty' => 'Login ID cannot be empty',
            'manager_id_can_not_empty' => 'Account ID cannot be empty',
            'auth_not_match' => 'The current account does not have permission to log in to this device',
        ],
];