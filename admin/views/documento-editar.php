<?php
if (!defined('ABSPATH')) exit;

$post_id = get_the_ID();
$pasta_atual = wp_get_post_terms($post_id, 'bc_pasta', array('fields' => 'ids'));
$stats = BC_Estatisticas::getInstance()->obterEstatisticasDocumento($post_id);
?>

<div class="bc-editor-wrap">
    <input type="hidden" id="post_ID" value="<?php echo $post_id; ?>">
    
    <!-- Header do Editor -->
    <div class="bc-editor-header">
        <div class="bc-editor-titulo">
            <input type="text" 
                   id="bc-titulo" 
                   name="post_title" 
                   value="<?php echo esc_attr(get_the_title()); ?>" 
                   placeholder="<?php esc_attr_e('Digite o título do documento...', 'base-conhecimento'); ?>">
        </div>

        <div class="bc-editor-acoes">
            <!-- Preview -->
            <button type="button" class="bc-btn bc-btn-preview">
                <span class="dashicons dashicons-visibility"></span>
                <?php _e('Preview', 'base-conhecimento'); ?>
            </button>

            <!-- Salvar Rascunho -->
            <button type="button" class="bc-btn bc-btn-rascunho">
                <span class="dashicons dashicons-save"></span>
                <?php _e('Salvar Rascunho', 'base-conhecimento'); ?>
            </button>

            <!-- Publicar -->
            <button type="button" class="bc-btn bc-btn-primary bc-btn-publicar">
                <span class="dashicons dashicons-paper-plane"></span>
                <?php _e('Publicar', 'base-conhecimento'); ?>
            </button>
        </div>
    </div>

    <!-- Container Principal -->
    <div class="bc-editor-container">
        <!-- Editor Principal -->
        <div class="bc-editor-principal">
            <!-- Barra de Ferramentas -->
            <div class="bc-editor-toolbar">
                <div class="bc-toolbar-grupo">
                    <button type="button" data-comando="undo" title="<?php esc_attr_e('Desfazer', 'base-conhecimento'); ?>">
                        <span class="dashicons dashicons-undo"></span>
                    </button>
                    <button type="button" data-comando="redo" title="<?php esc_attr_e('Refazer', 'base-conhecimento'); ?>">
                        <span class="dashicons dashicons-redo"></span>
                    </button>
                </div>

                <div class="bc-toolbar-grupo">
                    <button type="button" data-comando="bold" title="<?php esc_attr_e('Negrito', 'base-conhecimento'); ?>">
                        <span class="dashicons dashicons-editor-bold"></span>
                    </button>
                    <button type="button" data-comando="italic" title="<?php esc_attr_e('Itálico', 'base-conhecimento'); ?>">
                        <span class="dashicons dashicons-editor-italic"></span>
                    </button>
                    <button type="button" data-comando="underline" title="<?php esc_attr_e('Sublinhado', 'base-conhecimento'); ?>">
                        <span class="dashicons dashicons-editor-underline"></span>
                    </button>
                </div>

                <div class="bc-toolbar-grupo">
                    <button type="button" data-comando="h2" title="<?php esc_attr_e('Título 2', 'base-conhecimento'); ?>">H2</button>
                    <button type="button" data-comando="h3" title="<?php esc_attr_e('Título 3', 'base-conhecimento'); ?>">H3</button>
                    <button type="button" data-comando="h4" title="<?php esc_attr_e('Título 4', 'base-conhecimento'); ?>">H4</button>
                </div>

                <div class="bc-toolbar-grupo">
                    <button type="button" data-comando="link" title="<?php esc_attr_e('Link', 'base-conhecimento'); ?>">
                        <span class="dashicons dashicons-admin-links"></span>
                    </button>
                    <button type="button" data-comando="imagem" title="<?php esc_attr_e('Imagem', 'base-conhecimento'); ?>">
                        <span class="dashicons dashicons-format-image"></span>
                    </button>
                    <button type="button" data-comando="video" title="<?php esc_attr_e('Vídeo', 'base-conhecimento'); ?>">
                        <span class="dashicons dashicons-format-video"></span>
                    </button>
                </div>

                <div class="bc-toolbar-grupo">
                    <button type="button" data-comando="lista" title="<?php esc_attr_e('Lista', 'base-conhecimento'); ?>">
                        <span class="dashicons dashicons-editor-ul"></span>
                    </button>
                    <button type="button" data-comando="lista-numerada" title="<?php esc_attr_e('Lista Numerada', 'base-conhecimento'); ?>">
                        <span class="dashicons dashicons-editor-ol"></span>
                    </button>
                    <button type="button" data-comando="citacao" title="<?php esc_attr_e('Citação', 'base-conhecimento'); ?>">
                        <span class="dashicons dashicons-editor-quote"></span>
                    </button>
                </div>

                <div class="bc-toolbar-grupo">
                    <button type="button" data-comando="codigo" title="<?php esc_attr_e('Código', 'base-conhecimento'); ?>">
                        <span class="dashicons dashicons-editor-code"></span>
                    </button>
                    <button type="button" data-comando="tabela" title="<?php esc_attr_e('Tabela', 'base-conhecimento'); ?>">
                        <span class="dashicons dashicons-editor-table"></span>
                    </button>
                </div>
            </div>

            <!-- Área de Edição -->
            <div class="bc-editor-conteudo">
                <?php 
                $editor_settings = array(
                    'textarea_name' => 'post_content',
                    'editor_height' => 500,
                    'media_buttons' => false,
                    'tinymce' => array(
                        'toolbar1' => 'formatselect,bold,italic,underline,bullist,numlist,link,unlink,undo,redo',
                        'toolbar2' => '',
                    ),
                    'quicktags' => false
                );
                wp_editor(get_post_field('post_content', $post_id), 'bc_editor', $editor_settings);
                ?>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="bc-editor-sidebar">
            <!-- Pasta -->
            <div class="bc-editor-box">
                <h3 class="bc-box-titulo"><?php _e('Pasta', 'base-conhecimento'); ?></h3>
                <select name="bc_pasta" class="bc-select-pasta">
                    <option value=""><?php _e('Selecione uma pasta', 'base-conhecimento'); ?></option>
                    <?php
                    $pastas = get_terms(array(
                        'taxonomy' => 'bc_pasta',
                        'hide_empty' => false
                    ));

                    foreach ($pastas as $pasta) {
                        printf(
                            '<option value="%s" %s>%s</option>',
                            esc_attr($pasta->term_id),
                            selected(in_array($pasta->term_id, $pasta_atual), true, false),
                            esc_html($pasta->name)
                        );
                    }
                    ?>
                </select>
            </div>

            <!-- Configurações -->
            <div class="bc-editor-box">
                <h3 class="bc-box-titulo"><?php _e('Configurações', 'base-conhecimento'); ?></h3>
                
                <div class="bc-campo-grupo">
                    <label class="bc-checkbox">
                        <input type="checkbox" 
                               name="bc_destaque" 
                               value="1" 
                               <?php checked(get_post_meta($post_id, '_bc_destaque', true), '1'); ?>>
                        <?php _e('Destacar na página inicial', 'base-conhecimento'); ?>
                    </label>
                </div>

                <div class="bc-campo-grupo">
                    <label class="bc-checkbox">
                        <input type="checkbox" 
                               name="bc_sidebar" 
                               value="1" 
                               <?php checked(get_post_meta($post_id, '_bc_sidebar', true), '1'); ?>>
                        <?php _e('Mostrar índice lateral', 'base-conhecimento'); ?>
                    </label>
                </div>

                <div class="bc-campo-grupo">
                    <label class="bc-label"><?php _e('Ordem', 'base-conhecimento'); ?></label>
                    <input type="number" 
                           name="menu_order" 
                           value="<?php echo get_post_field('menu_order', $post_id); ?>" 
                           class="small-text">
                </div>

                <div class="bc-campo-grupo">
                    <label class="bc-label"><?php _e('Ícone', 'base-conhecimento'); ?></label>
                    <input type="text" 
                           name="bc_icone" 
                           value="<?php echo esc_attr(get_post_meta($post_id, '_bc_icone', true)); ?>" 
                           class="regular-text">
                    <button type="button" class="button bc-selecionar-icone">
                        <?php _e('Escolher Ícone', 'base-conhecimento'); ?>
                    </button>
                </div>
            </div>

            <!-- Estatísticas -->
            <?php if ($stats): ?>
            <div class="bc-editor-box">
                <h3 class="bc-box-titulo"><?php _e('Estatísticas', 'base-conhecimento'); ?></h3>
                
                <div class="bc-stats-grid">
                    <div class="bc-stat-item">
                        <span class="bc-stat-label"><?php _e('Visualizações', 'base-conhecimento'); ?></span>
                        <span class="bc-stat-valor"><?php echo BC_Helpers::formatarVisualizacoes($stats->total_visualizacoes); ?></span>
                    </div>
                    <div class="bc-stat-item">
                        <span class="bc-stat-label"><?php _e('Curtidas', 'base-conhecimento'); ?></span>
                        <span class="bc-stat-valor"><?php echo $stats->total_curtidas; ?></span>
                    </div>
                    <div class="bc-stat-item">
                        <span class="bc-stat-label"><?php _e('Não Curtidas', 'base-conhecimento'); ?></span>
                        <span class="bc-stat-valor"><?php echo $stats->total_nao_curtidas; ?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>