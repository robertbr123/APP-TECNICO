/**
 * Módulo de Relatórios / Exportação
 * Ondeline Tech - App do Técnico
 */

(function() {
    document.addEventListener('DOMContentLoaded', function() {
        // Admin-only access
        var user = JSON.parse(localStorage.getItem('user_data') || '{}');
        if (user.role !== 'admin') {
            document.body.innerHTML = '<div class="flex items-center justify-center min-h-screen bg-background-light dark:bg-background-dark"><div class="text-center p-8"><span class="material-symbols-outlined text-5xl text-gray-300 mb-4 block">lock</span><h2 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Acesso Restrito</h2><p class="text-sm text-gray-500 mb-4">Apenas administradores podem acessar os relatórios.</p><a href="dashboard.php" class="text-primary font-semibold text-sm">Voltar ao Dashboard</a></div></div>';
            return;
        }

        // Default dates: first of month to today
        var today = new Date().toISOString().split('T')[0];
        var firstDay = today.substring(0, 8) + '01';
        document.getElementById('date-from').value = firstDay;
        document.getElementById('date-to').value = today;
        setupNotificationBadge();
        loadTechniciansList();
    });

    window.generateReport = async function(reportType, format) {
        var dateFrom = document.getElementById('date-from').value;
        var dateTo = document.getElementById('date-to').value;

        if (!dateFrom || !dateTo) {
            showError('Selecione o período');
            return;
        }

        try {
            showLoading('Gerando relatório...');

            var response = await API.get('reports.php', {
                report: reportType,
                date_from: dateFrom,
                date_to: dateTo
            });

            hideLoading();

            if (!response.success) {
                showError(response.message || 'Erro ao gerar relatório');
                return;
            }

            if (response.data.length === 0) {
                showWarning('Nenhum dado encontrado para o período selecionado');
                return;
            }

            if (format === 'csv') {
                exportCSV(response.data, response.columns, reportType);
            } else if (format === 'pdf') {
                exportPDF(response.data, response.columns, response.summary, reportType);
            }
        } catch (e) {
            hideLoading();
            showError('Erro: ' + e.message);
        }
    };

    function exportCSV(data, columns, reportType) {
        var headers = columns.map(function(c) { return c.label; });
        var keys = columns.map(function(c) { return c.key; });

        var csvContent = '\uFEFF'; // BOM for UTF-8
        csvContent += headers.join(';') + '\n';

        data.forEach(function(row) {
            var line = keys.map(function(key) {
                var val = row[key] || '';
                // Escape quotes and wrap in quotes
                val = String(val).replace(/"/g, '""');
                return '"' + val + '"';
            });
            csvContent += line.join(';') + '\n';
        });

        var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        downloadBlob(blob, 'relatorio_' + reportType + '_' + new Date().toISOString().split('T')[0] + '.csv');

        showSuccess(data.length + ' registros exportados para CSV');
    }

    function exportPDF(data, columns, summary, reportType) {
        var jsPDF = window.jspdf.jsPDF;
        var doc = new jsPDF({ orientation: data[0] && columns.length > 6 ? 'landscape' : 'portrait' });

        var reportNames = {
            clients: 'Relatório de Clientes',
            timeclock: 'Relatório de Ponto',
            inventory: 'Relatório de Estoque',
            work_orders: 'Relatório de Ordens de Serviço',
            checklists: 'Relatório de Checklists',
            ranking: 'Ranking de Técnicos',
            cadastro_completo: 'Ficha de Cadastro Completa'
        };

        // Header
        doc.setFontSize(18);
        doc.setTextColor(19, 91, 236);
        doc.text('Ondeline', 14, 20);

        doc.setFontSize(14);
        doc.setTextColor(0, 0, 0);
        doc.text(reportNames[reportType] || 'Relatório', 14, 30);

        doc.setFontSize(9);
        doc.setTextColor(128, 128, 128);
        var dateFrom = document.getElementById('date-from').value;
        var dateTo = document.getElementById('date-to').value;
        doc.text('Período: ' + formatDateBR(dateFrom) + ' a ' + formatDateBR(dateTo), 14, 37);
        doc.text('Gerado em: ' + new Date().toLocaleString('pt-BR'), 14, 42);
        doc.text('Total de registros: ' + data.length, 14, 47);

        // Summary info
        var startY = 54;
        if (summary) {
            doc.setFontSize(10);
            doc.setTextColor(0, 0, 0);

            if (summary.total_all_time !== undefined) {
                doc.text('Total geral: ' + summary.total_all_time + ' | Período: ' + summary.total_period, 14, startY);
                startY += 8;
            }
            if (summary.total_hours) {
                doc.text('Total de dias: ' + summary.total_days + ' | Total de horas: ' + summary.total_hours, 14, startY);
                startY += 8;
            }
            if (summary.by_status) {
                var statusText = summary.by_status.map(function(s) { return s.status + ': ' + s.total; }).join(' | ');
                doc.text(statusText, 14, startY);
                startY += 8;
            }
        }

        // Table
        var headers = columns.map(function(c) { return c.label; });
        var keys = columns.map(function(c) { return c.key; });

        var tableData = data.map(function(row) {
            return keys.map(function(key) {
                var val = row[key] || '';
                // Truncate long values for PDF
                if (String(val).length > 50) val = String(val).substring(0, 47) + '...';
                return String(val);
            });
        });

        doc.autoTable({
            head: [headers],
            body: tableData,
            startY: startY,
            styles: {
                fontSize: 7,
                cellPadding: 2,
                overflow: 'linebreak'
            },
            headStyles: {
                fillColor: [19, 91, 236],
                textColor: 255,
                fontSize: 8,
                fontStyle: 'bold'
            },
            alternateRowStyles: {
                fillColor: [245, 245, 250]
            },
            margin: { left: 14, right: 14 }
        });

        // Footer
        var pageCount = doc.internal.getNumberOfPages();
        for (var i = 1; i <= pageCount; i++) {
            doc.setPage(i);
            doc.setFontSize(8);
            doc.setTextColor(128, 128, 128);
            doc.text('Ondeline Tech - Página ' + i + ' de ' + pageCount, doc.internal.pageSize.width / 2, doc.internal.pageSize.height - 10, { align: 'center' });
        }

        doc.save('relatorio_' + reportType + '_' + new Date().toISOString().split('T')[0] + '.pdf');
        showSuccess(data.length + ' registros exportados para PDF');
    }

    function downloadBlob(blob, filename) {
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        setTimeout(function() {
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }, 100);
    }

    function formatDateBR(dateStr) {
        if (!dateStr) return '';
        var parts = dateStr.split('-');
        return parts[2] + '/' + parts[1] + '/' + parts[0];
    }

    // =====================================================
    // Tabs
    // =====================================================
    window.switchReportTab = function(tab) {
        ['exportar', 'ranking', 'tecnico'].forEach(function(t) {
            var content = document.getElementById('tab-content-' + t);
            var btn = document.getElementById('rtab-' + t);
            if (t === tab) {
                content.classList.remove('hidden');
                btn.className = 'flex-1 px-3 py-2 text-xs font-bold rounded-lg bg-primary text-white whitespace-nowrap';
            } else {
                content.classList.add('hidden');
                btn.className = 'flex-1 px-3 py-2 text-xs font-bold rounded-lg text-gray-500 whitespace-nowrap';
            }
        });
    };

    // =====================================================
    // Ranking
    // =====================================================
    var _rankingData = null;

    window.loadRanking = async function() {
        var dateFrom = document.getElementById('date-from').value;
        var dateTo = document.getElementById('date-to').value;
        if (!dateFrom || !dateTo) { showError('Selecione o periodo'); return; }

        var container = document.getElementById('ranking-container');
        container.innerHTML = '<p class="text-gray-400 text-center text-sm py-6">Carregando...</p>';

        try {
            var response = await API.get('reports.php', { report: 'ranking', date_from: dateFrom, date_to: dateTo });
            if (!response.success) { showError(response.message); return; }

            _rankingData = response;
            var items = response.data;

            if (items.length === 0) {
                container.innerHTML = '<p class="text-gray-400 text-center text-sm py-6">Nenhum tecnico com atividade no periodo</p>';
                return;
            }

            var medals = ['bg-yellow-400', 'bg-gray-300', 'bg-orange-400'];
            var html = '';

            items.forEach(function(item, i) {
                var medalClass = i < 3 ? medals[i] : 'bg-gray-200 dark:bg-gray-700';
                var textClass = i < 3 ? 'text-white' : 'text-gray-500';
                var highlight = i === 0 ? 'bg-yellow-50 dark:bg-yellow-500/5 border border-yellow-200 dark:border-yellow-500/10' : '';
                var initials = (item.full_name || '?').split(' ').map(function(n) { return n[0]; }).join('').substring(0, 2).toUpperCase();

                html += '<div class="flex items-center gap-3 p-3 rounded-xl ' + highlight + '">' +
                    '<div class="w-7 h-7 rounded-full ' + medalClass + ' flex items-center justify-center flex-shrink-0">' +
                        '<span class="text-xs font-bold ' + textClass + '">' + (i + 1) + '</span>' +
                    '</div>' +
                    (item.photo
                        ? '<img src="' + item.photo + '" class="w-9 h-9 rounded-full object-cover flex-shrink-0" alt="">'
                        : '<div class="w-9 h-9 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0"><span class="text-xs font-bold text-primary">' + initials + '</span></div>'
                    ) +
                    '<div class="flex-1 min-w-0">' +
                        '<p class="text-sm font-semibold text-gray-900 dark:text-white truncate">' + (item.full_name || item.username) + '</p>' +
                        '<div class="flex gap-3 mt-0.5">' +
                            '<span class="text-[10px] text-green-600">' + item.total_cadastros + ' cad</span>' +
                            '<span class="text-[10px] text-cyan-600">' + item.total_checklists + ' chk</span>' +
                            '<span class="text-[10px] text-blue-600">' + item.total_ordens + ' os</span>' +
                        '</div>' +
                    '</div>' +
                    '<div class="text-right flex-shrink-0">' +
                        '<p class="text-lg font-bold text-gray-900 dark:text-white">' + item.total_geral + '</p>' +
                        '<p class="text-[10px] text-gray-400">total</p>' +
                    '</div>' +
                '</div>';
            });

            container.innerHTML = html;
        } catch (e) {
            container.innerHTML = '<p class="text-red-400 text-center text-sm py-6">Erro ao carregar ranking</p>';
        }
    };

    // =====================================================
    // Relatorio por Tecnico
    // =====================================================
    var _techReport = null;

    async function loadTechniciansList() {
        try {
            var response = await API.get('reports.php', { report: 'technicians_list' });
            if (!response.success) return;

            var select = document.getElementById('technician-select');
            response.data.forEach(function(tech) {
                var option = document.createElement('option');
                option.value = tech.id;
                option.textContent = tech.full_name || tech.username;
                select.appendChild(option);
            });
        } catch (e) { /* ignore */ }
    }

    window.loadTechnicianReport = async function() {
        var techId = document.getElementById('technician-select').value;
        var dateFrom = document.getElementById('date-from').value;
        var dateTo = document.getElementById('date-to').value;

        if (!techId) { showError('Selecione um tecnico'); return; }
        if (!dateFrom || !dateTo) { showError('Selecione o periodo'); return; }

        var list = document.getElementById('tech-services-list');
        list.innerHTML = '<p class="text-gray-400 text-center text-sm py-6">Carregando...</p>';

        try {
            var response = await API.get('reports.php', {
                report: 'technician_detail',
                technician_id: techId,
                date_from: dateFrom,
                date_to: dateTo
            });

            if (!response.success) { showError(response.message); return; }

            _techReport = response;

            // Resumo
            var summary = response.summary;
            document.getElementById('tech-summary').classList.remove('hidden');
            document.getElementById('tech-cadastros').textContent = summary.cadastros;
            document.getElementById('tech-checklists').textContent = summary.checklists;
            document.getElementById('tech-ordens').textContent = summary.ordens;
            document.getElementById('btn-tech-pdf').disabled = false;

            // Lista de servicos
            if (response.data.length === 0) {
                list.innerHTML = '<p class="text-gray-400 text-center text-sm py-6">Nenhum servico no periodo</p>';
                return;
            }

            var typeIcons = {
                'Cadastro': { icon: 'person_add', color: 'text-green-500 bg-green-50' },
                'Instalacao': { icon: 'build', color: 'text-cyan-500 bg-cyan-50' },
                'Migracao': { icon: 'swap_horiz', color: 'text-orange-500 bg-orange-50' },
                'Reparo': { icon: 'handyman', color: 'text-red-500 bg-red-50' },
                'Manutencao': { icon: 'engineering', color: 'text-purple-500 bg-purple-50' }
            };

            var html = '';
            response.data.forEach(function(item) {
                var typeInfo = typeIcons[item.tipo_servico] || { icon: 'assignment', color: 'text-blue-500 bg-blue-50' };
                // OS items
                if (item.tipo_servico.startsWith('OS #')) {
                    typeInfo = { icon: 'assignment', color: 'text-blue-500 bg-blue-50' };
                }

                html += '<div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800/50 rounded-xl">' +
                    '<div class="size-9 rounded-lg ' + typeInfo.color + ' dark:bg-opacity-20 flex items-center justify-center flex-shrink-0">' +
                        '<span class="material-symbols-outlined text-base">' + typeInfo.icon + '</span>' +
                    '</div>' +
                    '<div class="flex-1 min-w-0">' +
                        '<p class="text-sm font-semibold text-gray-900 dark:text-white truncate">' + (item.cliente || '-') + '</p>' +
                        '<p class="text-[11px] text-gray-500 truncate">' + item.tipo_servico + (item.servico_detalhe ? ' - ' + item.servico_detalhe : '') + '</p>' +
                    '</div>' +
                    '<div class="text-right flex-shrink-0">' +
                        '<p class="text-xs font-bold text-gray-700 dark:text-gray-300">' + item.data + '</p>' +
                        '<p class="text-[10px] text-gray-400">' + item.horario + '</p>' +
                    '</div>' +
                '</div>';
            });

            list.innerHTML = html;
        } catch (e) {
            list.innerHTML = '<p class="text-red-400 text-center text-sm py-6">Erro ao carregar</p>';
        }
    };

    window.exportTechnicianPDF = function() {
        if (!_techReport || !_techReport.data || _techReport.data.length === 0) {
            showError('Carregue os dados primeiro');
            return;
        }

        var jsPDF = window.jspdf.jsPDF;
        var doc = new jsPDF({ orientation: 'portrait' });

        var dateFrom = document.getElementById('date-from').value;
        var dateTo = document.getElementById('date-to').value;
        var summary = _techReport.summary;
        var techName = summary.technician_name || 'Tecnico';

        // Header
        doc.setFontSize(18);
        doc.setTextColor(19, 91, 236);
        doc.text('Ondeline Tech', 14, 20);

        doc.setFontSize(14);
        doc.setTextColor(0, 0, 0);
        doc.text('Relatorio do Tecnico: ' + techName, 14, 30);

        doc.setFontSize(9);
        doc.setTextColor(128, 128, 128);
        doc.text('Periodo: ' + formatDateBR(dateFrom) + ' a ' + formatDateBR(dateTo), 14, 37);
        doc.text('Gerado em: ' + new Date().toLocaleString('pt-BR'), 14, 42);

        // Summary boxes
        doc.setFontSize(10);
        doc.setTextColor(0, 0, 0);
        doc.text('Resumo:', 14, 52);

        doc.setFontSize(9);
        doc.text('Cadastros: ' + summary.cadastros + '  |  Checklists: ' + summary.checklists + '  |  Ordens: ' + summary.ordens + '  |  Total: ' + summary.total, 14, 58);

        // Table
        var headers = ['Tipo', 'Cliente', 'CPF', 'Detalhe', 'Data', 'Horario'];
        var keys = ['tipo_servico', 'cliente', 'cpf', 'servico_detalhe', 'data', 'horario'];

        var tableData = _techReport.data.map(function(row) {
            return keys.map(function(key) {
                var val = row[key] || '';
                if (String(val).length > 40) val = String(val).substring(0, 37) + '...';
                return String(val);
            });
        });

        doc.autoTable({
            head: [headers],
            body: tableData,
            startY: 64,
            styles: { fontSize: 7, cellPadding: 2, overflow: 'linebreak' },
            headStyles: { fillColor: [19, 91, 236], textColor: 255, fontSize: 8, fontStyle: 'bold' },
            alternateRowStyles: { fillColor: [245, 245, 250] },
            margin: { left: 14, right: 14 }
        });

        // Footer
        var pageCount = doc.internal.getNumberOfPages();
        for (var i = 1; i <= pageCount; i++) {
            doc.setPage(i);
            doc.setFontSize(8);
            doc.setTextColor(128, 128, 128);
            doc.text('Ondeline Tech - Relatorio ' + techName + ' - Pagina ' + i + ' de ' + pageCount, doc.internal.pageSize.width / 2, doc.internal.pageSize.height - 10, { align: 'center' });
        }

        doc.save('relatorio_tecnico_' + techName.replace(/\s+/g, '_') + '_' + new Date().toISOString().split('T')[0] + '.pdf');
        showSuccess(_techReport.data.length + ' registros exportados para PDF');
    };

    async function setupNotificationBadge() {
        try {
            var response = await API.get('notifications.php', { action: 'unread_count' });
            if (response.success && response.data.count > 0) {
                var badge = document.getElementById('notif-badge');
                if (badge) {
                    badge.textContent = response.data.count > 9 ? '9+' : response.data.count;
                    badge.classList.remove('hidden');
                }
            }
        } catch (e) { /* badge failed */ }
    }
})();
