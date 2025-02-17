<?php
if (!defined('ABSPATH')) exit;

class BC_Taxonomias {
    private static $instance = null;
    
    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'registrarTaxonomias'));
        add_action('bc_categoria_add_form_fields', array($this, 'adicionarCamposCategoria'));
        add_action('bc_categoria_edit_form_fields', array($this, 'editarCamposCategoria'));
        add_action('created_bc_categoria', array($this, 'salvarCamposCategoria'));
        add_action('edited_bc_categoria', array($this, 'salvarCamposCategoria'));
        add_filter('manage_edit-bc_categoria_columns', array($this, 'gerenciarColunasCategoria'));
        add_filter('manage_bc_categoria_custom_column', array($this, 'gerenciarConteudoColunasCategoria'), 10, 3);
    }

    public function registrarTaxonomias() {
        $labels = array(
            'name'                       => __('Pastas', 'base-conhecimento'),
            'singular_name'              => __('Pasta', 'base-conhecimento'),
            'menu_name'                  => __('Pastas', 'base-conhecimento'),
            'all_items'                  => __('Todas as Pastas', 'base-conhecimento'),
            'parent_item'                => __('Pasta Pai', 'base-conhecimento'),
            'parent_item_colon'          => __('Pasta Pai:', 'base-conhecimento'),
            'new_item_name'              => __('Nova Pasta', 'base-conhecimento'),
            'add_new_item'               => __('Adicionar Nova Pasta', 'base-conhecimento'),
            'edit_item'                  => __('Editar Pasta', 'base-conhecimento'),
            'update_item'                => __('Atualizar Pasta', 'base-conhecimento'),
            'view_item'                  => __('Visualizar Pasta', 'base-conhecimento'),
            'search_items'               => __('Procurar Pasta', 'base-conhecimento'),
            'not_found'                  => __('Não encontrado', 'base-conhecimento'),
            'no_terms'                   => __('Sem pastas', 'base-conhecimento'),
            'items_list'                 => __('Lista de Pastas', 'base-conhecimento'),
            'items_list_navigation'      => __('Navegação da lista de pastas', 'base-conhecimento'),
        );

        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => true,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => false,
            'show_in_rest'               => true,
            'rest_base'                  => 'pastas',
            'rewrite'                    => array(
                'slug'                      => 'pasta',
                'with_front'                => false,
                'hierarchical'              => true,
            ),
        );

        register_taxonomy('bc_pasta', array('bc_documento'), $args);
    }

    public function adicionarCamposCategoria() {
        ?>
        <div class="form-field">
            <label for="bc_icone"><?php _e('Ícone', 'base-conhecimento'); ?></label>
            <input type="text" name="bc_icone" id="bc_icone" value="" class="regular-text">
            <p class="description"><?php _e('Insira a classe do ícone (Ex: fas fa-folder)', 'base-conhecimento'); ?></p>
        </div>

        <div class="form-field">
            <label for="bc_cor"><?php _e('Cor', 'base-conhecimento'); ?></label>
            <input type="color" name="bc_cor" id="bc_cor" value="#6B7280">
            <p class="description"><?php _e('Selecione a cor para esta pasta', 'base-conhecimento'); ?></p>
        </div>

        <div class="form-field">
            <label for="bc_descricao_curta"><?php _e('Descrição Curta', 'base-conhecimento'); ?></label>
            <textarea name="bc_descricao_curta" id="bc_descricao_curta" rows="3"></textarea>
            <p class="description"><?php _e('Uma breve descrição que aparecerá nos cards da página inicial', 'base-conhecimento'); ?></p>
        </div>
        <?php
    }

    public function editarCamposCategoria($term) {
        $icone = get_term_meta($term->term_id, 'bc_icone', true);
        $cor = get_term_meta($term->term_id, 'bc_cor', true);
        $descricao_curta = get_term_meta($term->term_id, 'bc_descricao_curta', true);
        ?>
        <tr class="form-field">
            <th scope="row">
                <label for="bc_icone"><?php _e('Ícone', 'base-conhecimento'); ?></label>
            </th>
            <td>
                <input type="text" name="bc_icone" id="bc_icone" value="<?php echo esc_attr($icone); ?>" class="regular-text">
                <p class="description"><?php _e('Insira a classe do ícone (Ex: fas fa-folder)', 'base-conhecimento'); ?></p>
            </td>
        </tr>

        <tr class="form-field">
            <th scope="row">
                <label for="bc_cor"><?php _e('Cor', 'base-conhecimento'); ?></label>
            </th>
            <td>
                <input type="color" name="bc_cor" id="bc_cor" value="<?php echo esc_attr($cor); ?>">
                <p class="description"><?php _e('Selecione a cor para esta pasta', 'base-conhecimento'); ?></p>
            </td>
        </tr>

        <tr class="form-field">
            <th scope="row">
                <label for="bc_descricao_curta"><?php _e('Descrição Curta', 'base-conhecimento'); ?></label>
            </th>
            <td>
                <textarea name="bc_descricao_curta" id="bc_descricao_curta" rows="3"><?php echo esc_textarea($descricao_curta); ?></textarea>
                <p class="description"><?php _e('Uma breve descrição que aparecerá nos cards da página inicial', 'base-conhecimento'); ?></p>
            </td>
        </tr>
        <?php
    }

    public function salvarCamposCategoria($term_id) {
        if (isset($_POST['bc_icone'])) {
            update_term_meta($term_id, 'bc_icone', sanitize_text_field($_POST['bc_icone']));
        }
        
        if (isset($_POST['bc_cor'])) {
            update_term_meta($term_id, 'bc_cor', sanitize_hex_color($_POST['bc_cor']));
        }
        
        if (isset($_POST['bc_descricao_curta'])) {
            update_term_meta($term_id, 'bc_descricao_curta', sanitize_textarea_field($_POST['bc_descricao_curta']));
        }
    }

    public function gerenciarColunasCategoria($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['icone'] = __('Ícone', 'base-conhecimento');
        $new_columns['name'] = $columns['name'];
        $new_columns['description'] = $columns['description'];
        $new_columns['slug'] = $columns['slug'];
        $new_columns['posts'] = $columns['posts'];

        return $new_columns;
    }

    public function gerenciarConteudoColunasCategoria($content, $column_name, $term_id) {
        if ($column_name == 'icone') {
            $icone = get_term_meta($term_id, 'bc_icone', true);
            $cor = get_term_meta($term_id, 'bc_cor', true);
            if ($icone) {
                $content = '<i class="' . esc_attr($icone) . '" style="color: ' . esc_attr($cor) . ';"></i>';
            }
        }
        return $content;
    }
}

// Inicializar a classe
BC_Taxonomias::getInstance();
