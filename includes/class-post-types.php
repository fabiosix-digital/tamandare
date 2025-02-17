<?php
if (!defined('ABSPATH')) exit;

class BC_Post_Types {
    private static $instance = null;
    
    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'registrarPostTypes'));
        add_action('init', array($this, 'registrarTaxonomias'));
        add_filter('post_updated_messages', array($this, 'mensagensPersonalizadas'));
        add_filter('bulk_post_updated_messages', array($this, 'mensagensBulkPersonalizadas'), 10, 2);
    }

    public function registrarPostTypes() {
        register_post_type('bc_documento', array(
            'labels' => array(
                'name'                  => __('Documentos', 'base-conhecimento'),
                'singular_name'         => __('Documento', 'base-conhecimento'),
                'menu_name'             => __('Base de Conhecimento', 'base-conhecimento'),
                'name_admin_bar'        => __('Documento', 'base-conhecimento'),
                'add_new'               => __('Adicionar Novo', 'base-conhecimento'),
                'add_new_item'          => __('Adicionar Novo Documento', 'base-conhecimento'),
                'new_item'              => __('Novo Documento', 'base-conhecimento'),
                'edit_item'             => __('Editar Documento', 'base-conhecimento'),
                'view_item'             => __('Ver Documento', 'base-conhecimento'),
                'all_items'             => __('Todos os Documentos', 'base-conhecimento'),
                'search_items'          => __('Pesquisar Documentos', 'base-conhecimento'),
                'parent_item_colon'     => __('Documento Pai:', 'base-conhecimento'),
                'not_found'             => __('Nenhum documento encontrado.', 'base-conhecimento'),
                'not_found_in_trash'    => __('Nenhum documento encontrado na lixeira.', 'base-conhecimento')
            ),
            'public'                => true,
            'publicly_queryable'    => true, // Adicionado para garantir URLs amigáveis
            'query_var'             => true, // Adicionado para facilitar a consulta via query var
            'hierarchical'          => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'show_in_nav_menus'     => true,
            'show_in_admin_bar'     => true,
            'menu_position'         => 25,
            'menu_icon'             => 'dashicons-book-alt',
            'supports'              => array(
                'title',
                'editor',
                'thumbnail',
                'excerpt',
                'revisions',
                'page-attributes',
                'custom-fields'
            ),
            'has_archive'           => true,
            'rewrite'               => array(
                'slug'                  => 'documentacao',
                'with_front'            => false,
                'hierarchical'          => false // Alterado para false para evitar conflito na estrutura de URLs e erros 404
            ),
            'capabilities'          => array(
                'edit_post'             => 'edit_document',
                'read_post'             => 'read_document',
                'delete_post'           => 'delete_document',
                'edit_posts'            => 'edit_documents',
                'edit_others_posts'     => 'edit_others_documents',
                'publish_posts'         => 'publish_documents',
                'read_private_posts'    => 'read_private_documents'
            ),
            'show_in_rest'          => true,
            'template'              => array(
                array('core/heading', array(
                    'level' => 1
                )),
                array('core/paragraph', array(
                    'placeholder' => __('Comece a escrever seu documento aqui...', 'base-conhecimento')
                ))
            ),
            'template_lock'         => false
        ));
    }

    public function registrarTaxonomias() {
        register_taxonomy('bc_pasta', 'bc_documento', array(
            'labels' => array(
                'name'                       => __('Categorias', 'base-conhecimento'),
                'singular_name'              => __('Categoria', 'base-conhecimento'),
                'search_items'               => __('Pesquisar Categorias', 'base-conhecimento'),
                'popular_items'              => __('Categorias Populares', 'base-conhecimento'),
                'all_items'                  => __('Todas as Categorias', 'base-conhecimento'),
                'parent_item'                => __('Categoria Pai', 'base-conhecimento'),
                'parent_item_colon'          => __('Categoria Pai:', 'base-conhecimento'),
                'edit_item'                  => __('Editar Categoria', 'base-conhecimento'),
                'update_item'                => __('Atualizar Categoria', 'base-conhecimento'),
                'add_new_item'               => __('Adicionar Nova Categoria', 'base-conhecimento'),
                'new_item_name'              => __('Nome da Nova Categoria', 'base-conhecimento'),
                'separate_items_with_commas' => __('Separe as categorias com vírgulas', 'base-conhecimento'),
                'add_or_remove_items'        => __('Adicionar ou remover categorias', 'base-conhecimento'),
                'choose_from_most_used'      => __('Escolher entre as categorias mais usadas', 'base-conhecimento'),
                'menu_name'                  => __('Categorias', 'base-conhecimento'),
            ),
            'hierarchical'               => true,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => false,
            'rewrite'                    => array(
                'slug'                       => 'pasta',
                'with_front'                 => false,
                'hierarchical'               => true
            ),
            'show_in_rest'               => true,
            'capabilities'               => array(
                'manage_terms'               => 'manage_document_categories',
                'edit_terms'                 => 'edit_document_categories',
                'delete_terms'               => 'delete_document_categories',
                'assign_terms'               => 'assign_document_categories'
            )
        ));
    }

    public function mensagensPersonalizadas($messages) {
        global $post;

        $messages['bc_documento'] = array(
            0  => '',
            1  => __('Documento atualizado.', 'base-conhecimento'),
            2  => __('Campo personalizado atualizado.', 'base-conhecimento'),
            3  => __('Campo personalizado deletado.', 'base-conhecimento'),
            4  => __('Documento atualizado.', 'base-conhecimento'),
            5  => isset($_GET['revision']) ? sprintf(
                __('Documento restaurado para a revisão de %s', 'base-conhecimento'),
                wp_post_revision_title((int) $_GET['revision'], false)
            ) : false,
            6  => __('Documento publicado.', 'base-conhecimento'),
            7  => __('Documento salvo.', 'base-conhecimento'),
            8  => __('Documento enviado.', 'base-conhecimento'),
            9  => sprintf(
                __('Documento agendado para: <strong>%1$s</strong>.', 'base-conhecimento'),
                date_i18n(__('M j, Y @ G:i', 'base-conhecimento'), strtotime($post->post_date))
            ),
            10 => __('Rascunho do documento atualizado.', 'base-conhecimento')
        );

        return $messages;
    }

    public function mensagensBulkPersonalizadas($bulk_messages, $bulk_counts) {
        $bulk_messages['bc_documento'] = array(
            'updated'   => _n(
                '%s documento atualizado.',
                '%s documentos atualizados.',
                $bulk_counts['updated'],
                'base-conhecimento'
            ),
            'locked'    => _n(
                '%s documento não atualizado, alguém está editando.',
                '%s documentos não atualizados, alguém está editando.',
                $bulk_counts['locked'],
                'base-conhecimento'
            ),
            'deleted'   => _n(
                '%s documento excluído permanentemente.',
                '%s documentos excluídos permanentemente.',
                $bulk_counts['deleted'],
                'base-conhecimento'
            ),
            'trashed'   => _n(
                '%s documento movido para a lixeira.',
                '%s documentos movidos para a lixeira.',
                $bulk_counts['trashed'],
                'base-conhecimento'
            ),
            'untrashed' => _n(
                '%s documento restaurado da lixeira.',
                '%s documentos restaurados da lixeira.',
                $bulk_counts['untrashed'],
                'base-conhecimento'
            ),
        );

        return $bulk_messages;
    }
}

// Inicializar a classe
BC_Post_Types::getInstance();
