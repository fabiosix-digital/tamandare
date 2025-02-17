<?php
if (!defined('ABSPATH')) exit;

$pasta_atual = get_queried_object();
$icone = get_term_meta($pasta_atual->term_id, 'bc_icone', true);
$cor = get_term_meta($pasta_atual->term_id, 'bc_cor', true);

get_header('base-conhecimento');
?>

<div class="bc-pasta-page">
    <!-- Menu Lateral -->
    <aside class="bc-sidebar">
        <?php echo do_shortcode('[documentacao_menu]'); ?>
    </aside>

    <!-- Conteúdo Principal -->
    <main class="bc-conteudo">
        <!-- Breadcrumbs -->
        <?php BC_Templates::getInstance()->renderizarBreadcrumbs(); ?>

        <!-- Cabeçalho da Pasta -->
        <header class="bc-pasta-header">
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

            <!-- Campo de Busca -->
            <div class="bc-busca">
                <form role="search" method="get" action="<?php echo home_url('/'); ?>">
                    <input type="hidden" name="post_type" value="bc_documento">
                    <input type="hidden" name="bc_pasta" value="<?php echo $pasta_atual->slug; ?>">
                    <div class="bc-campo-busca">
                        <input type="search" 
                               class="bc-busca-input" 
                               placeholder="<?php esc_attr_e('Pesquisar nesta pasta...', 'base-conhecimento'); ?>"
                               value="<?php echo get_search_query(); ?>"
                               name="s">
                        <button type="submit" class="bc-busca-btn">
                            <span class="dashicons dashicons-search"></span>
                        </button>
                    </div>
                </form>
            </div>
        </header>

        <!-- Subpastas -->
        <?php
        $subpastas = get_terms(array(
            'taxonomy' => 'bc_pasta',
            'parent' => $pasta_atual->term_id,
            'hide_empty' => false
        ));

        if (!empty($subpastas)):
        ?>
            <section class="bc-subpastas">
                <div class="bc-grid">
                    <?php foreach ($subpastas as $subpasta):
                        $icone = get_term_meta($subpasta->term_id, 'bc_icone', true);
                        $cor = get_term_meta($subpasta->term_id, 'bc_cor', true);
                    ?>
                        <a href="<?php echo get_term_link($subpasta); ?>" class="bc-subpasta-card">
                            <?php if ($icone): ?>
                                <div class="bc-subpasta-icone" style="color: <?php echo esc_attr($cor); ?>">
                                    <i class="<?php echo esc_attr($icone); ?>"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="bc-subpasta-info">
                                <h2 class="bc-subpasta-titulo"><?php echo $subpasta->name; ?></h2>
                                <?php if ($subpasta->description): ?>
                                    <p class="bc-subpasta-descricao"><?php echo $subpasta->description; ?></p>
                                <?php endif; ?>
                                <span class="bc-subpasta-contagem">
                                    <?php echo sprintf(
                                        _n('%d documento', '%d documentos', $subpasta->count, 'base-conhecimento'),
                                        $subpasta->count
                                    ); ?>
                                </span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- Documentos -->
        <?php if (have_posts()): ?>
            <div class="bc-documentos">
                <?php while (have_posts()): the_post();
                    $stats = BC_Estatisticas::getInstance()->obterEstatisticasDocumento(get_the_ID());
                ?>
                    <article class="bc-documento-card">
                        <h2 class="bc-documento-titulo">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h2>

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
            <div class="bc-sem-documentos">
                <p><?php _e('Nenhum documento encontrado nesta pasta.', 'base-conhecimento'); ?></p>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php get_footer('base-conhecimento'); ?>