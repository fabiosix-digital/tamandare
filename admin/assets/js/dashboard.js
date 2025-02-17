(function($) {
    'use strict';

    // Objeto principal do Dashboard
    const BCDashboard = {
        // Armazena instâncias dos gráficos
        charts: {},

        // Inicialização
        init: function() {
            this.initMetricas();
            this.initGraficoDesempenho();
            this.initGraficoEngajamento();
            this.initFiltros();
            this.initAtualizacaoAutomatica();
        },

        // Inicializa as métricas principais
        initMetricas: function() {
            this.atualizarMetricas();
            
            // Atualiza a cada 5 minutos
            setInterval(() => {
                this.atualizarMetricas();
            }, 300000);
        },

        // Atualiza os valores das métricas
        atualizarMetricas: function() {
            $.ajax({
                url: bcDashboard.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'bc_obter_metricas',
                    nonce: bcDashboard.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const data = response.data;
                        
                        // Atualiza os valores com animação
                        $('.bc-metrica-valor').each(function() {
                            const $this = $(this);
                            const valor = data[$this.data('metrica')];
                            const valorAtual = parseInt($this.text().replace(/\D/g,''));
                            
                            BCDashboard.animateNumber($this, valorAtual, valor);
                        });
                    }
                }
            });
        },

        // Inicializa o gráfico de desempenho
        initGraficoDesempenho: function() {
            const ctx = document.getElementById('graficoDesempenho');
            if (!ctx) return;

            this.charts.desempenho = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [
                        {
                            label: bcDashboard.strings.visualizacoes,
                            data: [],
                            borderColor: '#6B46C1',
                            backgroundColor: 'rgba(107, 70, 193, 0.1)',
                            tension: 0.4
                        },
                        {
                            label: bcDashboard.strings.curtidas,
                            data: [],
                            borderColor: '#48BB78',
                            backgroundColor: 'rgba(72, 187, 120, 0.1)',
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });

            this.atualizarGraficoDesempenho();
        },

        // Atualiza dados do gráfico de desempenho
        atualizarGraficoDesempenho: function(periodo = '30dias') {
            $.ajax({
                url: bcDashboard.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'bc_obter_dados_desempenho',
                    periodo: periodo,
                    nonce: bcDashboard.nonce
                },
                success: (response) => {
                    if (response.success && this.charts.desempenho) {
                        const data = response.data;
                        
                        this.charts.desempenho.data.labels = data.labels;
                        this.charts.desempenho.data.datasets[0].data = data.visualizacoes;
                        this.charts.desempenho.data.datasets[1].data = data.curtidas;
                        this.charts.desempenho.update();
                    }
                }
            });
        },

        // Inicializa o gráfico de engajamento
        initGraficoEngajamento: function() {
            const ctx = document.getElementById('graficoEngajamento');
            if (!ctx) return;

            this.charts.engajamento = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: [
                        bcDashboard.strings.curtidas,
                        bcDashboard.strings.naoCurtidas,
                        bcDashboard.strings.neutro
                    ],
                    datasets: [{
                        data: [0, 0, 0],
                        backgroundColor: [
                            '#48BB78',
                            '#F56565',
                            '#EDF2F7'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            this.atualizarGraficoEngajamento();
        },

        // Atualiza dados do gráfico de engajamento
        atualizarGraficoEngajamento: function() {
            $.ajax({
                url: bcDashboard.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'bc_obter_dados_engajamento',
                    nonce: bcDashboard.nonce
                },
                success: (response) => {
                    if (response.success && this.charts.engajamento) {
                        const data = response.data;
                        
                        this.charts.engajamento.data.datasets[0].data = [
                            data.curtidas,
                            data.nao_curtidas,
                            data.neutro
                        ];
                        this.charts.engajamento.update();
                    }
                }
            });
        },

        // Inicializa os filtros do dashboard
        initFiltros: function() {
            // Filtro de período
            $('#filtro-periodo').on('change', (e) => {
                const periodo = $(e.target).val();
                this.atualizarGraficoDesempenho(periodo);
            });

            // Filtro de categorias
            $('#filtro-categoria').on('change', (e) => {
                const categoria = $(e.target).val();
                this.atualizarMetricas(categoria);
                this.atualizarGraficoDesempenho($('#filtro-periodo').val(), categoria);
                this.atualizarGraficoEngajamento(categoria);
            });
        },

        // Inicializa atualização automática dos dados
        initAtualizacaoAutomatica: function() {
            setInterval(() => {
                const periodo = $('#filtro-periodo').val();
                const categoria = $('#filtro-categoria').val();

                this.atualizarMetricas(categoria);
                this.atualizarGraficoDesempenho(periodo, categoria);
                this.atualizarGraficoEngajamento(categoria);
            }, 300000); // Atualiza a cada 5 minutos
        },

        // Animação para números
        animateNumber: function($element, start, end) {
            $({ value: start }).animate({ value: end }, {
                duration: 1000,
                easing: 'swing',
                step: function() {
                    $element.text(Math.floor(this.value).toLocaleString());
                }
            });
        }
    };

    // Inicializa quando o documento estiver pronto
    $(document).ready(function() {
        BCDashboard.init();
    });

})(jQuery);