<?php
if (!defined('ABSPATH')) exit();

/**
 * Funções auxiliares para o carregamento dinâmico dos artigos
 */

/**
 * Retorna o conteúdo formatado do artigo para carregamento dinâmico.
 *
 * @param WP_Post $post Objeto do post do artigo.
 * @return string HTML formatado do artigo.
 */
if (!function_exists('bc_get_dynamic_article_content')) {
    function bc_get_dynamic_article_content($post) {
        if (!$post || !is_a($post, 'WP_Post')) {
            return '';
        }

        // Obtém informações do artigo
        $title = get_the_title($post);
        $content = apply_filters('the_content', $post->post_content);
        $date = get_the_date('', $post);
        $current_folders = wp_get_post_terms($post->ID, 'bc_pasta');
        
        // Monta o breadcrumb
        $breadcrumbs = '<div class="bc-breadcrumbs">';
        $breadcrumbs .= '<a href="' . home_url('/base-conhecimento') . '"><i class="fa fa-home"></i> Base de Conhecimento</a>';
        
        if (!empty($current_folders)) {
            $folder = $current_folders[0];
            if ($folder->parent) {
                $parent = get_term($folder->parent, 'bc_pasta');
                $breadcrumbs .= ' <i class="fa fa-chevron-right"></i> ';
                $breadcrumbs .= '<a href="' . get_term_link($parent) . '">' . $parent->name . '</a>';
            }
            $breadcrumbs .= ' <i class="fa fa-chevron-right"></i> ';
            $breadcrumbs .= '<a href="' . get_term_link($folder) . '">' . $folder->name . '</a>';
        }
        
        $breadcrumbs .= ' <i class="fa fa-chevron-right"></i> ';
        $breadcrumbs .= '<span>' . $title . '</span>';
        $breadcrumbs .= '</div>';

        // Monta o HTML do artigo
        ob_start();
        ?>
        <article id="bc-artigo" data-id="<?php echo esc_attr($post->ID); ?>" class="bc-artigo-container">
            <?php echo $breadcrumbs; ?>

            <header class="bc-artigo-header">
                <h1 class="bc-artigo-titulo"><?php echo esc_html($title); ?></h1>
                <div class="bc-artigo-meta">
                    <span class="bc-artigo-data">
                        <i class="fa fa-calendar"></i>
                        <?php echo esc_html($date); ?>
                    </span>
                    <?php if (!empty($current_folders)): ?>
                    <span class="bc-artigo-pasta">
                        <i class="fa fa-folder"></i>
                        <?php echo esc_html($current_folders[0]->name); ?>
                    </span>
                    <?php endif; ?>
                </div>
            </header>

            <div class="bc-artigo-conteudo">
                <?php echo $content; ?>
            </div>

            <div class="bc-artigo-feedback">
                <p><?php _e('Este artigo foi útil?', 'base-conhecimento'); ?></p>
                <div class="bc-feedback-botoes">
                    <button class="bc-btn-feedback" data-tipo="like" data-post-id="<?php echo esc_attr($post->ID); ?>">
                        <i class="fa fa-thumbs-up"></i> <?php _e('Sim', 'base-conhecimento'); ?>
                    </button>
                    <button class="bc-btn-feedback" data-tipo="dislike" data-post-id="<?php echo esc_attr($post->ID); ?>">
                        <i class="fa fa-thumbs-down"></i> <?php _e('Não', 'base-conhecimento'); ?>
                    </button>
                </div>
            </div>

            <?php
            // Artigos relacionados
            $related = get_posts(array(
                'post_type' => 'bc_documento',
                'tax_query' => array(
                    array(
                        'taxonomy' => 'bc_pasta',
                        'field'    => 'term_id',
                        'terms'    => wp_list_pluck($current_folders, 'term_id'),
                    ),
                ),
                'post__not_in'   => array($post->ID),
                'posts_per_page' => 3,
                'orderby'        => 'rand',
            ));

            if (!empty($related)) :
            ?>
            <div class="bc-artigos-relacionados">
                <h3><?php _e('Artigos Relacionados', 'base-conhecimento'); ?></h3>
                <div class="bc-relacionados-grid">
                    <?php foreach ($related as $related_post) : ?>
                        <a href="<?php echo get_permalink($related_post->ID); ?>" 
                           class="bc-relacionado-item"
                           data-carregar-artigo="<?php echo $related_post->ID; ?>">
                            <i class="fa fa-file-text-o"></i>
                            <span><?php echo get_the_title($related_post->ID); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </article>
        <?php
        return ob_get_clean();
    }
}

/**
 * Função para registrar visualização do artigo
 */
function bc_register_view($post_id) {
    $views = get_post_meta($post_id, '_bc_visualizacoes', true);
    $views = $views ? $views + 1 : 1;
    update_post_meta($post_id, '_bc_visualizacoes', $views);
}

/**
 * Função para registrar feedback do artigo
 */
function bc_register_feedback($post_id, $tipo) {
    $meta_key = $tipo === 'like' ? '_bc_curtidas' : '_bc_nao_curtidas';
    $count = get_post_meta($post_id, $meta_key, true);
    $count = $count ? $count + 1 : 1;
    update_post_meta($post_id, $meta_key, $count);
}

/**
 * Função para corrigir URLs dos artigos
 */
function bc_fix_document_url($url, $post_id) {
    return str_replace('/documentacao/', '/bc_documento/', $url);
}
add_filter('post_type_link', 'bc_fix_document_url', 10, 2);