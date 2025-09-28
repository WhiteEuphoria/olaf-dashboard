<?php $__env->startSection('title', 'Главная'); ?>
<?php $__env->startSection('content'); ?>

<div class="rootpage">
<h1>Адаптивная верстка «personal-acc»</h1>
<p><b>Описание:</b> Адаптиваня верстка. Анимации и проработка адаптива. Грамотная и чистая верстка</p>
<span class="descpro">Ниже вы найдете ссылки на все страницы. Для просмотра кликайте на нужную страничку
			(откроется
			в новом окне)</span>
<div class="rootpage__info">
</div>
<h2>Cтраницы:</h2>
<ol class="rootpage__list">
<li><a href="<?php echo e(route('login')); ?>" target="_blank">Логин</a></li>
<li><a href="<?php echo e(route('register')); ?>" target="_blank">Регистрация</a></li>
<li><a href="<?php echo e(route('enter')); ?>" target="_blank">Прикрепите ваши документы для проверки</a></li>
<li><a href="<?php echo e(route('verification.notice')); ?>" target="_blank">Верификация</a></li>
<li><a href="<?php echo e(route('user.dashboard')); ?>" target="_blank">Личный кабинет</a></li>
<li><a href="<?php echo e(route('user.violation')); ?>" target="_blank">Сообщить о наружении(моб)</a></li>
<li><a href="<?php echo e(route('user.withdraw')); ?>" target="_blank">Вывод(моб)</a></li>
<li><a href="<?php echo e(route('admin.login')); ?>" target="_blank">Вход (Админка)</a></li>
<li><a href="<?php echo e(route('admin.dashboard')); ?>" target="_blank">Админка</a></li>
</ol>
<!-- <h2>Страницы в разработке:</h2>
    <ol class="rootpage__list">

    </ol> -->
<h2>Модальные окна:</h2>
<ol class="rootpage__list">
<!-- <li><a target="_blank" href="modal.html">Модальные окна</a></li> -->
</ol>
<!-- -->
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/admin/Pictures/html_blade-codex-fix-edit-user-and-edit-account-functionality-50to50/resources/views/main/index.blade.php ENDPATH**/ ?>