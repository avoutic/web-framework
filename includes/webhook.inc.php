<?php
class Webhook extends FrameworkCore
{
    static function trigger($webhook_name, $data)
    {
        $url = WF::get_config('webhooks.'.$webhook_name);

        $jsonEncodedData = json_encode($data);

        $opts = array(
            CURLOPT_URL             => $url,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_CUSTOMREQUEST   => 'POST',
            CURLOPT_POST            => 1,
            CURLOPT_POSTFIELDS      => json_encode($data),
            CURLOPT_HTTPHEADER      => array('Content-Type: application/json','Content-Length: ' . strlen($jsonEncodedData))
        );

        $curl = curl_init();
        curl_setopt_array($curl, $opts);
        $result = curl_exec($curl);
        curl_close($curl);
    }
};
?>
