<?php
if (!defined('ABSPATH')) exit;

class BC_Helpers {
    private static $instance = null;
    
    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Construtor vazio pois esta classe é apenas para métodos auxiliares
    }

    /**
     * Formata o número de visualizações para exibição
     */
    public static function formatarVisualizacoes($numero) {
        if ($numero >= 1000000) {
            return round($numero / 1000000, 1) . 'M';
        } elseif ($numero >= 1000) {
            return round($numero / 1000, 1) . 'K';
        }
        return $numero;
    }

    /**
     * Calcula o tempo estimado de leitura com base no conteúdo
     */
    public static function calcularTempoLeitura($conteudo) {
        $palavras = str_word_count(strip_tags($conteudo));
        $minutos = ceil($palavras / 200); // Média de 200 palavras por minuto
        return $minutos;
    }

    /**
     * Formata o tempo de leitura (em segundos) para exibição
     */
    public static function formatarTempoLeitura($segundos) {
        if ($segundos < 3600) {
            return floor($segundos / 60) . ' min';
        }
        return floor($segundos / 3600) . ' hrs';
    }

    /**
     * Gera um slug único para pastas
     */
    public static function gerarSlugUnico($titulo, $term_id = 0) {
        $slug = sanitize_title($titulo);
        $original_slug = $slug;
        $contador = 1;
        
        while (term_exists($slug, 'bc_pasta')) {
            $term = get_term_by('slug', $slug, 'bc_pasta');
            if ($term_id && $term->term_id == $term_id) {
                break;
            }
            $slug = $original_slug . '-' . $contador;
            $contador++;
        }
        
        return $slug;
    }

    /**
     * Obtém o caminho completo da pasta
     */
    public static function obterCaminhoPasta($term_id) {
        $term = get_term($term_id, 'bc_pasta');
        if (is_wp_error($term)) {
            return '';
        }

        $caminho = array($term->name);
        $parent_id = $term->parent;

        while ($parent_id) {
            $parent = get_term($parent_id, 'bc_pasta');
            if (is_wp_error($parent)) {
                break;
            }
            array_unshift($caminho, $parent->name);
            $parent_id = $parent->parent;
        }

        return implode(' > ', $caminho);
    }

    /**
     * Obtém a hierarquia completa de pastas
     */
    public static function obterHierarquiaPastas() {
        $pastas = get_terms(array(
            'taxonomy' => 'bc_pasta',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ));

        $hierarquia = array();
        
        foreach ($pastas as $pasta) {
            if ($pasta->parent == 0) {
                $hierarquia[$pasta->term_id] = array(
                    'nome' => $pasta->name,
                    'filhos' => self::obterPastasFilhas($pastas, $pasta->term_id)
                );
            }
        }

        return $hierarquia;
    }

    /**
     * Função recursiva para obter pastas filhas
     */
    private static function obterPastasFilhas($pastas, $parent_id) {
        $filhos = array();
        
        foreach ($pastas as $pasta) {
            if ($pasta->parent == $parent_id) {
                $filhos[$pasta->term_id] = array(
                    'nome' => $pasta->name,
                    'filhos' => self::obterPastasFilhas($pastas, $pasta->term_id)
                );
            }
        }

        return $filhos;
    }

    /**
     * Limpa o cache de um documento específico
     */
    public static function limparCacheDocumento($post_id) {
        wp_cache_delete($post_id, 'bc_documento_stats');
        wp_cache_delete('bc_ultimos_documentos');
        wp_cache_delete('bc_documentos_populares');
    }

    /**
     * Verifica se um documento está favoritado para o usuário atual
     */
    public static function documentoFavoritado($post_id) {
        $user_id = get_current_user_id();
        if (!$user_id) return false;

        $favoritos = get_user_meta($user_id, 'bc_documentos_favoritos', true);
        if (!is_array($favoritos)) $favoritos = array();

        return in_array($post_id, $favoritos);
    }

    /**
     * Adiciona ou remove um documento dos favoritos
     */
    public static function toggleFavorito($post_id) {
        $user_id = get_current_user_id();
        if (!$user_id) return false;

        $favoritos = get_user_meta($user_id, 'bc_documentos_favoritos', true);
        if (!is_array($favoritos)) $favoritos = array();

        $index = array_search($post_id, $favoritos);
        if ($index !== false) {
            unset($favoritos[$index]);
            $favoritos = array_values($favoritos);
            $acao = 'removido';
        } else {
            $favoritos[] = $post_id;
            $acao = 'adicionado';
        }

        update_user_meta($user_id, 'bc_documentos_favoritos', $favoritos);
        return $acao;
    }

    /**
     * Obtém documentos relacionados
     */
    public static function obterDocumentosRelacionados($post_id, $limite = 5) {
        $categorias = wp_get_post_terms($post_id, 'bc_pasta', array('fields' => 'ids'));
        
        if (empty($categorias)) {
            return array();
        }

        $args = array(
            'post_type' => 'bc_documento',
            'post_status' => 'publish',
            'posts_per_page' => $limite,
            'post__not_in' => array($post_id),
            'tax_query' => array(
                array(
                    'taxonomy' => 'bc_pasta',
                    'field' => 'id',
                    'terms' => $categorias
                )
            ),
            'orderby' => 'rand'
        );

        $query = new WP_Query($args);
        return $query->posts;
    }

    /**
     * Gera URL segura para compartilhamento
     */
    public static function gerarUrlCompartilhamento($post_id) {
        $hash = wp_hash($post_id . get_current_user_id() . time());
        update_post_meta($post_id, 'bc_hash_compartilhamento', $hash);
        
        return add_query_arg(array(
            'bc_share' => $hash
        ), get_permalink($post_id));
    }

    /**
     * Verifica se uma URL de compartilhamento é válida
     */
    public static function validarUrlCompartilhamento($post_id, $hash) {
        $hash_salvo = get_post_meta($post_id, 'bc_hash_compartilhamento', true);
        return $hash === $hash_salvo;
    }

    /**
     * Formata data para exibição
     */
    public static function formatarData($data) {
        $timestamp = strtotime($data);
        $diferenca = time() - $timestamp;
        
        if ($diferenca < 60) {
            return __('Agora mesmo', 'base-conhecimento');
        } elseif ($diferenca < 3600) {
            $minutos = floor($diferenca / 60);
            return sprintf(_n('há %d minuto', 'há %d minutos', $minutos, 'base-conhecimento'), $minutos);
        } elseif ($diferenca < 86400) {
            $horas = floor($diferenca / 3600);
            return sprintf(_n('há %d hora', 'há %d horas', $horas, 'base-conhecimento'), $horas);
        } elseif ($diferenca < 604800) {
            $dias = floor($diferenca / 86400);
            return sprintf(_n('há %d dia', 'há %d dias', $dias, 'base-conhecimento'), $dias);
        } else {
            return date_i18n(get_option('date_format'), $timestamp);
        }
    }
    
    /**
     * Gera o breadcrumb dinâmico para um documento
     */
    public static function breadcrumb($post_id) {
        $breadcrumb = array();
        // Link para a base de conhecimento
        $breadcrumb[] = '<a href="' . home_url() . '">' . __('Base de Conhecimento', 'base-conhecimento') . '</a>';
        
        // Obter termos da taxonomia bc_pasta associados ao post
        $terms = wp_get_post_terms($post_id, 'bc_pasta');
        if (!empty($terms) && !is_wp_error($terms)) {
            // Usar o primeiro termo para montar o breadcrumb
            $term = $terms[0];
            // Obter ancestrais do termo
            $ancestors = get_ancestors($term->term_id, 'bc_pasta');
            $ancestors = array_reverse($ancestors);
            foreach ($ancestors as $ancestor_id) {
                $ancestor = get_term($ancestor_id, 'bc_pasta');
                if (!is_wp_error($ancestor) && $ancestor) {
                    $breadcrumb[] = '<a href="' . get_term_link($ancestor) . '">' . $ancestor->name . '</a>';
                }
            }
            // Adicionar o termo atual
            $breadcrumb[] = '<a href="' . get_term_link($term) . '">' . $term->name . '</a>';
        }
        // Adicionar o título do post
        $breadcrumb[] = get_the_title($post_id);
        
        return implode(' > ', $breadcrumb);
    }
}

// Inicializar a classe
BC_Helpers::getInstance();
