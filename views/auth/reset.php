<div class="view-container">
    <div class="card">
        <h1><?= e(t('auth.reset_title')) ?></h1>
        <form method="POST" action="/reset-password/<?= e($token) ?>">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="password"><?= e(t('auth.password_label')) ?></label>
                <input type="password" id="password" name="password" minlength="8" required autofocus>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><?= e(t('auth.reset_password_button')) ?></button>
            </div>
        </form>
    </div>
</div>