<div class="view-container">
    <div class="card">
        <h1><?= e(t('profile.edit_title')) ?></h1>
        <form method="POST" action="/profile">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="name"><?= e(t('auth.name_label')) ?></label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="<?= e(old('name') ?: $user['name']) ?>"
                    required
                    autofocus
                >
            </div>

            <hr class="divider">

            <p class="text-muted" style="font-size:0.85rem;margin-bottom:1rem;">
                <?= e(t('profile.password_section_hint')) ?>
            </p>

            <div class="form-group">
                <label for="current_password"><?= e(t('profile.current_password_label')) ?></label>
                <input type="password" id="current_password" name="current_password">
            </div>

            <div class="form-group">
                <label for="new_password"><?= e(t('profile.new_password_label')) ?></label>
                <input type="password" id="new_password" name="new_password" minlength="8">
            </div>

            <div class="form-group">
                <label for="new_password_confirmation"><?= e(t('profile.confirm_password_label')) ?></label>
                <input type="password" id="new_password_confirmation" name="new_password_confirmation" minlength="8">
            </div>

            <div class="form-actions">
                <a href="/" class="btn btn-secondary"><?= e(t('common.cancel')) ?></a>
                <button type="submit" class="btn btn-primary"><?= e(t('common.save')) ?></button>
            </div>
        </form>
    </div>
</div>