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
<span>Brokers</span>
<span>
<svg fill="none" height="18" viewbox="0 0 18 18" width="18" xmlns="http://www.w3.org/2000/svg">
<path d="M11.8267 5.85585C11.4438 5.91737 9.27407 6.28103 9.13728 6.28103C8.99996 6.28103 8.99996 6.28103 8.99996 6.28103C8.99996 6.28103 8.99996 6.28103 8.86264 6.28103C8.72532 6.28103 6.55607 5.91737 6.17318 5.85585C5.51509 5.74929 4.96578 5.93551 4.77351 6.01514C3.96601 6.35022 4.23738 7.13357 4.41646 7.42361C4.66421 7.82242 5.60408 8.86941 6.53023 8.99189C7.73764 9.15066 8.78023 8.24756 8.99996 8.24756C9.21912 8.24756 10.2623 9.15066 11.4697 8.99189C12.3958 8.86941 13.3363 7.82238 13.5835 7.42361C13.7625 7.13357 14.0339 6.35025 13.2264 6.01514C13.0336 5.93548 12.4848 5.74926 11.8267 5.85585ZM6.49177 7.76909C6.10175 7.39172 6.16658 7.01267 6.62143 6.9819C7.07737 6.95005 7.84532 7.14011 7.85794 7.39168C7.89036 8.02123 6.88179 8.14758 6.49177 7.76909ZM11.5081 7.76909C11.1176 8.14758 10.109 8.02123 10.142 7.39172C10.1546 7.14014 10.9226 6.95005 11.3774 6.98194C11.8333 7.0127 11.8982 7.39172 11.5081 7.76909Z" fill="currentColor"></path>
<path d="M14.1592 4.07482C14.1592 3.89795 13.9757 3.54417 13.6467 3.47277C13.1715 0.673453 10.426 0 9 0C7.57396 0 4.8285 0.673453 4.3528 3.47277C4.02321 3.54417 3.84082 3.89795 3.84082 4.07482C3.84082 4.25225 3.84082 5.24377 3.84082 5.24377H14.1592C14.1592 5.24377 14.1592 4.25225 14.1592 4.07482Z" fill="currentColor"></path>
<path d="M12.5409 13.4377C12.5409 12.7274 12.3201 11.9507 11.6186 11.9507H6.38083C5.67989 11.9507 5.45854 12.7274 5.45854 13.4377C5.45854 13.6063 2.17639 14.4512 2.17639 16.3436C2.17639 16.7836 3.24592 17.9997 8.96264 17.9997H9.03678C14.7541 17.9997 15.8236 16.7835 15.8236 16.3436C15.8236 14.4512 12.5409 13.6063 12.5409 13.4377Z" fill="currentColor"></path>
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

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/admin/Pictures/html_blade-codex-fix-edit-user-and-edit-account-functionality-50to50/resources/views/user/dashboard.blade.php ENDPATH**/ ?>