<?php $__env->startSection('title', 'Кабинет'); ?>
<?php $__env->startSection('content'); ?>

<?php
    $withdrawCardErrors = $errors->withdraw_card ?? null;
    $withdrawIbanErrors = $errors->withdraw_iban ?? null;
    $withdrawCryptoErrors = $errors->withdraw_crypto ?? null;
    $withdrawInitialMethod = old('method') ?: session('last_withdraw_method');
    $withdrawActiveMethod = $withdrawInitialMethod ?: 'card';
    $supportMessages = $supportMessages ?? [];
    $supportErrors = $errors->support ?? null;
    $supportShouldOpen = session()->has('support_open') || ($supportErrors && $supportErrors->any());
    $violationErrors = $errors->violation ?? null;
    $violationStatus = session('violation_status');
    $violationShouldOpen = (bool) ($violationStatus || ($violationErrors && $violationErrors->any()));
?>

<div class="wrapper">
<header class="header">
<div class="container">
<div class="header__inner">
<a class="header__logo logo" href="#"><img alt="" src="<?php echo e(asset('personal-acc/img/logo.svg')); ?>"/></a>
<div class="header__actions">
<button class="btn btn--light btn-support" data-support-btn="" type="button">
							Support
							<span class="btn__icon">
<img alt="support" src="<?php echo e(asset('personal-acc/img/icons/support.svg')); ?>"/>
</span>
</button>
<div class="desktop">
<button class="btn" data-popup="#withdraw-modal" type="button"><span>Withdrawal of funds</span>
</button>
</div>
<div class="mobile">
<a class="btn" href="<?php echo e(route('user.withdraw')); ?>">Withdrawal <span class="btn__icon">
<img alt="withdraw" src="<?php echo e(asset('personal-acc/img/icons/withdraw.svg')); ?>"/>
</span></a>
</div>
<form method="POST" action="<?php echo e(route('user.logout')); ?>" style="margin-left: 0.5rem;">
<?php echo csrf_field(); ?>
<button class="btn btn--light" type="submit">Выход</button>
</form>
</div>
</div>
</div>
</header>
<main class="page">
<div class="container">
<div class="grid">
<div class="mobile-nav-tabs">
<button class="active" data-tab="main" type="button">
<span>Accounts</span>
<span>
<svg fill="none" height="18" viewBox="0 0 18 18" width="18" xmlns="http://www.w3.org/2000/svg">
<rect x="2" y="4" width="14" height="10" rx="2" stroke="currentColor" stroke-width="1.5"></rect>
<path d="M4 7.5H14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
<rect x="4" y="10.5" width="4" height="2" rx="1" fill="currentColor"></rect>
<circle cx="12.5" cy="11.5" r="1.5" fill="currentColor"></circle>
</svg>
</span>
</button>
<button data-tab="aside" type="button">
<span>Transactions</span>
<span>
<svg fill="none" height="18" viewbox="0 0 19 18" width="19" xmlns="http://www.w3.org/2000/svg">
<path d="M4.4091 5.65924C4.4091 3.9926 5.7652 2.6365 7.43184 2.6365H13.2438C13.0344 1.90404 12.3669 1.36377 11.5682 1.36377H4.25001C3.28464 1.36377 2.5 2.14841 2.5 3.11378V12.9775C2.5 13.9428 3.28464 14.7275 4.25001 14.7275H4.4091V5.65924Z" fill="currentColor"></path>
<path d="M14.75 3.90918H7.43183C6.46646 3.90918 5.68182 4.69382 5.68182 5.65919V14.8865C5.68182 15.8519 6.46646 16.6365 7.43183 16.6365H14.75C15.7154 16.6365 16.5001 15.8519 16.5001 14.8865V5.65919C16.5001 4.69382 15.7154 3.90918 14.75 3.90918ZM13.4773 14.7274H8.70456C8.44111 14.7274 8.22729 14.5136 8.22729 14.2501C8.22729 13.9867 8.44111 13.7729 8.70456 13.7729H13.4773C13.7408 13.7729 13.9546 13.9867 13.9546 14.2501C13.9546 14.5136 13.7408 14.7274 13.4773 14.7274ZM13.4773 12.1819H8.70456C8.44111 12.1819 8.22729 11.9681 8.22729 11.7047C8.22729 11.4412 8.44111 11.2274 8.70456 11.2274H13.4773C13.7408 11.2274 13.9546 11.4412 13.9546 11.7047C13.9546 11.9681 13.7408 12.1819 13.4773 12.1819ZM13.4773 9.95466H8.70456C8.44111 9.95466 8.22729 9.74084 8.22729 9.47738C8.22729 9.21393 8.44111 9.00011 8.70456 9.00011H13.4773C13.7408 9.00011 13.9546 9.21393 13.9546 9.47738C13.9546 9.74084 13.7408 9.95466 13.4773 9.95466ZM13.4773 7.40919H8.70456C8.44111 7.40919 8.22729 7.19537 8.22729 6.93192C8.22729 6.66846 8.44111 6.45464 8.70456 6.45464H13.4773C13.7408 6.45464 13.9546 6.66846 13.9546 6.93192C13.9546 7.19537 13.7408 7.40919 13.4773 7.40919Z" fill="currentColor"></path>
</svg>
</span>
</button>
</div>
<?php echo $__env->make('client.dashboard.partials.overview', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<div class="chat" data-support-window="" data-support-open="<?php echo e($supportShouldOpen ? 'true' : 'false'); ?>" data-support-fetch-url="<?php echo e(route('user.support.messages')); ?>" data-support-send-url="<?php echo e(route('user.support.store')); ?>" data-support-empty-text="<?php echo e(__('There are no messages yet.')); ?>">
<div class="chat__head">
<div class="chat__item">
<img alt="person" src="<?php echo e(asset('personal-acc/img/icons/person-support.svg')); ?>"/>
<span>Support Service</span>
</div>
<button class="chat__close" type="button">
<svg fill="none" height="20" viewbox="0 0 20 20" width="20" xmlns="http://www.w3.org/2000/svg">
<path d="M1 1L19 19M19 1L1 19" stroke="black" stroke-linecap="round" stroke-width="2"></path>
</svg>
</button>
</div>
<div class="chat__body">
<div class="chat__list" data-support-messages>
    <?php $__empty_1 = true; $__currentLoopData = $supportMessages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php
            $isUserMessage = ($message['direction'] ?? '') === 'outbound';
            $bubbleClasses = 'chat__item' . ($isUserMessage ? ' chat__item--answer' : '');
        ?>
        <div class="<?php echo e($bubbleClasses); ?>">
            <div class="chat__item-content">
                <span class="chat__item-text"><?php echo nl2br(e($message['message'] ?? '')); ?></span>
                <?php if(! empty($message['created_at'])): ?>
                    <span class="chat__item-time"><?php echo e($message['created_at']); ?></span>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="chat__item"><?php echo e(__('There are no messages yet.')); ?></div>
    <?php endif; ?>
</div>
</div>
<form class="chat__bottom" method="POST" action="<?php echo e(route('user.support.store')); ?>" data-support-form>
<?php echo csrf_field(); ?>
<div class="field <?php if($supportErrors && $supportErrors->has('message')): ?> has-error <?php endif; ?>">
<input class="chat__input" name="message" placeholder="Write here..." type="text" value="<?php echo e(old('message')); ?>"/>
<?php $__errorArgs = ['message', 'support'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
    <span class="error-message"><?php echo e($message); ?></span>
<?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
</div>
<span class="chat__error" data-support-error></span>
<button class="chat__submit" type="submit"><img alt="send" src="<?php echo e(asset('personal-acc/img/icons/send.svg')); ?>"/></button>
</form>
</div>
</div>
</div>
</main>
</div>
<?php echo $__env->make('user.partials.violation-modal', [
    'violationErrors' => $violationErrors,
    'violationStatus' => $violationStatus,
    'violationShouldOpen' => $violationShouldOpen,
], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<div aria-hidden="true" class="popup popup--sm" id="create-modal">
<div class="popup__wrapper">
<div class="popup__content">
<div class="create-account">
<div class="create-account__title">Создание нового счёта</div>
<form action="#" class="create-account__form">
<?php echo csrf_field(); ?>

<div class="field"><input placeholder="Название счёта (Account name)" type="text"/></div>
<div class="field"><input placeholder="Номер счёта (Account number)" type="text"/></div>
<div class="field"><input placeholder="Тип счёта (Account type)" type="text"/></div>
<div class="field"><input placeholder="Банк (Bank)" type="text"/></div>
<div class="field"><input placeholder="Инициалы клиента (Client's fullname)" type="text"/></div>
<div class="field"><input placeholder="Срок действия (Expiration date)" type="text"/></div>
<select>
<option disabled="" selected="" value="">Статус</option>
<option value="2">Active</option>
<option value="3">Hold</option>
<option value="4">Blocked</option>
</select>
<button class="btn btn--md" type="submit">Добавить новый счёт</button>
</form>
</div>
</div>
</div>
</div>
<?php echo $__env->make('user.partials.withdraw-modal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>
</div>
</div>
</div>
</div>
</div>
</div>
</div>
</div>
</div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/olaf-dashboard/resources/views/user/dashboard.blade.php ENDPATH**/ ?>