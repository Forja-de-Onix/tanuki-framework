<div class="view-container">
    <div class="view-header">
        <a href="/todo" class="btn btn-secondary btn-sm">← Back</a>
        <h1><?= e($todo['title']) ?></h1>
    </div>

    <div class="card">
        <div class="detail-header-row">
            <div>
                <?php if ($todo['completed']): ?>
                    <span class="badge badge-success badge-lg">✓ Completed</span>
                <?php else: ?>
                    <span class="badge badge-warning badge-lg">⏳ Pending</span>
                <?php endif; ?>
            </div>
            <a href="/todo/<?= e($todo['id']) ?>/edit" class="btn btn-secondary btn-sm">Edit</a>
        </div>

        <?php if (!empty($todo['description'])): ?>
            <p><?= e($todo['description']) ?></p>
        <?php else: ?>
            <p class="text-muted description-empty">No description.</p>
        <?php endif; ?>

        <hr class="divider">

        <div class="meta-row">
            <span>📅 Created: <?= e(format_date($todo['created_at'])) ?></span>
            <?php if ($todo['updated_at'] !== $todo['created_at']): ?>
                <span>✏️ Modified: <?= e(format_date($todo['updated_at'])) ?></span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick actions -->
    <div class="quick-actions">
        <form method="POST" action="/todo/<?= e($todo['id']) ?>" class="grow">
            <input type="hidden" name="_method" value="PUT">
            <input type="hidden" name="title"       value="<?= e($todo['title']) ?>">
            <input type="hidden" name="description" value="<?= e($todo['description']) ?>">
            <input type="hidden" name="completed"   value="<?= $todo['completed'] ? '0' : '1' ?>">
            <button type="submit" class="btn btn-primary btn-block">
                <?= $todo['completed'] ? '↩ Mark as pending' : '✓ Mark as completed' ?>
            </button>
        </form>

        <form method="POST" action="/todo/<?= e($todo['id']) ?>"
              onsubmit="return confirm('Delete this task?')">
            <input type="hidden" name="_method" value="DELETE">
            <button type="submit" class="btn btn-danger">Delete</button>
        </form>
    </div>
</div>