<?php
/**
 * Plugin Name: Base de Conhecimento
 * Plugin URI: https://www.6web.com.br
 * Description: Sistema avançado de documentação e base de conhecimento com interface moderna e intuitiva
 * Version: 1.0.0
 * Author: 6Web Soluções Digitais
 * Author URI: https://www.6web.com.br
 * Text Domain: base-conhecimento
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) exit;

class BaseConhecimento {
    private static $instance = null;
    
    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->defineConstantes();
        $this->incluirArquivos();
        $this->iniciarHooks();
    }

    private function defineConstantes() {
        define('BC_PLUGIN_PATH', plugin_dir_path(__FILE__));
        define('BC_PLUGIN_URL', plugin_dir_url(__FILE__));
        define('BC_VERSION', '1.0.0');
        define('BC_DB_VERSION', '1.0.0');
        define('BC_TEXT_DOMAIN', 'base-conhecimento');
    }

    private function incluirArquivos() {
        require_once BC_PLUGIN_PATH . 'includes/class-post-types.php';
        require_once BC_PLUGIN_PATH . 'includes/class-taxonomias.php';
        require_once BC_PLUGIN_PATH . 'includes/class-shortcodes.php';
        require_once BC_PLUGIN_PATH . 'includes/class-estatisticas.php';
        require_once BC_PLUGIN_PATH . 'includes/class-templates.php';
        require_once BC_PLUGIN_PATH . 'includes/class-ajax.php';
        require_once BC_PLUGIN_PATH . 'includes/class-helpers.php';
        
        if (is_admin()) {
            require_once BC_PLUGIN_PATH . 'admin/class-admin.php';
            require_once BC_PLUGIN_PATH . 'admin/class-dashboard.php';
            require_once BC_PLUGIN_PATH . 'admin/class-documentos.php';
        }
    }

    private function iniciarHooks() {
        register_activation_hook(__FILE__, array($this, 'ativar'));
        register_deactivation_hook(__FILE__, array($this, 'desativar'));
        
        add_action('init', array($this, 'iniciar'));
        add_action('init', array($this, 'carregarTextdomain'));
        add_action('admin_enqueue_scripts', array($this, 'adminAssets'));
        add_action('wp_enqueue_scripts', array($this, 'publicAssets'));

        add_filter('template_include', array($this, 'templateLoader'));
        add_filter('single_template', array($this, 'singleTemplate'));
        add_filter('archive_template', array($this, 'archiveTemplate'));
        add_filter('taxonomy_template', array($this, 'taxonomyTemplate'));
        add_filter('search_template', array($this, 'searchTemplate'));
    }

    public function templateLoader($template) {
        $templates = array(
            'arquivo' => 'public/views/arquivo.php',
            'artigo' => 'public/views/artigo.php',
            'categoria' => 'public/views/categoria.php',
            'resultados-busca' => 'public/views/resultados-busca.php',
            'home' => 'public/views/home.php'
        );

        foreach ($templates as $key => $file) {
            if (file_exists(BC_PLUGIN_PATH . $file)) {
                $templates[$key] = BC_PLUGIN_PATH . $file;
            }
        }

        return $template;
    }

    public function singleTemplate($template) {
        if (is_singular('bc_documento')) {
            $novo_template = BC_PLUGIN_PATH . 'public/views/artigo.php';
            if (file_exists($novo_template)) {
                return $novo_template;
            }
        }
        return $template;
    }

    public function archiveTemplate($template) {
        if (is_post_type_archive('bc_documento')) {
            $novo_template = BC_PLUGIN_PATH . 'public/views/arquivo.php';
            if (file_exists($novo_template)) {
                return $novo_template;
            }
        }
        return $template;
    }

    public function taxonomyTemplate($template) {
        if (is_tax('bc_pasta')) {
            $novo_template = BC_PLUGIN_PATH . 'public/views/categoria.php';
            if (file_exists($novo_template)) {
                return $novo_template;
            }
        }
        return $template;
    }

    public function searchTemplate($template) {
        if (isset($_GET['post_type']) && $_GET['post_type'] === 'bc_documento') {
            $novo_template = BC_PLUGIN_PATH . 'public/views/resultados-busca.php';
            if (file_exists($novo_template)) {
                return $novo_template;
            }
        }
        return $template;
    }

    public function ativar() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}bc_estatisticas (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            documento_id bigint(20) NOT NULL,
            visualizacoes int(11) DEFAULT 0,
            curtidas int(11) DEFAULT 0,
            nao_curtidas int(11) DEFAULT 0,
            tempo_leitura int(11) DEFAULT 0,
            data_criacao datetime DEFAULT CURRENT_TIMESTAMP,
            ultima_atualizacao datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY documento_id (documento_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        add_option('bc_db_version', BC_DB_VERSION);
        
        $this->criarPaginasPadrao();
        
        flush_rewrite_rules();
    }

    private function criarPaginasPadrao() {
        $paginas = array(
            'base-de-conhecimento' => array(
                'title' => 'Base de Conhecimento',
                'content' => '[documentacao_completa]'
            ),
            'documentacao' => array(
                'title' => 'Documentação',
                'content' => '[documentacao_home]'
            )
        );

        foreach ($paginas as $slug => $pagina) {
            if (null === get_page_by_path($slug)) {
                wp_insert_post(array(
                    'post_title' => $pagina['title'],
                    'post_content' => $pagina['content'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_name' => $slug
                ));
            }
        }
    }

    public function desativar() {
        flush_rewrite_rules();
    }

    public function iniciar() {
        do_action('base_conhecimento_init');
    }

    public function carregarTextdomain() {
        load_plugin_textdomain(
            BC_TEXT_DOMAIN,
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    public function adminAssets($hook) {
        if (strpos($hook, 'base-conhecimento') !== false) {
            wp_enqueue_style(
                'base-conhecimento-admin',
                BC_PLUGIN_URL . 'admin/assets/css/admin.css',
                array(),
                BC_VERSION
            );

            wp_enqueue_script(
                'base-conhecimento-admin',
                BC_PLUGIN_URL . 'admin/assets/js/admin.js',
                array('jquery', 'wp-element'),
                BC_VERSION,
                true
            );

            wp_enqueue_script(
                'base-conhecimento-dashboard',
                BC_PLUGIN_URL . 'admin/assets/js/dashboard.js',
                array('jquery', 'wp-element'),
                BC_VERSION,
                true
            );

            if (in_array($hook, array('post.php', 'post-new.php'))) {
                wp_enqueue_style(
                    'base-conhecimento-editor',
                    BC_PLUGIN_URL . 'admin/assets/css/editor.css',
                    array(),
                    BC_VERSION
                );

                wp_enqueue_script(
                    'base-conhecimento-editor',
                    BC_PLUGIN_URL . 'admin/assets/js/editor.js',
                    array('jquery'),
                    BC_VERSION,
                    true
                );
            }

            wp_localize_script('base-conhecimento-admin', 'bcData', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bc_nonce'),
                'strings' => array(
                    'salvando' => __('Salvando...', BC_TEXT_DOMAIN),
                    'salvo' => __('Salvo!', BC_TEXT_DOMAIN),
                    'erro' => __('Erro ao salvar', BC_TEXT_DOMAIN)
                )
            ));
        }
    }

    public function publicAssets() {
        wp_enqueue_style(
            'base-conhecimento',
            BC_PLUGIN_URL . 'public/assets/css/public.css',
            array(),
            BC_VERSION
        );

        wp_enqueue_script(
            'base-conhecimento',
            BC_PLUGIN_URL . 'public/assets/js/public.js',
            array('jquery'),
            BC_VERSION,
            true
        );

        wp_enqueue_script(
            'base-conhecimento-pesquisa',
            BC_PLUGIN_URL . 'public/assets/js/pesquisa.js',
            array('jquery'),
            BC_VERSION,
            true
        );

        wp_localize_script('base-conhecimento', 'bcPublicData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bc_public_nonce')
        ));
    }
}

function iniciar_base_conhecimento() {
    return BaseConhecimento::getInstance();
}

add_action('plugins_loaded', 'iniciar_base_conhecimento');
