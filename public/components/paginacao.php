<?php
if (!defined('ABSPATH')) exit;

class BC_Paginacao_Component {
    public static function render($args = array()) {
        global $wp_query;

        if ($wp_query->max_num_pages <= 1) {
            return;
        }

        $current = max(1, get_query_var('paged'));
        $total = $wp_query->max_num_pages;
        $links = isset($args['links']) ? intval($args['links']) : 2;
        
        // Define o range de páginas a serem mostradas
        $start = $current - $links;
        $end = $current + $links;
        
        if ($start < 1) {
            $end += abs($start) + 1;
            $start = 1;
        }
        
        if ($end > $total) {
            $start -= ($end - $total);
            $end = $total;
        }
        
        $start = max(1, $start);
        $end = min($total, $end);
        ?>

        <nav class="bc-paginacao" aria-label="<?php esc_attr_e('Navegação entre páginas', 'base-conhecimento'); ?>">
            <div class="bc-paginacao-info">
                <?php
                printf(
                    __('Página %1$s de %2$s', 'base-conhecimento'),
                    $current,
                    $total
                );
                ?>
            </div>

            <ul class="bc-paginacao-lista">
                <?php
                // Primeira página
                if ($start > 1): ?>
                    <li class="bc-paginacao-item">
                        <a href="<?php echo get_pagenum_link(1); ?>" class="bc-paginacao-link" aria-label="<?php esc_attr_e('Primeira página', 'base-conhecimento'); ?>">
                            <span class="dashicons dashicons-controls-skipback"></span>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- Página anterior -->
                <?php if ($current > 1): ?>
                    <li class="bc-paginacao-item">
                        <a href="<?php echo get_pagenum_link($current - 1); ?>" class="bc-paginacao-link" aria-label="<?php esc_attr_e('Página anterior', 'base-conhecimento'); ?>">
                            <span class="dashicons dashicons-arrow-left-alt2"></span>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- Números das páginas -->
                <?php
                for ($i = $start; $i <= $end; $i++):
                    $is_current = $current === $i;
                ?>
                    <li class="bc-paginacao-item">
                        <?php if ($is_current): ?>
                            <span class="bc-paginacao-atual" aria-current="page">
                                <?php echo $i; ?>
                            </span>
                        <?php else: ?>
                            <a href="<?php echo get_pagenum_link($i); ?>" class="bc-paginacao-link">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    </li>
                <?php endfor; ?>

                <!-- Próxima página -->
                <?php if ($current < $total): ?>
                    <li class="bc-paginacao-item">
                        <a href="<?php echo get_pagenum_link($current + 1); ?>" class="bc-paginacao-link" aria-label="<?php esc_attr_e('Próxima página', 'base-conhecimento'); ?>">
                            <span class="dashicons dashicons-arrow-right-alt2"></span>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- Última página -->
                <?php if ($end < $total): ?>
                    <li class="bc-paginacao-item">
                        <a href="<?php echo get_pagenum_link($total); ?>" class="bc-paginacao-link" aria-label="<?php esc_attr_e('Última página', 'base-conhecimento'); ?>">
                            <span class="dashicons dashicons-controls-skipforward"></span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>

            <!-- Navegação por Seletor -->
            <div class="bc-paginacao-select">
                <select class="bc-select-pagina" onchange="window.location.href=this.value">
                    <?php
                    for ($i = 1; $i <= $total; $i++) {
                        printf(
                            '<option value="%s" %s>%s %d</option>',
                            esc_url(get_pagenum_link($i)),
                            selected($current, $i, false),
                            __('Página', 'base-conhecimento'),
                            $i
                        );
                    }
                    ?>
                </select>
            </div>
        </nav>

        <!-- Atalhos de Teclado -->
        <script>
        document.addEventListener('keydown', function(e) {
            if (e.altKey) {
                if (e.key === 'ArrowLeft' && <?php echo $current; ?> > 1) {
                    window.location.href = '<?php echo get_pagenum_link($current - 1); ?>';
                }
                else if (e.key === 'ArrowRight' && <?php echo $current; ?> < <?php echo $total; ?>) {
                    window.location.href = '<?php echo get_pagenum_link($current + 1); ?>';
                }
            }
        });
        </script>
        <?php
    }
}