(function($) {
    'use strict';

    const BCPublic = {
        init: function() {
            this.initBusca();
            this.initMenuLateral();
            this.initTemaEscuro();
            this.initCarregamentoAjax();
            this.initFeedback();
            this.initHistoryState();
            this.registrarVisualizacao();
        },

        // Inicializa busca em tempo real
        initBusca: function() {
            let timeoutId;
            const $input = $('.bc-busca-input');
            const $resultados = $('.bc-resultados-busca');

            $input.on('input', function() {
                const termo = $(this).val();
                clearTimeout(timeoutId);

                if (termo.length > 2) {
                    timeoutId = setTimeout(function() {
                        $.ajax({
                            url: bcPublicData.ajaxUrl,
                            type: 'POST',
                            data: {
                                action: 'bc_busca_ajax',
                                termo: termo,
                                nonce: bcPublicData.nonce
                            },
                            beforeSend: function() {
                                $resultados.html('<div class="bc-loading">Buscando...</div>');
                            },
                            success: function(response) {
                                if (response.success) {
                                    let html = '<div class="bc-resultados-lista">';
                                    response.data.forEach(function(item) {
                                        const icon = item.type === 'folder' ? 'folder' : 'file-text-o';
                                        html += `
                                            <div class="bc-resultado-item">
                                                <i class="fa fa-${icon}"></i>
                                                <a href="${item.link}" data-carregar-artigo="${item.id}" class="bc-resultado-link">
                                                    ${item.titulo}
                                                </a>
                                                ${item.pasta ? `<span class="bc-resultado-pasta">${item.pasta}</span>` : ''}
                                            </div>
                                        `;
                                    });
                                    html += '</div>';
                                    $resultados.html(html);
                                } else {
                                    $resultados.html('<div class="bc-no-results">Nenhum resultado encontrado</div>');
                                }
                            }
                        });
                    }, 300);
                } else {
                    $resultados.empty();
                }
            });

            $(document).on('click', function(e) {
                if (!$(e.target).closest('.bc-busca').length) {
                    $resultados.empty();
                }
            });
        },

        // Inicializa menu lateral
        initMenuLateral: function() {
            const $menu = $('.bc-sidebar');
            const $toggle = $('.bc-menu-toggle');
            const $pastas = $('.bc-sidebar-folder');

            $toggle.on('click', function() {
                $menu.toggleClass('active');
                $('body').toggleClass('bc-menu-active');
            });

            $pastas.on('click', function() {
                const $this = $(this);
                const $submenu = $this.next('ul');
                
                if ($submenu.length) {
                    $this.toggleClass('active');
                    $submenu.slideToggle(200);
                }
            });

            $(document).on('click', function(e) {
                if ($('body').hasClass('bc-menu-active') && 
                    !$(e.target).closest('.bc-sidebar, .bc-menu-toggle').length) {
                    $menu.removeClass('active');
                    $('body').removeClass('bc-menu-active');
                }
            });
        },

        // Inicializa tema escuro
        initTemaEscuro: function() {
            const $toggle = $('.bc-tema-toggle');
            const isDark = localStorage.getItem('bc_tema_escuro') === 'true';

            if (isDark) {
                $('body').addClass('bc-tema-escuro');
                $toggle.addClass('active');
            }

            $toggle.on('click', function() {
                const $body = $('body');
                $body.toggleClass('bc-tema-escuro');
                $(this).toggleClass('active');
                localStorage.setItem('bc_tema_escuro', $body.hasClass('bc-tema-escuro'));
            });
        },

        // Inicializa carregamento AJAX dos artigos
        initCarregamentoAjax: function() {
            $(document).on('click', 'a[data-carregar-artigo]', function(e) {
                e.preventDefault();
                const $link = $(this);
                const postId = $link.data('carregar-artigo');
                let url = $link.attr('href');
                
                // Corrige a URL para o formato correto
                url = url.replace('/documentacao/', '/bc_documento/');

                $.ajax({
                    url: bcPublicData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'bc_carregar_artigo',
                        id: postId,
                        nonce: bcPublicData.nonce
                    },
                    beforeSend: function() {
                        $('.bc-single-content').html('<div class="bc-loading">Carregando artigo...</div>');
                    },
                    success: function(response) {
                        if (response.success) {
                            $('.bc-single-content').html(response.data.html);
                            
                            // Atualiza URL e histórico
                            if (history.pushState) {
                                history.pushState({ postId: postId }, '', url);
                            }

                            // Atualiza menu lateral
                            $('.bc-sidebar-menu a').removeClass('active');
                            $(`.bc-sidebar-menu a[data-carregar-artigo="${postId}"]`).addClass('active');

                            // Registra visualização
                            BCPublic.registrarVisualizacao(postId);

                            // Fecha menu mobile se estiver aberto
                            if ($('body').hasClass('bc-menu-active')) {
                                $('.bc-sidebar').removeClass('active');
                                $('body').removeClass('bc-menu-active');
                            }
                        }
                    },
                    error: function() {
                        window.location.href = url;
                    }
                });
            });
        },

        // Inicializa controle do histórico do navegador
        initHistoryState: function() {
            if (window.history && window.history.pushState) {
                $(window).on('popstate', function(e) {
                    if (e.originalEvent.state && e.originalEvent.state.postId) {
                        BCPublic.carregarArtigo(e.originalEvent.state.postId);
                    } else {
                        location.reload();
                    }
                });
            }
        },

        // Carrega artigo via AJAX
        carregarArtigo: function(postId) {
            $.ajax({
                url: bcPublicData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'bc_carregar_artigo',
                    id: postId,
                    nonce: bcPublicData.nonce
                },
                beforeSend: function() {
                    $('.bc-single-content').html('<div class="bc-loading">Carregando artigo...</div>');
                },
                success: function(response) {
                    if (response.success) {
                        $('.bc-single-content').html(response.data.html);
                        $('.bc-sidebar-menu a').removeClass('active');
                        $(`.bc-sidebar-menu a[data-carregar-artigo="${postId}"]`).addClass('active');
                        BCPublic.registrarVisualizacao(postId);
                    }
                }
            });
        },

        // Inicializa sistema de feedback
        initFeedback: function() {
            $(document).on('click', '.bc-btn-feedback', function(e) {
                e.preventDefault();
                const $btn = $(this);
                const tipo = $btn.data('tipo');
                const postId = $btn.data('post-id');

                $.ajax({
                    url: bcPublicData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'bc_registrar_feedback',
                        tipo: tipo,
                        post_id: postId,
                        nonce: bcPublicData.nonce
                    },
                    beforeSend: function() {
                        $btn.prop('disabled', true);
                    },
                    success: function(response) {
                        if (response.success) {
                            $btn.addClass('active').siblings().removeClass('active');
                            const mensagem = tipo === 'like' ? 'Obrigado pelo feedback positivo!' : 'Obrigado pelo feedback!';
                            $btn.closest('.bc-artigo-feedback').append(`<div class="bc-feedback-mensagem">${mensagem}</div>`);
                        }
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                    }
                });
            });
        },

        // Registra visualização do artigo
        registrarVisualizacao: function(postId) {
            if (!postId) {
                const $artigo = $('#bc-artigo');
                if ($artigo.length) {
                    postId = $artigo.data('id');
                }
            }
            
            if (postId) {
                $.ajax({
                    url: bcPublicData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'bc_registrar_visualizacao',
                        post_id: postId,
                        nonce: bcPublicData.nonce
                    }
                });
            }
        }
    };

    // Inicializa quando o documento estiver pronto
    $(document).ready(function() {
        BCPublic.init();
    });

})(jQuery);