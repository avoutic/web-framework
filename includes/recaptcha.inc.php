<?php
class Recaptcha extends FrameworkCore
{
    function __construct()
    {
        parent::__construct();

        $this->module_config = $this->get_config('security.recaptcha');

        WF::verify(strlen($this->module_config['site_key']), 'Missing reCAPTCHA Site Key');
        WF::verify(strlen($this->module_config['secret_key']), 'Missing reCAPTCHA Secret Key');
    }

    function verify_response($recaptcha_response)
    {
        if (!strlen($recaptcha_response))
            return false;

        $recaptcha_secret = $this->module_config['secret_key'];

        $recaptcha_data = array(
            'secret' => $recaptcha_secret,
            'response' => $recaptcha_response,
        );

        $verify = curl_init();
        curl_setopt($verify, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
        curl_setopt($verify, CURLOPT_POST, true);
        curl_setopt($verify, CURLOPT_POSTFIELDS, http_build_query($recaptcha_data));
        curl_setopt($verify, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($verify);
        curl_close($verify);

        $response = json_decode($response, true);

        if (isset($response['error_codes']))
        {
            WF::verify(!in_array('invalid-input-secret', $response['error-codes']), 'Invalid reCAPTCHA input secret used');
            WF::verify(!in_array('invalid-keys-secret', $response['error-codes']), 'Invalid reCAPTCHA key used');
        }

        return $response['success'];
    }
};
?>
