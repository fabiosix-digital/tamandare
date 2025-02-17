<?php
if (!defined('ABSPATH')) exit;
?>

<div class="wrap bc-dashboard-wrap">
    <h1 class="wp-heading-inline">
        <?php _e('Dashboard - Base de Conhecimento', 'base-conhecimento'); ?>
        <a href="<?php echo admin_url('post-new.php?post_type=bc_documento'); ?>" class="page-title-action">
            <?php _e('Adicionar Novo Documento', 'base-conhecimento'); ?>
        </a>
    </h1>

    <!-- Filtros -->
    <div class="bc-dashboard-filtros">
        <select id="filtro-periodo" class="bc-filtro-select">
            <option value="7dias"><?php _e('Últimos 7 dias', 'base-conhecimento'); ?></option>
            <option value="30dias" selected><?php _e('Últimos 30 dias', 'base-conhecimento'); ?></option>
            <option value="90dias"><?php _e('Últimos 90 dias', 'base-conhecimento'); ?></option>
            <option value="12meses"><?php _e('Últimos 12 meses', 'base-conhecimento'); ?></option>
        </select>

        <select id="filtro-categoria" class="bc-filtro-select">
            <option value=""><?php _e('Todas as pastas', 'base-conhecimento'); ?></option>
            <?php
            $pastas = get_terms(array(
                'taxonomy' => 'bc_pasta',
                'hide_empty' => false
            ));
            foreach ($pastas as $pasta) {
                printf(
                    '<option value="%s">%s</option>',
                    esc_attr($pasta->term_id),
                    esc_html($pasta->name)
                );
            }
            ?>
        </select>
    </div>

    <!-- Cards de Métricas -->
    <div class="bc-metricas-container">
        <!-- Total de Documentos -->
        <div class="bc-metrica-card">
            <div class="bc-metrica-header">
                <div class="bc-metrica-icone artigos">
                    <span class="dashicons dashicons-media-document"></span>
                </div>
                <div class="bc-metrica-info">
                    <h3 class="bc-metrica-titulo"><?php _e('Total de Documentos', 'base-conhecimento'); ?></h3>
                    <div class="bc-metrica-valor" data-metrica="total_documentos">0</div>
                </div>
            </div>
        </div>

        <!-- Total de Visualizações -->
        <div class="bc-metrica-card">
            <div class="bc-metrica-header">
                <div class="bc-metrica-icone visualizacoes">
                    <span class="dashicons dashicons-visibility"></span>
                </div>
                <div class="bc-metrica-info">
                    <h3 class="bc-metrica-titulo"><?php _e('Visualizações', 'base-conhecimento'); ?></h3>
                    <div class="bc-metrica-valor" data-metrica="total_visualizacoes">0</div>
                </div>
            </div>
        </div>

        <!-- Total de Curtidas -->
        <div class="bc-metrica-card">
            <div class="bc-metrica-header">
                <div class="bc-metrica-icone curtidas">
                    <span class="dashicons dashicons-thumbs-up"></span>
                </div>
                <div class="bc-metrica-info">
                    <h3 class="bc-metrica-titulo"><?php _e('Curtidas', 'base-conhecimento'); ?></h3>
                    <div class="bc-metrica-valor" data-metrica="total_curtidas">0</div>
                </div>
            </div>
        </div>

        <!-- Total de Não Curtidas -->
        <div class="bc-metrica-card">
            <div class="bc-metrica-header">
                <div class="bc-metrica-icone nao-curtidas">
                    <span class="dashicons dashicons-thumbs-down"></span>
                </div>
                <div class="bc-metrica-info">
                    <h3 class="bc-metrica-titulo"><?php _e('Não Curtidas', 'base-conhecimento'); ?></h3>
                    <div class="bc-metrica-valor" data-metrica="total_nao_curtidas">0</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="bc-grafico-container">
        <!-- Gráfico de Desempenho -->
        <div class="bc-grafico-principal">
            <div class="bc-grafico-header">
                <h2 class="bc-grafico-titulo"><?php _e('Desempenho dos Artigos', 'base-conhecimento'); ?></h2>
            </div>
            <div class="bc-grafico-canvas">
                <canvas id="graficoDesempenho"></canvas>
            </div>
        </div>

        <!-- Artigos Mais Visualizados -->
        <div class="bc-artigos-populares">
            <h2 class="bc-artigos-titulo"><?php _e('Artigos Mais Visualizados', 'base-conhecimento'); ?></h2>
            <div id="artigosPopulares">
                <?php
                $artigos_populares = BC_Estatisticas::getInstance()->obterArtigosPopulares();
                if (!empty($artigos_populares)):
                    foreach ($artigos_populares as $artigo):
                ?>
                    <div class="bc-artigo-item">
                        <a href="<?php echo get_edit_post_link($artigo->ID); ?>" class="bc-artigo-link">
                            <?php echo get_the_title($artigo->ID); ?>
                        </a>
                        <div class="bc-artigo-meta">
                            <span class="bc-artigo-views">
                                <span class="dashicons dashicons-visibility"></span>
                                <?php echo BC_Helpers::formatarVisualizacoes($artigo->total_visualizacoes); ?>
                            </span>
                            <span class="bc-artigo-tempo">
                                <?php echo BC_Helpers::formatarTempoLeitura($artigo->tempo_total_leitura); ?>
                            </span>
                        </div>
                        <div class="bc-artigo-barra">
                            <div class="bc-barra-progresso" style="width: <?php echo $artigo->porcentagem; ?>%"></div>
                        </div>
                    </div>
                <?php
                    endforeach;
                else:
                ?>
                    <p class="bc-sem-dados"><?php _e('Nenhum dado disponível ainda.', 'base-conhecimento'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Últimas Atividades -->
    <div class="bc-atividades">
        <h2 class="bc-atividades-titulo"><?php _e('Últimas Atividades', 'base-conhecimento'); ?></h2>
        <div id="ultimasAtividades">
            <?php
            $atividades = BC_Estatisticas::getInstance()->obterUltimasAtividades();
            if (!empty($atividades)):
                foreach ($atividades as $atividade):
            ?>
                <div class="bc-atividade-item">
                    <div class="bc-atividade-icone">
                        <span class="dashicons <?php echo esc_attr($atividade['icone']); ?>"></span>
                    </div>
                    <div class="bc-atividade-info">
                        <div class="bc-atividade-texto">
                            <?php echo $atividade['descricao']; ?>
                        </div>
                        <div class="bc-atividade-tempo">
                            <?php echo BC_Helpers::formatarData($atividade['data']); ?>
                        </div>
                    </div>
                </div>
            <?php
                endforeach;
            else:
            ?>
                <p class="bc-sem-dados"><?php _e('Nenhuma atividade registrada ainda.', 'base-conhecimento'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>