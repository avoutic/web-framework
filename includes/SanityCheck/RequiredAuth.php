<?php

namespace WebFramework\Core\SanityCheck;

class RequiredAuth extends Base
{
    public function perform_checks(): bool
    {
        // Check if all required auth files are present
        //
        $this->add_output('Checking for auths:'.PHP_EOL);

        $required_auths = $this->config;

        $error = false;
        $app_dir = $this->get_app_dir();

        foreach ($required_auths as $filename)
        {
            $path = "{$app_dir}/includes/auth/{$filename}";

            $exists = file_exists($path);

            if ($exists)
            {
                $this->add_output(" - {$filename} present".PHP_EOL);

                continue;
            }

            $this->add_output(" - {$filename} not present. Not fixing.".PHP_EOL);
            $error = true;
        }

        if (!$error)
        {
            $this->add_output('  Pass'.PHP_EOL.PHP_EOL);
        }
        else
        {
            $this->add_output(PHP_EOL.'Breaking off');

            return false;
        }

        return true;
    }
}
