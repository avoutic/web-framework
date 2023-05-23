<?php

namespace WebFramework\Core;

use WebFramework\Security\ProtectService;

class Browserless
{
    private string $footer_template = '';
    private string $header_template = '';
    private string $api_key = '';

    private const PDF_MAGIC = '%PDF-';

    public function __construct(
        private AssertService $assert_service,
        private ProtectService $protect_service,
        private string $local_server,
        private string $pdf_endpoint,
        private string $token,
    ) {
    }

    private function is_pdf(string $filename): bool
    {
        return (file_get_contents($filename, false, null, 0, strlen(self::PDF_MAGIC)) === self::PDF_MAGIC) ? true : false;
    }

    private function is_pdf_string(string $str): bool
    {
        return (substr($str, 0, strlen(self::PDF_MAGIC)) === self::PDF_MAGIC) ? true : false;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function set_api_key_data(array $data): void
    {
        $this->api_key = $this->protect_service->pack_array($data);
    }

    public function set_footer_template(string $template): void
    {
        $this->footer_template = $template;
    }

    public function set_header_template(string $template): void
    {
        $this->header_template = $template;
    }

    public function output_pdf(string $relative_url, string $output_filename): void
    {
        $target_url = "{$this->local_server}{$relative_url}";

        $query = parse_url($target_url, PHP_URL_QUERY);
        if ($query)
        {
            $target_url .= "&auth={$this->api_key}";
        }
        else
        {
            $target_url .= "?auth={$this->api_key}";
        }

        $filename = $output_filename;

        $result = $this->get_pdf_result($target_url);
        $this->assert_service->verify($this->is_pdf_string($result), 'Failed to generate NDA: '.$result);

        header('Cache-Control: public');
        header('Content-type: application/pdf');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        header('Content-Length: '.strlen($result));

        echo $result;

        exit();
    }

    public function output_stream(string $relative_url): mixed
    {
        $target_url = "{$this->local_server}{$relative_url}";

        $query = parse_url($target_url, PHP_URL_QUERY);
        if ($query)
        {
            $target_url .= "&auth={$this->api_key}";
        }
        else
        {
            $target_url .= "?auth={$this->api_key}";
        }

        $tmp_file = tmpfile();
        $this->assert_service->verify($tmp_file !== false, 'Failed to get temporary stream');

        $result = $this->get_pdf_result($target_url, $tmp_file);
        $tmp_path = stream_get_meta_data($tmp_file)['uri'];
        $this->assert_service->verify($this->is_pdf($tmp_path), 'Failed to generate PDF: '.file_get_contents($tmp_path));

        return $tmp_file;
    }

    private function get_pdf_result($url, $output_stream = false): mixed
    {
        $pdf_endpoint = "{$this->pdf_endpoint}?token={$this->token}";

        $data = [
            'url' => "{$url}",
            'emulateMedia' => 'print',
            'options' => [
                'format' => 'A4',
                'margin' => [
                    'left' => '50px',
                    'right' => '50px',
                    'top' => '60px',
                    'bottom' => '60px',
                ],
                'printBackground' => true,
                'scale' => 0.5,
            ],
        ];

        if (strlen($this->header_template) || strlen($this->footer_template))
        {
            $data['options']['displayHeaderFooter'] = true;

            if (strlen($this->header_template))
            {
                $data['options']['headerTemplate'] = $this->header_template;
            }

            if (strlen($this->footer_template))
            {
                $data['options']['footerTemplate'] = $this->footer_template;
            }
        }

        $opts = [
            CURLOPT_URL => $pdf_endpoint,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Cache-Control: no-cache',
            ],
        ];

        if ($output_stream !== false)
        {
            $opts[CURLOPT_FILE] = $output_stream;
        }
        else
        {
            $opts[CURLOPT_RETURNTRANSFER] = true;
        }

        $curl = curl_init();
        curl_setopt_array($curl, $opts);

        $result = curl_exec($curl);

        curl_close($curl);

        if ($output_stream !== false)
        {
            return true;
        }

        return $result;
    }
}
