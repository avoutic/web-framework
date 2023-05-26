<?php

namespace WebFramework\Actions;

use WebFramework\Core\PageAction;

class Logoff extends PageAction
{
    /**
     * @return array<string, string>
     */
    public static function getFilter(): array
    {
        return [
            'return_page' => FORMAT_RETURN_PAGE,
        ];
    }

    protected function getTitle(): string
    {
        return 'Logoff';
    }

    protected function doLogic(): void
    {
        $this->deauthenticate();

        $returnPage = $this->getInputVar('return_page');

        if (!strlen($returnPage) || substr($returnPage, 0, 2) == '//')
        {
            $returnPage = '/';
        }

        if (substr($returnPage, 0, 1) != '/')
        {
            $returnPage = '/'.$returnPage;
        }

        header('Location: '.$this->getBaseUrl().$returnPage);

        exit();
    }

    protected function displayContent(): void
    {
        echo <<<'HTML'
<div>
  Logging off.
</div>
HTML;
    }
}
