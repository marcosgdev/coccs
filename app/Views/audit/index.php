<section class="gc-card p-3">
    <div class="table-responsive">
        <table class="table table-hover datatable align-middle w-100">
            <thead><tr><th>ID</th><th>Usuario</th><th>Acao</th><th>Tabela</th><th>Registro</th><th>IP</th><th>Data</th></tr></thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= e($log['id']) ?></td>
                    <td><?= e($log['usuario_id']) ?></td>
                    <td><?= e($log['acao']) ?></td>
                    <td><?= e($log['tabela']) ?></td>
                    <td><?= e($log['registro_id']) ?></td>
                    <td><?= e($log['ip']) ?></td>
                    <td><?= e($log['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
