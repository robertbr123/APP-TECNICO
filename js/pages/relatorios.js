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
            work_orders: 'Relatório de Ordens de Serviço'
        };

        // Header
        doc.setFontSize(18);
        doc.setTextColor(19, 91, 236);
        doc.text('Ondeline Tech', 14, 20);

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
