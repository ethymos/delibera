<?php

/**
 * Controla os distintos temas do Delibera
 * disponíveis.
 */
class DeliberaThemes
{
    /**
     * Caminho para o diretório
     * do tema padrão
     * @var string
     */
    public $defaultThemePath;
    
    /**
     * URL para o diretório do
     * tema padrão
     * @var string
     */
    public $defaultThemeUrl;
    
    function __construct()
    {
        $this->defaultThemePath = __DIR__ . '/themes/';
        $this->defaultThemeUrl = plugins_url('/delibera/themes/');
    }
    
    /**
     * Retorna o diretório do tema
     * atual.
     * 
     * @return string
     */
    public function getThemeDir()
    {
        if (file_exists(get_stylesheet_directory() . '/delibera_theme/')) {
            return get_stylesheet_directory() . '/delibera_theme/';
        } else {
            return $this->defaultThemePath;
        }
    }
    
    /**
     * Retorna a URL para o diretório 
     * principal do tema atual.
     * 
     * @return string
     */
    public function getThemeUrl()
    {
        if (file_exists(get_stylesheet_directory() . '/delibera_theme/')) {
            return get_stylesheet_directory_uri() . '/delibera_theme/';
        } else {
            return $this->defaultThemeUrl;
        }
    }

    /**
     * Retorna o caminho no sistema de arquivos
     * para um arquivo no tema atual. Se o arquivo
     * não existir, retorna o caminho para o arquivo 
     * no tema padrão.
     * 
     * @param string $file_name
     * @return string
     */
    public function themeFilePath($fileName)
    {
        $filePath = $this->getThemeDir() . $fileName;
        
        if (file_exists($filePath)) {
            return $filePath;
        } else {
            return $this->defaultThemePath . $fileName;
        }
    }
    
    /**
     * Retorna a url para um arquivo no tema atual.
     * Se o arquivo não existir, retorna o caminho
     * para o arquivo no tema padrão.
     * 
     * @param string $file_name
     * @return string
     */
    public function themeFileUrl($fileName)
    {
        $filePath = $this->getThemeDir() . $fileName;
        
        if (file_exists($filePath)) {
            return $this->getThemeUrl() . $fileName;
        } else {
            return $this->defaultThemeUrl . $fileName;
        }
    }
    
    /**
     * Inclui os arquivos do tema relacionados com 
     * a listagem de pautas e retorna o template
     * a ser usado.
     * 
     * @param string $archiveTemplate
     * @return string
     */
    public function archiveTemplate($archiveTemplate)
    {
        global $post;
    
        if (get_post_type($post) == "pauta" || is_post_type_archive('pauta')) {
            wp_enqueue_style('delibera_style', $this->themeFileUrl('delibera_style.css'));
            
            if (is_post_type_archive('pauta')) {
                $archiveTemplate = $this->themeFilePath('archive-pauta.php');
            }
        }
                
        return $archiveTemplate;
    }
    
    /**
     * Inclui os arquivos do tema relacionados com 
     * a página de uma pauta e retorna o template
     * a ser usado.
     * 
     * @param string $singleTemplate
     * @return string
     */
    public function singleTemplate($singleTemplate)
    {
        global $post;
    
        if (get_post_type($post) == "pauta" || is_post_type_archive( 'pauta' )) {
            wp_enqueue_style('delibera_style', $this->themeFileUrl('delibera_style.css'));
            
            if ($post->post_type == 'pauta') {
                $singleTemplate = $this->themeFilePath('single-pauta.php');
            }
        }
        
        return $singleTemplate;
    }
    
    /**
     * Adiciona o CSS do admin conforme o
     * tema.
     * 
     * @return null
     */
    public function adminPrintStyles()
    {
        wp_enqueue_style('delibera_admin_style', $this->themeFileUrl('delibera_admin.css'));
    }
    
    /**
     * Carrega o arquivo de template do loop
     * de pautas para o tema atual. Se o arquivo
     * não existir usa o arquivo do tema padrão.
     * 
     * @return null
     */
    public function archiveLoop()
    {
        load_template($this->themeFilePath('delibera-loop-archive.php'), true);
    }
}

$deliberaThemes = new DeliberaThemes;

add_filter('archive_template', array($deliberaThemes, 'archiveTemplate'));
add_filter('single_template', array($deliberaThemes, 'singleTemplate'));
add_action('admin_print_styles', array($deliberaThemes, 'adminPrintStyles'));

// apenas adiciona o arquivo com a função que gera o header
require_once($deliberaThemes->defaultThemePath . 'delibera_header.php');
