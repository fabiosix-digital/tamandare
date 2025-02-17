<?php
if (!defined('ABSPATH')) exit;

class BC_Admin {
    private static $instance = null;
    
    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'adicionarMenus'));
        add_action('admin_init', array($this, 'registrarConfiguracoes'));
        add_action('admin_enqueue_scripts', array($this, 'carregarAssets'));
        add_action('add_meta_boxes', array($this, 'adicionarMetaBoxes'));
        add_action('save_post_bc_documento', array($this, 'salvarMetaBoxes'));
        add_filter('manage_bc_documento_posts_columns', array($this, 'definirColunas'));
        add_action('manage_bc_documento_posts_custom_column', array($this, 'exibirConteudoColunas'), 10, 2);
        add_filter('manage_edit-bc_documento_sortable_columns', array($this, 'definirColunasOrdenaveis'));
    }

    public function adicionarMenus() {
        add_menu_page(
            __('Base de Conhecimento', 'base-conhecimento'),
            __('Base de Conhecimento', 'base-conhecimento'),
            'edit_posts',
            'base-conhecimento',
            array($this, 'paginaDashboard'),
            'dashicons-book-alt',
            25
        );

        add_submenu_page(
            'base-conhecimento',
            __('Dashboard', 'base-conhecimento'),
            __('Dashboard', 'base-conhecimento'),
            'edit_posts',
            'base-conhecimento',
            array($this, 'paginaDashboard')
        );

        add_submenu_page(
            'base-conhecimento',
            __('Todos os Documentos', 'base-conhecimento'),
            __('Todos os Documentos', 'base-conhecimento'),
            'edit_posts',
            'edit.php?post_type=bc_documento'
        );

        add_submenu_page(
            'base-conhecimento',
            __('Adicionar Novo', 'base-conhecimento'),
            __('Adicionar Novo', 'base-conhecimento'),
            'edit_posts',
            'post-new.php?post_type=bc_documento'
        );

        add_submenu_page(
            'base-conhecimento',
            __('Pastas', 'base-conhecimento'),
            __('Pastas', 'base-conhecimento'),
            'manage_categories',
            'edit-tags.php?taxonomy=bc_pasta&post_type=bc_documento'
        );

        add_submenu_page(
            'base-conhecimento',
            __('Configurações', 'base-conhecimento'),
            __('Configurações', 'base-conhecimento'),
            'manage_options',
            'bc-configuracoes',
            array($this, 'paginaConfiguracoes')
        );
    }

    public function registrarConfiguracoes() {
        register_setting('bc_opcoes', 'bc_configuracoes');

        add_settings_section(
            'bc_secao_geral',
            __('Configurações Gerais', 'base-conhecimento'),
            array($this, 'secaoGeral'),
            'bc-configuracoes'
        );

        add_settings_field(
            'bc_titulo_home',
            __('Título da Página Inicial', 'base-conhecimento'),
            array($this, 'campoTexto'),
            'bc-configuracoes',
            'bc_secao_geral',
            array(
                'id' => 'titulo_home',
                'desc' => __('Título exibido no topo da página inicial', 'base-conhecimento')
            )
        );

        add_settings_field(
            'bc_descricao_home',
            __('Descrição da Página Inicial', 'base-conhecimento'),
            array($this, 'campoTextarea'),
            'bc-configuracoes',
            'bc_secao_geral',
            array(
                'id' => 'descricao_home',
                'desc' => __('Breve descrição exibida abaixo do título', 'base-conhecimento')
            )
        );

        add_settings_field(
            'bc_itens_por_pagina',
            __('Itens por Página', 'base-conhecimento'),
            array($this, 'campoNumero'),
            'bc-configuracoes',
            'bc_secao_geral',
            array(
                'id' => 'itens_por_pagina',
                'desc' => __('Número de documentos exibidos por página', 'base-conhecimento')
            )
        );

        add_settings_field(
            'bc_permitir_comentarios',
            __('Permitir Comentários', 'base-conhecimento'),
            array($this, 'campoCheckbox'),
            'bc-configuracoes',
            'bc_secao_geral',
            array(
                'id' => 'permitir_comentarios',
                'desc' => __('Habilitar comentários nos documentos', 'base-conhecimento')
            )
        );

        add_settings_field(
            'bc_mostrar_autor',
            __('Mostrar Autor', 'base-conhecimento'),
            array($this, 'campoCheckbox'),
            'bc-configuracoes',
            'bc_secao_geral',
            array(
                'id' => 'mostrar_autor',
                'desc' => __('Exibir nome do autor nos documentos', 'base-conhecimento')
            )
        );

        add_settings_field(
            'bc_tema_escuro',
            __('Tema Escuro', 'base-conhecimento'),
            array($this, 'campoCheckbox'),
            'bc-configuracoes',
            'bc_secao_geral',
            array(
                'id' => 'tema_escuro',
                'desc' => __('Habilitar opção de tema escuro', 'base-conhecimento')
            )
        );
    }

    public function paginaDashboard() {
        include BC_PLUGIN_PATH . 'admin/views/dashboard.php';
    }

    public function paginaConfiguracoes() {
        include BC_PLUGIN_PATH . 'admin/views/configuracoes.php';
    }

    public function secaoGeral() {
        echo '<p>' . __('Configure as opções gerais da Base de Conhecimento.', 'base-conhecimento') . '</p>';
    }

    public function campoTexto($args) {
        $opcoes = get_option('bc_configuracoes', array());
        $valor = isset($opcoes[$args['id']]) ? $opcoes[$args['id']] : '';
        
        printf(
            '<input type="text" id="bc_%s" name="bc_configuracoes[%s]" value="%s" class="regular-text">',
            esc_attr($args['id']),
            esc_attr($args['id']),
            esc_attr($valor)
        );
        
        if (isset($args['desc'])) {
            printf('<p class="description">%s</p>', esc_html($args['desc']));
        }
    }

    public function campoTextarea($args) {
        $opcoes = get_option('bc_configuracoes', array());
        $valor = isset($opcoes[$args['id']]) ? $opcoes[$args['id']] : '';
        
        printf(
            '<textarea id="bc_%s" name="bc_configuracoes[%s]" rows="5" cols="50">%s</textarea>',
            esc_attr($args['id']),
            esc_attr($args['id']),
            esc_textarea($valor)
        );
        
        if (isset($args['desc'])) {
            printf('<p class="description">%s</p>', esc_html($args['desc']));
        }
    }

    public function campoNumero($args) {
        $opcoes = get_option('bc_configuracoes', array());
        $valor = isset($opcoes[$args['id']]) ? $opcoes[$args['id']] : '';
        
        printf(
            '<input type="number" id="bc_%s" name="bc_configuracoes[%s]" value="%s" class="small-text">',
            esc_attr($args['id']),
            esc_attr($args['id']),
            esc_attr($valor)
        );
        
        if (isset($args['desc'])) {
            printf('<p class="description">%s</p>', esc_html($args['desc']));
        }
    }

    public function campoCheckbox($args) {
        $opcoes = get_option('bc_configuracoes', array());
        $valor = isset($opcoes[$args['id']]) ? $opcoes[$args['id']] : '';
        
        printf(
            '<input type="checkbox" id="bc_%s" name="bc_configuracoes[%s]" value="1" %s>',
            esc_attr($args['id']),
            esc_attr($args['id']),
            checked(1, $valor, false)
        );
        
        if (isset($args['desc'])) {
            printf('<label for="bc_%s">%s</label>', esc_attr($args['id']), esc_html($args['desc']));
        }
    }

    public function carregarAssets($hook) {
        // Carrega assets apenas nas páginas do plugin
        if (strpos($hook, 'base-conhecimento') !== false || 
            $hook === 'post-new.php' || 
            $hook === 'post.php') {
            
            // CSS Admin
            wp_enqueue_style(
                'base-conhecimento-admin',
                BC_PLUGIN_URL . 'admin/assets/css/admin.css',
                array(),
                BC_VERSION
            );

            // JavaScript Admin
            wp_enqueue_script(
                'base-conhecimento-admin',
                BC_PLUGIN_URL . 'admin/assets/js/admin.js',
                array('jquery', 'jquery-ui-sortable'),
                BC_VERSION,
                true
            );

            // Localize Script
            wp_localize_script('base-conhecimento-admin', 'bcAdmin', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bc_admin_nonce'),
                'strings' => array(
                    'confirmExcluir' => __('Tem certeza que deseja excluir este item?', 'base-conhecimento'),
                    'salvando' => __('Salvando...', 'base-conhecimento'),
                    'salvo' => __('Salvo!', 'base-conhecimento'),
                    'erro' => __('Erro ao salvar', 'base-conhecimento')
                )
            ));
        }
    }

    public function adicionarMetaBoxes() {
        add_meta_box(
            'bc_documento_info',
            __('Informações do Documento', 'base-conhecimento'),
            array($this, 'metaboxDocumentoInfo'),
            'bc_documento',
            'side',
            'high'
        );
    }

    public function metaboxDocumentoInfo($post) {
        wp_nonce_field('bc_documento_info', 'bc_documento_info_nonce');
        
        $visibilidade = get_post_meta($post->ID, 'bc_visibilidade', true);
        $visibilidade = !empty($visibilidade) ? $visibilidade : 'publico';
        $destaque = get_post_meta($post->ID, 'bc_destaque', true);
        $destaque = !empty($destaque) ? $destaque : 0;
        ?>
        <p>
            <label for="bc_visibilidade"><?php _e('Visibilidade:', 'base-conhecimento'); ?></label>
            <select name="bc_visibilidade" id="bc_visibilidade">
                <option value="publico" <?php selected($visibilidade, 'publico'); ?>>
                    <?php _e('Público', 'base-conhecimento'); ?>
                </option>
                <option value="privado" <?php selected($visibilidade, 'privado'); ?>>
                    <?php _e('Privado', 'base-conhecimento'); ?>
                </option>
                <option value="restrito" <?php selected($visibilidade, 'restrito'); ?>>
                    <?php _e('Restrito', 'base-conhecimento'); ?>
                </option>
            </select>
        </p>
        <p>
            <label>
                <input type="checkbox" name="bc_destaque" value="1" <?php checked($destaque, '1'); ?>>
                <?php _e('Destacar na página inicial', 'base-conhecimento'); ?>
            </label>
        </p>
        <?php
    }

    public function salvarMetaBoxes($post_id) {
        if (!isset($_POST['bc_documento_info_nonce']) || 
            !wp_verify_nonce($_POST['bc_documento_info_nonce'], 'bc_documento_info')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        if (isset($_POST['bc_visibilidade'])) {
            update_post_meta(
                $post_id,
                'bc_visibilidade',
                sanitize_text_field($_POST['bc_visibilidade'])
            );
        }
        
        if (isset($_POST['bc_destaque'])) {
            update_post_meta($post_id, 'bc_destaque', '1');
        } else {
            delete_post_meta($post_id, 'bc_destaque');
        }
    }

    public function definirColunas($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['pasta'] = __('Pasta', 'base-conhecimento');
        $new_columns['visualizacoes'] = __('Visualizações', 'base-conhecimento');
        $new_columns['curtidas'] = __('Curtidas', 'base-conhecimento');
        $new_columns['autor'] = __('Autor', 'base-conhecimento');
        $new_columns['data'] = $columns['date'];
        
        return $new_columns;
    }

    public function exibirConteudoColunas($column, $post_id) {
        switch ($column) {
            case 'pasta':
                $pastas = wp_get_post_terms($post_id, 'bc_pasta');
                if (!empty($pastas)) {
                    $links = array();
                    foreach ($pastas as $pasta) {
                        $links[] = sprintf(
                            '<a href="%s">%s</a>',
                            esc_url(admin_url('edit.php?post_type=bc_documento&bc_pasta=' . $pasta->slug)),
                            esc_html($pasta->name)
                        );
                    }
                    echo implode(', ', $links);
                }
                break;
            
            case 'visualizacoes':
                $stats = BC_Estatisticas::getInstance()->obterEstatisticasDocumento($post_id);
                echo BC_Helpers::formatarVisualizacoes($stats ? $stats->total_visualizacoes : 0);
                break;
            
            case 'curtidas':
                $stats = BC_Estatisticas::getInstance()->obterEstatisticasDocumento($post_id);
                if ($stats) {
                    printf(
                        '<span class="bc-curtidas">👍 %d</span> <span class="bc-nao-curtidas">👎 %d</span>',
                        $stats->total_curtidas,
                        $stats->total_nao_curtidas
                    );
                } else {
                    echo '👍 0 👎 0';
                }
                break;
            
            case 'autor':
                $autor = get_user_by('id', get_post_field('post_author', $post_id));
                if ($autor) {
                    printf(
                        '<a href="%s">%s</a>',
                        esc_url(admin_url('edit.php?post_type=bc_documento&author=' . $autor->ID)),
                        esc_html($autor->display_name)
                    );
                }
                break;
        }
    }

    public function definirColunasOrdenaveis($columns) {
        $columns['visualizacoes'] = 'visualizacoes';
        $columns['curtidas'] = 'curtidas';
        return $columns;
    }
}

// Inicializar a classe
BC_Admin::getInstance();
