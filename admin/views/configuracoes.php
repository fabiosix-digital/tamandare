<?php
if (!defined('ABSPATH')) exit;

$opcoes = get_option('bc_configuracoes');
?>

<div class="wrap bc-configuracoes-wrap">
    <h1><?php _e('Configurações - Base de Conhecimento', 'base-conhecimento'); ?></h1>

    <form method="post" action="options.php" class="bc-form">
        <?php settings_fields('bc_opcoes'); ?>

        <div class="bc-configuracoes-grid">
            <!-- Configurações Gerais -->
            <div class="bc-card">
                <h2 class="bc-card-header"><?php _e('Configurações Gerais', 'base-conhecimento'); ?></h2>
                
                <div class="bc-card-body">
                    <!-- Título da Página Inicial -->
                    <div class="bc-campo-grupo">
                        <label class="bc-label" for="bc_titulo_home">
                            <?php _e('Título da Página Inicial', 'base-conhecimento'); ?>
                        </label>
                        <input type="text" 
                               id="bc_titulo_home" 
                               name="bc_configuracoes[titulo_home]" 
                               value="<?php echo esc_attr($opcoes['titulo_home'] ?? 'Como podemos te ajudar?'); ?>" 
                               class="bc-input regular-text">
                        <p class="description">
                            <?php _e('Título exibido no topo da página inicial.', 'base-conhecimento'); ?>
                        </p>
                    </div>

                    <!-- Descrição da Página Inicial -->
                    <div class="bc-campo-grupo">
                        <label class="bc-label" for="bc_descricao_home">
                            <?php _e('Descrição da Página Inicial', 'base-conhecimento'); ?>
                        </label>
                        <textarea id="bc_descricao_home" 
                                  name="bc_configuracoes[descricao_home]" 
                                  class="bc-textarea large-text" 
                                  rows="3"><?php echo esc_textarea($opcoes['descricao_home'] ?? ''); ?></textarea>
                        <p class="description">
                            <?php _e('Breve descrição exibida abaixo do título.', 'base-conhecimento'); ?>
                        </p>
                    </div>

                    <!-- Logotipo -->
                    <div class="bc-campo-grupo">
                        <label class="bc-label"><?php _e('Logotipo', 'base-conhecimento'); ?></label>
                        <div class="bc-upload-logo">
                            <?php
                            $logo_id = $opcoes['logo_id'] ?? '';
                            $logo_url = '';
                            if ($logo_id) {
                                $logo_url = wp_get_attachment_image_url($logo_id, 'full');
                            }
                            ?>
                            <div class="bc-logo-preview">
                                <?php if ($logo_url): ?>
                                    <img src="<?php echo esc_url($logo_url); ?>" alt="Logo">
                                <?php endif; ?>
                            </div>
                            <input type="hidden" 
                                   name="bc_configuracoes[logo_id]" 
                                   id="bc_logo_id" 
                                   value="<?php echo esc_attr($logo_id); ?>">
                            <button type="button" class="button bc-upload-btn">
                                <?php _e('Escolher Logo', 'base-conhecimento'); ?>
                            </button>
                            <button type="button" class="button bc-remover-logo" <?php echo !$logo_id ? 'style="display:none;"' : ''; ?>>
                                <?php _e('Remover', 'base-conhecimento'); ?>
                            </button>
                        </div>
                    </div>

                    <!-- Cores -->
                    <div class="bc-campo-grupo">
                        <label class="bc-label"><?php _e('Cores', 'base-conhecimento'); ?></label>
                        <div class="bc-cores-grid">
                            <div class="bc-cor-item">
                                <label><?php _e('Cor Principal', 'base-conhecimento'); ?></label>
                                <input type="color" 
                                       name="bc_configuracoes[cor_principal]" 
                                       value="<?php echo esc_attr($opcoes['cor_principal'] ?? '#6B46C1'); ?>">
                            </div>
                            <div class="bc-cor-item">
                                <label><?php _e('Cor Secundária', 'base-conhecimento'); ?></label>
                                <input type="color" 
                                       name="bc_configuracoes[cor_secundaria]" 
                                       value="<?php echo esc_attr($opcoes['cor_secundaria'] ?? '#805AD5'); ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Configurações de Exibição -->
            <div class="bc-card">
                <h2 class="bc-card-header"><?php _e('Configurações de Exibição', 'base-conhecimento'); ?></h2>
                
                <div class="bc-card-body">
                    <!-- Itens por Página -->
                    <div class="bc-campo-grupo">
                        <label class="bc-label" for="bc_itens_pagina">
                            <?php _e('Itens por Página', 'base-conhecimento'); ?>
                        </label>
                        <input type="number" 
                               id="bc_itens_pagina" 
                               name="bc_configuracoes[itens_pagina]" 
                               value="<?php echo intval($opcoes['itens_pagina'] ?? 12); ?>" 
                               min="1" 
                               max="100" 
                               class="small-text">
                    </div>

                    <!-- Layout da Grade -->
                    <div class="bc-campo-grupo">
                        <label class="bc-label"><?php _e('Layout da Grade', 'base-conhecimento'); ?></label>
                        <select name="bc_configuracoes[layout_grade]" class="bc-select">
                            <option value="3" <?php selected($opcoes['layout_grade'] ?? '3', '3'); ?>>
                                <?php _e('3 colunas', 'base-conhecimento'); ?>
                            </option>
                            <option value="2" <?php selected($opcoes['layout_grade'] ?? '3', '2'); ?>>
                                <?php _e('2 colunas', 'base-conhecimento'); ?>
                            </option>
                            <option value="4" <?php selected($opcoes['layout_grade'] ?? '3', '4'); ?>>
                                <?php _e('4 colunas', 'base-conhecimento'); ?>
                            </option>
                        </select>
                    </div>

                    <!-- Opções de Exibição -->
                    <div class="bc-campo-grupo">
                        <label class="bc-label"><?php _e('Opções de Exibição', 'base-conhecimento'); ?></label>
                        <div class="bc-checkbox-list">
                            <label>
                                <input type="checkbox" 
                                       name="bc_configuracoes[mostrar_autor]" 
                                       value="1" 
                                       <?php checked($opcoes['mostrar_autor'] ?? '', '1'); ?>>
                                <?php _e('Mostrar autor', 'base-conhecimento'); ?>
                            </label>
                            <label>
                                <input type="checkbox" 
                                       name="bc_configuracoes[mostrar_data]" 
                                       value="1" 
                                       <?php checked($opcoes['mostrar_data'] ?? '', '1'); ?>>
                                <?php _e('Mostrar data', 'base-conhecimento'); ?>
                            </label>
                            <label>
                                <input type="checkbox" 
                                       name="bc_configuracoes[mostrar_views]" 
                                       value="1" 
                                       <?php checked($opcoes['mostrar_views'] ?? '', '1'); ?>>
                                <?php _e('Mostrar visualizações', 'base-conhecimento'); ?>
                            </label>
                            <label>
                                <input type="checkbox" 
                                       name="bc_configuracoes[tema_escuro]" 
                                       value="1" 
                                       <?php checked($opcoes['tema_escuro'] ?? '', '1'); ?>>
                                <?php _e('Habilitar tema escuro', 'base-conhecimento'); ?>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Configurações de SEO -->
            <div class="bc-card">
                <h2 class="bc-card-header"><?php _e('Configurações de SEO', 'base-conhecimento'); ?></h2>
                
                <div class="bc-card-body">
                    <!-- Título SEO -->
                    <div class="bc-campo-grupo">
                        <label class="bc-label" for="bc_seo_titulo">
                            <?php _e('Título SEO', 'base-conhecimento'); ?>
                        </label>
                        <input type="text" 
                               id="bc_seo_titulo" 
                               name="bc_configuracoes[seo_titulo]" 
                               value="<?php echo esc_attr($opcoes['seo_titulo'] ?? ''); ?>" 
                               class="regular-text">
                        <p class="description">
                            <?php _e('Título para SEO da página inicial da base de conhecimento.', 'base-conhecimento'); ?>
                        </p>
                    </div>

                    <!-- Descrição SEO -->
                    <div class="bc-campo-grupo">
                        <label class="bc-label" for="bc_seo_descricao">
                            <?php _e('Descrição SEO', 'base-conhecimento'); ?>
                        </label>
                        <textarea id="bc_seo_descricao" 
                                  name="bc_configuracoes[seo_descricao]" 
                                  class="large-text" 
                                  rows="3"><?php echo esc_textarea($opcoes['seo_descricao'] ?? ''); ?></textarea>
                        <p class="description">
                            <?php _e('Descrição para SEO da página inicial da base de conhecimento.', 'base-conhecimento'); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <?php submit_button(); ?>
    </form>
</div>