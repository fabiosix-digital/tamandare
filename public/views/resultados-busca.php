<?php
if (!defined('ABSPATH')) exit;

get_header('base-conhecimento');

$termo_busca = get_search_query();
?>

<div class="bc-busca-page">
    <!-- Menu Lateral -->
    <aside class="bc-sidebar">
        <?php echo do_shortcode('[documentacao_menu]'); ?>
    </aside>

    <!-- Conteúdo Principal -->
    <main class="bc-conteudo">
        <!-- Cabeçalho -->
        <header class="bc-busca-header">
            <div class="bc-breadcrumbs">
                <a href="<?php echo home_url('/base-conhecimento'); ?>"><?php _e('Base de Conhecimento', 'base-conhecimento'); ?></a>
                <span class="bc-breadcrumb-separator">/</span>
                <span class="bc-breadcrumb-atual"><?php _e('Resultados da Busca', 'base-conhecimento'); ?></span>
            </div>

            <h1 class="bc-busca-titulo">
                <?php printf(
                    __('Resultados da busca por: "%s"', 'base-conhecimento'),
                    esc_html($termo_busca)
                ); ?>
            </h1>

            <!-- Formulário de Busca -->
            <div class="bc-busca-form">
                <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
                    <input type="hidden" name="post_type" value="bc_documento">
                    <div class="bc-campo-busca">
                        <input type="search" 
                               class="bc-busca-input" 
                               name="s"
                               value="<?php echo esc_attr($termo_busca); ?>"
                               placeholder="<?php esc_attr_e('Refine sua busca...', 'base-conhecimento'); ?>">
                        <button type="submit" class="bc-busca-btn">
                            <span class="dashicons dashicons-search"></span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Filtros -->
            <div class="bc-busca-filtros">
                <div class="bc-filtro-grupo">
                    <label><?php _e('Ordenar por:', 'base-conhecimento'); ?></label>
                    <select class="bc-filtro-select" data-orderby>
                        <option value="relevance" <?php selected(get_query_var('orderby'), 'relevance'); ?>>
                            <?php _e('Relevância', 'base-conhecimento'); ?>
                        </option>
                        <option value="date" <?php selected(get_query_var('orderby'), 'date'); ?>>
                            <?php _e('Data', 'base-conhecimento'); ?>
                        </option>
                        <option value="title" <?php selected(get_query_var('orderby'), 'title'); ?>>
                            <?php _e('Título', 'base-conhecimento'); ?>
                        </option>
                    </select>
                </div>

                <div class="bc-filtro-grupo">
                    <label><?php _e('Pasta:', 'base-conhecimento'); ?></label>
                    <?php
                    wp_dropdown_categories(array(
                        'taxonomy' => 'bc_pasta',
                        'name' => 'bc_pasta',
                        'show_option_all' => __('Todas as pastas', 'base-conhecimento'),
                        'selected' => get_query_var('bc_pasta'),
                        'hierarchical' => true,
                        'class' => 'bc-filtro-select'
                    ));
                    ?>
                </div>
            </div>

            <!-- Resumo dos Resultados -->
            <div class="bc-busca-resumo">
                <?php
                global $wp_query;
                printf(
                    _n(
                        'Encontrado %d resultado',
                        'Encontrados %d resultados',
                        $wp_query->found_posts,
                        'base-conhecimento'
                    ),
                    $wp_query->found_posts
                );
                ?>
            </div>
        </header>

        <!-- Resultados -->
        <?php if (have_posts()): ?>
            <div class="bc-resultados">
                <?php while (have_posts()): the_post();
                    $pasta = wp_get_post_terms(get_the_ID(), 'bc_pasta');
                    $stats = BC_Estatisticas::getInstance()->obterEstatisticasDocumento(get_the_ID());
                    $texto_destacado = get_post_meta(get_the_ID(), '_bc_texto_destacado', true);
                ?>
                    <article class="bc-resultado-item">
                        <h2 class="bc-resultado-titulo">
                            <a href="<?php the_permalink(); ?>">
                                <?php echo bc_destacar_termo(get_the_title(), $termo_busca); ?>
                            </a>
                        </h2>

                        <?php if (!empty($pasta)): ?>
                            <div class="bc-resultado-pasta">
                                <span class="dashicons dashicons-category"></span>
                                <a href="<?php echo get_term_link($pasta[0]); ?>">
                                    <?php echo $pasta[0]->name; ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <div class="bc-resultado-excerpt">
                            <?php
                            if ($texto_destacado) {
                                echo bc_destacar_termo($texto_destacado, $termo_busca);
                            } else {
                                echo bc_destacar_termo(get_the_excerpt(), $termo_busca);
                            }
                            ?>
                        </div>

                        <div class="bc-resultado-meta">
                            <span class="bc-resultado-data">
                                <span class="dashicons dashicons-calendar-alt"></span>
                                <?php echo get_the_date(); ?>
                            </span>

                            <?php if ($stats): ?>
                                <span class="bc-resultado-views">
                                    <span class="dashicons dashicons-visibility"></span>
                                    <?php echo BC_Helpers::formatarVisualizacoes($stats->total_visualizacoes); ?>
                                </span>

                                <span class="bc-resultado-feedback">
                                    <span class="dashicons dashicons-thumbs-up"></span>
                                    <?php echo $stats->total_curtidas; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endwhile; ?>

                <?php
                // Paginação
                the_posts_pagination(array(
                    'mid_size' => 2,
                    'prev_text' => '<span class="dashicons dashicons-arrow-left-alt2"></span>',
                    'next_text' => '<span class="dashicons dashicons-arrow-right-alt2"></span>'
                ));
                ?>
            </div>
        <?php else: ?>
            <div class="bc-sem-resultados">
                <div class="bc-sem-resultados-icone">
                    <span class="dashicons dashicons-search"></span>
                </div>
                <h2><?php _e('Nenhum resultado encontrado', 'base-conhecimento'); ?></h2>
                <p><?php _e('Tente refinar sua busca ou navegue pelas categorias.', 'base-conhecimento'); ?></p>
                
                <!-- Sugestões -->
                <div class="bc-sugestoes">
                    <h3><?php _e('Sugestões:', 'base-conhecimento'); ?></h3>
                    <ul>
                        <li><?php _e('Verifique se não há erros de digitação', 'base-conhecimento'); ?></li>
                        <li><?php _e('Use palavras-chave diferentes', 'base-conhecimento'); ?></li>
                        <li><?php _e('Use termos mais gerais', 'base-conhecimento'); ?></li>
                        <li><?php _e('Navegue pelas pastas no menu lateral', 'base-conhecimento'); ?></li>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php
/**
 * Função para destacar o termo buscado no texto
 */
function bc_destacar_termo($texto, $termo) {
    if (empty($termo)) return $texto;
    
    $termos = explode(' ', $termo);
    $padrao = array();
    
    foreach ($termos as $t) {
        if (strlen($t) > 2) {
            $padrao[] = preg_quote($t, '/');
        }
    }
    
    if (empty($padrao)) return $texto;
    
    $padrao = '/(' . implode('|', $padrao) . ')/iu';
    return preg_replace($padrao, '<mark>$1</mark>', $texto);
}

get_footer('base-conhecimento');
?>