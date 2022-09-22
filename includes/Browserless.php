<?php
namespace WebFramework\Core;

class Browserless extends FrameworkCore
{
    /**
     * @var array<string, string>
     */
    private array $config;
    private string $footer_template = '';
    private string $header_template = '';

    private const PDF_MAGIC = "%PDF-";

    function __construct()
    {
        parent::__construct();

        $this->load_config();
    }

    private function load_config(): void
    {
        $config = $this->get_auth_config('browserless');

        $this->verify(isset($config['local_server']), 'Local server is missing');
        $this->verify(isset($config['pdf_endpoint']), 'PDF endpoint is missing');
        $this->verify(isset($config['token']), 'Token is missing');

        $this->config = $config;
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
        $this->api_key = $this->encode_and_auth_array($data);
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
        $target_url = "{$this->config['local_server']}{$relative_url}";
        $target_url .= "?auth={$this->api_key}";
        $filename = $output_filename;

        $result = $this->get_pdf_result($target_url);
        $this->verify($this->is_pdf_string($result), 'Failed to generate NDA: '.$result);

        header('Cache-Control: public');
        header('Content-type: application/pdf');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        header('Content-Length: '.strlen($result));

        echo $result;
        exit();
    }

    private function get_pdf_result($url, $output_stream = false): mixed
    {
        $pdf_endpoint = "{$this->config['pdf_endpoint']}?token={$this->config['token']}";

        $data = array(
            'url' => "{$url}",
            'emulateMedia' => 'print',
            'options' => array(
                'format' => 'A4',
                'margin' => array(
                    'left' => '50px',
                    'right' => '50px',
                    'top' => '60px',
                    'bottom' => '60px',
                ),
                'printBackground' => true,
                'scale' => 0.5,
            ),
        );

        if (strlen($this->header_template) || strlen($this->footer_template))
        {
            $data['options']['displayHeaderFooter'] = true;

            if (strlen($this->header_template))
                $data['options']['headerTemplate'] = $this->header_template;

            if (strlen($this->footer_template))
                $data['options']['footerTemplate'] = $this->footer_template;
        };

        $opts = array(
            CURLOPT_URL             => $pdf_endpoint,
            CURLOPT_CUSTOMREQUEST   => 'POST',
            CURLOPT_POST            => 1,
            CURLOPT_POSTFIELDS      => json_encode($data),
            CURLOPT_HTTPHEADER      => array(
                'Content-Type: application/json',
                'Cache-Control: no-cache',
            ),
        );

        if ($output_stream !== false)
            $opts[CURLOPT_FILE] = $output_stream;
        else
            $opts[CURLOPT_RETURNTRANSFER] = true;

        $curl = curl_init();
        curl_setopt_array($curl, $opts);

        $result = curl_exec($curl);

        curl_close($curl);

        if ($output_stream !== false)
            return true;
        else
            return $result;
    }
};
?>
