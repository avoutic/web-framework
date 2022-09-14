<?php
namespace WebFramework\Core;

class Webhook extends FrameworkCore
{
    /**
     * @param array<mixed> $data
     */
    static function trigger(string $webhook_name, array $data): void
    {
        $url = WF::get_config('webhooks.'.$webhook_name);

        $jsonEncodedData = json_encode($data);
        WF::verify($jsonEncodedData !== false, 'Failed to encode data');

        $opts = array(
            CURLOPT_URL             => $url,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_CUSTOMREQUEST   => 'POST',
            CURLOPT_POST            => 1,
            CURLOPT_POSTFIELDS      => json_encode($data),
            CURLOPT_HTTPHEADER      => array('Content-Type: application/json','Content-Length: ' . strlen($jsonEncodedData)),
        );

        $curl = curl_init();
        curl_setopt_array($curl, $opts);
        $result = curl_exec($curl);
        curl_close($curl);
    }
};
?>
