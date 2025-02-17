<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BC_Documentos {
    private static $instance = null;
    
    public static function getInstance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init', array( $this, 'registrarPostType' ) );
        add_action( 'add_meta_boxes', array( $this, 'adicionarMetaBoxes' ) );
        add_action( 'save_post_bc_documento', array( $this, 'salvarMetaBoxes' ) );
        add_filter( 'manage_bc_documento_posts_columns', array( $this, 'definirColunas' ) );
        add_action( 'manage_bc_documento_posts_custom_column', array( $this, 'exibirConteudoColunas' ), 10, 2 );
        add_filter( 'manage_edit-bc_documento_sortable_columns', array( $this, 'definirColunasOrdenaveis' ) );
        add_action( 'restrict_manage_posts', array( $this, 'adicionarFiltros' ) );
        add_action( 'pre_get_posts', array( $this, 'modificarQuery' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'carregarAssets' ) );
        add_action( 'wp_ajax_bc_reordenar_documentos', array( $this, 'reordenarDocumentos' ) );
    }
    
    /**
     * Registra o custom post type "bc_documento"
     */
    public function registrarPostType() {
        $labels = array(
            'name'                  => _x( 'Documentos', 'Post type general name', 'base-conhecimento' ),
            'singular_name'         => _x( 'Documento', 'Post type singular name', 'base-conhecimento' ),
            'menu_name'             => _x( 'Documentos', 'Admin Menu text', 'base-conhecimento' ),
            'name_admin_bar'        => _x( 'Documento', 'Add New on Toolbar', 'base-conhecimento' ),
            'add_new'               => _x( 'Adicionar Novo', 'Documento', 'base-conhecimento' ),
            'add_new_item'          => __( 'Adicionar Novo Documento', 'base-conhecimento' ),
            'new_item'              => __( 'Novo Documento', 'base-conhecimento' ),
            'edit_item'             => __( 'Editar Documento', 'base-conhecimento' ),
            'view_item'             => __( 'Visualizar Documento', 'base-conhecimento' ),
            'all_items'             => __( 'Todos os Documentos', 'base-conhecimento' ),
            'search_items'          => __( 'Buscar Documentos', 'base-conhecimento' ),
            'parent_item_colon'     => __( 'Documento Pai:', 'base-conhecimento' ),
            'not_found'             => __( 'Nenhum documento encontrado.', 'base-conhecimento' ),
            'not_found_in_trash'    => __( 'Nenhum documento encontrado na lixeira.', 'base-conhecimento' )
        );
    
        $args = array(
            'labels'                => $labels,
            'public'                => true,
            'publicly_queryable'    => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'query_var'             => true,
            'rewrite'               => array( 'slug' => 'bc_documento' ),
            'capability_type'       => 'post',
            'has_archive'           => true,
            'hierarchical'          => false,
            'menu_position'         => null,
            'supports'              => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments', 'page-attributes' )
        );
    
        register_post_type( 'bc_documento', $args );
    }
    
    /**
     * Carrega os assets (CSS e JS) conforme a tela atual
     */
    public function carregarAssets( $hook ) {
        // Na tela de edição ou criação do post
        if ( $hook == 'post.php' || $hook == 'post-new.php' ) {
            if ( get_post_type() == 'bc_documento' ) {
                wp_enqueue_style( 'bc-editor', BC_PLUGIN_URL . 'admin/assets/css/editor.css', array(), BC_VERSION );
                wp_enqueue_script( 'bc-editor', BC_PLUGIN_URL . 'admin/assets/js/editor.js', array( 'jquery' ), BC_VERSION, true );
                
                // Editor avançado com CodeMirror
                wp_enqueue_style( 'codemirror', 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css' );
                wp_enqueue_script( 'codemirror', 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js' );
                wp_enqueue_script( 'codemirror-xml', 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/xml/xml.min.js' );
                wp_enqueue_script( 'codemirror-markdown', 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/markdown/markdown.min.js' );
                
                wp_localize_script( 'bc-editor', 'bcEditor', array(
                    'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                    'nonce'   => wp_create_nonce( 'bc_editor_nonce' )
                ) );
            }
        }
        
        // Na tela de listagem dos posts
        if ( $hook == 'edit.php' && get_post_type() == 'bc_documento' ) {
            wp_enqueue_style( 'bc-lista', BC_PLUGIN_URL . 'admin/assets/css/lista.css', array(), BC_VERSION );
            wp_enqueue_script( 'jquery-ui-sortable' );
            wp_enqueue_script( 'bc-lista', BC_PLUGIN_URL . 'admin/assets/js/lista.js', array( 'jquery', 'jquery-ui-sortable' ), BC_VERSION, true );
            
            wp_localize_script( 'bc-lista', 'bcLista', array(
                'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
                'nonce'     => wp_create_nonce( 'bc_lista_nonce' ),
                'ordenando' => __( 'Ordenando...', 'base-conhecimento' ),
                'sucesso'   => __( 'Ordem atualizada com sucesso!', 'base-conhecimento' ),
                'erro'      => __( 'Erro ao atualizar a ordem.', 'base-conhecimento' )
            ) );
        }
    }
    
    /**
     * Adiciona as Meta Boxes no editor do post
     */
    public function adicionarMetaBoxes() {
        add_meta_box(
            'bc_documento_opcoes',
            __( 'Opções do Documento', 'base-conhecimento' ),
            array( $this, 'renderizarMetaboxOpcoes' ),
            'bc_documento',
            'side',
            'high'
        );

        add_meta_box(
            'bc_documento_visualizacao',
            __( 'Prévia do Documento', 'base-conhecimento' ),
            array( $this, 'renderizarMetaboxPrevia' ),
            'bc_documento',
            'normal',
            'high'
        );

        add_meta_box(
            'bc_documento_relacionados',
            __( 'Documentos Relacionados', 'base-conhecimento' ),
            array( $this, 'renderizarMetaboxRelacionados' ),
            'bc_documento',
            'normal',
            'low'
        );
    }
    
    /**
     * Renderiza a Meta Box de opções
     */
    public function renderizarMetaboxOpcoes( $post ) {
        wp_nonce_field( 'bc_documento_opcoes', 'bc_documento_opcoes_nonce' );
        
        $ordem       = get_post_meta( $post->ID, '_bc_ordem', true );
        $icone       = get_post_meta( $post->ID, '_bc_icone', true );
        $destaque    = get_post_meta( $post->ID, '_bc_destaque', true );
        $visibilidade = get_post_meta( $post->ID, '_bc_visibilidade', true );
        
        // Definindo valores padrão para evitar erros em valores vazios
        $ordem = !empty($ordem) ? intval($ordem) : 0;
        $icone = !empty($icone) ? esc_attr($icone) : 'default-icon';
        $destaque = !empty($destaque) ? 1 : 0;
        $visibilidade = !empty($visibilidade) ? $visibilidade : 'publico';
        ?>
        <div class="bc-metabox-opcoes">
            <p>
                <label for="bc_ordem"><?php _e( 'Ordem:', 'base-conhecimento' ); ?></label>
                <input type="number" id="bc_ordem" name="bc_ordem" value="<?php echo esc_attr( $ordem ); ?>" class="small-text">
            </p>
            
            <p>
                <label for="bc_icone"><?php _e( 'Ícone:', 'base-conhecimento' ); ?></label>
                <input type="text" id="bc_icone" name="bc_icone" value="<?php echo esc_attr( $icone ); ?>" class="regular-text">
                <button type="button" class="button bc-selecionar-icone">
                    <?php _e( 'Selecionar Ícone', 'base-conhecimento' ); ?>
                </button>
            </p>
            
            <p>
                <label>
                    <input type="checkbox" name="bc_destaque" value="1" <?php checked( $destaque, '1' ); ?>>
                    <?php _e( 'Destacar na página inicial', 'base-conhecimento' ); ?>
                </label>
            </p>
            
            <p>
                <label for="bc_visibilidade"><?php _e( 'Visibilidade:', 'base-conhecimento' ); ?></label>
                <select id="bc_visibilidade" name="bc_visibilidade">
                    <option value="publico" <?php selected( $visibilidade, 'publico' ); ?>>
                        <?php _e( 'Público', 'base-conhecimento' ); ?>
                    </option>
                    <option value="privado" <?php selected( $visibilidade, 'privado' ); ?>>
                        <?php _e( 'Privado', 'base-conhecimento' ); ?>
                    </option>
                    <option value="protegido" <?php selected( $visibilidade, 'protegido' ); ?>>
                        <?php _e( 'Protegido por Senha', 'base-conhecimento' ); ?>
                    </option>
                </select>
            </p>
        </div>
        <?php
    }
    
    /**
     * Renderiza a Meta Box de prévia do documento
     */
    public function renderizarMetaboxPrevia( $post ) {
        ?>
        <div class="bc-previa-documento">
            <div class="bc-previa-header">
                <div class="bc-previa-acoes">
                    <button type="button" class="button bc-previa-desktop" title="<?php esc_attr_e( 'Visualizar Desktop', 'base-conhecimento' ); ?>">
                        <span class="dashicons dashicons-desktop"></span>
                    </button>
                    <button type="button" class="button bc-previa-tablet" title="<?php esc_attr_e( 'Visualizar Tablet', 'base-conhecimento' ); ?>">
                        <span class="dashicons dashicons-tablet"></span>
                    </button>
                    <button type="button" class="button bc-previa-mobile" title="<?php esc_attr_e( 'Visualizar Mobile', 'base-conhecimento' ); ?>">
                        <span class="dashicons dashicons-smartphone"></span>
                    </button>
                </div>
            </div>
            
            <div class="bc-previa-conteudo">
                <iframe src="<?php echo add_query_arg( 'preview', 'true', get_permalink( $post->ID ) ); ?>" frameborder="0"></iframe>
            </div>
        </div>
        <?php
    }
    
    /**
     * Renderiza a Meta Box de documentos relacionados
     */
    public function renderizarMetaboxRelacionados( $post ) {
        $relacionados = get_post_meta( $post->ID, '_bc_relacionados', true );
        $relacionados = is_array( $relacionados ) ? $relacionados : array();
        ?>
        <div class="bc-documentos-relacionados">
            <p>
                <input type="text" class="bc-busca-documentos" placeholder="<?php esc_attr_e( 'Pesquisar documentos...', 'base-conhecimento' ); ?>">
            </p>
            
            <ul class="bc-lista-relacionados">
                <?php
                foreach ( $relacionados as $doc_id ) {
                    $documento = get_post( $doc_id );
                    // Verifica se o documento existe e se seu tipo é 'bc_documento'
                    if ( ! $documento || $documento->post_type !== 'bc_documento' ) {
                        continue;
                    }
                    ?>
                    <li data-id="<?php echo $documento->ID; ?>">
                        <input type="hidden" name="bc_relacionados[]" value="<?php echo $documento->ID; ?>">
                        <span class="bc-relacionado-titulo"><?php echo $documento->post_title; ?></span>
                        <button type="button" class="bc-remover-relacionado">
                            <span class="dashicons dashicons-no"></span>
                        </button>
                    </li>
                    <?php
                }
                ?>
            </ul>
            
            <div class="bc-resultados-busca"></div>
        </div>
        <?php
    }
    
    /**
     * Salva os dados das Meta Boxes ao salvar o post
     */
    public function salvarMetaBoxes( $post_id ) {
        // Verifica o nonce
        if ( ! isset( $_POST['bc_documento_opcoes_nonce'] ) || 
             ! wp_verify_nonce( $_POST['bc_documento_opcoes_nonce'], 'bc_documento_opcoes' ) ) {
            return;
        }
    
        // Verifica se é autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
    
        // Verifica permissões do usuário
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
    
        // Salvar ordem
        if ( isset( $_POST['bc_ordem'] ) ) {
            update_post_meta($post_id, '_bc_ordem', intval($_POST['bc_ordem']));
        }
    
        // Salvar ícone
        if ( isset( $_POST['bc_icone'] ) ) {
            update_post_meta($post_id, '_bc_icone', sanitize_text_field($_POST['bc_icone']));
        }
    
        // Salvar destaque
        if ( isset( $_POST['bc_destaque'] ) ) {
            update_post_meta($post_id, '_bc_destaque', '1');
        } else {
            delete_post_meta($post_id, '_bc_destaque');
        }
    
        // Salvar visibilidade
        if ( isset( $_POST['bc_visibilidade'] ) ) {
            update_post_meta($post_id, '_bc_visibilidade', sanitize_text_field($_POST['bc_visibilidade']));
        }
    
        // Salvar documentos relacionados
        if ( isset( $_POST['bc_relacionados'] ) ) {
            $relacionados = array_map('intval', $_POST['bc_relacionados']);
            update_post_meta($post_id, '_bc_relacionados', $relacionados);
        } else {
            delete_post_meta($post_id, '_bc_relacionados');
        }
    }
    
    /**
     * Define as colunas personalizadas na listagem de posts
     */
    public function definirColunas( $columns ) {
        $new_columns = array();
        $new_columns['cb']         = $columns['cb'];
        $new_columns['title']      = __( 'Título', 'base-conhecimento' );
        $new_columns['order']      = __( 'Ordem', 'base-conhecimento' );
        $new_columns['visibilidade'] = __( 'Visibilidade', 'base-conhecimento' );
        return $new_columns;
    }
    
    /**
     * Exibe o conteúdo das colunas personalizadas
     */
    public function exibirConteudoColunas( $column, $post_id ) {
        if ( 'order' === $column ) {
            echo esc_html( get_post_meta($post_id, '_bc_ordem', true) );
        }
        if ( 'visibilidade' === $column ) {
            echo esc_html( ucfirst( get_post_meta($post_id, '_bc_visibilidade', true) ) );
        }
    }
    
    /**
     * Define quais colunas são ordenáveis
     */
    public function definirColunasOrdenaveis( $columns ) {
        $columns['order'] = 'menu_order';
        return $columns;
    }
    
    /**
     * Adiciona filtros na tela de listagem dos posts
     */
    public function adicionarFiltros( $post_type ) {
        if ( 'bc_documento' === $post_type ) {
            $visibilidade = isset($_GET['bc_visibilidade']) ? $_GET['bc_visibilidade'] : '';
            ?>
            <select name="bc_visibilidade">
                <option value=""><?php _e( 'Todas as visibilidades', 'base-conhecimento' ); ?></option>
                <option value="publico" <?php selected($visibilidade, 'publico'); ?>><?php _e( 'Público', 'base-conhecimento' ); ?></option>
                <option value="privado" <?php selected($visibilidade, 'privado'); ?>><?php _e( 'Privado', 'base-conhecimento' ); ?></option>
                <option value="protegido" <?php selected($visibilidade, 'protegido'); ?>><?php _e( 'Protegido por Senha', 'base-conhecimento' ); ?></option>
            </select>
            <?php
        }
    }
    
    /**
     * Modifica a query da listagem com base no filtro de visibilidade
     */
    public function modificarQuery( $query ) {
        global $pagenow;
        if ( is_admin() && $pagenow == 'edit.php' && 'bc_documento' === $query->get( 'post_type' ) ) {
            if ( isset( $_GET['bc_visibilidade'] ) && '' !== $_GET['bc_visibilidade'] ) {
                $query->set( 'meta_key', '_bc_visibilidade' );
                $query->set( 'meta_value', sanitize_text_field( $_GET['bc_visibilidade'] ) );
            }
        }
    }
    
    /**
     * Reordena os documentos via AJAX
     */
    public function reordenarDocumentos() {
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error();
        }
    
        check_ajax_referer( 'bc_lista_nonce', 'nonce' );
    
        $ordem = isset( $_POST['ordem'] ) ? $_POST['ordem'] : array();
        
        foreach ( $ordem as $posicao => $post_id ) {
            wp_update_post( array(
                'ID'         => intval( $post_id ),
                'menu_order' => $posicao
            ) );
        }
    
        wp_send_json_success();
    }
}

// Inicializa a classe
BC_Documentos::getInstance();
