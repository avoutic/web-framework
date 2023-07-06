<?php

namespace WebFramework\Core;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

abstract class PageAction extends ActionCore
{
    protected string $frameFile;

    /**
     * @var array<mixed>
     */
    protected array $pageContent = [];

    public function init(): void
    {
        parent::init();

        $this->frameFile = $this->configService->get('actions.default_frame_file');
        $this->pageContent['base_url'] = $this->configService->get('base_url');
    }

    protected function getCsrfToken(): string
    {
        return $this->csrfService->getToken();
    }

    protected function getTitle(): string
    {
        return 'No Title Defined';
    }

    protected function getContentTitle(): string
    {
        return $this->getTitle();
    }

    protected function getCanonical(): string
    {
        return '';
    }

    protected function getOnload(): string
    {
        return '';
    }

    protected function getKeywords(): string
    {
        return '';
    }

    protected function getDescription(): string
    {
        return '';
    }

    protected function getMetaRobots(): string
    {
        // Default behaviour is "index,follow"
        //
        return 'index,follow';
    }

    protected function getTemplateSystem(): string
    {
        $templateFile = $this->getTemplateFile();

        if (substr($templateFile, -6) === '.latte')
        {
            return 'latte';
        }

        return 'native';
    }

    protected function getFrameFile(): string
    {
        return $this->frameFile;
    }

    /**
     * @param array<mixed> $args
     */
    public function loadTemplate(string $name, array $args = []): void
    {
        $appDir = $this->getAppDir();
        $this->verify(file_exists("{$appDir}/templates/{$name}.inc.php"), 'Requested template not present');

        include "{$appDir}/templates/{$name}.inc.php";
    }

    protected function isBlocked(string $name): bool
    {
        return $this->input[$name] != $this->rawInput[$name];
    }

    protected function checkSanity(): void
    {
    }

    protected function doLogic(): void
    {
    }

    protected function displayHeader(): void
    {
    }

    protected function displayFooter(): void
    {
    }

    protected function displayContent(): void
    {
        $templateFile = $this->getTemplateFile();

        $this->loadTemplate($templateFile, $this->pageContent);
    }

    protected function getTemplateFile(): string
    {
        return '';
    }

    protected function displayFrame(): void
    {
        $templateSystem = $this->getTemplateSystem();

        if ($templateSystem === 'latte')
        {
            $this->displayWithLatte();

            return;
        }

        if ($templateSystem === 'native')
        {
            $this->displayWithNative();

            return;
        }

        $this->reportError('Not a supported template_system: '.$templateSystem);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getCoreVariables(): array
    {
        $data = [];

        $data['base_url'] = $this->getBaseUrl();
        $data['content_title'] = $this->getContentTitle();
        $data['csrf_token'] = $this->getCsrfToken();
        $data['canonical'] = $this->getCanonical();
        $data['description'] = $this->getDescription();
        $data['keywords'] = $this->getKeywords();
        $data['meta_robots'] = $this->getMetaRobots();
        $data['title'] = $this->getTitle();

        $data['build_info'] = $this->getBuildInfo();
        $data['config'] = $this->getConfig('');
        $data['messages'] = $this->getMessages();

        return $data;
    }

    protected function displayWithLatte(): void
    {
        $appDir = $this->getAppDir();
        $templateDir = "{$appDir}/templates";

        $this->pageContent['core'] = $this->getCoreVariables();

        $latte = new \Latte\Engine();
        $latte->setTempDirectory('/tmp/latte');
        $latte->setLoader(new \Latte\Loaders\FileLoader($templateDir));

        $templateFile = $this->getTemplateFile();

        $this->verify(file_exists("{$templateDir}/{$templateFile}"), 'Requested template not present');

        $output = $latte->renderToString($templateFile, $this->pageContent);

        echo($output);
    }

    protected function displayWithNative(): void
    {
        // Unset availability of input in display
        // Forces explicit handling in do_logic()
        //
        $this->input = [];
        $this->rawInput = [];

        ob_start();

        if (strlen($this->getFrameFile()))
        {
            $appDir = $this->getAppDir();
            $frameFile = "{$appDir}/frames/".$this->getFrameFile();
            $this->verify(file_exists($frameFile), 'Requested frame file not present');

            require $frameFile;
        }
        else
        {
            $this->displayContent();
        }

        $content = ob_get_clean();

        echo($content);
    }

    /**
     * @param array<string, string> $args
     */
    public function htmlMain(Request $request, Response $response, array $args): void
    {
        $this->handlePermissionsAndInputs($request, $args);
        $this->checkSanity();
        $this->doLogic();
        $this->displayFrame();

        exit();
    }
}
