<div class="list-header">
    <div>
        <h1>📋 <?= e(t('todo.list_title')) ?></h1>
        <p class="text-muted">
            <?= e(t('todo.count_summary', ['total' => $total, 'pending' => $pending, 'completed' => $completed])) ?>
        </p>
    </div>
    <a href="/todo/create" class="btn btn-primary"><?= e(t('todo.new_task_button')) ?></a>
</div>

<?php if (empty($todo)): ?>
    <div class="card empty-state">
        <div class="icon">📝</div>
        <p><?= e(t('todo.empty_state')) ?></p>
        <br>
        <a href="/todo/create" class="btn btn-primary"><?= e(t('todo.create_task_button')) ?></a>
    </div>
<?php else: ?>
    <div class="card card-flush">
        <table>
            <thead>
                <tr>
                    <th><?= e(t('todo.th_status')) ?></th>
                    <th><?= e(t('todo.th_title')) ?></th>
                    <th><?= e(t('todo.th_description')) ?></th>
                    <th><?= e(t('todo.th_created')) ?></th>
                    <th><?= e(t('todo.th_actions')) ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($todo as $td): ?>
                <tr>
                    <td>
                        <?php if ($td['completed']): ?>
                            <span class="badge badge-success"><?= e(t('todo.status_done')) ?></span>
                        <?php else: ?>
                            <span class="badge badge-warning"><?= e(t('todo.status_pending')) ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="/todo/<?= e($td['id']) ?>"
                           class="title-link <?= $td['completed'] ? 'is-completed' : '' ?>">
                            <?= e($td['title']) ?>
                        </a>
                    </td>
                    <td class="text-muted truncate">
                        <?= e($td['description'] ?: '—') ?>
                    </td>
                    <td class="text-muted nowrap" style="font-size:0.8rem;">
                        <?= e(format_date($td['created_at'])) ?>
                    </td>
                    <td>
                        <div class="actions">
                            <a href="/todo/<?= e($td['id']) ?>/edit" class="btn btn-secondary btn-sm"><?= e(t('common.edit')) ?></a>

                            <!-- Toggle completed / pending -->
                            <form method="POST" action="/todo/<?= e($td['id']) ?>" class="inline-form">
                                <input type="hidden" name="_method" value="PUT">
                                <input type="hidden" name="title"       value="<?= e($td['title']) ?>">
                                <input type="hidden" name="description" value="<?= e($td['description']) ?>">
                                <input type="hidden" name="completed"   value="<?= $td['completed'] ? '0' : '1' ?>">
                                <button type="submit" class="btn btn-secondary btn-sm">
                                    <?= $td['completed'] ? e(t('todo.action_reopen')) : e(t('todo.action_complete')) ?>
                                </button>
                            </form>

                            <!-- Delete -->
                            <form method="POST" action="/todo/<?= e($td['id']) ?>" class="inline-form"
                                  onsubmit="return confirm('<?= e(t('todo.confirm_delete')) ?>')">
                                <input type="hidden" name="_method" value="DELETE">
                                <button type="submit" class="btn btn-danger btn-sm"><?= e(t('common.delete')) ?></button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>