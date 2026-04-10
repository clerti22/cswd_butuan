(() => {
    if (window.clientRecordMode) {
        return;
    }

    const STYLE_ID = 'client-record-mode-styles';

    const ensureStyles = () => {
        if (document.getElementById(STYLE_ID)) {
            return;
        }

        const style = document.createElement('style');
        style.id = STYLE_ID;
        style.textContent = `
            .client-record-controls {
                display: flex;
                justify-content: flex-end;
                gap: 0.75rem;
                margin: 0 0 1rem;
                flex-wrap: wrap;
            }

            .client-record-controls__btn {
                appearance: none;
                border: 0;
                border-radius: 999px;
                padding: 0.8rem 1.35rem;
                font: inherit;
                font-weight: 700;
                cursor: pointer;
                transition: transform 0.18s ease, box-shadow 0.18s ease, opacity 0.18s ease;
            }

            .client-record-controls__btn:hover:not(:disabled) {
                transform: translateY(-1px);
            }

            .client-record-controls__btn:disabled {
                opacity: 0.6;
                cursor: not-allowed;
            }

            .client-record-controls__btn--edit,
            .client-record-controls__btn--save {
                color: #fff;
                background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
                box-shadow: 0 14px 24px rgba(37, 99, 235, 0.22);
            }

            .client-record-controls__btn--cancel {
                background: #e5e7eb;
                color: #1f2937;
            }

            .client-record-controls__btn[hidden] {
                display: none !important;
            }
        `;

        document.head.appendChild(style);
    };

    const getRecordId = () => {
        const params = new URLSearchParams(window.location.search);
        const rawValue = params.get('id');
        const parsedValue = Number(rawValue);

        if (!Number.isInteger(parsedValue) || parsedValue <= 0) {
            return null;
        }

        return parsedValue;
    };

    const setElementReadOnlyState = (element, isEditable) => {
        if (!(element instanceof HTMLElement)) {
            return;
        }

        if (element.matches('button, [data-record-mode-ignore], .client-record-controls__btn')) {
            return;
        }

        if (element instanceof HTMLInputElement || element instanceof HTMLTextAreaElement) {
            if (element.type === 'hidden') {
                return;
            }

            if (['checkbox', 'radio', 'date', 'file'].includes(element.type)) {
                element.disabled = !isEditable;
                return;
            }

            element.readOnly = !isEditable;
            element.disabled = false;
            return;
        }

        if (element instanceof HTMLSelectElement) {
            element.disabled = !isEditable;
        }
    };

    const toggleContainerEditState = (container, isEditable, extraIgnoredSelectors = []) => {
        if (!(container instanceof HTMLElement)) {
            return;
        }

        const ignoredMatches = extraIgnoredSelectors.join(',');
        container.querySelectorAll('input, select, textarea, button').forEach((element) => {
            if (ignoredMatches && element.matches(ignoredMatches)) {
                return;
            }

            setElementReadOnlyState(element, isEditable);
        });
    };

    const mountControls = ({
        anchor,
        submitButton,
        onEnableEdit,
        onCancelEdit,
        onSaveStateChange
    }) => {
        ensureStyles();

        const controls = document.createElement('div');
        controls.className = 'client-record-controls';
        controls.innerHTML = `
            <button type="button" class="client-record-controls__btn client-record-controls__btn--edit">Edit</button>
            <button type="button" class="client-record-controls__btn client-record-controls__btn--save" hidden>Save / Update</button>
            <button type="button" class="client-record-controls__btn client-record-controls__btn--cancel" hidden>Cancel Edit</button>
        `;

        const editButton = controls.querySelector('.client-record-controls__btn--edit');
        const saveButton = controls.querySelector('.client-record-controls__btn--save');
        const cancelButton = controls.querySelector('.client-record-controls__btn--cancel');

        const setEditing = (isEditing) => {
            editButton.hidden = isEditing;
            saveButton.hidden = !isEditing;
            cancelButton.hidden = !isEditing;

            if (submitButton instanceof HTMLElement) {
                submitButton.hidden = !isEditing;
                submitButton.textContent = 'Save / Update';
            }

            onSaveStateChange?.(isEditing);
        };

        editButton?.addEventListener('click', async () => {
            const confirmed = await window.clientFormSubmitConfirmation?.confirm('Are you sure you want to edit this record?');
            if (!confirmed) {
                return;
            }

            onEnableEdit?.();
            setEditing(true);
        });

        cancelButton?.addEventListener('click', () => {
            onCancelEdit?.();
            setEditing(false);
        });

        saveButton?.addEventListener('click', () => {
            if (submitButton instanceof HTMLElement) {
                submitButton.click();
            }
        });

        if (anchor instanceof HTMLElement) {
            anchor.prepend(controls);
        }

        setEditing(false);

        return {
            setEditing,
            controls,
        };
    };

    window.clientRecordMode = {
        getRecordId,
        hasRecordId() {
            return getRecordId() !== null;
        },
        toggleContainerEditState,
        mountControls,
    };
})();
