<?php
if (!defined('ABSPATH')) exit;

class BC_Dashboard {
    private static $instance = null;
    
    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_dashboard_setup', array($this, 'adicionarWidgetsDashboard'));
        add_action('admin_enqueue_scripts', array($this, 'carregarAssets'));
    }
    
    /**
     * Adiciona o widget do dashboard na área de Dashboard do WordPress.
     */
    public function adicionarWidgetsDashboard() {
        wp_add_dashboard_widget(
            'bc_dashboard_widget',
            __('Base de Conhecimento Dashboard', 'base-conhecimento'),
            array($this, 'renderizarDashboard')
        );
    }

    public function carregarAssets($hook) {
        if ($hook === 'base-conhecimento_page_base-conhecimento' || 
            $hook === 'toplevel_page_base-conhecimento') {
            
            // CSS do Dashboard
            wp_enqueue_style(
                'base-conhecimento-dashboard',
                BC_PLUGIN_URL . 'admin/assets/css/dashboard.css',
                array(),
                BC_VERSION
            );

            // JavaScript e bibliotecas necessárias
            wp_enqueue_script(
                'chart-js',
                'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js',
                array(),
                '3.7.0',
                true
            );

            wp_enqueue_script(
                'base-conhecimento-dashboard',
                BC_PLUGIN_URL . 'admin/assets/js/dashboard.js',
                array('jquery', 'chart-js'),
                BC_VERSION,
                true
            );

            // Localize Script
            wp_localize_script('base-conhecimento-dashboard', 'bcDashboard', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bc_dashboard_nonce'),
                'strings' => array(
                    'visualizacoes' => __('Visualizações', 'base-conhecimento'),
                    'curtidas' => __('Curtidas', 'base-conhecimento'),
                    'naoCurtidas' => __('Não Curtidas', 'base-conhecimento'),
                    'carregando' => __('Carregando...', 'base-conhecimento')
                )
            ));
        }
    }

    public function renderizarDashboard() {
        $estatisticas = $this->obterEstatisticas();
        ?>
        <div class="wrap bc-dashboard">
            <h1><?php _e('Dashboard - Base de Conhecimento', 'base-conhecimento'); ?></h1>
            
            <!-- Cards de Métricas -->
            <div class="bc-metricas-grid">
                <div class="bc-metrica-card">
                    <div class="bc-metrica-icone">
                        <span class="dashicons dashicons-media-document"></span>
                    </div>
                    <div class="bc-metrica-info">
                        <h3><?php _e('Total de Artigos', 'base-conhecimento'); ?></h3>
                        <div class="bc-metrica-valor"><?php echo $estatisticas['total_artigos']; ?></div>
                    </div>
                </div>

                <div class="bc-metrica-card">
                    <div class="bc-metrica-icone">
                        <span class="dashicons dashicons-visibility"></span>
                    </div>
                    <div class="bc-metrica-info">
                        <h3><?php _e('Visualizações', 'base-conhecimento'); ?></h3>
                        <div class="bc-metrica-valor"><?php echo BC_Helpers::formatarVisualizacoes($estatisticas['total_visualizacoes']); ?></div>
                    </div>
                </div>

                <div class="bc-metrica-card">
                    <div class="bc-metrica-icone">
                        <span class="dashicons dashicons-thumbs-up"></span>
                    </div>
                    <div class="bc-metrica-info">
                        <h3><?php _e('Curtidas', 'base-conhecimento'); ?></h3>
                        <div class="bc-metrica-valor"><?php echo $estatisticas['total_curtidas']; ?></div>
                    </div>
                </div>

                <div class="bc-metrica-card">
                    <div class="bc-metrica-icone">
                        <span class="dashicons dashicons-thumbs-down"></span>
                    </div>
                    <div class="bc-metrica-info">
                        <h3><?php _e('Não Curtidas', 'base-conhecimento'); ?></h3>
                        <div class="bc-metrica-valor"><?php echo $estatisticas['total_nao_curtidas']; ?></div>
                    </div>
                </div>
            </div>

            <!-- Gráficos -->
            <div class="bc-graficos-grid">
                <div class="bc-grafico-card">
                    <h3><?php _e('Desempenho dos Artigos', 'base-conhecimento'); ?></h3>
                    <div class="bc-grafico-container">
                        <canvas id="graficoDesempenho"></canvas>
                    </div>
                </div>

                <div class="bc-grafico-card">
                    <h3><?php _e('Artigos Mais Visualizados', 'base-conhecimento'); ?></h3>
                    <div class="bc-lista-artigos">
                        <?php foreach ($estatisticas['mais_visualizados'] as $artigo): ?>
                            <div class="bc-artigo-item">
                                <a href="<?php echo get_edit_post_link($artigo['id']); ?>" class="bc-artigo-titulo">
                                    <?php echo $artigo['titulo']; ?>
                                </a>
                                <div class="bc-artigo-meta">
                                    <span class="bc-artigo-views">
                                        <span class="dashicons dashicons-visibility"></span>
                                        <?php echo BC_Helpers::formatarVisualizacoes($artigo['visualizacoes']); ?>
                                    </span>
                                    <span class="bc-artigo-tempo">
                                        <?php echo $artigo['tempo_leitura']; ?>
                                    </span>
                                </div>
                                <div class="bc-artigo-barra">
                                    <div class="bc-barra-progresso" style="width: <?php echo $artigo['porcentagem']; ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Últimas Atividades -->
            <div class="bc-atividades-card">
                <h3><?php _e('Últimas Atividades', 'base-conhecimento'); ?></h3>
                <div class="bc-lista-atividades">
                    <?php foreach ($estatisticas['ultimas_atividades'] as $atividade): ?>
                        <div class="bc-atividade-item">
                            <div class="bc-atividade-icone">
                                <span class="dashicons <?php echo $atividade['icone']; ?>"></span>
                            </div>
                            <div class="bc-atividade-info">
                                <div class="bc-atividade-descricao">
                                    <?php echo $atividade['descricao']; ?>
                                </div>
                                <div class="bc-atividade-meta">
                                    <?php echo BC_Helpers::formatarData($atividade['data']); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
    }

    private function obterEstatisticas() {
        global $wpdb;
        $tabela = $wpdb->prefix . 'bc_estatisticas';

        // Total de artigos
        $total_artigos = wp_count_posts('bc_documento')->publish;

        // Estatísticas gerais
        $stats = $wpdb->get_row("
            SELECT 
                SUM(visualizacoes) as total_visualizacoes,
                SUM(curtidas) as total_curtidas,
                SUM(nao_curtidas) as total_nao_curtidas
            FROM {$tabela}
        ");

        // Artigos mais visualizados
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

        // Últimas atividades
        $atividades = $this->obterUltimasAtividades();

        // Formata os dados dos artigos mais visualizados
        $max_visualizacoes = 0;
        foreach ($mais_visualizados as $artigo) {
            if ($artigo->total_visualizacoes > $max_visualizacoes) {
                $max_visualizacoes = $artigo->total_visualizacoes;
            }
        }

        $artigos_formatados = array();
        foreach ($mais_visualizados as $artigo) {
            $artigos_formatados[] = array(
                'id' => $artigo->ID,
                'titulo' => $artigo->post_title,
                'visualizacoes' => $artigo->total_visualizacoes,
                'tempo_leitura' => BC_Helpers::formatarTempoLeitura($artigo->tempo_total_leitura),
                'porcentagem' => ($artigo->total_visualizacoes / $max_visualizacoes) * 100
            );
        }

        return array(
            'total_artigos' => $total_artigos,
            'total_visualizacoes' => $stats->total_visualizacoes,
            'total_curtidas' => $stats->total_curtidas,
            'total_nao_curtidas' => $stats->total_nao_curtidas,
            'mais_visualizados' => $artigos_formatados,
            'ultimas_atividades' => $atividades
        );
    }

    private function obterUltimasAtividades() {
        $atividades = array();
        
        // Últimos artigos publicados
        $artigos = get_posts(array(
            'post_type' => 'bc_documento',
            'posts_per_page' => 5,
            'orderby' => 'date',
            'order' => 'DESC'
        ));

        foreach ($artigos as $artigo) {
            $atividades[] = array(
                'tipo' => 'novo_artigo',
                'icone' => 'dashicons-media-document',
                'descricao' => sprintf(
                    __('Novo artigo publicado: %s', 'base-conhecimento'),
                    $artigo->post_title
                ),
                'data' => $artigo->post_date
            );
        }

        // Últimas atualizações
        $atualizacoes = get_posts(array(
            'post_type' => 'bc_documento',
            'posts_per_page' => 5,
            'orderby' => 'modified',
            'order' => 'DESC'
        ));

        foreach ($atualizacoes as $atualizacao) {
            if ($atualizacao->post_date !== $atualizacao->post_modified) {
                $atividades[] = array(
                    'tipo' => 'atualizacao',
                    'icone' => 'dashicons-update',
                    'descricao' => sprintf(
                        __('Artigo atualizado: %s', 'base-conhecimento'),
                        $atualizacao->post_title
                    ),
                    'data' => $atualizacao->post_modified
                );
            }
        }

        // Ordena as atividades por data
        usort($atividades, function($a, $b) {
            return strtotime($b['data']) - strtotime($a['data']);
        });

        return array_slice($atividades, 0, 10);
    }
}

// Inicializar a classe
BC_Dashboard::getInstance();
