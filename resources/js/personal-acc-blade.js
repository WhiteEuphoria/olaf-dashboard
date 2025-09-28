const initCryptoToggle = () => {
    const toggleBtn = document.querySelector('.btn-toggle-crypto-window');
    const infoBlock = document.querySelector('.type-crypto-window');
    const cryptoForm = document.querySelector('.form-crypto');

    if (!toggleBtn || !infoBlock || !cryptoForm) {
        return;
    }

    toggleBtn.addEventListener('click', () => {
        cryptoForm.classList.toggle('hide');
        infoBlock.classList.toggle('show');
    });
};

const initLoaders = () => {
    const loaders = document.querySelectorAll('.loading');

    if (!loaders.length) {
        return;
    }

    loaders.forEach((loader) => {
        const progressCircle = loader.querySelector('.progress');
        const percentText = loader.querySelector('.loading__percent');

        if (!progressCircle || !percentText) {
            return;
        }

        const radius = Number(progressCircle.getAttribute('r')) || 54;
        const circumference = 2 * Math.PI * radius;

        const updateCircle = (percent) => {
            const safePercent = Math.min(Math.max(percent, 0), 100);
            const targetOffset = circumference - (safePercent / 100) * circumference;
            progressCircle.style.strokeDashoffset = targetOffset;
        };

        progressCircle.style.strokeDasharray = circumference;
        progressCircle.style.strokeDashoffset = circumference;
        progressCircle.style.transition = 'stroke-dashoffset 0.6s ease';

        const observer = new MutationObserver(() => {
            const raw = (percentText.textContent || '0').replace(/[^\d]/g, '');
            const newPercent = parseInt(raw, 10) || 0;
            updateCircle(newPercent);
        });

        observer.observe(percentText, {
            characterData: true,
            childList: true,
            subtree: true,
        });

        const initial = parseInt((percentText.textContent || '0').replace(/[^\d]/g, ''), 10) || 0;
        updateCircle(initial);
    });
};

const initPasswordToggles = () => {
    document.querySelectorAll('.field__wrapper').forEach((wrapper) => {
        const input = wrapper.querySelector('input[type="password"]');
        const toggleButton = wrapper.querySelector('.field__icon');

        if (!input || !toggleButton) {
            return;
        }

        const icon = toggleButton.querySelector('img');
        const defaultIcon = icon ? icon.getAttribute('src') : null;
        const activeIcon = icon ? icon.getAttribute('data-active-icon') : null;

        toggleButton.addEventListener('click', () => {
            const isPassword = input.getAttribute('type') === 'password';
            input.setAttribute('type', isPassword ? 'text' : 'password');

            if (icon && activeIcon) {
                icon.setAttribute('src', isPassword ? activeIcon : defaultIcon);
            }
        });
    });
};

document.addEventListener('DOMContentLoaded', () => {
    initCryptoToggle();
    initLoaders();
    initPasswordToggles();
});
