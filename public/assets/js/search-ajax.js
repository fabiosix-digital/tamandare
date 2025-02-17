(function($) {
    'use strict';

    $(document).ready(function() {
        var $searchInput = $('.bc-busca-input');
        var $resultsContainer = $('.bc-resultados-busca');

        $searchInput.on('input', function() {
            var query = $(this).val();
            if (query.length < 3) {
                $resultsContainer.empty();
                return;
            }

            clearTimeout($searchInput.data('timeout'));
            $searchInput.data('timeout', setTimeout(function() {
                $.ajax({
                    url: bcPublicData.ajaxUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'bc_busca_ajax',
                        termo: query,
                        nonce: bcPublicData.nonce
                    },
                    beforeSend: function() {
                        $resultsContainer.html('<div class="bc-loading">Buscando...</div>');
                    },
                    success: function(response) {
                        if (response.success && response.data && response.data.length > 0) {
                            var html = '';
                            $.each(response.data, function(index, item) {
                                if (item.type === 'folder') {
                                    html += '<div class="bc-card bc-folder" data-term-id="' + item.id + '">';
                                    html += '<div class="bc-card-icon"><i class="fa fa-folder"></i></div>';
                                    html += '<div class="bc-card-title"><a href="' + item.link + '">' + item.titulo + '</a></div>';
                                    html += '</div>';
                                } else {
                                    html += '<div class="bc-card bc-documento" data-post-id="' + item.id + '">';
                                    html += '<div class="bc-card-icon"><i class="fa fa-file-text-o"></i></div>';
                                    html += '<div class="bc-card-title"><a href="' + item.link + '" data-carregar-artigo="' + item.id + '">' + item.titulo + '</a></div>';
                                    html += '</div>';
                                }
                            });
                            $resultsContainer.html(html);
                        } else {
                            $resultsContainer.html('<div class="bc-no-results">Nenhum resultado encontrado</div>');
                        }
                    },
                    error: function() {
                        $resultsContainer.html('<div class="bc-error">Erro na busca. Tente novamente.</div>');
                    }
                });
            }, 500));
        });

        // Fecha os resultados ao clicar fora da área de busca
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.bc-busca').length) {
                $resultsContainer.empty();
            }
        });
    });
})(jQuery);
