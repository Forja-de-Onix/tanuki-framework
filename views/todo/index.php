<div class="list-header">
    <div>
        <h1>📋 TODO List</h1>
        <p class="text-muted">
            <?= $total ?> tasks &mdash; <?= $pending ?> pending, <?= $completed ?> completed
        </p>
    </div>
    <a href="/todo/create" class="btn btn-primary">+ New task</a>
</div>

<?php if (empty($todo)): ?>
    <div class="card empty-state">
        <div class="icon">📝</div>
        <p>No tasks yet. Create the first one!</p>
        <br>
        <a href="/todo/create" class="btn btn-primary">+ Create task</a>
    </div>
<?php else: ?>
    <div class="card card-flush">
        <table>
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($todo as $td): ?>
                <tr>
                    <td>
                        <?php if ($td['completed']): ?>
                            <span class="badge badge-success">✓ Done</span>
                        <?php else: ?>
                            <span class="badge badge-warning">⏳ Pending</span>
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
                            <a href="/todo/<?= e($td['id']) ?>/edit" class="btn btn-secondary btn-sm">Edit</a>

                            <!-- Toggle completed / pending -->
                            <form method="POST" action="/todo/<?= e($td['id']) ?>" class="inline-form">
                                <input type="hidden" name="_method" value="PUT">
                                <input type="hidden" name="title"       value="<?= e($td['title']) ?>">
                                <input type="hidden" name="description" value="<?= e($td['description']) ?>">
                                <input type="hidden" name="completed"   value="<?= $td['completed'] ? '0' : '1' ?>">
                                <button type="submit" class="btn btn-secondary btn-sm">
                                    <?= $td['completed'] ? '↩ Reopen' : '✓ Complete' ?>
                                </button>
                            </form>

                            <!-- Delete -->
                            <form method="POST" action="/todo/<?= e($td['id']) ?>" class="inline-form"
                                  onsubmit="return confirm('Are you sure you want to delete this task?')">
                                <input type="hidden" name="_method" value="DELETE">
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>