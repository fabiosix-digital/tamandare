<?php
if (!defined('ABSPATH')) exit;

$pasta_atual = isset($_GET['bc_pasta']) ? get_term_by('slug', $_GET['bc_pasta'], 'bc_pasta') : null;
?>

<div class="wrap bc-documentos-wrap">
    <h1 class="wp-heading-inline">
        <?php
        if ($pasta_atual) {
            printf(__('Documentos em: %s', 'base-conhecimento'), $pasta_atual->name);
        } else {
            _e('Todos os Documentos', 'base-conhecimento');
        }
        ?>
        <a href="<?php echo admin_url('post-new.php?post_type=bc_documento'); ?>" class="page-title-action">
            <?php _e('Adicionar Novo', 'base-conhecimento'); ?>
        </a>
    </h1>

    <!-- Filtros e Ações -->
    <div class="bc-documentos-acoes">
        <div class="bc-filtros">
            <!-- Filtro por Pasta -->
            <select name="bc_pasta" class="bc-filtro-select">
                <option value=""><?php _e('Todas as Pastas', 'base-conhecimento'); ?></option>
                <?php
                $pastas = get_terms(array(
                    'taxonomy'   => 'bc_pasta',
                    'hide_empty' => false
                ));

                foreach ($pastas as $pasta) {
                    printf(
                        '<option value="%s" %s>%s</option>',
                        esc_attr($pasta->slug),
                        selected($pasta_atual && $pasta->term_id === $pasta_atual->term_id, true, false),
                        esc_html($pasta->name)
                    );
                }
                ?>
            </select>

            <!-- Filtro por Status -->
            <select name="post_status" class="bc-filtro-select">
                <option value=""><?php _e('Todos os Status', 'base-conhecimento'); ?></option>
                <option value="publish"><?php _e('Publicado', 'base-conhecimento'); ?></option>
                <option value="draft"><?php _e('Rascunho', 'base-conhecimento'); ?></option>
                <option value="pending"><?php _e('Pendente', 'base-conhecimento'); ?></option>
            </select>

            <!-- Ordenação -->
            <select name="orderby" class="bc-filtro-select">
                <option value="date"><?php _e('Data', 'base-conhecimento'); ?></option>
                <option value="title"><?php _e('Título', 'base-conhecimento'); ?></option>
                <option value="menu_order"><?php _e('Ordem Personalizada', 'base-conhecimento'); ?></option>
                <option value="views"><?php _e('Visualizações', 'base-conhecimento'); ?></option>
            </select>

            <button type="button" class="button bc-aplicar-filtros">
                <?php _e('Aplicar', 'base-conhecimento'); ?>
            </button>
        </div>

        <div class="bc-acoes-em-massa">
            <select name="acao" class="bc-acao-select">
                <option value=""><?php _e('Ações em Massa', 'base-conhecimento'); ?></option>
                <option value="publicar"><?php _e('Publicar', 'base-conhecimento'); ?></option>
                <option value="rascunho"><?php _e('Mover para Rascunho', 'base-conhecimento'); ?></option>
                <option value="excluir"><?php _e('Excluir', 'base-conhecimento'); ?></option>
                <option value="mover"><?php _e('Mover para Pasta', 'base-conhecimento'); ?></option>
            </select>
            <button type="button" class="button bc-aplicar-acao">
                <?php _e('Aplicar', 'base-conhecimento'); ?>
            </button>
        </div>
    </div>

    <!-- Lista de Documentos -->
    <div class="bc-documentos-lista" data-ordem="<?php echo $pasta_atual ? 'pasta' : 'geral'; ?>">
        <?php
        $args = array(
            'post_type'      => 'bc_documento',
            'posts_per_page' => -1,
            'post_status'    => array('publish', 'draft', 'pending'),
            'orderby'        => 'menu_order',
            'order'          => 'ASC'
        );

        if ($pasta_atual) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'bc_pasta',
                    'field'    => 'term_id',
                    'terms'    => $pasta_atual->term_id
                )
            );
        }

        $documentos = get_posts($args);

        if (!empty($documentos)):
            foreach ($documentos as $documento):
                $stats  = BC_Estatisticas::getInstance()->obterEstatisticasDocumento($documento->ID);
                $pastas = wp_get_post_terms($documento->ID, 'bc_pasta');
                ?>
                <div class="bc-documento-item" data-id="<?php echo $documento->ID; ?>">
                    <!-- Alça para Drag and Drop -->
                    <div class="bc-drag-handle">
                        <span class="dashicons dashicons-menu"></span>
                    </div>

                    <!-- Checkbox para Seleção -->
                    <div class="bc-documento-check">
                        <input type="checkbox" name="documentos[]" value="<?php echo $documento->ID; ?>">
                    </div>

                    <!-- Informações do Documento -->
                    <div class="bc-documento-info">
                        <h3 class="bc-documento-titulo">
                            <a href="<?php echo get_edit_post_link($documento->ID); ?>">
                                <?php echo get_the_title($documento->ID); ?>
                            </a>
                            <?php if ($documento->post_status !== 'publish'): ?>
                                <span class="bc-status"><?php echo get_post_status_object($documento->post_status)->label; ?></span>
                            <?php endif; ?>
                        </h3>

                        <div class="bc-documento-meta">
                            <?php if (!empty($pastas)): ?>
                                <span class="bc-documento-pasta">
                                    <span class="dashicons dashicons-category"></span>
                                    <?php
                                    $links_pastas = array();
                                    foreach ($pastas as $pasta) {
                                        $links_pastas[] = sprintf(
                                            '<a href="%s">%s</a>',
                                            esc_url(admin_url('edit.php?post_type=bc_documento&bc_pasta=' . $pasta->slug)),
                                            esc_html($pasta->name)
                                        );
                                    }
                                    echo implode(', ', $links_pastas);
                                    ?>
                                </span>
                            <?php endif; ?>

                            <span class="bc-documento-data">
                                <span class="dashicons dashicons-calendar-alt"></span>
                                <?php echo get_the_date('', $documento->ID); ?>
                            </span>

                            <?php if ($stats): ?>
                                <span class="bc-documento-views">
                                    <span class="dashicons dashicons-visibility"></span>
                                    <?php echo BC_Helpers::formatarVisualizacoes($stats->total_visualizacoes); ?>
                                </span>

                                <span class="bc-documento-feedback">
                                    <span class="dashicons dashicons-thumbs-up"></span>
                                    <?php echo $stats->total_curtidas; ?>
                                    <span class="dashicons dashicons-thumbs-down"></span>
                                    <?php echo $stats->total_nao_curtidas; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Ações Rápidas -->
                    <div class="bc-documento-acoes">
                        <a href="<?php echo get_edit_post_link($documento->ID); ?>" class="button">
                            <span class="dashicons dashicons-edit"></span>
                            <?php _e('Editar', 'base-conhecimento'); ?>
                        </a>
                        <a href="<?php echo get_permalink($documento->ID); ?>" class="button" target="_blank">
                            <span class="dashicons dashicons-visibility"></span>
                            <?php _e('Visualizar', 'base-conhecimento'); ?>
                        </a>
                        <button type="button" class="button bc-excluir-documento" data-id="<?php echo $documento->ID; ?>">
                            <span class="dashicons dashicons-trash"></span>
                            <?php _e('Excluir', 'base-conhecimento'); ?>
                        </button>
                    </div>
                </div>
            <?php
            endforeach;
        else:
            ?>
            <div class="bc-sem-documentos">
                <p><?php _e('Nenhum documento encontrado.', 'base-conhecimento'); ?></p>
                <a href="<?php echo admin_url('post-new.php?post_type=bc_documento'); ?>" class="button button-primary">
                    <?php _e('Criar Primeiro Documento', 'base-conhecimento'); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
