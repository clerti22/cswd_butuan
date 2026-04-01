(() => {
    if (window.clientFormSubmitConfirmation) {
        return;
    }

    const STYLE_ID = 'client-form-submit-confirm-styles';
    const MODAL_ID = 'clientFormSubmitConfirmation';
    const TITLE_ID = 'clientFormSubmitConfirmationTitle';
    const MESSAGE_ID = 'clientFormSubmitConfirmationMessage';
    const DEFAULT_MESSAGE = 'Are you sure? Please double check the details you provided.';

    const ensureStyles = () => {
        if (document.getElementById(STYLE_ID)) {
            return;
        }

        const style = document.createElement('style');
        style.id = STYLE_ID;
        style.textContent = `
            .client-confirm-modal {
                position: fixed;
                inset: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 1.5rem;
                background: rgba(15, 23, 42, 0.58);
                backdrop-filter: blur(3px);
                opacity: 0;
                visibility: hidden;
                pointer-events: none;
                transition: opacity 0.22s ease, visibility 0.22s ease;
                z-index: 1400;
            }

            .client-confirm-modal.is-active {
                opacity: 1;
                visibility: visible;
                pointer-events: auto;
            }

            .client-confirm-modal__dialog {
                width: min(100%, 420px);
                background: #ffffff;
                color: #111827;
                border-radius: 24px;
                border: 1px solid rgba(15, 23, 42, 0.08);
                box-shadow: 0 26px 60px rgba(15, 23, 42, 0.25);
                padding: 2rem 1.5rem 1.5rem;
                text-align: center;
            }

            .client-confirm-modal__badge {
                width: 68px;
                height: 68px;
                border-radius: 999px;
                margin: 0 auto 1.25rem;
                display: flex;
                align-items: center;
                justify-content: center;
                border: 1px solid rgba(250, 204, 21, 0.4);
                background: radial-gradient(circle at center, rgba(250, 204, 21, 0.22), rgba(250, 204, 21, 0.08));
                color: #f59e0b;
                font-size: 1.8rem;
            }

            .client-confirm-modal__title {
                margin: 0;
                font-size: 1.45rem;
                font-weight: 700;
                color: #111827;
            }

            .client-confirm-modal__message {
                margin: 0.85rem 0 0;
                line-height: 1.6;
                color: #4b5563;
                font-size: 0.98rem;
            }

            .client-confirm-modal__actions {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 0.85rem;
                margin-top: 1.75rem;
            }

            .client-confirm-modal__button {
                appearance: none;
                border: 0;
                border-radius: 14px;
                padding: 0.9rem 1rem;
                font: inherit;
                font-weight: 600;
                cursor: pointer;
                transition: transform 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
            }

            .client-confirm-modal__button:hover {
                transform: translateY(-1px);
            }

            .client-confirm-modal__button--cancel {
                background: #f3f4f6;
                color: #374151;
                box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.08);
            }

            .client-confirm-modal__button--confirm {
                background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
                color: #ffffff;
                box-shadow: 0 14px 26px rgba(37, 99, 235, 0.25);
            }

            @media (max-width: 480px) {
                .client-confirm-modal__dialog {
                    padding: 1.75rem 1.15rem 1.15rem;
                    border-radius: 20px;
                }

                .client-confirm-modal__actions {
                    grid-template-columns: 1fr;
                }
            }
        `;

        document.head.appendChild(style);
    };

    const ensureModal = () => {
        let modal = document.getElementById(MODAL_ID);
        if (modal) {
            return modal;
        }

        modal = document.createElement('div');
        modal.id = MODAL_ID;
        modal.className = 'client-confirm-modal';
        modal.setAttribute('aria-hidden', 'true');
        modal.innerHTML = `
            <div class="client-confirm-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="${TITLE_ID}" aria-describedby="${MESSAGE_ID}">
                <div class="client-confirm-modal__badge" aria-hidden="true">!</div>
                <h2 class="client-confirm-modal__title" id="${TITLE_ID}">Confirm Submission</h2>
                <p class="client-confirm-modal__message" id="${MESSAGE_ID}">${DEFAULT_MESSAGE}</p>
                <div class="client-confirm-modal__actions">
                    <button type="button" class="client-confirm-modal__button client-confirm-modal__button--cancel" data-confirm-cancel>Cancel</button>
                    <button type="button" class="client-confirm-modal__button client-confirm-modal__button--confirm" data-confirm-accept>Confirm</button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        return modal;
    };

    ensureStyles();
    const modal = ensureModal();
    const messageNode = modal.querySelector(`#${MESSAGE_ID}`);
    const confirmButton = modal.querySelector('[data-confirm-accept]');
    const cancelButton = modal.querySelector('[data-confirm-cancel]');

    let activeResolver = null;
    let previousOverflow = '';
    let lastActiveElement = null;

    const close = (result) => {
        if (!activeResolver) {
            return;
        }

        modal.classList.remove('is-active');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = previousOverflow;

        const resolver = activeResolver;
        activeResolver = null;
        resolver(result);

        if (lastActiveElement instanceof HTMLElement) {
            lastActiveElement.focus();
        }
    };

    cancelButton?.addEventListener('click', () => close(false));
    confirmButton?.addEventListener('click', () => close(true));

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            close(false);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && activeResolver) {
            close(false);
        }
    });

    window.clientFormSubmitConfirmation = {
        async confirm(message = DEFAULT_MESSAGE) {
            if (activeResolver) {
                return false;
            }

            if (messageNode) {
                messageNode.textContent = message;
            }

            lastActiveElement = document.activeElement;
            previousOverflow = document.body.style.overflow;
            modal.classList.add('is-active');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';

            window.requestAnimationFrame(() => {
                confirmButton?.focus();
            });

            return new Promise((resolve) => {
                activeResolver = resolve;
            });
        }
    };
})();
