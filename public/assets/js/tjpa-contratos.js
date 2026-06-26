(function () {
    'use strict';

    // ── Helpers ────────────────────────────────────────────────────────────────

    function esc(str) {
        return String(str ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function fmtMoney(val) {
        const n = parseFloat(val);
        if (isNaN(n)) return '-';
        return n.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }

    function fmtDate(val) {
        if (!val) return '-';
        const m = String(val).match(/(\d{4})-(\d{2})-(\d{2})/);
        return m ? `${m[3]}/${m[2]}/${m[1]}` : val;
    }

    // 3º segmento de descricaoLocalGestor separado por "\"
    function extractUnidade(localGestor) {
        if (!localGestor) return '';
        const parts = localGestor.split('\\');
        return parts[2] || parts[parts.length - 1] || localGestor;
    }

    function situacaoBadge(situacao) {
        const s = (situacao || '').toLowerCase();
        if (s === 'ativo') return 'bg-success';
        if (s === 'encerrado') return 'bg-secondary';
        if (s === 'rescindido') return 'bg-danger';
        return 'bg-info text-dark';
    }

    // ── Regras de negócio ──────────────────────────────────────────────────────

    function calcTotalFinanceiro(valorOriginal, aditivos) {
        let total = parseFloat(valorOriginal || 0);
        (aditivos || []).forEach(function (a) {
            const tipo = (a.descricaoTipo || '').toLowerCase();
            if (tipo.includes('reajuste') || tipo.includes('prorrog')) {
                total += parseFloat(a.valorAditivo || 0);
            }
        });
        return total;
    }

    function findRealEndDate(aditivos) {
        let last = null;
        (aditivos || []).forEach(function (a) {
            (a.alteracoes || []).forEach(function (alt) {
                if (alt.dataFinal && (!last || alt.dataFinal > last)) {
                    last = alt.dataFinal;
                }
            });
        });
        return last;
    }

    // ── DOM refs ───────────────────────────────────────────────────────────────

    const form       = document.getElementById('tjpa-search-form');
    const loadingEl  = document.getElementById('tjpa-loading');
    const resultsDiv = document.getElementById('tjpa-results');
    const tbody      = document.getElementById('tjpa-tbody');
    const countEl    = document.getElementById('tjpa-count');

    if (!form) return;

    // ── Busca principal ────────────────────────────────────────────────────────

    form.addEventListener('submit', async function (evt) {
        evt.preventDefault();

        const fd = new FormData(form);
        const params = new URLSearchParams();
        fd.forEach(function (v, k) { if (v.trim()) params.append(k, v.trim()); });

        loadingEl.classList.remove('d-none');
        resultsDiv.classList.add('d-none');
        tbody.innerHTML = '';
        countEl.textContent = '';

        try {
            const res = await fetch('/api/tjpa/contratos?' + params.toString());
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const contratos = await res.json();

            if (!Array.isArray(contratos) || contratos.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted">Nenhum contrato encontrado.</td></tr>';
                countEl.textContent = '0 contratos encontrados';
                resultsDiv.classList.remove('d-none');
                return;
            }

            countEl.textContent = contratos.length + ' contrato(s) encontrado(s)';

            contratos.forEach(function (c) {
                const numeroExibicao = c.numeroExibicao || c.numeroContrato || '-';
                const objeto = c.objeto || c.objetoContrato || '';
                const dataInicio = c.dataInicio || c.dataInicioVigencia || '';
                const dataFim    = c.dataFim    || c.dataFimVigencia    || '';

                const tr = document.createElement('tr');
                tr.innerHTML = [
                    `<td class="text-nowrap">`,
                    `  <button class="btn btn-link btn-sm p-0 me-1 tjpa-expand-btn" type="button">`,
                    `    <i class="bi bi-chevron-right"></i>`,
                    `  </button>`,
                    esc(numeroExibicao),
                    `</td>`,
                    `<td>${esc(c.exercicio || '-')}</td>`,
                    `<td>${esc(c.nomeContratado || '-')}</td>`,
                    `<td class="text-truncate" style="max-width:180px" title="${esc(objeto)}">${esc(objeto.length > 55 ? objeto.slice(0, 55) + '…' : objeto || '-')}</td>`,
                    `<td>${esc(extractUnidade(c.descricaoLocalGestor))}</td>`,
                    `<td class="text-nowrap">${fmtDate(dataInicio)}</td>`,
                    `<td class="text-nowrap">${fmtDate(dataFim)}</td>`,
                    `<td class="text-end text-nowrap">${fmtMoney(c.valorOriginal)}</td>`,
                    `<td><span class="badge ${situacaoBadge(c.descricaoSituacaoContrato)}">${esc(c.descricaoSituacaoContrato || '-')}</span></td>`,
                ].join('');

                const trDetail = document.createElement('tr');
                trDetail.className = 'tjpa-detail-row d-none bg-light';
                trDetail.innerHTML = '<td colspan="9"><div class="tjpa-detail p-3 small">Carregando detalhes...</div></td>';

                tbody.appendChild(tr);
                tbody.appendChild(trDetail);

                tr.querySelector('.tjpa-expand-btn').addEventListener('click', function () {
                    toggleDetail(tr, trDetail, c);
                });
            });

            resultsDiv.classList.remove('d-none');

        } catch (err) {
            tbody.innerHTML = `<tr><td colspan="9" class="text-center text-danger"><i class="bi bi-exclamation-triangle me-1"></i>Erro ao consultar a API: ${esc(err.message)}</td></tr>`;
            resultsDiv.classList.remove('d-none');
        } finally {
            loadingEl.classList.add('d-none');
        }
    });

    // ── Detalhe de linha ───────────────────────────────────────────────────────

    async function toggleDetail(tr, trDetail, contrato) {
        const icon = tr.querySelector('.tjpa-expand-btn i');
        const isOpen = !trDetail.classList.contains('d-none');

        if (isOpen) {
            trDetail.classList.add('d-none');
            icon.className = 'bi bi-chevron-right';
            return;
        }

        trDetail.classList.remove('d-none');
        icon.className = 'bi bi-chevron-down';

        const detailDiv = trDetail.querySelector('.tjpa-detail');
        if (detailDiv.dataset.loaded) return;
        detailDiv.dataset.loaded = '1';

        try {
            // Aditivos
            const adRes = await fetch(
                `/api/tjpa/aditivos?exercicioContrato=${encodeURIComponent(contrato.exercicio || '')}&numeroContrato=${encodeURIComponent(contrato.numeroContrato || '')}`
            );
            const aditivos = adRes.ok ? (await adRes.json()) : [];

            // Empenhos
            const empenhoIds = (contrato.empenhos || []).map(function (e) { return e.empenho; }).filter(Boolean);
            let empenhosMap = {};
            if (empenhoIds.length > 0) {
                const empRes = await fetch('/api/tjpa/empenhos?ids=' + encodeURIComponent(empenhoIds.join(',')));
                if (empRes.ok) empenhosMap = await empRes.json();
            }

            // Cálculos de negócio
            const totalFinanceiro = calcTotalFinanceiro(contrato.valorOriginal, aditivos);
            const realEnd         = findRealEndDate(aditivos);

            let totalLiquidado = 0, totalPago = 0;
            Object.values(empenhosMap).forEach(function (emp) {
                if (emp) {
                    totalLiquidado += parseFloat(emp.valorLiquidacao || 0);
                    totalPago      += parseFloat(emp.valorPagamentoAtualLiquido || 0);
                }
            });

            const dataFimOriginal = contrato.dataFim || contrato.dataFimVigencia || '';

            detailDiv.innerHTML = [
                `<div class="row g-2 mb-3">`,
                `  <div class="col-md-3"><span class="text-muted">Modalidade</span><br><strong>${esc(contrato.descricaoModalidade || '-')}</strong></div>`,
                `  <div class="col-md-3"><span class="text-muted">Licitação</span><br><strong>${esc(contrato.numeroLicitacao || '-')}/${esc(contrato.exercicioLicitacao || '-')}</strong></div>`,
                `  <div class="col-md-3"><span class="text-muted">Valor total c/ aditivos</span><br><strong>${fmtMoney(totalFinanceiro)}</strong></div>`,
                `  <div class="col-md-3"><span class="text-muted">Fim de vigência real</span><br><strong>${fmtDate(realEnd || dataFimOriginal)}</strong></div>`,
                `</div>`,
                `<div class="row g-2 mb-3">`,
                `  <div class="col-md-3"><span class="text-muted">Total liquidado</span><br><strong>${fmtMoney(totalLiquidado)}</strong></div>`,
                `  <div class="col-md-3"><span class="text-muted">Total pago líquido</span><br><strong>${fmtMoney(totalPago)}</strong></div>`,
                `  <div class="col-md-6"><span class="text-muted">Objeto completo</span><br>${esc(contrato.objeto || contrato.objetoContrato || '-')}</div>`,
                `</div>`,
                renderAditivos(aditivos),
                renderEmpenhos(contrato.empenhos || [], empenhosMap),
            ].join('');

        } catch (err) {
            detailDiv.innerHTML = `<span class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i>Erro ao carregar detalhes: ${esc(err.message)}</span>`;
        }
    }

    function renderAditivos(aditivos) {
        if (!aditivos || aditivos.length === 0) {
            return '<p class="text-muted mb-2"><em>Sem aditivos registrados.</em></p>';
        }
        const rows = aditivos.map(function (a) {
            return [
                '<tr>',
                `<td>${esc(a.numeroAditivo || a.numeroAlteracao || '-')}</td>`,
                `<td>${esc(a.descricaoTipo || '-')}</td>`,
                `<td class="text-nowrap">${fmtDate(a.dataCelebracao)}</td>`,
                `<td class="text-end text-nowrap">${fmtMoney(a.valorAditivo)}</td>`,
                `<td class="text-truncate" style="max-width:200px" title="${esc(a.descricao || '')}">${esc((a.descricao || '-').slice(0, 80))}${(a.descricao || '').length > 80 ? '…' : ''}</td>`,
                '</tr>',
            ].join('');
        }).join('');

        return [
            `<p class="fw-semibold mb-1">Aditivos / Apostilamentos (${aditivos.length})</p>`,
            `<table class="table table-sm table-bordered mb-3">`,
            `<thead class="table-light"><tr><th>Nº</th><th>Tipo</th><th>Data</th><th>Valor</th><th>Descrição</th></tr></thead>`,
            `<tbody>${rows}</tbody>`,
            `</table>`,
        ].join('');
    }

    function renderEmpenhos(empenhosList, empenhosMap) {
        if (!empenhosList || empenhosList.length === 0) return '';
        const rows = empenhosList.map(function (em) {
            const emp = empenhosMap[em.empenho];
            return [
                '<tr>',
                `<td class="text-nowrap">${esc(em.empenho)}</td>`,
                `<td class="text-nowrap">${fmtDate(em.dataEmpenho)}</td>`,
                `<td class="text-end text-nowrap">${fmtMoney(em.valor)}</td>`,
                `<td class="text-end text-nowrap">${emp ? fmtMoney(emp.valorLiquidacao) : '-'}</td>`,
                `<td class="text-end text-nowrap">${emp ? fmtMoney(emp.valorPagamentoAtualLiquido) : '-'}</td>`,
                '</tr>',
            ].join('');
        }).join('');

        return [
            `<p class="fw-semibold mb-1">Empenhos (${empenhosList.length})</p>`,
            `<table class="table table-sm table-bordered mb-2">`,
            `<thead class="table-light"><tr><th>Empenho</th><th>Data</th><th>Empenhado</th><th>Liquidado</th><th>Pago líquido</th></tr></thead>`,
            `<tbody>${rows}</tbody>`,
            `</table>`,
        ].join('');
    }

})();

// ── Sincronização TJPA ─────────────────────────────────────────────────────────
(function () {
    'use strict';

    function esc(str) {
        return String(str ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    var btnRun    = document.getElementById('sync-btn-run');
    var btnReset  = document.getElementById('sync-btn-reset');
    var btnClose  = document.getElementById('sync-btn-close');
    var idle      = document.getElementById('sync-idle');
    var running   = document.getElementById('sync-running');
    var runningMsg = document.getElementById('sync-running-msg');
    var resultDiv = document.getElementById('sync-result');
    var resultBody = document.getElementById('sync-result-body');

    if (!btnRun) return;

    function getCsrf() {
        return (document.getElementById('sync-csrf') || {}).value || '';
    }

    function showRunning(msg) {
        btnRun.disabled = true;
        if (btnReset) btnReset.disabled = true;
        idle.classList.add('d-none');
        resultDiv.classList.add('d-none');
        running.classList.remove('d-none');
        if (runningMsg) runningMsg.textContent = msg || 'Consultando a API e sincronizando dados...';
    }

    function showResult(html) {
        running.classList.add('d-none');
        resultDiv.classList.remove('d-none');
        resultBody.innerHTML = html;
        btnRun.disabled = false;
        if (btnReset) btnReset.disabled = false;
    }

    function syncResultHtml(data) {
        var msgs = (data.messages || []).map(function (m) {
            return '<li>' + esc(m) + '</li>';
        }).join('');
        return [
            '<div class="alert alert-success mb-3">',
            '  <strong>Sincronização concluída</strong> em ' + esc(data.duration) + 's',
            '</div>',
            '<div class="row text-center g-2 mb-3">',
            '  <div class="col-4"><div class="border rounded p-2"><div class="fs-4 fw-bold text-success">' + esc(data.created) + '</div><div class="small text-muted">Criados</div></div></div>',
            '  <div class="col-4"><div class="border rounded p-2"><div class="fs-4 fw-bold text-primary">' + esc(data.updated) + '</div><div class="small text-muted">Atualizados</div></div></div>',
            '  <div class="col-4"><div class="border rounded p-2"><div class="fs-4 fw-bold text-danger">' + esc(data.errors) + '</div><div class="small text-muted">Erros</div></div></div>',
            '</div>',
            msgs ? '<ul class="small text-muted list-unstyled mb-0">' + msgs + '</ul>' : '',
            data.created > 0 || data.updated > 0
                ? '<div class="mt-3"><a href="/contratos" class="btn btn-primary btn-sm w-100">Ver contratos sincronizados</a></div>'
                : '',
        ].join('');
    }

    btnRun.addEventListener('click', async function () {
        showRunning('Consultando a API e sincronizando dados...');
        try {
            var res = await fetch('/sync/tjpa', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: '_csrf=' + encodeURIComponent(getCsrf()),
            });
            var data = await res.json();
            if (data.success) {
                showResult(syncResultHtml(data));
            } else {
                showResult('<div class="alert alert-danger"><strong>Erro:</strong> ' + esc(data.error || 'Falha desconhecida.') + '</div>');
            }
        } catch (err) {
            showResult('<div class="alert alert-danger"><strong>Erro de rede:</strong> ' + esc(err.message) + '</div>');
        }
    });

    if (btnReset) {
        btnReset.addEventListener('click', async function () {
            if (!confirm('Isso apagará TODOS os contratos locais (ARPs serão preservadas) e reimportará tudo da API.\n\nContinuar?')) return;

            showRunning('Apagando contratos locais...');
            try {
                // 1. Reset
                var res1 = await fetch('/sync/reset-contratos', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: '_csrf=' + encodeURIComponent(getCsrf()),
                });
                var d1 = await res1.json();
                if (!d1.success) {
                    showResult('<div class="alert alert-danger"><strong>Erro no reset:</strong> ' + esc(d1.error) + '</div>');
                    return;
                }

                // 2. Sync
                if (runningMsg) runningMsg.textContent = d1.deletados + ' contratos removidos. Reimportando da API...';
                var res2 = await fetch('/sync/tjpa', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: '_csrf=' + encodeURIComponent(getCsrf()),
                });
                var d2 = await res2.json();
                if (d2.success) {
                    showResult(
                        '<div class="alert alert-info mb-3"><i class="bi bi-check2-circle me-1"></i>' + esc(d1.message) + '</div>' +
                        syncResultHtml(d2)
                    );
                } else {
                    showResult('<div class="alert alert-danger"><strong>Erro no sync:</strong> ' + esc(d2.error || 'Falha desconhecida.') + '</div>');
                }
            } catch (err) {
                showResult('<div class="alert alert-danger"><strong>Erro de rede:</strong> ' + esc(err.message) + '</div>');
            }
        });
    }

    // Reseta modal ao fechar
    document.getElementById('modalSyncTjpa')?.addEventListener('hidden.bs.modal', function () {
        idle.classList.remove('d-none');
        running.classList.add('d-none');
        resultDiv.classList.add('d-none');
        resultBody.innerHTML = '';
        btnRun.disabled = false;
    });
})();
