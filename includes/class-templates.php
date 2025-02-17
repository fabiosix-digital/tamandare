<?php
if (!defined('ABSPATH')) exit;

class BC_Templates {
    private static $instance = null;
    
    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_filter('template_include', array($this, 'carregarTemplate'));
        add_filter('single_template', array($this, 'carregarTemplateSingle'));
        add_filter('archive_template', array($this, 'carregarTemplateArchive'));
        add_filter('taxonomy_template', array($this, 'carregarTemplateTaxonomia'));
        add_filter('search_template', array($this, 'carregarTemplateBusca'));
        add_action('wp_enqueue_scripts', array($this, 'carregarAssets'));
        add_action('wp_ajax_bc_busca_ajax', array($this, 'buscaAjax'));
        add_action('wp_ajax_nopriv_bc_busca_ajax', array($this, 'buscaAjax'));
        add_action('wp_ajax_bc_carregar_artigo', array($this, 'carregarArtigoAjax'));
        add_action('wp_ajax_nopriv_bc_carregar_artigo', array($this, 'carregarArtigoAjax'));
        add_filter('bc_footer_info', array($this, 'alterarCopyright'));
    }

    public function carregarTemplate($template) {
        if (is_singular('bc_documento')) {
            $this->removerBotoesTopoDaPagina();
            $this->removerCampoBuscaNoArtigo();
        }

        if (is_post_type_archive('bc_documento')) {
            $novo_template = BC_PLUGIN_PATH . 'public/views/arquivo.php';
            if (file_exists($novo_template)) {
                return $novo_template;
            }
        }
        return $template;
    }

    // Método criado para o single: carrega o template para um documento individual
    public function carregarTemplateSingle($template) {
        $novo_template = BC_PLUGIN_PATH . 'public/views/single-documento.php';
        if (file_exists($novo_template)) {
            return $novo_template;
        }
        return $template;
    }

    public function carregarTemplateArchive($template) {
        $novo_template = BC_PLUGIN_PATH . 'public/views/arquivo.php';
        if (file_exists($novo_template)) {
            return $novo_template;
        }
        return $template;
    }

    public function carregarTemplateTaxonomia($template) {
        $novo_template = BC_PLUGIN_PATH . 'public/views/taxonomia.php';
        if (file_exists($novo_template)) {
            return $novo_template;
        }
        return $template;
    }

    public function carregarTemplateBusca($template) {
        $novo_template = BC_PLUGIN_PATH . 'public/views/busca.php';
        if (file_exists($novo_template)) {
            return $novo_template;
        }
        return $template;
    }

    public function buscaAjax() {
        check_ajax_referer('bc_ajax_nonce', 'nonce');

        $termo = sanitize_text_field($_POST['termo']);
        $resultados = array();

        if (strlen($termo) >= 3) {
            $args = array(
                'post_type'      => 'bc_documento',
                'posts_per_page' => -1,
                's'              => $termo,
                'orderby'        => 'title',
                'order'          => 'ASC'
            );

            $query = new WP_Query($args);
            
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $pasta = wp_get_post_terms(get_the_ID(), 'bc_pasta');
                    $resultados[] = array(
                        'id'          => get_the_ID(),
                        'titulo'      => get_the_title(),
                        'link'        => get_permalink(),
                        'pasta'       => !empty($pasta) ? $pasta[0]->name : '',
                        'pasta_link'  => !empty($pasta) ? get_term_link($pasta[0]) : '',
                        'excerpt'     => get_the_excerpt()
                    );
                }
            }
            wp_reset_postdata();
        }

        wp_send_json_success($resultados);
    }

    public function carregarArtigoAjax() {
        check_ajax_referer('bc_ajax_nonce', 'nonce');
        
        $post_id = intval($_POST['id']);
        $post = get_post($post_id);

        if ($post && $post->post_type === 'bc_documento') {
            $conteudo = array(
                'titulo'      => get_the_title($post),
                'conteudo'    => apply_filters('the_content', $post->post_content),
                'breadcrumbs' => $this->obterBreadcrumbsArray($post),
                'meta'        => $this->obterMetaArtigo($post)
            );
            wp_send_json_success($conteudo);
        }
        
        wp_send_json_error();
    }

    private function removerCampoBuscaNoArtigo() {
        // Caso necessário, coloque aqui o código para remover o campo de busca no artigo.
    }

    private function removerBotoesTopoDaPagina() {
        // Caso necessário, coloque aqui o código para remover ou desabilitar os botões "Ver todos os artigos" e "Alternar tema".
    }

    private function obterMetaArtigo($post) {
        $stats = BC_Estatisticas::getInstance()->obterEstatisticasDocumento($post->ID);
        $pasta = wp_get_post_terms($post->ID, 'bc_pasta');
        $tempo_leitura = BC_Helpers::calcularTempoLeitura($post->post_content);

        return array(
            'data' => get_the_date('', $post),
            'tempo_leitura' => $tempo_leitura,
            'visualizacoes' => $stats ? $stats->total_visualizacoes : 0,
            'pasta' => !empty($pasta) ? $pasta[0] : null
        );
    }

    public function alterarCopyright($copyright) {
        return 'Copyright © ' . date('Y') . ' by 6Web Soluções Digitais - Todos os Direitos reservados.';
    }

    public function obterBreadcrumbs() {
        return $this->renderizarBreadcrumbs($this->obterBreadcrumbsArray());
    }

    private function obterBreadcrumbsArray($post = null) {
        $breadcrumbs = array();
        
        $breadcrumbs[] = array(
            'title' => __('Base de Conhecimento', 'base-conhecimento'),
            'url'   => get_post_type_archive_link('bc_documento')
        );
        
        if (is_tax('bc_pasta')) {
            $term = get_queried_object();
            $this->adicionarAncestralPasta($breadcrumbs, $term);
        }
        
        if ($post || is_singular('bc_documento')) {
            if (!$post) {
                $post = get_queried_object();
            }
            
            $terms = wp_get_post_terms($post->ID, 'bc_pasta');
            
            if (!empty($terms)) {
                $this->adicionarAncestralPasta($breadcrumbs, $terms[0]);
            }
            
            $breadcrumbs[] = array(
                'title' => get_the_title($post),
                'url'   => false
            );
        }
        
        return $breadcrumbs;
    }

    private function adicionarAncestralPasta(&$breadcrumbs, $term) {
        $ancestors = array_reverse(get_ancestors($term->term_id, 'bc_pasta', 'taxonomy'));
        
        foreach ($ancestors as $ancestor) {
            $ancestor_term = get_term($ancestor, 'bc_pasta');
            $breadcrumbs[] = array(
                'title' => $ancestor_term->name,
                'url'   => get_term_link($ancestor_term)
            );
        }
        
        $breadcrumbs[] = array(
            'title' => $term->name,
            'url'   => get_term_link($term)
        );
    }

    public function renderizarBreadcrumbs($breadcrumbs = null) {
        if (!$breadcrumbs) {
            $breadcrumbs = $this->obterBreadcrumbsArray();
        }
        
        if (!empty($breadcrumbs)) {
            echo '<nav class="bc-breadcrumbs" aria-label="'. esc_attr__('Navegação', 'base-conhecimento') .'">';
            foreach ($breadcrumbs as $index => $item) {
                if ($index > 0) {
                    echo '<span class="bc-breadcrumb-separator">/</span>';
                }
                
                if ($item['url']) {
                    printf(
                        '<a href="%s" class="bc-breadcrumb-link">%s</a>',
                        esc_url($item['url']),
                        esc_html($item['title'])
                    );
                } else {
                    printf(
                        '<span class="bc-breadcrumb-atual">%s</span>',
                        esc_html($item['title'])
                    );
                }
            }
            echo '</nav>';
        }
    }
}

BC_Templates::getInstance();
