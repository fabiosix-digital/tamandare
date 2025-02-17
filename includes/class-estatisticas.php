<?php
if (!defined('ABSPATH')) exit;

class BC_Estatisticas {
    private static $instance = null;
    
    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_ajax_bc_registrar_visualizacao', array($this, 'registrarVisualizacao'));
        add_action('wp_ajax_nopriv_bc_registrar_visualizacao', array($this, 'registrarVisualizacao'));
        add_action('wp_ajax_bc_registrar_feedback', array($this, 'registrarFeedback'));
        add_action('wp_ajax_nopriv_bc_registrar_feedback', array($this, 'registrarFeedback'));
        add_action('wp_ajax_bc_obter_estatisticas', array($this, 'obterEstatisticas'));
        add_action('wp_ajax_bc_obter_grafico_visualizacoes', array($this, 'obterGraficoVisualizacoes'));
    }

    public function registrarVisualizacao() {
        if (!isset($_POST['documento_id']) || !wp_verify_nonce($_POST['nonce'], 'bc_nonce')) {
            wp_send_json_error();
        }

        $documento_id = intval($_POST['documento_id']);
        
        global $wpdb;
        $tabela = $wpdb->prefix . 'bc_estatisticas';
        
        $registro = $wpdb->get_row($wpdb->prepare(
            "SELECT id, visualizacoes FROM {$tabela} 
            WHERE documento_id = %d 
            AND DATE(data_criacao) = CURDATE()",
            $documento_id
        ));
        
        if ($registro) {
            $wpdb->update(
                $tabela,
                array('visualizacoes' => $registro->visualizacoes + 1),
                array('id' => $registro->id),
                array('%d'),
                array('%d')
            );
        } else {
            $wpdb->insert(
                $tabela,
                array(
                    'documento_id' => $documento_id,
                    'visualizacoes' => 1,
                    'data_criacao' => current_time('mysql')
                ),
                array('%d', '%d', '%s')
            );
        }
        
        wp_send_json_success();
    }

    public function registrarFeedback() {
        if (!isset($_POST['documento_id']) || !isset($_POST['tipo']) || 
            !wp_verify_nonce($_POST['nonce'], 'bc_nonce')) {
            wp_send_json_error();
        }

        $documento_id = intval($_POST['documento_id']);
        $tipo = sanitize_text_field($_POST['tipo']);
        
        if (!in_array($tipo, array('curtida', 'nao_curtida'))) {
            wp_send_json_error();
        }
        
        global $wpdb;
        $tabela = $wpdb->prefix . 'bc_estatisticas';
        
        $registro = $wpdb->get_row($wpdb->prepare(
            "SELECT id, curtidas, nao_curtidas FROM {$tabela} 
            WHERE documento_id = %d 
            AND DATE(data_criacao) = CURDATE()",
            $documento_id
        ));
        
        if ($registro) {
            $dados = array(
                $tipo === 'curtida' ? 'curtidas' : 'nao_curtidas' => 
                $tipo === 'curtida' ? $registro->curtidas + 1 : $registro->nao_curtidas + 1
            );
            
            $wpdb->update(
                $tabela,
                $dados,
                array('id' => $registro->id),
                array('%d'),
                array('%d')
            );
        } else {
            $dados = array(
                'documento_id' => $documento_id,
                'curtidas' => $tipo === 'curtida' ? 1 : 0,
                'nao_curtidas' => $tipo === 'nao_curtida' ? 1 : 0,
                'data_criacao' => current_time('mysql')
            );
            
            $wpdb->insert(
                $tabela,
                $dados,
                array('%d', '%d', '%d', '%s')
            );
        }
        
        wp_send_json_success();
    }

    public function obterArtigosPopulares($limite = 5) {
        global $wpdb;
        $tabela = $wpdb->prefix . 'bc_estatisticas';
        
        $resultados = $wpdb->get_results($wpdb->prepare("
            SELECT 
                p.ID,
                p.post_title,
                SUM(e.visualizacoes) as total_visualizacoes,
                SUM(e.tempo_leitura) as tempo_total_leitura
            FROM {$wpdb->posts} p
            LEFT JOIN {$tabela} e ON p.ID = e.documento_id
            WHERE p.post_type = 'bc_documento'
            AND p.post_status = 'publish'
            GROUP BY p.ID
            ORDER BY total_visualizacoes DESC
            LIMIT %d
        ", $limite));

        $artigos = array();
        $max_visualizacoes = 0;

        foreach ($resultados as $resultado) {
            if ($resultado->total_visualizacoes > $max_visualizacoes) {
                $max_visualizacoes = $resultado->total_visualizacoes;
            }
        }

        foreach ($resultados as $resultado) {
            $artigos[] = array(
                'ID' => $resultado->ID,
                'titulo' => $resultado->post_title,
                'total_visualizacoes' => $resultado->total_visualizacoes,
                'tempo_total_leitura' => $resultado->tempo_total_leitura,
                'porcentagem' => $max_visualizacoes > 0 ? ($resultado->total_visualizacoes / $max_visualizacoes) * 100 : 0
            );
        }

        return $artigos;
    }

    public function obterEstatisticas() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_GET['nonce'], 'bc_nonce')) {
            wp_send_json_error();
        }
        
        global $wpdb;
        $tabela = $wpdb->prefix . 'bc_estatisticas';
        
        $total_documentos = wp_count_posts('bc_documento')->publish;
        
        $stats = $wpdb->get_row("
            SELECT 
                SUM(visualizacoes) as total_visualizacoes,
                SUM(curtidas) as total_curtidas,
                SUM(nao_curtidas) as total_nao_curtidas
            FROM {$tabela}
        ");
        
        $mais_visualizados = $wpdb->get_results("
            SELECT 
                p.ID,
                p.post_title,
                SUM(e.visualizacoes) as total_visualizacoes,
                SUM(e.tempo_leitura) as tempo_total_leitura
            FROM {$wpdb->posts} p
            LEFT JOIN {$tabela} e ON p.ID = e.documento_id
            WHERE p.post_type = 'bc_documento'
            AND p.post_status = 'publish'
            GROUP BY p.ID
            ORDER BY total_visualizacoes DESC
            LIMIT 5
        ");
        
        $dados = array(
            'total_documentos' => $total_documentos,
            'total_visualizacoes' => $stats->total_visualizacoes,
            'total_curtidas' => $stats->total_curtidas,
            'total_nao_curtidas' => $stats->total_nao_curtidas,
            'mais_visualizados' => array_map(function($doc) {
                return array(
                    'id' => $doc->ID,
                    'titulo' => $doc->post_title,
                    'visualizacoes' => $doc->total_visualizacoes,
                    'tempo_leitura' => self::formatarTempoLeitura($doc->tempo_total_leitura)
                );
            }, $mais_visualizados)
        );
        
        wp_send_json_success($dados);
    }

    public function obterGraficoVisualizacoes() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_GET['nonce'], 'bc_nonce')) {
            wp_send_json_error();
        }
        
        global $wpdb;
        $tabela = $wpdb->prefix . 'bc_estatisticas';
        
        $resultados = $wpdb->get_results("
            SELECT 
                DATE(data_criacao) as data,
                SUM(visualizacoes) as visualizacoes,
                SUM(curtidas) as curtidas,
                SUM(nao_curtidas) as nao_curtidas
            FROM {$tabela}
            WHERE data_criacao >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY DATE(data_criacao)
            ORDER BY data_criacao ASC
        ");
        
        $dados = array_map(function($resultado) {
            return array(
                'data' => $resultado->data,
                'visualizacoes' => intval($resultado->visualizacoes),
                'curtidas' => intval($resultado->curtidas),
                'nao_curtidas' => intval($resultado->nao_curtidas)
            );
        }, $resultados);
        
        wp_send_json_success($dados);
    }

    /**
     * Formata o tempo de leitura (em segundos) para exibição
     */
    private static function formatarTempoLeitura($segundos) {
        if ($segundos < 3600) {
            return floor($segundos / 60) . ' min';
        }
        return floor($segundos / 3600) . ' hrs';
    }

    public function obterEstatisticasDocumento($documento_id) {
        global $wpdb;
        $tabela = $wpdb->prefix . 'bc_estatisticas';
        
        return $wpdb->get_row($wpdb->prepare("
            SELECT 
                SUM(visualizacoes) as total_visualizacoes,
                SUM(curtidas) as total_curtidas,
                SUM(nao_curtidas) as total_nao_curtidas,
                SUM(tempo_leitura) as tempo_total_leitura
            FROM {$tabela}
            WHERE documento_id = %d
        ", $documento_id));
    }
}

BC_Estatisticas::getInstance();
