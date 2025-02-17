(function($) {
    'use strict';

    const BCEditor = {
        init: function() {
            this.initEditor();
            this.initAutoSave();
            this.initToolbar();
            this.initMediaUpload();
            this.initPreview();
        },

        // Inicializa o editor
        initEditor: function() {
            this.editor = wp.editor.initialize('bc_editor', {
                tinymce: {
                    plugins: 'lists,link,image,code,table,paste,wordcount,fullscreen,hr',
                    toolbar1: 'formatselect | bold italic | bullist numlist | link image | code table hr',
                    toolbar2: '',
                    height: 500,
                    paste_as_text: true,
                    content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; line-height: 1.6; }',
                    setup: function(editor) {
                        // Adiciona atalhos de teclado
                        editor.addShortcut('meta+s', 'Save', function() {
                            BCEditor.salvarDocumento();
                        });

                        // Eventos do editor
                        editor.on('change', function() {
                            BCEditor.marcarAlterado();
                        });

                        editor.on('keydown', function(e) {
                            // Previne fechamento acidental
                            if (e.keyCode === 8 && editor.getContent().length === 0) {
                                return false;
                            }
                        });
                    }
                },
                quicktags: false
            });
        },

        // Auto-save
        initAutoSave: function() {
            setInterval(() => {
                if (this.conteudoAlterado) {
                    this.salvarRascunho();
                }
            }, 60000); // Auto-save a cada minuto
        },

        // Barra de ferramentas
        initToolbar: function() {
            $('.bc-editor-toolbar button').on('click', function() {
                const comando = $(this).data('comando');
                BCEditor.executarComando(comando);
            });
        },

        // Upload de mídia
        initMediaUpload: function() {
            // WordPress Media Uploader
            let frame;

            $('.bc-btn-media').on('click', function(e) {
                e.preventDefault();

                if (frame) {
                    frame.open();
                    return;
                }

                frame = wp.media({
                    title: 'Selecionar Arquivo',
                    button: {
                        text: 'Inserir no documento'
                    },
                    multiple: false
                });

                frame.on('select', function() {
                    const attachment = frame.state().get('selection').first().toJSON();
                    BCEditor.inserirMidia(attachment);
                });

                frame.open();
            });
        },

        // Preview em tempo real
        initPreview: function() {
            $('.bc-btn-preview').on('click', function() {
                const conteudo = BCEditor.editor.getContent();
                const $modal = $('#bc-preview-modal');
                const $iframe = $modal.find('iframe');

                // Atualiza preview
                $iframe.contents().find('body').html(conteudo);
                $modal.show();
            });
        },

        // Executa comandos do editor
        executarComando: function(comando) {
            const editor = this.editor;

            switch (comando) {
                case 'code':
                    editor.insertContent('<pre><code>\n\n</code></pre>');
                    break;

                case 'table':
                    editor.insertContent(
                        '<table class="bc-table">' +
                        '<thead><tr><th>Cabeçalho 1</th><th>Cabeçalho 2</th></tr></thead>' +
                        '<tbody><tr><td>Célula 1</td><td>Célula 2</td></tr></tbody>' +
                        '</table>'
                    );
                    break;

                case 'alerta':
                    editor.insertContent(
                        '<div class="bc-alerta bc-alerta-info">' +
                        '<p>Seu texto aqui...</p>' +
                        '</div>'
                    );
                    break;

                default:
                    editor.execCommand(comando);
            }
        },

        // Insere mídia no editor
        inserirMidia: function(attachment) {
            let html = '';

            if (attachment.type === 'image') {
                html = `<img src="${attachment.url}" alt="${attachment.alt}" class="bc-imagem">`;
            } else {
                html = `<a href="${attachment.url}" class="bc-arquivo">${attachment.title}</a>`;
            }

            this.editor.insertContent(html);
        },

        // Salva o documento
        salvarDocumento: function(publicar = false) {
            const dados = {
                action: 'bc_salvar_documento',
                post_id: $('#post_ID').val(),
                titulo: $('#bc-titulo').val(),
                conteudo: this.editor.getContent(),
                pasta: $('.bc-select-pasta').val(),
                destaque: $('input[name="bc_destaque"]').is(':checked') ? 1 : 0,
                status: publicar ? 'publish' : 'draft',
                nonce: bcEditor.nonce
            };

            $.ajax({
                url: bcEditor.ajaxUrl,
                type: 'POST',
                data: dados,
                beforeSend: function() {
                    BCEditor.mostrarSalvando();
                },
                success: function(response) {
                    if (response.success) {
                        BCEditor.mostrarSalvo();
                        BCEditor.conteudoAlterado = false;
                    } else {
                        BCEditor.mostrarErro();
                    }
                }
            });
        },

        // Salva rascunho
        salvarRascunho: function() {
            this.salvarDocumento(false);
        },

        // Publica documento
        publicar: function() {
            this.salvarDocumento(true);
        },

        // UI Feedback
        mostrarSalvando: function() {
            $('.bc-status').html('<span class="bc-salvando">Salvando...</span>');
        },

        mostrarSalvo: function() {
            $('.bc-status').html('<span class="bc-salvo">Salvo!</span>');
            setTimeout(() => {
                $('.bc-status').empty();
            }, 2000);
        },

        mostrarErro: function() {
            $('.bc-status').html('<span class="bc-erro">Erro ao salvar</span>');
        },

        marcarAlterado: function() {
            this.conteudoAlterado = true;
            $('.bc-status').html('<span class="bc-nao-salvo">Não salvo</span>');
        }
    };

    // Inicializa quando o documento estiver pronto
    $(document).ready(function() {
        BCEditor.init();
    });

})(jQuery);