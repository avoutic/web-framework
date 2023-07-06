<?php

namespace WebFramework\Core;

use WebFramework\Security\ProtectService;

class Browserless
{
    private string $footerTemplate = '';
    private string $headerTemplate = '';
    private string $apiKey = '';

    private const PDF_MAGIC = '%PDF-';

    public function __construct(
        private AssertService $assertService,
        private ProtectService $protectService,
        private string $localServer,
        private string $pdfEndpoint,
        private string $token,
    ) {
    }

    private function isPdf(string $filename): bool
    {
        return (file_get_contents($filename, false, null, 0, strlen(self::PDF_MAGIC)) === self::PDF_MAGIC) ? true : false;
    }

    private function isPdfString(string $str): bool
    {
        return (substr($str, 0, strlen(self::PDF_MAGIC)) === self::PDF_MAGIC) ? true : false;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function setApiKeyData(array $data): void
    {
        $this->apiKey = $this->protectService->packArray($data);
    }

    public function setFooterTemplate(string $template): void
    {
        $this->footerTemplate = $template;
    }

    public function setHeaderTemplate(string $template): void
    {
        $this->headerTemplate = $template;
    }

    public function outputPdf(string $relativeUrl, string $outputFilename): void
    {
        $targetUrl = "{$this->localServer}{$relativeUrl}";

        $query = parse_url($targetUrl, PHP_URL_QUERY);
        if ($query)
        {
            $targetUrl .= "&auth={$this->apiKey}";
        }
        else
        {
            $targetUrl .= "?auth={$this->apiKey}";
        }

        $filename = $outputFilename;

        $result = $this->getPdfResult($targetUrl);
        $this->assertService->verify($this->isPdfString($result), 'Failed to generate NDA: '.$result);

        header('Cache-Control: public');
        header('Content-type: application/pdf');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        header('Content-Length: '.strlen($result));

        echo $result;

        exit();
    }

    public function outputStream(string $relativeUrl): mixed
    {
        $targetUrl = "{$this->localServer}{$relativeUrl}";

        $query = parse_url($targetUrl, PHP_URL_QUERY);
        if ($query)
        {
            $targetUrl .= "&auth={$this->apiKey}";
        }
        else
        {
            $targetUrl .= "?auth={$this->apiKey}";
        }

        $tmpFile = tmpfile();
        $this->assertService->verify($tmpFile !== false, 'Failed to get temporary stream');

        $result = $this->getPdfResult($targetUrl, $tmpFile);
        $tmpPath = stream_get_meta_data($tmpFile)['uri'];
        $this->assertService->verify($this->isPdf($tmpPath), 'Failed to generate PDF: '.file_get_contents($tmpPath));

        return $tmpFile;
    }

    private function getPdfResult($url, $outputStream = false): mixed
    {
        $pdfEndpoint = "{$this->pdfEndpoint}?token={$this->token}";

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

        if (strlen($this->headerTemplate) || strlen($this->footerTemplate))
        {
            $data['options']['displayHeaderFooter'] = true;

            if (strlen($this->headerTemplate))
            {
                $data['options']['headerTemplate'] = $this->headerTemplate;
            }

            if (strlen($this->footerTemplate))
            {
                $data['options']['footerTemplate'] = $this->footerTemplate;
            }
        }

        $opts = [
            CURLOPT_URL => $pdfEndpoint,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Cache-Control: no-cache',
            ],
        ];

        if ($outputStream !== false)
        {
            $opts[CURLOPT_FILE] = $outputStream;
        }
        else
        {
            $opts[CURLOPT_RETURNTRANSFER] = true;
        }

        $curl = curl_init();
        curl_setopt_array($curl, $opts);

        $result = curl_exec($curl);

        curl_close($curl);

        if ($outputStream !== false)
        {
            return true;
        }

        return $result;
    }
}
