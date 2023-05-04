<?php

namespace WebFramework\Core;

abstract class PageAction extends ActionCore
{
    protected string $frame_file;

    /**
     * @var array<mixed>
     */
    protected array $page_content = [];

    public function __construct()
    {
        parent::__construct();

        $this->frame_file = $this->get_config('actions.default_frame_file');
        $this->page_content['base_url'] = $this->get_base_url();
    }

    protected function get_csrf_token(): string
    {
        return $this->web_handler->get_csrf_token();
    }

    protected function get_title(): string
    {
        return 'No Title Defined';
    }

    protected function get_content_title(): string
    {
        return $this->get_title();
    }

    protected function get_canonical(): string
    {
        return '';
    }

    protected function get_onload(): string
    {
        return '';
    }

    protected function get_keywords(): string
    {
        return '';
    }

    protected function get_description(): string
    {
        return '';
    }

    protected function get_meta_robots(): string
    {
        // Default behaviour is "index,follow"
        //
        return 'index,follow';
    }

    protected function get_template_system(): string
    {
        $template_file = $this->get_template_file();

        if (substr($template_file, -6) === '.latte')
        {
            return 'latte';
        }

        return 'native';
    }

    protected function get_frame_file(): string
    {
        return $this->frame_file;
    }

    /**
     * @param array<mixed> $args
     */
    public function load_template(string $name, array $args = []): void
    {
        $app_dir = $this->get_app_dir();
        $this->verify(file_exists("{$app_dir}/templates/{$name}.inc.php"), 'Requested template not present');

        include "{$app_dir}/templates/{$name}.inc.php";
    }

    protected function is_blocked(string $name): bool
    {
        return $this->input[$name] != $this->raw_input[$name];
    }

    protected function check_sanity(): void
    {
    }

    protected function do_logic(): void
    {
    }

    protected function display_header(): void
    {
    }

    protected function display_footer(): void
    {
    }

    protected function display_content(): void
    {
        $template_file = $this->get_template_file();

        $this->load_template($template_file, $this->page_content);
    }

    protected function get_template_file(): string
    {
        return '';
    }

    protected function display_frame(): void
    {
        $template_system = $this->get_template_system();

        if ($template_system === 'latte')
        {
            $this->display_with_latte();
            return;
        }
        elseif ($template_system === 'native')
        {
            $this->display_with_native();
            return;
        }

        $this->verify(false, 'Not a supported template_system: '.$template_system);
    }

    /**
     * @return array<string, mixed>
     */
    protected function get_core_variables(): array
    {
        $data = [];

        $data['base_url'] = $this->get_base_url();
        $data['content_title'] = $this->get_content_title();
        $data['csrf_token'] = $this->get_csrf_token();
        $data['canonical'] = $this->get_canonical();
        $data['description'] = $this->get_description();
        $data['keywords'] = $this->get_keywords();
        $data['meta_robots'] = $this->get_meta_robots();
        $data['title'] = $this->get_title();

        $data['build_info'] = $this->get_build_info();
        $data['config'] = $this->get_config('');
        $data['messages'] = $this->get_messages();

        return $data;
    }

    protected function display_with_latte(): void
    {
        $app_dir = $this->get_app_dir();
        $template_dir = "{$app_dir}/templates";

        $this->page_content['core'] = $this->get_core_variables();

        $latte = new \Latte\Engine;
        $latte->setTempDirectory('/tmp/latte');
        $latte->setLoader(new \Latte\Loaders\FileLoader($template_dir));

        $template_file = $this->get_template_file();

        $this->verify(file_exists("{$template_dir}/{$template_file}"), 'Requested template not present');

        $output = $latte->renderToString($template_file, $this->page_content);

        echo($output);
        return;
    }

    protected function display_with_native(): void
    {
        // Unset availability of input in display
        // Forces explicit handling in do_logic()
        //
        $this->input = [];
        $this->raw_input = [];

        ob_start();

        if (strlen($this->get_frame_file()))
        {
            $app_dir = $this->get_app_dir();
            $frame_file = "{$app_dir}/frames/".$this->get_frame_file();
            $this->verify(file_exists($frame_file), 'Requested frame file not present');

            require $frame_file;
        }
        else
        {
            $this->display_content();
        }

        $content = ob_get_clean();

        echo($content);
    }

    public function html_main(): void
    {
        $this->check_sanity();
        $this->do_logic();
        $this->display_frame();
    }
}
