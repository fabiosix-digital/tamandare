(function($) {
    'use strict';

    // Objeto de pesquisa
    const BCPesquisa = {
        init: function() {
            this.cache = {};
            this.ultimoTermo = '';
            this.timeoutId = null;
            this.pesquisando = false;

            this.initBuscaInstantanea();
            this.initFiltros();
            this.initSugestoes();
            this.initHighlight();
        },

        // Inicializa busca instantânea
        initBuscaInstantanea: function() {
            const self = this;
            const $input = $('.bc-busca-input');
            const $resultados = $('.bc-resultados-busca');
            const $loading = $('<div class="bc-loading">Pesquisando...</div>');

            $input.on('input', function() {
                const termo = $(this).val().trim();

                // Limpa timeout anterior
                clearTimeout(self.timeoutId);

                // Limpa resultados se o termo for muito curto
                if (termo.length < 3) {
                    $resultados.empty();
                    return;
                }

                // Define novo timeout para evitar muitas requisições
                self.timeoutId = setTimeout(function() {
                    // Verifica se o termo já está em cache
                    if (self.cache[termo]) {
                        self.exibirResultados(self.cache[termo]);
                        return;
                    }

                    // Exibe loading
                    $resultados.html($loading);

                    // Faz a requisição
                    $.ajax({
                        url: bcPublicData.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'bc_pesquisa_instantanea',
                            termo: termo,
                            nonce: bcPublicData.nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                // Salva em cache
                                self.cache[termo] = response.data;
                                // Exibe resultados
                                self.exibirResultados(response.data);
                            }
                        }
                    });
                }, 300);
            });

            // Fecha resultados ao clicar fora
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.bc-busca').length) {
                    $resultados.empty();
                }
            });
        },

        // Exibe os resultados da busca
        exibirResultados: function(data) {
            const $resultados = $('.bc-resultados-busca');
            const $container = $('<div class="bc-resultados-container"></div>');

            if (data.total > 0) {
                // Adiciona resultados
                data.documentos.forEach(function(doc) {
                    const $item = $(`
                        <div class="bc-resultado-item">
                            <a href="${doc.link}" class="bc-resultado-titulo">${doc.titulo}</a>
                            <div class="bc-resultado-pasta">${doc.pasta}</div>
                            <div class="bc-resultado-excerpt">${doc.excerpt}</div>
                            <div class="bc-resultado-meta">
                                <span class="bc-resultado-data">${doc.data}</span>
                                <span class="bc-resultado-views">${doc.visualizacoes} visualizações</span>
                            </div>
                        </div>
                    `);
                    $container.append($item);
                });

                // Adiciona rodapé com total e link para mais resultados
                if (data.total > data.documentos.length) {
                    const $footer = $(`
                        <div class="bc-resultados-footer">
                            <span>${data.total} resultados encontrados</span>
                            <a href="${data.busca_url}" class="bc-ver-todos">Ver todos os resultados</a>
                        </div>
                    `);
                    $container.append($footer);
                }
            } else {
                // Mensagem de nenhum resultado
                $container.append(`
                    <div class="bc-sem-resultados">
                        <div class="bc-sem-resultados-icone">
                            <span class="dashicons dashicons-search"></span>
                        </div>
                        <p>Nenhum resultado encontrado para "${data.termo}"</p>
                        <div class="bc-sugestoes">
                            <p>Sugestões:</p>
                            <ul>
                                <li>Verifique se há erros de digitação</li>
                                <li>Tente palavras diferentes</li>
                                <li>Use termos mais gerais</li>
                            </ul>
                        </div>
                    </div>
                `);
            }

            $resultados.html($container);
        },

        // Inicializa filtros de pesquisa
        initFiltros: function() {
            const self = this;
            
            // Filtro por pasta
            $('.bc-filtro-pasta').on('change', function() {
                self.realizarPesquisa();
            });

            // Filtro por data
            $('.bc-filtro-data').on('change', function() {
                self.realizarPesquisa();
            });

            // Ordenação
            $('.bc-filtro-ordem').on('change', function() {
                self.realizarPesquisa();
            });
        },

        // Inicializa sugestões de pesquisa
        initSugestoes: function() {
            const self = this;
            const $input = $('.bc-busca-input');
            const $sugestoes = $('.bc-sugestoes-busca');

            $input.on('input', function() {
                const termo = $(this).val().trim();
                
                if (termo.length >= 3) {
                    $.ajax({
                        url: bcPublicData.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'bc_obter_sugestoes',
                            termo: termo,
                            nonce: bcPublicData.nonce
                        },
                        success: function(response) {
                            if (response.success && response.data.length > 0) {
                                self.exibirSugestoes(response.data);
                            } else {
                                $sugestoes.empty();
                            }
                        }
                    });
                } else {
                    $sugestoes.empty();
                }
            });
        },

        // Exibe sugestões de pesquisa
        exibirSugestoes: function(sugestoes) {
            const $sugestoes = $('.bc-sugestoes-busca');
            const $lista = $('<ul class="bc-lista-sugestoes"></ul>');

            sugestoes.forEach(function(sugestao) {
                const $item = $(`
                    <li class="bc-sugestao-item">
                        <span class="dashicons dashicons-search"></span>
                        <span class="bc-sugestao-texto">${sugestao}</span>
                    </li>
                `);
                $lista.append($item);
            });

            $sugestoes.html($lista);
        },

        // Inicializa highlight dos termos pesquisados
        initHighlight: function() {
            const urlParams = new URLSearchParams(window.location.search);
            const termo = urlParams.get('s');

            if (termo) {
                const termos = termo.split(' ').filter(t => t.length > 2);
                if (termos.length > 0) {
                    this.highlightTermos(termos);
                }
            }
        },

        // Destaca termos no conteúdo
        highlightTermos: function(termos) {
            const $conteudo = $('.bc-artigo-conteudo');
            let html = $conteudo.html();

            termos.forEach(function(termo) {
                const regex = new RegExp(`(${termo})`, 'gi');
                html = html.replace(regex, '<mark>$1</mark>');
            });

            $conteudo.html(html);
        }
    };

    // Inicializa quando o documento estiver pronto
    $(document).ready(function() {
        BCPesquisa.init();
    });

})(jQuery);