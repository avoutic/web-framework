<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Core;

use WebFramework\Security\ProtectService;

/**
 * Class Browserless.
 *
 * Handles PDF generation and manipulation using the Browserless service.
 */
class Browserless
{
    private string $footerTemplate = '';
    private string $headerTemplate = '';
    private string $apiKey = '';

    private const PDF_MAGIC = '%PDF-';

    /**
     * Browserless constructor.
     *
     * @param ProtectService $protectService Service for protecting sensitive data
     * @param string         $localServer    The local server URL
     * @param string         $pdfEndpoint    The endpoint for PDF generation
     * @param string         $token          Authentication token for the Browserless service
     */
    public function __construct(
        private ProtectService $protectService,
        private string $localServer,
        private string $pdfEndpoint,
        private string $token,
    ) {}

    /**
     * Check if a file is a PDF based on its magic number.
     *
     * @param string $filename The path to the file
     *
     * @return bool True if the file is a PDF, false otherwise
     */
    private function isPdf(string $filename): bool
    {
        return (file_get_contents($filename, false, null, 0, strlen(self::PDF_MAGIC)) === self::PDF_MAGIC) ? true : false;
    }

    /**
     * Check if a string starts with the PDF magic number.
     *
     * @param string $str The string to check
     *
     * @return bool True if the string starts with the PDF magic number, false otherwise
     */
    private function isPdfString(string $str): bool
    {
        return (substr($str, 0, strlen(self::PDF_MAGIC)) === self::PDF_MAGIC) ? true : false;
    }

    /**
     * Set the API key data.
     *
     * @param array<string, mixed> $data The API key data
     */
    public function setApiKeyData(array $data): void
    {
        $this->apiKey = $this->protectService->packArray($data);
    }

    /**
     * Set the footer template for PDF generation.
     *
     * @param string $template The footer template
     */
    public function setFooterTemplate(string $template): void
    {
        $this->footerTemplate = $template;
    }

    /**
     * Set the header template for PDF generation.
     *
     * @param string $template The header template
     */
    public function setHeaderTemplate(string $template): void
    {
        $this->headerTemplate = $template;
    }

    /**
     * Generate and output a PDF file.
     *
     * @param string $relativeUrl    The relative URL to generate the PDF from
     * @param string $outputFilename The filename for the output PDF
     *
     * @throws \RuntimeException If PDF generation fails
     */
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
        if (!$this->isPdfString($result))
        {
            throw new \RuntimeException('Failed to generate NDA: '.$result);
        }

        header('Cache-Control: public');
        header('Content-type: application/pdf');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        header('Content-Length: '.strlen($result));

        echo $result;

        exit();
    }

    /**
     * Generate a PDF and return it as a stream.
     *
     * @param string $relativeUrl The relative URL to generate the PDF from
     *
     * @return resource The PDF as a stream
     *
     * @throws \RuntimeException If PDF generation fails
     */
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
        if ($tmpFile === false)
        {
            throw new \RuntimeException('Failed to get temporary stream');
        }

        $result = $this->getPdfResult($targetUrl, $tmpFile);
        $metaData = stream_get_meta_data($tmpFile);
        $tmpPath = $metaData['uri'] ?? throw new \RuntimeException('Failed to get temporary stream');
        if (!$this->isPdf($tmpPath))
        {
            throw new \RuntimeException('Failed to generate PDF: '.file_get_contents($tmpPath));
        }

        return $tmpFile;
    }

    /**
     * Get the PDF result from the Browserless service.
     *
     * @param string         $url          The URL to generate the PDF from
     * @param false|resource $outputStream Optional output stream
     *
     * @return mixed The PDF result
     */
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
