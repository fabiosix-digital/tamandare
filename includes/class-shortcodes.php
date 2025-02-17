<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Caso a constante BC_PLUGIN_PATH não esteja definida, defina-a (ajuste conforme a estrutura do seu plugin)
if ( ! defined( 'BC_PLUGIN_PATH' ) ) {
    define( 'BC_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}

class BC_Shortcodes {
    private static $instance = null;
    
    public static function getInstance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_shortcode( 'documentacao_menu', array( $this, 'menuLateral' ) );
        add_shortcode( 'documentacao_busca', array( $this, 'campoBusca' ) );
        add_shortcode( 'documentacao_home', array( $this, 'paginaInicial' ) );
        add_shortcode( 'documentacao_artigo', array( $this, 'exibirArtigo' ) );
        add_shortcode( 'documentacao_paginacao', array( $this, 'paginacaoArtigos' ) );
        add_shortcode( 'documentacao_completa', array( $this, 'sistemaCompleto' ) );
    }

    /**
     * Exibe o menu lateral
     */
    public function menuLateral( $atts ) {
        ob_start();
        include BC_PLUGIN_PATH . 'public/components/menu.php';
        return ob_get_clean();
    }

    /**
     * Exibe o campo de busca
     */
    public function campoBusca( $atts ) {
        ob_start();
        include BC_PLUGIN_PATH . 'public/components/busca.php';
        return ob_get_clean();
    }

    /**
     * Exibe a página inicial
     */
    public function paginaInicial( $atts ) {
        ob_start();
        include BC_PLUGIN_PATH . 'public/views/home.php';
        return ob_get_clean();
    }

    /**
     * Exibe o artigo/documento (verifica se há um ID válido) com breadcrumb dinâmico, navegação e botão "Ler Próximo Artigo"
     */
    public function exibirArtigo( $atts ) {
        // Define os atributos padrão e captura o ID do artigo
        $atts = shortcode_atts( array(
            'id' => ''
        ), $atts );
        $post_id = !empty( $atts['id'] ) ? intval( $atts['id'] ) : get_the_ID();
        if ( ! $post_id ) {
            return __( 'Erro: Documento não encontrado.', 'base-conhecimento' );
        }
        // Variável para o breadcrumb dinâmico (assumindo que a função BC_Helpers::breadcrumb existe)
        $breadcrumb = function_exists('BC_Helpers::breadcrumb') ? BC_Helpers::breadcrumb($post_id) : '';
        // Variáveis para navegação entre artigos
        $prev_link = get_previous_post_link('%link', __('Artigo Anterior', 'base-conhecimento'));
        $next_link = get_next_post_link('%link', __('Artigo Próximo', 'base-conhecimento'));
        // Botão "Ler Próximo Artigo"
        $next_post = get_adjacent_post(false, '', false);
        $ler_proximo = '';
        if ($next_post) {
            $ler_proximo = '<a class="bc-btn-read-next" href="' . get_permalink($next_post->ID) . '">' . __('Ler Próximo Artigo', 'base-conhecimento') . '</a>';
        }
        // Define uma variável para uso na view, se necessário
        $documento_id = $post_id;
        
        ob_start();
        include BC_PLUGIN_PATH . 'public/views/artigo.php';
        return ob_get_clean();
    }

    /**
     * Exibe a paginação dos artigos
     */
    public function paginacaoArtigos( $atts ) {
        ob_start();
        include BC_PLUGIN_PATH . 'public/components/paginacao.php';
        return ob_get_clean();
    }

    /**
     * Exibe o sistema completo (menu + conteúdo principal)
     */
    public function sistemaCompleto( $atts ) {
        ob_start();
        ?>
        <div class="bc-sistema-completo">
            <?php echo do_shortcode( '[documentacao_menu]' ); ?>
            
            <div class="bc-conteudo-principal">
                <?php 
                if ( is_singular( 'bc_documento' ) ) {
                    echo do_shortcode( '[documentacao_artigo]' );
                } else {
                    echo do_shortcode( '[documentacao_home]' );
                }
                ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Inicializar a classe
BC_Shortcodes::getInstance();
