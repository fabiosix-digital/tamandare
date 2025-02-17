<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BC_Menu_Component {
    /**
     * Renderiza o menu lateral
     * 
     * @param array $args Pode conter 'pasta_atual' e 'documento_atual'
     */
    public static function render( $args = array() ) {
        // Utilize get_option com valor padrão para evitar erros
        $config = get_option( 'bc_configuracoes', array() );
        $logo_id = isset( $config['logo_id'] ) ? $config['logo_id'] : '';
        
        $pasta_atual = isset( $args['pasta_atual'] ) ? $args['pasta_atual'] : null;
        $documento_atual = isset( $args['documento_atual'] ) ? $args['documento_atual'] : null;
        ?>
        <div class="bc-menu-lateral" data-menu>
            <!-- Cabeçalho do Menu -->
            <div class="bc-menu-header">
                <?php
                if ( $logo_id ) {
                    $logo_url = wp_get_attachment_image_url( $logo_id, 'full' );
                    echo '<div class="bc-menu-logo">';
                    echo '<img src="' . esc_url( $logo_url ) . '" alt="Logo">';
                    echo '</div>';
                }
                ?>
                <button class="bc-menu-toggle" data-menu-toggle>
                    <span class="dashicons dashicons-menu"></span>
                </button>
            </div>

            <!-- Busca no Menu -->
            <div class="bc-menu-busca">
                <input type="text" 
                       class="bc-menu-busca-input" 
                       placeholder="<?php esc_attr_e( 'Buscar documentos...', 'base-conhecimento' ); ?>"
                       data-menu-search>
                <button type="button" class="bc-menu-busca-btn">
                    <span class="dashicons dashicons-search"></span>
                </button>
            </div>

            <!-- Navegação -->
            <nav class="bc-menu-nav">
                <?php
                $pastas = get_terms( array(
                    'taxonomy'   => 'bc_pasta',
                    'hide_empty' => false,
                    'parent'     => 0,
                    'orderby'    => 'menu_order',
                    'order'      => 'ASC'
                ) );

                if ( ! empty( $pastas ) ):
                ?>
                    <ul class="bc-menu-list">
                        <?php foreach ( $pastas as $pasta ):
                            $icone = get_term_meta( $pasta->term_id, 'bc_icone', true );
                            $cor = get_term_meta( $pasta->term_id, 'bc_cor', true );
                            $is_active = $pasta_atual && $pasta_atual->term_id === $pasta->term_id;
                        ?>
                            <li class="bc-menu-item <?php echo $is_active ? 'active' : ''; ?>">
                                <a href="<?php echo get_term_link( $pasta ); ?>" class="bc-menu-link" data-pasta="<?php echo $pasta->term_id; ?>">
                                    <?php if ( $icone ): ?>
                                        <i class="<?php echo esc_attr( $icone ); ?>" style="color: <?php echo esc_attr( $cor ); ?>"></i>
                                    <?php else: ?>
                                        <span class="dashicons dashicons-category"></span>
                                    <?php endif; ?>
                                    <span class="bc-menu-texto"><?php echo esc_html( $pasta->name ); ?></span>
                                    <?php if ( self::tem_subpastas( $pasta->term_id ) ): ?>
                                        <span class="bc-menu-seta dashicons dashicons-arrow-right-alt2"></span>
                                    <?php endif; ?>
                                </a>
                                <?php self::render_subpastas( $pasta->term_id, $pasta_atual, $documento_atual ); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </nav>

            <!-- Rodapé do Menu -->
            <div class="bc-menu-footer">
                <button type="button" class="bc-tema-toggle" data-tema-toggle>
                    <span class="dashicons dashicons-lightbulb"></span>
                    <?php _e( 'Alternar Tema', 'base-conhecimento' ); ?>
                </button>
            </div>
        </div>
        <?php
    }

    /**
     * Verifica se uma pasta possui subpastas
     * 
     * @param int $pasta_id
     * @return bool
     */
    private static function tem_subpastas( $pasta_id ) {
        $subpastas = get_terms( array(
            'taxonomy'   => 'bc_pasta',
            'hide_empty' => false,
            'parent'     => $pasta_id
        ) );
        return ! empty( $subpastas );
    }

    /**
     * Renderiza recursivamente as subpastas
     *
     * @param int $pasta_id
     * @param object|null $pasta_atual
     * @param object|null $documento_atual
     * @param int $nivel
     */
    private static function render_subpastas( $pasta_id, $pasta_atual, $documento_atual, $nivel = 1 ) {
        $subpastas = get_terms( array(
            'taxonomy'   => 'bc_pasta',
            'hide_empty' => false,
            'parent'     => $pasta_id,
            'orderby'    => 'menu_order',
            'order'      => 'ASC'
        ) );

        if ( ! empty( $subpastas ) ):
        ?>
            <ul class="bc-submenu-list nivel-<?php echo intval( $nivel ); ?>">
                <?php foreach ( $subpastas as $subpasta ):
                    $icone = get_term_meta( $subpasta->term_id, 'bc_icone', true );
                    $cor = get_term_meta( $subpasta->term_id, 'bc_cor', true );
                    $is_active = $pasta_atual && $pasta_atual->term_id === $subpasta->term_id;
                ?>
                    <li class="bc-submenu-item <?php echo $is_active ? 'active' : ''; ?>">
                        <a href="<?php echo get_term_link( $subpasta ); ?>" class="bc-submenu-link" data-pasta="<?php echo $subpasta->term_id; ?>">
                            <?php if ( $icone ): ?>
                                <i class="<?php echo esc_attr( $icone ); ?>" style="color: <?php echo esc_attr( $cor ); ?>"></i>
                            <?php else: ?>
                                <span class="dashicons dashicons-category"></span>
                            <?php endif; ?>
                            <span class="bc-menu-texto"><?php echo esc_html( $subpasta->name ); ?></span>
                            <?php if ( self::tem_subpastas( $subpasta->term_id ) ): ?>
                                <span class="bc-menu-seta dashicons dashicons-arrow-right-alt2"></span>
                            <?php endif; ?>
                        </a>
                        <?php self::render_subpastas( $subpasta->term_id, $pasta_atual, $documento_atual, $nivel + 1 ); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php 
        endif;
    }
}
