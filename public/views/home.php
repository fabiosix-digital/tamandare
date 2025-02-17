<?php
if (!defined('ABSPATH')) exit;

get_header('base-conhecimento');

// Configurações
$configuracoes = get_option('bc_configuracoes');
$titulo_home = isset($configuracoes['titulo_home']) ? $configuracoes['titulo_home'] : __('Como podemos te ajudar?', 'base-conhecimento');
$descricao_home = isset($configuracoes['descricao_home']) ? $configuracoes['descricao_home'] : __('Encontre respostas para suas dúvidas em nossa base de conhecimento', 'base-conhecimento');
?>

<div class="bc-home">
    <!-- Header da Home -->
    <header class="bc-home-header">
        <div class="bc-container">
            <h1 class="bc-home-titulo"><?php echo esc_html($titulo_home); ?></h1>
            <?php if ($descricao_home): ?>
                <p class="bc-home-descricao"><?php echo esc_html($descricao_home); ?></p>
            <?php endif; ?>

            <!-- Campo de Busca -->
            <div class="bc-busca">
                <form role="search" method="get" class="bc-busca-form" action="<?php echo esc_url(home_url('/')); ?>">
                    <input type="hidden" name="post_type" value="bc_documento">
                    <div class="bc-campo-busca">
                        <input type="search" 
                               class="bc-busca-input" 
                               placeholder="<?php esc_attr_e('Digite sua pergunta...', 'base-conhecimento'); ?>"
                               value="<?php echo get_search_query(); ?>"
                               name="s"
                               title="<?php esc_attr_e('Pesquisar por:', 'base-conhecimento'); ?>"
                               data-busca-docs>
                        <button type="submit" class="bc-busca-btn">
                            <span class="dashicons dashicons-search"></span>
                        </button>
                    </div>
                </form>
                <div class="bc-resultados-busca" data-resultados-busca></div>
            </div>

            <!-- Botões de Ação removidos conforme solicitação -->
            <!--
            <div class="bc-botoes-acao">
                <a href="<?php echo esc_url(get_post_type_archive_link('bc_documento')); ?>" class="bc-btn bc-btn-primario">
                    <?php _e('Ver todos os artigos', 'base-conhecimento'); ?>
                </a>
                <button class="bc-btn bc-btn-secundario" data-toggle-tema>
                    <span class="dashicons dashicons-visibility"></span>
                    <?php _e('Alternar tema', 'base-conhecimento'); ?>
                </button>
            </div>
            -->
        </div>
    </header>

    <!-- Grid de Categorias -->
    <section class="bc-categorias">
        <div class="bc-container">
            <div class="bc-grid">
                <?php
                $categorias = get_terms(array(
                    'taxonomy' => 'bc_pasta',
                    'parent' => 0,
                    'hide_empty' => false
                ));

                if (!empty($categorias) && !is_wp_error($categorias)) :
                    foreach ($categorias as $categoria):
                        $icone = get_term_meta($categoria->term_id, 'bc_icone', true);
                        $cor = get_term_meta($categoria->term_id, 'bc_cor', true);
                        $descricao_curta = get_term_meta($categoria->term_id, 'bc_descricao_curta', true);

                        // Inicia array para agrupar itens: subpastas e artigos
                        $items = array();

                        // 1. Obtém as subpastas diretas da pasta principal
                        $child_folders = get_terms(array(
                            'taxonomy'   => 'bc_pasta',
                            'parent'     => $categoria->term_id,
                            'hide_empty' => false,
                        ));
                        if (!empty($child_folders) && !is_wp_error($child_folders)) {
                            foreach ($child_folders as $child) {
                                $items[] = array(
                                    'type'   => 'folder',
                                    'id'     => $child->term_id,
                                    'titulo' => $child->name,
                                    'link'   => get_term_link($child),
                                    'nivel'  => 1  // Indicador de subpasta (nivel 1)
                                );
                            }
                        }

                        // 2. Obtém os IDs da pasta principal e de todas as suas subpastas (descendentes)
                        $descendant_ids = get_term_children($categoria->term_id, 'bc_pasta');
                        $all_terms = array_merge(array($categoria->term_id), $descendant_ids);

                        // 3. Consulta os artigos (posts) que pertencem à pasta principal e seus descendentes
                        $args = array(
                            'post_type'      => 'bc_documento',
                            'posts_per_page' => 3,
                            'tax_query'      => array(
                                array(
                                    'taxonomy'         => 'bc_pasta',
                                    'field'            => 'term_id',
                                    'terms'            => $all_terms,
                                    'include_children' => false // já incluímos os descendentes
                                ),
                            ),
                            'orderby'        => 'menu_order',
                            'order'          => 'ASC',
                        );
                        $query = new WP_Query($args);
                        if ($query->have_posts()) {
                            while ($query->have_posts()) {
                                $query->the_post();
                                $items[] = array(
                                    'type'   => 'document',
                                    'id'     => get_the_ID(),
                                    'titulo' => get_the_title(),
                                    'link'   => get_permalink()
                                );
                            }
                            wp_reset_postdata();
                        }

                        // 4. Ordena os itens alfabeticamente pelo título
                        if (!empty($items)) {
                            usort($items, function($a, $b) {
                                return strcmp($a['titulo'], $b['titulo']);
                            });
                        }
                ?>
                    <div class="bc-categoria-card">
                        <div class="bc-categoria-header">
                            <?php if ($icone): ?>
                                <div class="bc-categoria-icone" style="color: <?php echo esc_attr($cor); ?>">
                                    <i class="<?php echo esc_attr($icone); ?>"></i>
                                </div>
                            <?php endif; ?>
                            
                            <h2 class="bc-categoria-titulo">
                                <a href="<?php echo esc_url(get_term_link($categoria)); ?>">
                                    <?php echo esc_html($categoria->name); ?>
                                </a>
                            </h2>

                            <?php if ($descricao_curta): ?>
                                <p class="bc-categoria-descricao"><?php echo esc_html($descricao_curta); ?></p>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($items)): ?>
                            <ul class="bc-categoria-artigos">
                                <?php foreach ($items as $item): ?>
                                    <li class="bc-artigo-item <?php echo ($item['type'] === 'folder' && isset($item['nivel']) && $item['nivel'] > 0) ? 'subfolder' : ''; ?>">
                                        <?php if ($item['type'] === 'folder'): ?>
                                            <!-- Pastas (subpastas) NÃO clicáveis -->
                                            <span class="bc-artigo-link">
                                                <i class="fa fa-folder"></i> <?php echo esc_html($item['titulo']); ?>
                                            </span>
                                        <?php else: ?>
                                            <!-- Artigos clicáveis -->
                                            <a href="<?php echo esc_url($item['link']); ?>" class="bc-artigo-link" data-carregar-artigo="<?php echo esc_attr($item['id']); ?>">
                                                <i class="fa fa-file-text-o"></i> <?php echo esc_html($item['titulo']); ?>
                                            </a>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="bc-sem-artigos">
                                <?php _e('Nenhum item encontrado nesta pasta.', 'base-conhecimento'); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php
                    endforeach;
                else:
                    echo '<p>' . esc_html__('Nenhuma pasta encontrada.', 'base-conhecimento') . '</p>';
                endif;
                ?>
            </div>
        </div>
    </section>

    <!-- Artigos em Destaque -->
    <?php
    $destaques = get_posts(array(
        'post_type' => 'bc_documento',
        'posts_per_page' => 6,
        'meta_key' => '_bc_destaque',
        'meta_value' => '1'
    ));

    if (!empty($destaques)):
    ?>
        <section class="bc-destaques">
            <div class="bc-container">
                <h2 class="bc-secao-titulo"><?php _e('Artigos em Destaque', 'base-conhecimento'); ?></h2>
                
                <div class="bc-destaques-grid">
                    <?php foreach ($destaques as $destaque):
                        $pasta = wp_get_post_terms($destaque->ID, 'bc_pasta');
                        $stats = BC_Estatisticas::getInstance()->obterEstatisticasDocumento($destaque->ID);
                    ?>
                        <article class="bc-destaque-card">
                            <h3 class="bc-destaque-titulo">
                                <a href="<?php echo get_permalink($destaque->ID); ?>" data-carregar-artigo="<?php echo esc_attr($destaque->ID); ?>">
                                    <?php echo get_the_title($destaque->ID); ?>
                                </a>
                            </h3>
                            
                            <?php if (!empty($pasta)): ?>
                                <div class="bc-destaque-pasta">
                                    <a href="<?php echo get_term_link($pasta[0]); ?>">
                                        <?php echo $pasta[0]->name; ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <div class="bc-destaque-meta">
                                <span class="bc-destaque-views">
                                    <span class="dashicons dashicons-visibility"></span>
                                    <?php echo BC_Helpers::formatarVisualizacoes($stats ? $stats->total_visualizacoes : 0); ?>
                                </span>
                                <span class="bc-destaque-curtidas">
                                    <span class="dashicons dashicons-thumbs-up"></span>
                                    <?php echo $stats ? $stats->total_curtidas : 0; ?>
                                </span>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
</div>

<?php get_footer('base-conhecimento'); ?>
