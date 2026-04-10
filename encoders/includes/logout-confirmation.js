(() => {
    const logoutModal = document.getElementById('logoutModal');
    const cancelLogoutButton = document.getElementById('cancelLogoutButton');
    const confirmLogoutButton = document.getElementById('confirmLogoutButton');

    if (!logoutModal || !cancelLogoutButton || !confirmLogoutButton) {
        return;
    }

    const logoutTriggerSelector = '[data-logout-confirm], #logoutTrigger, #logoutLink';
    let activeTrigger = null;
    let pendingAction = null;

    const resolveLogoutAction = (trigger) => {
        if (!(trigger instanceof HTMLElement)) {
            return null;
        }

        if (trigger instanceof HTMLAnchorElement) {
            const href = trigger.getAttribute('href');
            return href ? { type: 'link', url: href } : null;
        }

        const form = trigger.closest('form');
        if (form instanceof HTMLFormElement) {
            return { type: 'form', form };
        }

        const formAction = trigger.getAttribute('formaction');
        if (formAction) {
            return { type: 'link', url: formAction };
        }

        const logoutUrl = trigger.dataset.logoutUrl;
        if (logoutUrl) {
            return { type: 'link', url: logoutUrl };
        }

        return null;
    };

    const closeSidebarIfNeeded = () => {
        if (typeof window.closeSidebar === 'function') {
            window.closeSidebar();
        }
    };

    const setModalState = (isOpen) => {
        logoutModal.classList.toggle('active', isOpen);
        logoutModal.setAttribute('aria-hidden', String(!isOpen));
        document.body.style.overflow = isOpen ? 'hidden' : '';

        if (isOpen) {
            closeSidebarIfNeeded();
            confirmLogoutButton.focus();
            return;
        }

        if (activeTrigger instanceof HTMLElement) {
            activeTrigger.focus();
        }

        activeTrigger = null;
        pendingAction = null;
    };

    const openLogoutConfirmation = (trigger, action) => {
        activeTrigger = trigger;
        pendingAction = action;
        setModalState(true);
    };

    document.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof Element)) {
            return;
        }

        const trigger = target.closest(logoutTriggerSelector);
        if (!(trigger instanceof HTMLElement)) {
            return;
        }

        const action = resolveLogoutAction(trigger);
        if (action === null) {
            return;
        }

        event.preventDefault();
        openLogoutConfirmation(trigger, action);
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.matches(logoutTriggerSelector)) {
            return;
        }

        event.preventDefault();
        openLogoutConfirmation(form, { type: 'form', form });
    });

    cancelLogoutButton.addEventListener('click', () => setModalState(false));

    confirmLogoutButton.addEventListener('click', () => {
        if (pendingAction === null) {
            setModalState(false);
            return;
        }

        if (pendingAction.type === 'form') {
            pendingAction.form.submit();
            return;
        }

        window.location.href = pendingAction.url;
    });

    logoutModal.addEventListener('click', (event) => {
        if (event.target === logoutModal) {
            setModalState(false);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && logoutModal.classList.contains('active')) {
            setModalState(false);
        }
    });
})();
