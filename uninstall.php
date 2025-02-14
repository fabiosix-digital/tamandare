<?php
// Se o WordPress não estiver chamando este arquivo, aborta
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

// Remove todos os posts do tipo documento
$documentos = get_posts(array(
    'post_type' => 'bc_documento',
    'numberposts' => -1,
    'post_status' => 'any'
));

foreach ($documentos as $documento) {
    wp_delete_post($documento->ID, true);
}

// Remove todas as taxonomias
$termos = get_terms(array(
    'taxonomy' => 'bc_pasta',
    'hide_empty' => false
));

foreach ($termos as $termo) {
    wp_delete_term($termo->term_id, 'bc_pasta');
}

// Remove tabela de estatísticas
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}bc_estatisticas");

// Remove opções do plugin
delete_option('bc_configuracoes');
delete_option('bc_db_version');

// Limpa qualquer cache transiente
delete_transient('bc_cache_estatisticas');
delete_transient('bc_cache_documentos_populares');