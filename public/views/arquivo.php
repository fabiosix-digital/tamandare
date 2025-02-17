<?php
if (!defined('ABSPATH')) exit;

get_header('base-conhecimento');

// Verifica se está em uma pasta específica
$pasta_atual = null;
if (is_tax('bc_pasta')) {
    $pasta_atual = get_queried_object();
}
?>

<div class="bc-arquivo">
    <!-- Menu Lateral -->
    <aside class="bc-sidebar">
        <?php echo do_shortcode('[documentacao_menu]'); ?>
    </aside>

    <!-- Conteúdo Principal -->
    <main class="bc-conteudo">
        <!-- Cabeçalho -->
        <header class="bc-arquivo-header">
            <?php BC_Templates::getInstance()->renderizarBreadcrumbs(); ?>

            <?php if ($pasta_atual): ?>
                <?php
                $icone = get_term_meta($pasta_atual->term_id, 'bc_icone', true);
                $cor = get_term_meta($pasta_atual->term_id, 'bc_cor', true);
                ?>
                <div class="bc-pasta-header">
                    <?php if ($icone): ?>
                        <div class="bc-pasta-icone" style="color: <?php echo esc_attr($cor); ?>">
                            <i class="<?php echo esc_attr($icone); ?>"></i>
                        </div>
                    <?php endif; ?>

                    <h1 class="bc-pasta-titulo"><?php echo $pasta_atual->name; ?></h1>
                    
                    <?php if ($pasta_atual->description): ?>
                        <div class="bc-pasta-descricao">
                            <?php echo wpautop($pasta_atual->description); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <h1 class="bc-arquivo-titulo"><?php _e('Todos os Documentos', 'base-conhecimento'); ?></h1>
            <?php endif; ?>

            <!-- Ferramentas -->
            <div class="bc-arquivo-tools">
                <!-- Busca -->
                <div class="bc-arquivo-busca">
                    <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
                        <input type="hidden" name="post_type" value="bc_documento">
                        <?php if ($pasta_atual): ?>
                            <input type="hidden" name="bc_pasta" value="<?php echo $pasta_atual->slug; ?>">
                        <?php endif; ?>
                        <input type="search" 
                               name="s" 
                               placeholder="<?php esc_attr_e('Buscar documentos...', 'base-conhecimento'); ?>"
                               value="<?php echo get_search_query(); ?>"
                               class="bc-busca-input">
                        <button type="submit" class="bc-busca-btn">
                            <span class="dashicons dashicons-search"></span>
                        </button>
                    </form>
                </div>

                <!-- Ordenação -->
                <div class="bc-arquivo-ordem">
                    <select name="orderby" class="bc-ordem-select">
                        <option value="title" <?php selected(get_query_var('orderby'), 'title'); ?>>
                            <?php _e('Título', 'base-conhecimento'); ?>
                        </option>
                        <option value="date" <?php selected(get_query_var('orderby'), 'date'); ?>>
                            <?php _e('Data', 'base-conhecimento'); ?>
                        </option>
                        <option value="menu_order" <?php selected(get_query_var('orderby'), 'menu_order'); ?>>
                            <?php _e('Ordem Personalizada', 'base-conhecimento'); ?>
                        </option>
                    </select>
                </div>

                <!-- Visualização -->
                <div class="bc-arquivo-view">
                    <button class="bc-view-btn bc-view-grid active" data-view="grid">
                        <span class="dashicons dashicons-grid-view"></span>
                    </button>
                    <button class="bc-view-btn bc-view-list" data-view="list">
                        <span class="dashicons dashicons-list-view"></span>
                    </button>
                </div>
            </div>
        </header>

        <!-- Lista de Subpastas -->
        <?php if ($pasta_atual):
            $subpastas = get_terms(array(
                'taxonomy' => 'bc_pasta',
                'parent' => $pasta_atual->term_id,
                'hide_empty' => false
            ));

            if (!empty($subpastas)):
            ?>
                <section class="bc-subpastas">
                    <div class="bc-subpastas-grid">
                        <?php foreach ($subpastas as $subpasta):
                            $icone = get_term_meta($subpasta->term_id, 'bc_icone', true);
                            $cor = get_term_meta($subpasta->term_id, 'bc_cor', true);
                            $total_docs = $subpasta->count;
                        ?>
                            <!-- Subpastas NÃO são clicáveis e possuem indentação (classe "subfolder") -->
                            <div class="bc-subpasta-card subfolder">
                                <?php if ($icone): ?>
                                    <div class="bc-subpasta-icone" style="color: <?php echo esc_attr($cor); ?>">
                                        <i class="<?php echo esc_attr($icone); ?>"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="bc-subpasta-info">
                                    <h2 class="bc-subpasta-titulo"><?php echo $subpasta->name; ?></h2>
                                    <?php if ($total_docs > 0): ?>
                                        <span class="bc-subpasta-count">
                                            <?php printf(
                                                _n('%d documento', '%d documentos', $total_docs, 'base-conhecimento'),
                                                $total_docs
                                            ); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif;
        endif; ?>

        <!-- Lista de Documentos -->
        <?php if (have_posts()): ?>
            <div class="bc-documentos bc-view-grid">
                <?php 
                while (have_posts()): the_post();
                    $pasta = wp_get_post_terms(get_the_ID(), 'bc_pasta');
                    $stats = BC_Estatisticas::getInstance()->obterEstatisticasDocumento(get_the_ID());
                ?>
                    <article class="bc-documento-card">
                        <h2 class="bc-documento-titulo">
                            <a href="<?php the_permalink(); ?>" data-carregar-artigo="<?php the_ID(); ?>"><?php the_title(); ?></a>
                        </h2>

                        <?php if (!empty($pasta) && !$pasta_atual): ?>
                            <div class="bc-documento-pasta">
                                <a href="<?php echo get_term_link($pasta[0]); ?>">
                                    <?php echo $pasta[0]->name; ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <div class="bc-documento-excerpt">
                            <?php the_excerpt(); ?>
                        </div>

                        <div class="bc-documento-meta">
                            <span class="bc-documento-data">
                                <span class="dashicons dashicons-calendar-alt"></span>
                                <?php echo get_the_date(); ?>
                            </span>

                            <?php if ($stats): ?>
                                <span class="bc-documento-views">
                                    <span class="dashicons dashicons-visibility"></span>
                                    <?php echo BC_Helpers::formatarVisualizacoes($stats->total_visualizacoes); ?>
                                </span>

                                <span class="bc-documento-feedback">
                                    <span class="dashicons dashicons-thumbs-up"></span>
                                    <?php echo $stats->total_curtidas; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>

            <?php
            // Paginação
            the_posts_pagination(array(
                'mid_size' => 2,
                'prev_text' => '<span class="dashicons dashicons-arrow-left-alt2"></span>',
                'next_text' => '<span class="dashicons dashicons-arrow-right-alt2"></span>'
            ));
            ?>

        <?php else: ?>
            <div class="bc-sem-resultados">
                <div class="bc-sem-resultados-icone">
                    <span class="dashicons dashicons-media-document"></span>
                </div>
                <h2><?php _e('Nenhum documento encontrado', 'base-conhecimento'); ?></h2>
                <p><?php _e('Tente fazer uma nova busca ou navegue pelas categorias.', 'base-conhecimento'); ?></p>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php get_footer('base-conhecimento'); ?>
