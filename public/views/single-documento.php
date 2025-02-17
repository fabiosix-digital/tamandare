<?php
if (!defined('ABSPATH')) exit;

get_header('base-conhecimento');
?>

<div class="bc-single-container">
    <!-- Menu Lateral (Sidebar) -->
    <aside class="bc-sidebar">
        <div class="bc-sidebar-header">
            <h2>
                <i class="fa fa-book"></i>
                <?php _e('Navegação', 'base-conhecimento'); ?>
            </h2>
            <button class="bc-menu-toggle">
                <i class="fa fa-times"></i>
            </button>
        </div>

        <div class="bc-sidebar-content">
            <?php
            $main_folders = get_terms(array(
                'taxonomy'   => 'bc_pasta',
                'parent'     => 0,
                'hide_empty' => false,
            ));

            if (!empty($main_folders) && !is_wp_error($main_folders)) :
                echo '<ul class="bc-sidebar-menu">';
                foreach ($main_folders as $folder) {
                    $folder_icon = get_term_meta($folder->term_id, 'bc_icone', true);
                    $folder_icon = $folder_icon ? $folder_icon : 'fa-folder';
                    
                    echo '<li class="bc-sidebar-item">';
                    echo '<div class="bc-sidebar-folder">';
                    echo '<i class="fa ' . esc_attr($folder_icon) . '"></i>';
                    echo '<span>' . esc_html($folder->name) . '</span>';
                    echo '<i class="fa fa-chevron-down bc-toggle-icon"></i>';
                    echo '</div>';
                    
                    // Documentos da pasta principal
                    $main_docs = get_posts(array(
                        'post_type' => 'bc_documento',
                        'tax_query' => array(
                            array(
                                'taxonomy' => 'bc_pasta',
                                'field'    => 'term_id',
                                'terms'    => $folder->term_id,
                            ),
                        ),
                        'posts_per_page' => -1,
                        'orderby'        => 'menu_order title',
                        'order'          => 'ASC',
                    ));

                    if (!empty($main_docs)) {
                        echo '<ul class="bc-sidebar-docs">';
                        foreach ($main_docs as $doc) {
                            $active_class = (get_the_ID() == $doc->ID) ? ' active' : '';
                            echo '<li class="bc-doc-item' . $active_class . '">';
                            echo '<i class="fa fa-file-text-o"></i>';
                            echo '<a href="' . get_permalink($doc->ID) . '" data-carregar-artigo="' . esc_attr($doc->ID) . '">' 
                                 . esc_html($doc->post_title) . '</a>';
                            echo '</li>';
                        }
                        echo '</ul>';
                    }

                    // Subpastas
                    $child_folders = get_terms(array(
                        'taxonomy'   => 'bc_pasta',
                        'parent'     => $folder->term_id,
                        'hide_empty' => false,
                    ));

                    if (!empty($child_folders) && !is_wp_error($child_folders)) {
                        echo '<ul class="bc-sidebar-submenu">';
                        foreach ($child_folders as $child) {
                            $child_icon = get_term_meta($child->term_id, 'bc_icone', true);
                            $child_icon = $child_icon ? $child_icon : 'fa-folder';
                            
                            echo '<li class="bc-sidebar-subitem">';
                            echo '<div class="bc-sidebar-folder">';
                            echo '<i class="fa ' . esc_attr($child_icon) . '"></i>';
                            echo '<span>' . esc_html($child->name) . '</span>';
                            echo '<i class="fa fa-chevron-down bc-toggle-icon"></i>';
                            echo '</div>';

                            // Documentos da subpasta
                            $child_docs = get_posts(array(
                                'post_type' => 'bc_documento',
                                'tax_query' => array(
                                    array(
                                        'taxonomy' => 'bc_pasta',
                                        'field'    => 'term_id',
                                        'terms'    => $child->term_id,
                                    ),
                                ),
                                'posts_per_page' => -1,
                                'orderby'        => 'menu_order title',
                                'order'          => 'ASC',
                            ));

                            if (!empty($child_docs)) {
                                echo '<ul class="bc-sidebar-docs">';
                                foreach ($child_docs as $doc) {
                                    $active_class = (get_the_ID() == $doc->ID) ? ' active' : '';
                                    echo '<li class="bc-doc-item' . $active_class . '">';
                                    echo '<i class="fa fa-file-text-o"></i>';
                                    echo '<a href="' . get_permalink($doc->ID) . '" data-carregar-artigo="' . esc_attr($doc->ID) . '">' 
                                         . esc_html($doc->post_title) . '</a>';
                                    echo '</li>';
                                }
                                echo '</ul>';
                            }
                            echo '</li>';
                        }
                        echo '</ul>';
                    }
                    echo '</li>';
                }
                echo '</ul>';
            endif;
            ?>
        </div>
    </aside>

    <!-- Conteúdo Principal -->
    <main class="bc-single-content">
        <?php
        if (have_posts()) : while (have_posts()) : the_post();
            $current_folders = wp_get_post_terms(get_the_ID(), 'bc_pasta');
        ?>
            <!-- Breadcrumbs -->
            <div class="bc-breadcrumbs">
                <a href="<?php echo home_url('/base-conhecimento'); ?>">
                    <i class="fa fa-home"></i> Base de Conhecimento
                </a>
                <?php
                if (!empty($current_folders)) {
                    $folder = $current_folders[0];
                    if ($folder->parent) {
                        $parent = get_term($folder->parent, 'bc_pasta');
                        echo ' <i class="fa fa-chevron-right"></i> ';
                        echo '<a href="' . get_term_link($parent) . '">' . $parent->name . '</a>';
                    }
                    echo ' <i class="fa fa-chevron-right"></i> ';
                    echo '<a href="' . get_term_link($folder) . '">' . $folder->name . '</a>';
                }
                echo ' <i class="fa fa-chevron-right"></i> ';
                echo '<span>' . get_the_title() . '</span>';
                ?>
            </div>

            <article id="bc-artigo" data-id="<?php the_ID(); ?>" class="bc-artigo-container">
                <header class="bc-artigo-header">
                    <h1 class="bc-artigo-titulo"><?php the_title(); ?></h1>
                    <div class="bc-artigo-meta">
                        <span class="bc-artigo-data">
                            <i class="fa fa-calendar"></i>
                            <?php echo get_the_date(); ?>
                        </span>
                        <?php if (!empty($current_folders)): ?>
                        <span class="bc-artigo-pasta">
                            <i class="fa fa-folder"></i>
                            <?php echo esc_html($current_folders[0]->name); ?>
                        </span>
                        <?php endif; ?>
                        <span class="bc-artigo-views">
                            <i class="fa fa-eye"></i>
                            <?php 
                            $views = get_post_meta(get_the_ID(), '_bc_visualizacoes', true);
                            echo number_format_i18n(intval($views));
                            ?>
                        </span>
                    </div>
                </header>

                <div class="bc-artigo-conteudo">
                    <?php the_content(); ?>
                </div>

                <!-- Tags -->
                <?php
                $tags = get_the_terms(get_the_ID(), 'bc_tag');
                if (!empty($tags) && !is_wp_error($tags)) :
                ?>
                <div class="bc-artigo-tags">
                    <i class="fa fa-tags"></i>
                    <?php
                    foreach ($tags as $tag) {
                        echo '<a href="' . get_term_link($tag) . '" class="bc-tag">' . $tag->name . '</a>';
                    }
                    ?>
                </div>
                <?php endif; ?>

                <!-- Feedback -->
                <div class="bc-artigo-feedback">
                    <p><?php _e('Este artigo foi útil?', 'base-conhecimento'); ?></p>
                    <div class="bc-feedback-botoes">
                        <button class="bc-btn-feedback" data-tipo="like" data-post-id="<?php the_ID(); ?>">
                            <i class="fa fa-thumbs-up"></i> <?php _e('Sim', 'base-conhecimento'); ?>
                        </button>
                        <button class="bc-btn-feedback" data-tipo="dislike" data-post-id="<?php the_ID(); ?>">
                            <i class="fa fa-thumbs-down"></i> <?php _e('Não', 'base-conhecimento'); ?>
                        </button>
                    </div>
                </div>

                <!-- Artigos Relacionados -->
                <?php
                $related = get_posts(array(
                    'post_type' => 'bc_documento',
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'bc_pasta',
                            'field'    => 'term_id',
                            'terms'    => wp_list_pluck($current_folders, 'term_id'),
                        ),
                    ),
                    'post__not_in'   => array(get_the_ID()),
                    'posts_per_page' => 3,
                    'orderby'        => 'rand',
                ));

                if (!empty($related)) :
                ?>
                <div class="bc-artigos-relacionados">
                    <h3><?php _e('Artigos Relacionados', 'base-conhecimento'); ?></h3>
                    <div class="bc-relacionados-grid">
                        <?php foreach ($related as $post) : ?>
                            <a href="<?php echo get_permalink($post->ID); ?>" 
                               class="bc-relacionado-item"
                               data-carregar-artigo="<?php echo $post->ID; ?>">
                                <i class="fa fa-file-text-o"></i>
                                <span><?php echo get_the_title($post->ID); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </article>

            <!-- Navegação entre Artigos -->
            <nav class="bc-artigo-navegacao">
                <?php
                $prev_post = get_previous_post(true, '', 'bc_pasta');
                $next_post = get_next_post(true, '', 'bc_pasta');
                
                if ($prev_post): ?>
                    <a href="<?php echo get_permalink($prev_post); ?>" 
                       class="bc-nav-anterior" 
                       data-carregar-artigo="<?php echo $prev_post->ID; ?>">
                        <i class="fa fa-arrow-left"></i>
                        <span class="bc-nav-texto">
                            <span class="bc-nav-label"><?php _e('Anterior', 'base-conhecimento'); ?></span>
                            <span class="bc-nav-titulo"><?php echo get_the_title($prev_post); ?></span>
                        </span>
                    </a>
                <?php endif;

                if ($next_post): ?>
                    <a href="<?php echo get_permalink($next_post); ?>" 
                       class="bc-nav-proximo" 
                       data-carregar-artigo="<?php echo $next_post->ID; ?>">
                        <span class="bc-nav-texto">
                            <span class="bc-nav-label"><?php _e('Próximo', 'base-conhecimento'); ?></span>
                            <span class="bc-nav-titulo"><?php echo get_the_title($next_post); ?></span>
                        </span>
                        <i class="fa fa-arrow-right"></i>
                    </a>
                <?php endif; ?>
            </nav>
        <?php
        endwhile; endif;
        ?>
    </main>
</div>

<?php get_footer('base-conhecimento'); ?>