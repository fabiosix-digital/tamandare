<?php
if (!defined('ABSPATH')) exit;

class BC_Busca_Component {
    public static function render($args = array()) {
        $placeholder = isset($args['placeholder']) ? $args['placeholder'] : __('Como podemos te ajudar?', 'base-conhecimento');
        $mostrar_titulo = isset($args['mostrar_titulo']) ? $args['mostrar_titulo'] : true;
        $mostrar_filtros = isset($args['mostrar_filtros']) ? $args['mostrar_filtros'] : false;
        $pasta_atual = isset($args['pasta_atual']) ? $args['pasta_atual'] : null;
        ?>
        
        <div class="bc-busca-container">
            <?php if ($mostrar_titulo): ?>
                <div class="bc-busca-header">
                    <h2 class="bc-busca-titulo"><?php echo esc_html($placeholder); ?></h2>
                    <p class="bc-busca-subtitulo">
                        <?php _e('Encontre respostas para suas dúvidas em nossa base de conhecimento', 'base-conhecimento'); ?>
                    </p>
                </div>
            <?php endif; ?>

            <!-- Campo de Busca -->
            <div class="bc-busca">
                <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>" class="bc-busca-form">
                    <input type="hidden" name="post_type" value="bc_documento">
                    <?php if ($pasta_atual): ?>
                        <input type="hidden" name="bc_pasta" value="<?php echo $pasta_atual->slug; ?>">
                    <?php endif; ?>
                    
                    <div class="bc-campo-busca">
                        <input type="search" 
                               class="bc-busca-input" 
                               placeholder="<?php echo esc_attr($placeholder); ?>"
                               value="<?php echo get_search_query(); ?>"
                               name="s"
                               autocomplete="off"
                               data-busca>
                        <button type="submit" class="bc-busca-btn">
                            <span class="dashicons dashicons-search"></span>
                        </button>
                    </div>

                    <?php if ($mostrar_filtros): ?>
                        <div class="bc-busca-filtros">
                            <!-- Filtro por Pasta -->
                            <div class="bc-filtro-grupo">
                                <select name="pasta" class="bc-filtro-select">
                                    <option value=""><?php _e('Todas as pastas', 'base-conhecimento'); ?></option>
                                    <?php
                                    $pastas = get_terms(array(
                                        'taxonomy' => 'bc_pasta',
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
                            </div>

                            <!-- Filtro por Data -->
                            <div class="bc-filtro-grupo">
                                <select name="data" class="bc-filtro-select">
                                    <option value=""><?php _e('Qualquer data', 'base-conhecimento'); ?></option>
                                    <option value="7"><?php _e('Última semana', 'base-conhecimento'); ?></option>
                                    <option value="30"><?php _e('Último mês', 'base-conhecimento'); ?></option>
                                    <option value="90"><?php _e('Últimos 3 meses', 'base-conhecimento'); ?></option>
                                    <option value="365"><?php _e('Último ano', 'base-conhecimento'); ?></option>
                                </select>
                            </div>

                            <!-- Filtro por Ordenação -->
                            <div class="bc-filtro-grupo">
                                <select name="orderby" class="bc-filtro-select">
                                    <option value="relevance"><?php _e('Relevância', 'base-conhecimento'); ?></option>
                                    <option value="date"><?php _e('Data', 'base-conhecimento'); ?></option>
                                    <option value="title"><?php _e('Título', 'base-conhecimento'); ?></option>
                                    <option value="views"><?php _e('Mais vistos', 'base-conhecimento'); ?></option>
                                </select>
                            </div>
                        </div>
                    <?php endif; ?>
                </form>

                <!-- Resultados em Tempo Real -->
                <div class="bc-resultados-busca" data-resultados-busca></div>

                <!-- Sugestões de Busca -->
                <div class="bc-sugestoes-busca">
                    <div class="bc-sugestoes-titulo">
                        <?php _e('Sugestões de busca:', 'base-conhecimento'); ?>
                    </div>
                    <div class="bc-sugestoes-tags">
                        <?php
                        $termos_populares = get_terms(array(
                            'taxonomy' => 'post_tag',
                            'orderby' => 'count',
                            'order' => 'DESC',
                            'number' => 5,
                            'hide_empty' => true
                        ));

                        foreach ($termos_populares as $termo) {
                            printf(
                                '<a href="%s" class="bc-sugestao-tag">%s</a>',
                                get_term_link($termo),
                                esc_html($termo->name)
                            );
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    // Renderiza resultados da busca em tempo real
    public static function render_resultados($resultados) {
        if (empty($resultados)) {
            echo '<div class="bc-sem-resultados">';
            echo '<p>' . __('Nenhum resultado encontrado.', 'base-conhecimento') . '</p>';
            echo '</div>';
            return;
        }
        ?>
        <div class="bc-lista-resultados">
            <?php foreach ($resultados as $resultado): ?>
                <div class="bc-resultado-item">
                    <h3 class="bc-resultado-titulo">
                        <a href="<?php echo get_permalink($resultado->ID); ?>">
                            <?php echo get_the_title($resultado->ID); ?>
                        </a>
                    </h3>
                    
                    <?php
                    $pasta = wp_get_post_terms($resultado->ID, 'bc_pasta');
                    if (!empty($pasta)): ?>
                        <div class="bc-resultado-pasta">
                            <span class="dashicons dashicons-category"></span>
                            <?php echo $pasta[0]->name; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="bc-resultado-excerpt">
                        <?php echo wp_trim_words(get_the_excerpt($resultado->ID), 20); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
}