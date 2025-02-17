<?php
if (!defined('ABSPATH')) exit;

class BC_Ajax {
    private static $instance = null;

    private function __construct() {
        // Ajax para usuários logados e não logados
        add_action('wp_ajax_bc_carregar_artigo', array($this, 'carregarArtigo'));
        add_action('wp_ajax_nopriv_bc_carregar_artigo', array($this, 'carregarArtigo'));

        add_action('wp_ajax_bc_busca_ajax', array($this, 'buscarAjax'));
        add_action('wp_ajax_nopriv_bc_busca_ajax', array($this, 'buscarAjax'));

        add_action('wp_ajax_bc_registrar_feedback', array($this, 'registrarFeedback'));
        add_action('wp_ajax_nopriv_bc_registrar_feedback', array($this, 'registrarFeedback'));

        add_action('wp_ajax_bc_registrar_visualizacao', array($this, 'registrarVisualizacao'));
        add_action('wp_ajax_nopriv_bc_registrar_visualizacao', array($this, 'registrarVisualizacao'));

        add_action('wp_ajax_bc_toggle_favorito', array($this, 'toggleFavorito'));

        add_action('wp_ajax_bc_carregar_pasta', array($this, 'carregarPasta'));
        add_action('wp_ajax_nopriv_bc_carregar_pasta', array($this, 'carregarPasta'));
    }

    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function carregarArtigo() {
        check_ajax_referer('bc_nonce', 'nonce');

        $post_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if (!$post_id) {
            wp_send_json_error('ID do artigo não fornecido');
        }

        $post = get_post($post_id);
        
        if (!$post || $post->post_type !== 'bc_documento') {
            wp_send_json_error('Artigo não encontrado');
        }

        // Obtém o conteúdo formatado do artigo
        ob_start();
        include BC_PLUGIN_PATH . 'public/components/artigo.php';
        $html = ob_get_clean();

        // Registra a visualização diretamente (evitando chamar método inexistente em BC_Helpers)
        $views = get_post_meta($post_id, '_bc_visualizacoes', true);
        $views = $views ? intval($views) + 1 : 1;
        update_post_meta($post_id, '_bc_visualizacoes', $views);

        // Retorna o HTML e a URL correta
        wp_send_json_success(array(
            'html' => $html,
            'url'  => str_replace('/documentacao/', '/bc_documento/', get_permalink($post))
        ));
    }

    public function buscarAjax() {
        check_ajax_referer('bc_nonce', 'nonce');

        $termo = isset($_POST['termo']) ? sanitize_text_field($_POST['termo']) : '';
        
        if (empty($termo)) {
            wp_send_json_error('Termo de busca vazio');
        }

        $resultados = array();

        // Busca em pastas
        $pastas = get_terms(array(
            'taxonomy'   => 'bc_pasta',
            'hide_empty' => false,
            'search'     => $termo,
            'number'     => 5
        ));

        if (!is_wp_error($pastas)) {
            foreach ($pastas as $pasta) {
                $resultados[] = array(
                    'id'     => $pasta->term_id,
                    'titulo' => $pasta->name,
                    'link'   => get_term_link($pasta),
                    'type'   => 'folder',
                    'icone'  => get_term_meta($pasta->term_id, 'bc_icone', true)
                );
            }
        }

        // Busca em artigos
        $artigos = get_posts(array(
            'post_type'      => 'bc_documento',
            'posts_per_page' => 10,
            's'              => $termo,
            'orderby'        => 'relevance',
            'order'          => 'DESC'
        ));

        foreach ($artigos as $artigo) {
            $pasta = wp_get_post_terms($artigo->ID, 'bc_pasta');
            $resultados[] = array(
                'id'     => $artigo->ID,
                'titulo' => $artigo->post_title,
                'link'   => str_replace('/documentacao/', '/bc_documento/', get_permalink($artigo)),
                'type'   => 'document',
                'pasta'  => !empty($pasta) ? $pasta[0]->name : '',
                'icone'  => get_post_meta($artigo->ID, '_bc_icone', true)
            );
        }

        if (empty($resultados)) {
            wp_send_json_error('Nenhum resultado encontrado');
        }

        wp_send_json_success($resultados);
    }

    public function registrarFeedback() {
        check_ajax_referer('bc_nonce', 'nonce');

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $tipo = isset($_POST['tipo']) ? sanitize_text_field($_POST['tipo']) : '';

        if (!$post_id || !in_array($tipo, array('like', 'dislike'))) {
            wp_send_json_error('Parâmetros inválidos');
        }

        // Verifica cookie para evitar múltiplos feedbacks
        $cookie_name = 'bc_feedback_' . $post_id;
        if (isset($_COOKIE[$cookie_name])) {
            wp_send_json_error('Feedback já registrado');
        }

        // Registra o feedback
        $meta_key = $tipo === 'like' ? '_bc_curtidas' : '_bc_nao_curtidas';
        $count = get_post_meta($post_id, $meta_key, true);
        $count = $count ? intval($count) + 1 : 1;
        update_post_meta($post_id, $meta_key, $count);

        // Define cookie para 30 dias
        setcookie($cookie_name, '1', time() + (30 * DAY_IN_SECONDS), '/');

        // Atualiza estatísticas, se a classe estiver disponível
        if (class_exists('BC_Estatisticas')) {
            // Nota: Certifique-se de que BC_Estatisticas::registrarFeedback() aceita parâmetros se necessário
            BC_Estatisticas::getInstance()->registrarFeedback();
        }

        wp_send_json_success(array(
            'count' => $count,
            'mensagem' => $tipo === 'like' ? 
                __('Obrigado pelo feedback positivo!', 'base-conhecimento') : 
                __('Obrigado pelo feedback!', 'base-conhecimento')
        ));
    }

    public function registrarVisualizacao() {
        check_ajax_referer('bc_nonce', 'nonce');

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

        if (!$post_id) {
            wp_send_json_error('ID do artigo não fornecido');
        }

        // Verifica cookie para evitar múltiplas visualizações em sequência
        $cookie_name = 'bc_view_' . $post_id;
        if (!isset($_COOKIE[$cookie_name])) {
            // Registra a visualização
            $views = get_post_meta($post_id, '_bc_visualizacoes', true);
            $views = $views ? intval($views) + 1 : 1;
            update_post_meta($post_id, '_bc_visualizacoes', $views);

            // Define cookie por 1 hora
            setcookie($cookie_name, '1', time() + HOUR_IN_SECONDS, '/');

            // Atualiza estatísticas, se a classe estiver disponível
            if (class_exists('BC_Estatisticas')) {
                BC_Estatisticas::getInstance()->registrarVisualizacao();
            }

            wp_send_json_success(array(
                'views' => BC_Helpers::formatarVisualizacoes($views)
            ));
        }

        wp_send_json_error('Visualização já registrada');
    }

    public function toggleFavorito() {
        check_ajax_referer('bc_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('Usuário não logado');
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

        if (!$post_id) {
            wp_send_json_error('ID do artigo não fornecido');
        }

        $resultado = BC_Helpers::getInstance()->toggleFavorito($post_id);

        wp_send_json_success(array(
            'status' => $resultado,
            'mensagem' => $resultado === 'adicionado' ? 
                __('Artigo adicionado aos favoritos', 'base-conhecimento') : 
                __('Artigo removido dos favoritos', 'base-conhecimento')
        ));
    }

    public function carregarPasta() {
        check_ajax_referer('bc_nonce', 'nonce');

        $term_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        if (!$term_id) {
            wp_send_json_error('ID da pasta não fornecido');
        }

        $pasta = get_term($term_id, 'bc_pasta');
        
        if (!$pasta || is_wp_error($pasta)) {
            wp_send_json_error('Pasta não encontrada');
        }

        // Obtém o conteúdo da pasta
        ob_start();
        include BC_PLUGIN_PATH . 'public/components/categoria.php';
        $html = ob_get_clean();

        wp_send_json_success(array(
            'html' => $html,
            'url'  => get_term_link($pasta)
        ));
    }
}

// Inicializa a classe
BC_Ajax::getInstance();
