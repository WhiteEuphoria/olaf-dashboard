<?php $__env->startSection('title', 'Регистрация пользователя'); ?>

<?php $__env->startSection('content'); ?>
<div class="wrapper">
    <main class="page">
        <div class="auth-page">
            <div class="auth">
                <div class="logo">
                    <img src="<?php echo e(asset('personal-acc/img/logo.svg')); ?>" alt="logo">
                </div>
                <form class="auth-form" method="POST" action="<?php echo e(route('user.register.store')); ?>" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>

                    <div class="field">
                        <input type="text" name="first_name" placeholder="Имя" value="<?php echo e(old('first_name')); ?>" required autofocus>
                        <?php $__errorArgs = ['first_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <span class="field__error"><?php echo e($message); ?></span>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="field">
                        <input type="text" name="last_name" placeholder="Фамилия" value="<?php echo e(old('last_name')); ?>" required>
                        <?php $__errorArgs = ['last_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <span class="field__error"><?php echo e($message); ?></span>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="field">
                        <input type="email" name="email" placeholder="E-mail" value="<?php echo e(old('email')); ?>" required autocomplete="email">
                        <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <span class="field__error"><?php echo e($message); ?></span>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="field">
                        <div class="field__wrapper">
                            <input type="password" name="password" placeholder="Пароль" required autocomplete="new-password">
                            <button class="field__icon" type="button" data-password-toggle="register-password">
                                <img src="<?php echo e(asset('personal-acc/img/icons/eye.svg')); ?>" alt="eye">
                            </button>
                        </div>
                        <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <span class="field__error"><?php echo e($message); ?></span>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="field">
                        <div class="field__wrapper">
                            <input type="password" name="password_confirmation" placeholder="Повторите пароль" required autocomplete="new-password">
                            <button class="field__icon" type="button" data-password-toggle="register-password-confirmation">
                                <img src="<?php echo e(asset('personal-acc/img/icons/eye.svg')); ?>" alt="eye">
                            </button>
                        </div>
                    </div>

                    <div class="field">
                        <div class="file-upload" data-upload>
                            <input id="documents-input" class="file-upload__input" type="file" name="documents[]" multiple accept="image/*,.pdf" required>
                            <label for="documents-input" class="file-upload__label">
                                <span class="file-upload__icon" aria-hidden="true">📄</span>
                                Загрузить документы
                            </label>
                            <p class="file-upload__hint">Прикрепите паспорта, выписки или иные подтверждающие файлы (PDF, JPG, PNG, до 20&nbsp;МБ).</p>
                            <ul class="file-upload__list" data-upload-list></ul>
                        </div>
                        <?php $__errorArgs = ['documents'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <span class="field__error"><?php echo e($message); ?></span>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        <?php $__errorArgs = ['documents.*'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <span class="field__error"><?php echo e($message); ?></span>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <button class="btn" type="submit">Создать аккаунт</button>

                    <p style="margin-top:1rem; font-size:0.875rem; text-align:center; color:#63616C;">
                        Уже есть профиль? <a href="<?php echo e(route('user.login')); ?>" style="color:#0B69B7; font-weight:600;">Войти</a>
                    </p>
                </form>
            </div>
        </div>
    </main>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
<style>
    .file-upload {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .file-upload__input {
        display: none;
    }

    .file-upload__label {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.9rem 1.5rem;
        border-radius: 9999px;
        font-weight: 600;
        cursor: pointer;
        background: linear-gradient(135deg, #0B69B7, #052E51);
        color: #fff;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        box-shadow: 0 10px 25px rgba(11, 105, 183, 0.35);
    }

    .file-upload__label:hover {
        transform: translateY(-1px);
        box-shadow: 0 12px 30px rgba(11, 105, 183, 0.4);
    }

    .file-upload__icon {
        font-size: 1.4rem;
        line-height: 1;
    }

    .file-upload__hint {
        font-size: 0.8rem;
        color: #63616C;
        margin: 0;
    }

    .file-upload__list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
        font-size: 0.85rem;
        color: #1F2937;
    }

    .file-upload__badge {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        background: rgba(11, 105, 183, 0.08);
        color: #0B69B7;
        padding: 0.35rem 0.75rem;
        border-radius: 9999px;
        font-weight: 500;
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
            button.addEventListener('click', function () {
                const wrapper = button.closest('.field__wrapper');
                const input = wrapper ? wrapper.querySelector('input') : null;
                if (!input) {
                    return;
                }
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                button.classList.toggle('is-active', type === 'text');
            });
        });

        document.querySelectorAll('[data-upload]').forEach(function (upload) {
            const input = upload.querySelector('.file-upload__input');
            const list = upload.querySelector('[data-upload-list]');

            if (!input || !list) {
                return;
            }

            const render = function () {
                list.innerHTML = '';
                Array.from(input.files || []).forEach(function (file) {
                    const item = document.createElement('li');
                    item.innerHTML = `<span class="file-upload__badge">${file.name}</span>`;
                    list.appendChild(item);
                });
            };

            input.addEventListener('change', render);
        });
    });
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/admin/Pictures/html_blade-codex-fix-edit-user-and-edit-account-functionality-50to50/resources/views/user/auth/register.blade.php ENDPATH**/ ?>