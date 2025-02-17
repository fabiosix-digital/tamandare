<?php
if (!defined('ABSPATH')) exit;

class BC_Breadcrumbs_Component {
    public static function render($args = array()) {
        $separador = isset($args['separador']) ? $args['separador'] : '<span class="dashicons dashicons-arrow-right-alt2"></span>';
        $mostrar_home = isset($args['mostrar_home']) ? $args['mostrar_home'] : true;
        
        $breadcrumbs = array();

        // Página inicial
        if ($mostrar_home) {
            $breadcrumbs[] = array(
                'titulo' => __('Base de Conhecimento', 'base-conhecimento'),
                'url' => home_url('/base-conhecimento')
            );
        }

        // Verifica se está em uma pasta
        if (is_tax('bc_pasta')) {
            $pasta_atual = get_queried_object();
            
            // Adiciona pastas ancestrais
            $ancestrais = get_ancestors($pasta_atual->term_id, 'bc_pasta', 'taxonomy');
            $ancestrais = array_reverse($ancestrais);
            
            foreach ($ancestrais as $ancestral_id) {
                $ancestral = get_term($ancestral_id, 'bc_pasta');
                $breadcrumbs[] = array(
                    'titulo' => $ancestral->name,
                    'url' => get_term_link($ancestral)
                );
            }
            
            // Adiciona pasta atual
            $breadcrumbs[] = array(
                'titulo' => $pasta_atual->name,
                'url' => false
            );
        }
        
        // Verifica se está em um documento
        elseif (is_singular('bc_documento')) {
            $post = get_queried_object();
            
            // Obtém a pasta do documento
            $pastas = wp_get_post_terms($post->ID, 'bc_pasta');
            
            if (!empty($pastas)) {
                $pasta = $pastas[0];
                
                // Adiciona pastas ancestrais
                $ancestrais = get_ancestors($pasta->term_id, 'bc_pasta', 'taxonomy');
                $ancestrais = array_reverse($ancestrais);
                
                foreach ($ancestrais as $ancestral_id) {
                    $ancestral = get_term($ancestral_id, 'bc_pasta');
                    $breadcrumbs[] = array(
                        'titulo' => $ancestral->name,
                        'url' => get_term_link($ancestral)
                    );
                }
                
                // Adiciona pasta atual
                $breadcrumbs[] = array(
                    'titulo' => $pasta->name,
                    'url' => get_term_link($pasta)
                );
            }
            
            // Adiciona documento atual
            $breadcrumbs[] = array(
                'titulo' => get_the_title($post->ID),
                'url' => false
            );
        }
        
        // Verifica se está na busca
        elseif (is_search() && isset($_GET['post_type']) && $_GET['post_type'] === 'bc_documento') {
            $breadcrumbs[] = array(
                'titulo' => sprintf(
                    __('Resultados da busca por "%s"', 'base-conhecimento'),
                    get_search_query()
                ),
                'url' => false
            );
        }

        // Renderiza os breadcrumbs
        if (!empty($breadcrumbs)): ?>
            <nav class="bc-breadcrumbs" aria-label="<?php esc_attr_e('Navegação', 'base-conhecimento'); ?>">
                <ol class="bc-breadcrumbs-lista">
                    <?php foreach ($breadcrumbs as $index => $item): ?>
                        <li class="bc-breadcrumb-item">
                            <?php if ($index > 0): ?>
                                <span class="bc-breadcrumb-separador"><?php echo $separador; ?></span>
                            <?php endif; ?>

                            <?php if ($item['url']): ?>
                                <a href="<?php echo esc_url($item['url']); ?>" class="bc-breadcrumb-link">
                                    <?php echo esc_html($item['titulo']); ?>
                                </a>
                            <?php else: ?>
                                <span class="bc-breadcrumb-atual">
                                    <?php echo esc_html($item['titulo']); ?>
                                </span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>

                <?php if (is_singular('bc_documento')): ?>
                    <div class="bc-breadcrumb-acoes">
                        <button type="button" class="bc-btn-compartilhar" data-toggle="dropdown">
                            <span class="dashicons dashicons-share"></span>
                            <?php _e('Compartilhar', 'base-conhecimento'); ?>
                        </button>
                        <div class="bc-dropdown-menu">
                            <a href="#" class="bc-dropdown-item" data-share="twitter">
                                <span class="dashicons dashicons-twitter"></span>
                                Twitter
                            </a>
                            <a href="#" class="bc-dropdown-item" data-share="facebook">
                                <span class="dashicons dashicons-facebook-alt"></span>
                                Facebook
                            </a>
                            <a href="#" class="bc-dropdown-item" data-share="linkedin">
                                <span class="dashicons dashicons-linkedin"></span>
                                LinkedIn
                            </a>
                            <button type="button" class="bc-dropdown-item" data-share="copiar">
                                <span class="dashicons dashicons-admin-links"></span>
                                <?php _e('Copiar Link', 'base-conhecimento'); ?>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </nav>
        <?php endif;
    }
}