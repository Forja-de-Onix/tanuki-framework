<div class="view-container">
    <div class="card">
        <h1><?= e(t('auth.register_title')) ?></h1>
        <form method="POST" action="/register">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="name"><?= e(t('auth.name_label')) ?></label>
                <input type="text" id="name" name="name" value="<?= e(old('name')) ?>" required autofocus>
            </div>
            <div class="form-group">
                <label for="email"><?= e(t('auth.email_label')) ?></label>
                <input type="email" id="email" name="email" value="<?= e(old('email')) ?>" required>
            </div>
            <div class="form-group">
                <label for="password"><?= e(t('auth.password_label')) ?></label>
                <input type="password" id="password" name="password" minlength="8" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><?= e(t('auth.register_button')) ?></button>
            </div>
        </form>
    </div>
    <p class="text-muted" style="text-align:center;margin-top:1rem;">
        <?= e(t('auth.have_account')) ?> <a href="/login"><?= e(t('auth.login_title')) ?></a>
    </p>
</div>