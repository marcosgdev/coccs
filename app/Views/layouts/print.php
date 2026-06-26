<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Relatório') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --pr-accent: #1a3a5c;
            --pr-accent2: #2563eb;
            --pr-muted: #64748b;
            --pr-border: #e2e8f0;
            --pr-ok: #16a34a;
            --pr-warn: #d97706;
            --pr-danger: #dc2626;
        }
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: #f1f5f9; color: #1e293b; margin: 0; }

        /* Barra de ações (some ao imprimir) */
        .print-toolbar {
            position: sticky; top: 0; z-index: 100;
            background: var(--pr-accent); color: #fff;
            padding: 12px 24px; display: flex; align-items: center; gap: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,.2);
        }
        .print-toolbar .pt-title { font-weight: 700; font-size: 1rem; flex: 1; }
        .print-toolbar .pt-badge { background: rgba(255,255,255,.15); border-radius: 20px; padding: 3px 12px; font-size: .78rem; }
        .btn-print { background: #2563eb; color: #fff; border: none; border-radius: 8px; padding: 8px 20px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: .9rem; }
        .btn-print:hover { background: #1d4ed8; }
        .btn-back { background: rgba(255,255,255,.15); color: #fff; border: none; border-radius: 8px; padding: 8px 16px; font-size: .85rem; cursor: pointer; text-decoration: none; display: flex; align-items: center; gap: 6px; }
        .btn-back:hover { background: rgba(255,255,255,.25); color: #fff; }

        /* Filtros */
        .print-filters { background: #fff; border-bottom: 1px solid var(--pr-border); padding: 12px 24px; display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }

        /* Área do relatório */
        .report-wrap { max-width: 960px; margin: 32px auto; padding: 0 16px 64px; }

        /* Capa */
        .report-cover {
            background: linear-gradient(135deg, var(--pr-accent) 0%, #1e4d8c 60%, #2563eb 100%);
            color: #fff; border-radius: 16px; padding: 48px 40px 36px; margin-bottom: 32px;
            position: relative; overflow: hidden;
        }
        .report-cover::before {
            content: ''; position: absolute; right: -40px; top: -40px;
            width: 280px; height: 280px; border-radius: 50%;
            background: rgba(255,255,255,.05);
        }
        .report-cover::after {
            content: ''; position: absolute; right: 60px; bottom: -60px;
            width: 180px; height: 180px; border-radius: 50%;
            background: rgba(255,255,255,.04);
        }
        .cover-orgao   { font-size: .82rem; font-weight: 600; opacity: .75; text-transform: uppercase; letter-spacing: .1em; margin-bottom: 8px; }
        .cover-titulo  { font-size: 2rem; font-weight: 800; line-height: 1.2; margin-bottom: 6px; }
        .cover-subtitulo { font-size: 1rem; opacity: .8; margin-bottom: 28px; }
        .cover-stats   { display: flex; gap: 32px; flex-wrap: wrap; }
        .cover-stat    { text-align: center; }
        .cover-stat-num { font-size: 2rem; font-weight: 800; line-height: 1; }
        .cover-stat-label { font-size: .7rem; opacity: .7; text-transform: uppercase; letter-spacing: .07em; margin-top: 4px; }
        .cover-meta { position: absolute; right: 40px; bottom: 36px; text-align: right; font-size: .72rem; opacity: .6; }

        /* Cards de resumo por secretaria */
        .sec-card {
            background: #fff; border-radius: 12px; margin-bottom: 28px;
            box-shadow: 0 1px 4px rgba(0,0,0,.08); overflow: hidden;
            break-inside: avoid;
        }
        .sec-header {
            background: var(--pr-accent); color: #fff;
            padding: 14px 20px; display: flex; align-items: center; gap: 16px;
        }
        .sec-initial {
            width: 40px; height: 40px; border-radius: 10px;
            background: rgba(255,255,255,.2); display: flex; align-items: center;
            justify-content: center; font-weight: 800; font-size: 1.1rem; flex-shrink: 0;
        }
        .sec-name      { font-weight: 700; font-size: 1rem; flex: 1; }
        .sec-stats     { display: flex; gap: 20px; text-align: right; }
        .sec-stat-val  { font-size: 1.1rem; font-weight: 700; }
        .sec-stat-lbl  { font-size: .6rem; opacity: .7; text-transform: uppercase; }

        /* Barra de progresso de valor */
        .valor-bar-wrap { padding: 10px 20px 4px; background: #f8fafc; border-bottom: 1px solid var(--pr-border); }
        .valor-bar-track { height: 6px; background: #e2e8f0; border-radius: 3px; overflow: hidden; }
        .valor-bar-fill  { height: 100%; background: linear-gradient(90deg, #2563eb, #1d4ed8); border-radius: 3px; }
        .valor-bar-label { display: flex; justify-content: space-between; font-size: .65rem; color: var(--pr-muted); margin-top: 4px; }

        /* Tabela */
        .sec-table { width: 100%; border-collapse: collapse; font-size: .78rem; }
        .sec-table thead th {
            background: #f8fafc; color: var(--pr-muted); text-transform: uppercase;
            font-size: .64rem; letter-spacing: .05em; padding: 8px 12px;
            border-bottom: 2px solid var(--pr-border); font-weight: 700;
        }
        .sec-table tbody td { padding: 9px 12px; border-bottom: 1px solid var(--pr-border); vertical-align: middle; }
        .sec-table tbody tr:last-child td { border-bottom: none; }
        .sec-table tbody tr:hover { background: #f8fafc; }
        .chave-link { font-weight: 700; color: var(--pr-accent2); text-decoration: none; }
        .fornecedor-cell { color: #374151; max-width: 200px; }
        .valor-cell { font-weight: 600; text-align: right; white-space: nowrap; }
        .dias-badge {
            display: inline-block; padding: 2px 8px; border-radius: 12px;
            font-size: .68rem; font-weight: 700; white-space: nowrap;
        }
        .dias-ok      { background: #dcfce7; color: var(--pr-ok); }
        .dias-warn    { background: #fef9c3; color: var(--pr-warn); }
        .dias-danger  { background: #fee2e2; color: var(--pr-danger); }
        .dias-expired { background: #f1f5f9; color: var(--pr-muted); }
        .sit-badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: .65rem; font-weight: 700; }
        .sit-vigente  { background: #dcfce7; color: var(--pr-ok); }
        .sit-expirado { background: #fee2e2; color: var(--pr-danger); }
        .sit-outro    { background: #f1f5f9; color: var(--pr-muted); }

        /* Rodapé */
        .report-footer { text-align: center; color: var(--pr-muted); font-size: .72rem; padding: 24px; }

        /* IMPRESSÃO */
        @media print {
            @page { margin: 1.2cm 1cm; size: A4 portrait; }

            body { background: #fff; font-size: 11px; }
            .print-toolbar, .print-filters, .no-print { display: none !important; }

            /* Relatório ocupa a página inteira */
            .report-wrap { max-width: 100%; margin: 0; padding: 0 0 24px; }

            /* Capa: sem bordas arredondadas, sem efeitos que o browser não reproduz bem */
            .report-cover {
                border-radius: 0;
                margin-bottom: 16px;
                padding: 28px 24px 20px;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            /* Capa: tira o position:absolute da meta para não sobrepor os stats */
            .cover-meta {
                position: static !important;
                text-align: left;
                margin-top: 16px;
                border-top: 1px solid rgba(255,255,255,.2);
                padding-top: 10px;
                opacity: .65;
            }
            .cover-titulo  { font-size: 1.5rem; }
            .cover-stat-num { font-size: 1.5rem; }
            .cover-stats   { gap: 20px; }

            /* Cards de secretaria */
            .sec-card {
                box-shadow: none;
                border: 1px solid #e2e8f0;
                break-inside: avoid;
                margin-bottom: 16px;
            }
            .sec-header {
                padding: 10px 14px;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .sec-name  { font-size: .88rem; }
            .sec-stat-val { font-size: .95rem; }

            /* Barras e badges */
            .valor-bar-fill { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .dias-badge, .sit-badge { -webkit-print-color-adjust: exact; print-color-adjust: exact; }

            /* Tabela mais compacta */
            .sec-table { font-size: .7rem; }
            .sec-table thead th { padding: 5px 8px; font-size: .58rem; }
            .sec-table tbody td { padding: 5px 8px; }

            /* Rodapé não quebra de página */
            .report-footer { break-inside: avoid; font-size: .65rem; padding: 12px; }
        }
    </style>
</head>
<body>
<?= $content ?>
<script>
document.getElementById('btn-print')?.addEventListener('click', () => window.print());
</script>
<?= $scripts ?? '' ?>
</body>
</html>
