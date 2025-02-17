(function($) {
    'use strict';

    // Objeto principal do admin
    const BCAdmin = {
        init: function() {
            this.initDragAndDrop();
            this.initFeedback();
            this.initIconPicker();
            this.initPreview();
            this.initSearch();
            this.initSortable();
            this.initTabs();
            this.initTooltips();
        },

        // Inicializa Drag and Drop para reordenação
        initDragAndDrop: function() {
            const $lista = $('.bc-documentos-lista');
            if ($lista.length) {
                $lista.sortable({
                    handle: '.bc-drag-handle',
                    placeholder: 'bc-documento-placeholder',
                    axis: 'y',
                    update: function(event, ui) {
                        const ordem = [];
                        $lista.find('.bc-documento-item').each(function(index) {
                            ordem.push($(this).data('id'));
                        });

                        $.ajax({
                            url: bcAdmin.ajaxUrl,
                            type: 'POST',
                            data: {
                                action: 'bc_reordenar_documentos',
                                ordem: ordem,
                                nonce: bcAdmin.nonce
                            },
                            beforeSend: function() {
                                BCAdmin.showMessage('info', bcAdmin.strings.ordenando);
                            },
                            success: function(response) {
                                if (response.success) {
                                    BCAdmin.showMessage('success', bcAdmin.strings.ordenadoSucesso);
                                } else {
                                    BCAdmin.showMessage('error', bcAdmin.strings.ordenadoErro);
                                }
                            }
                        });
                    }
                });
            }
        },

        // Inicializa sistema de feedback
        initFeedback: function() {
            $(document).on('click', '.bc-btn-feedback', function(e) {
                e.preventDefault();
                const $btn = $(this);
                const tipo = $btn.data('feedback');
                const postId = $btn.data('post-id');

                $.ajax({
                    url: bcAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'bc_registrar_feedback',
                        tipo: tipo,
                        post_id: postId,
                        nonce: bcAdmin.nonce
                    },
                    beforeSend: function() {
                        $btn.prop('disabled', true);
                    },
                    success: function(response) {
                        if (response.success) {
                            const $count = $btn.find('.bc-feedback-count');
                            const currentCount = parseInt($count.text());
                            $count.text(currentCount + 1);
                            
                            $btn.addClass('bc-feedback-active');
                            $btn.siblings().removeClass('bc-feedback-active');
                        }
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                    }
                });
            });
        },

        // Inicializa seletor de ícones
        initIconPicker: function() {
            const $iconPicker = $('.bc-icon-picker');
            if ($iconPicker.length) {
                $iconPicker.each(function() {
                    const $this = $(this);
                    const $input = $this.find('.bc-icon-input');
                    const $preview = $this.find('.bc-icon-preview');
                    const $modal = $('#bc-icon-modal');

                    $this.on('click', '.bc-icon-btn', function() {
                        $modal.show();
                    });

                    $modal.on('click', '.bc-icon-item', function() {
                        const icon = $(this).data('icon');
                        $input.val(icon);
                        $preview.html(`<i class="${icon}"></i>`);
                        $modal.hide();
                    });

                    $(document).on('click', '.bc-modal-close', function() {
                        $modal.hide();
                    });
                });
            }
        },

        // Inicializa preview em tempo real
        initPreview: function() {
            const $preview = $('.bc-preview-frame');
            if ($preview.length) {
                let timeoutId;
                
                $('#content').on('input', function() {
                    clearTimeout(timeoutId);
                    timeoutId = setTimeout(function() {
                        const content = $(this).val();
                        $preview.contents().find('#bc-preview-content').html(content);
                    }, 500);
                });
            }
        },

        // Inicializa busca em tempo real
        initSearch: function() {
            const $search = $('.bc-search-input');
            if ($search.length) {
                let timeoutId;

                $search.on('input', function() {
                    const $this = $(this);
                    const $results = $('.bc-search-results');

                    clearTimeout(timeoutId);
                    timeoutId = setTimeout(function() {
                        const term = $this.val();
                        if (term.length > 2) {
                            $.ajax({
                                url: bcAdmin.ajaxUrl,
                                type: 'POST',
                                data: {
                                    action: 'bc_buscar_documentos',
                                    termo: term,
                                    nonce: bcAdmin.nonce
                                },
                                beforeSend: function() {
                                    $results.html('<div class="bc-loading">Buscando...</div>');
                                },
                                success: function(response) {
                                    if (response.success) {
                                        $results.html(response.data.html);
                                    } else {
                                        $results.html('<div class="bc-no-results">Nenhum resultado encontrado</div>');
                                    }
                                }
                            });
                        } else {
                            $results.empty();
                        }
                    }, 500);
                });
            }
        },

        // Inicializa ordenação de itens
        initSortable: function() {
            $('.bc-sortable').sortable({
                handle: '.bc-sort-handle',
                update: function() {
                    BCAdmin.updateOrder($(this));
                }
            });
        },

        // Inicializa sistema de abas
        initTabs: function() {
            $('.bc-tabs').on('click', '.bc-tab', function(e) {
                e.preventDefault();
                const $this = $(this);
                const target = $this.data('tab');

                $this.addClass('active').siblings().removeClass('active');
                $(target).addClass('active').siblings().removeClass('active');
            });
        },

        // Inicializa tooltips
        initTooltips: function() {
            $('[data-tooltip]').each(function() {
                const $this = $(this);
                const text = $this.data('tooltip');

                $this.append(`<div class="bc-tooltip">${text}</div>`);
            });
        },

        // Atualiza ordem dos itens
        updateOrder: function($container) {
            const items = [];
            $container.find('.bc-sortable-item').each(function(index) {
                items.push({
                    id: $(this).data('id'),
                    order: index
                });
            });

            $.ajax({
                url: bcAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'bc_atualizar_ordem',
                    items: items,
                    nonce: bcAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        BCAdmin.showMessage('success', bcAdmin.strings.ordemAtualizada);
                    } else {
                        BCAdmin.showMessage('error', bcAdmin.strings.erroAtualizarOrdem);
                    }
                }
            });
        },

        // Exibe mensagens
        showMessage: function(type, message) {
            const $message = $(`<div class="bc-message bc-message-${type}">${message}</div>`);
            $('.bc-messages').append($message);

            setTimeout(function() {
                $message.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        }
    };

    // Inicializa quando o documento estiver pronto
    $(document).ready(function() {
        BCAdmin.init();
    });

})(jQuery);