<?php $__env->startSection('title', 'Проверка документов'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $status = strtolower((string) $user->verification_status);
    if ($status === 'active') {
        $user->verification_status = 'approved';
        $user->save();
        $status = 'approved';
    }

    $isApproved = $status === 'approved';
    $title = $isApproved ? __('Верификация завершена') : __('Документы на проверке');
    $message = match ($status) {
        'rejected' => __('К сожалению, документы не прошли проверку. Обратитесь в поддержку для повторной отправки.'),
        'pending', 'hold' => __('Процедура верификации занимает от 10 минут до 3 часов. Мы уведомим вас о результате.'),
        default => __('Статус будет обновлён после проверки документов.'),
    };
?>

<div class="wrapper">
    <main class="page">
        <div class="auth-page">
            <div class="auth">
                <div class="logo">
                    <img src="<?php echo e(asset('personal-acc/img/logo.svg')); ?>" alt="logo">
                </div>

                <div class="loading">
                    <div class="loading__circle">
                        <svg viewBox="0 0 120 120">
                            <defs>
                                <linearGradient id="gradient" x1="0" y1="1" x2="1" y2="0">
                                    <stop offset="0%" stop-color="#0B69B7" />
                                    <stop offset="100%" stop-color="#052E51" />
                                </linearGradient>
                            </defs>
                            <circle class="bg" cx="60" cy="60" r="54" />
                            <circle class="progress" cx="60" cy="60" r="54" stroke-dasharray="339.2920065877" stroke-dashoffset="339.2920065877"></circle>
                        </svg>
                        <span class="loading__percent"><?php echo e($isApproved ? '100%' : '56%'); ?></span>
                    </div>
                </div>

                <div class="verify-text">
                    <p style="font-weight: 600; font-size:1.15rem; margin-bottom:0.75rem;"><?php echo e($title); ?></p>
                    <p><?php echo nl2br(e($message)); ?></p>

                    <?php if(session('status')): ?>
                        <p style="margin-top:0.75rem; font-size:0.9rem; color:#0B69B7; font-weight:600;">
                            <?php echo e(session('status')); ?>

                        </p>
                    <?php endif; ?>

                    <?php if($isApproved): ?>
                        <a class="btn" href="<?php echo e(route('user.dashboard')); ?>" style="margin-top:1.5rem; display:inline-flex;">Перейти в кабинет</a>
                    <?php else: ?>
                        <form method="POST" action="<?php echo e(route('user.logout')); ?>" style="margin-top:1.5rem;">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="btn" style="width:100%; background:rgba(11,105,183,0.1); color:#0B69B7;">Выйти из аккаунта</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.loading').forEach(function (loader) {
            const progressCircle = loader.querySelector('.progress');
            const percentText = loader.querySelector('.loading__percent');
            if (!progressCircle || !percentText) {
                return;
            }

            const radius = Number(progressCircle.getAttribute('r')) || 54;
            const circumference = 2 * Math.PI * radius;

            progressCircle.style.strokeDasharray = circumference;
            progressCircle.style.strokeDashoffset = circumference;
            progressCircle.style.transition = 'stroke-dashoffset 0.6s ease';

            const updateCircle = function (percent) {
                const value = Math.max(0, Math.min(percent, 100));
                progressCircle.style.strokeDashoffset = circumference - (value / 100) * circumference;
            };

            const observer = new MutationObserver(function () {
                const raw = (percentText.textContent || '0').replace(/[^\d]/g, '');
                updateCircle(parseInt(raw || '0', 10));
            });

            observer.observe(percentText, { characterData: true, childList: true, subtree: true });

            const initial = parseInt((percentText.textContent || '0').replace(/[^\d]/g, ''), 10) || 0;
            updateCircle(initial);
        });
    });
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/olaf-dashboard/resources/views/user/auth/verify.blade.php ENDPATH**/ ?>