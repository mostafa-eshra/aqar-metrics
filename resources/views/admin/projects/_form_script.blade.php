@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('project-form');
            const scheduleEditor = document.getElementById('collection-schedule-editor');
            const durationInput = document.getElementById('project_duration_years');
            const durationBadge = document.getElementById('collection-duration-badge');
            const excludedAlert = document.getElementById('excluded-years-alert');
            const excludedGroups = document.getElementById('excluded-years-groups');
            const scheduleStatus = document.getElementById('collection-schedule-status');
            const previewButton = document.getElementById('preview-button');
            const previewPanel = document.getElementById('preview-panel');
            const previewError = document.getElementById('preview-error');
            const previewResults = document.getElementById('preview-results');
            const previewScheduleWarning = document.getElementById('preview-schedule-warning');
            const previewAnnualCollection = document.getElementById('preview-annual-collection');
            const previewAnnualCollectionBody = document.getElementById('preview-annual-collection-body');
            const previewAnnualCollectionTotal = document.getElementById('preview-annual-collection-total');
            const cashFlowInputBody = document.getElementById('cash-flow-input-body');
            const unavailable = @json(__('app.projects.not_available'));
            const labels = {
                group: @json(__('app.projects.collection_schedule.group')),
                total: @json(__('app.projects.collection_schedule.total')),
                year: @json(__('app.projects.collection_schedule.year', ['year' => ':year'])),
                input: @json(__('app.projects.collection_schedule.input_label', ['group' => ':group', 'year' => ':year'])),
                duration: @json(__('app.projects.collection_schedule.duration_badge', ['count' => ':count'])),
                complete: @json(__('app.projects.collection_schedule.complete')),
                incomplete: @json(__('app.projects.collection_schedule.incomplete')),
                invalidDuration: @json(__('app.projects.collection_schedule.invalid_duration')),
                acceptRedistribution: @json(__('app.projects.collection_schedule.accept_redistribution')),
                redistributionPending: @json(__('app.projects.collection_schedule.redistribution_pending')),
                redistributionReady: @json(__('app.projects.collection_schedule.redistribution_ready')),
            };
            const scheduleState = new Map();
            const cashFlowState = new Map();
            const requiredTotal = Number(scheduleEditor?.dataset.requiredTotal ?? 100);

            const replacePlaceholders = (value, replacements) => {
                let result = value;

                for (const [key, replacement] of Object.entries(replacements)) {
                    result = result.replace(`:${key}`, String(replacement));
                }

                return result;
            };

            const normalizeNumber = value => String(value ?? '')
                .trim()
                .replace(/[٠-٩]/g, digit => String('٠١٢٣٤٥٦٧٨٩'.indexOf(digit)))
                .replace(/[۰-۹]/g, digit => String('۰۱۲۳۴۵۶۷۸۹'.indexOf(digit)))
                .replace(/[٬،,\s]/g, '')
                .replace(/٫/g, '.');

            const parseDuration = () => {
                const normalized = normalizeNumber(durationInput?.value);

                if (!/^\d+$/.test(normalized)) {
                    return null;
                }

                const duration = Number(normalized);

                return Number.isSafeInteger(duration) && duration >= 1 ? duration : null;
            };

            const cashFlowFields = @json(\App\Services\ProjectCashFlowService::INPUT_FIELDS);
            const cashFlowLabels = {
                yearZero: @json(__('app.phase_nine.cash_flow.year_zero')),
                year: @json(__('app.phase_nine.cash_flow.year', ['year' => ':year'])),
            };

            const captureCashFlowInputs = () => {
                cashFlowInputBody?.querySelectorAll('[data-cash-flow-input]').forEach(input => {
                    cashFlowState.set(`${input.dataset.year}|${input.dataset.field}`, input.value);
                });
            };

            const renderCashFlowInputs = duration => {
                if (!cashFlowInputBody || !Number.isInteger(duration) || duration < 1) {
                    return;
                }

                captureCashFlowInputs();
                cashFlowInputBody.replaceChildren();

                for (let year = 0; year <= duration; year += 1) {
                    const row = document.createElement('tr');
                    const heading = document.createElement('th');
                    heading.scope = 'row';
                    heading.className = 'text-nowrap';
                    heading.textContent = year === 0
                        ? cashFlowLabels.yearZero
                        : replacePlaceholders(cashFlowLabels.year, { year });
                    row.dataset.cashFlowYear = String(year);
                    row.appendChild(heading);

                    for (const field of cashFlowFields) {
                        const cell = document.createElement('td');
                        const input = document.createElement('input');
                        input.className = 'form-control form-control-sm text-end';
                        input.type = 'text';
                        input.inputMode = 'decimal';
                        input.name = `annual_cash_flow_schedule[${year}][${field}]`;
                        input.value = cashFlowState.get(`${year}|${field}`) ?? '0';
                        input.dataset.cashFlowInput = '';
                        input.dataset.year = String(year);
                        input.dataset.field = field;
                        cell.appendChild(input);
                        row.appendChild(cell);
                    }

                    cashFlowInputBody.appendChild(row);
                }
            };

            const parsePercentage = value => {
                const normalized = normalizeNumber(value);

                if (normalized === '' || !/^(?:\d+\.?\d*|\.\d+)$/.test(normalized)) {
                    return null;
                }

                const percentage = Number(normalized);

                return Number.isFinite(percentage) ? percentage : null;
            };

            const stateKey = (field, group, year) => `${field}|${group}|${year}`;

            const captureScheduleInputs = () => {
                if (!scheduleEditor) {
                    return;
                }

                scheduleEditor.querySelectorAll('[data-schedule-input], [data-excluded-input]').forEach(input => {
                    const row = input.closest('[data-schedule-row], [data-excluded-group]');

                    if (!row) {
                        return;
                    }

                    scheduleState.set(
                        stateKey(row.dataset.scheduleField, row.dataset.scheduleGroup, Number(input.dataset.year)),
                        input.value,
                    );
                });
            };

            const rowTotal = row => {
                let total = 0;

                for (const input of row.querySelectorAll('[data-schedule-input]')) {
                    const percentage = parsePercentage(input.value);

                    if (percentage === null) {
                        return null;
                    }

                    total += percentage;
                }

                return total;
            };

            const hasRequiredTotal = total => total !== null && Math.abs(total - requiredTotal) < 0.000001;

            const updateRowTotal = row => {
                const total = rowTotal(row);
                const totalBadge = row.querySelector('[data-group-total]');

                if (!totalBadge) {
                    return total;
                }

                totalBadge.textContent = total === null ? unavailable : `${total.toFixed(2)}%`;
                totalBadge.classList.toggle('text-bg-success', hasRequiredTotal(total));
                totalBadge.classList.toggle('text-bg-warning', !hasRequiredTotal(total));

                return total;
            };

            const matchingRow = (field, group) => scheduleEditor?.querySelector(
                `[data-schedule-row][data-schedule-field="${field}"][data-schedule-group="${group}"]`,
            );

            const updateEditorStatus = () => {
                if (!scheduleEditor || !scheduleStatus || !excludedGroups) {
                    return;
                }

                const rowsComplete = [...scheduleEditor.querySelectorAll('[data-schedule-row]')]
                    .every(row => hasRequiredTotal(updateRowTotal(row)));
                const hasExcludedYears = excludedGroups.querySelector('[data-excluded-group]') !== null;
                const isComplete = rowsComplete && !hasExcludedYears;

                scheduleStatus.textContent = isComplete ? labels.complete : labels.incomplete;
                scheduleStatus.classList.toggle('alert-success', isComplete);
                scheduleStatus.classList.toggle('alert-warning', !isComplete);
                excludedAlert?.classList.toggle('d-none', !hasExcludedYears);

                excludedGroups.querySelectorAll('[data-excluded-group]').forEach(groupElement => {
                    const row = matchingRow(groupElement.dataset.scheduleField, groupElement.dataset.scheduleGroup);
                    const ready = row !== null && hasRequiredTotal(rowTotal(row));
                    const button = groupElement.querySelector('[data-accept-redistribution]');
                    const hint = groupElement.querySelector('[data-redistribution-hint]');

                    if (button) {
                        button.disabled = !ready;
                    }

                    if (hint) {
                        hint.textContent = ready ? labels.redistributionReady : labels.redistributionPending;
                    }
                });
            };

            const createHeaderCell = (text, className = '') => {
                const cell = document.createElement('th');
                cell.scope = 'col';
                cell.textContent = text;
                cell.className = className;

                return cell;
            };

            const createPercentageCell = (field, group, groupLabel, year) => {
                const cell = document.createElement('td');
                const inputGroup = document.createElement('div');
                const input = document.createElement('input');
                const suffix = document.createElement('span');

                cell.dataset.yearCell = String(year);
                inputGroup.className = 'input-group input-group-sm schedule-percentage-input';
                inputGroup.dir = 'rtl';
                input.className = 'form-control text-end';
                input.dir = 'ltr';
                input.type = 'text';
                input.inputMode = 'decimal';
                input.name = `${field}[${group}][${year}]`;
                input.value = scheduleState.get(stateKey(field, group, year)) ?? '0';
                input.dataset.scheduleInput = '';
                input.dataset.year = String(year);
                input.setAttribute('aria-label', replacePlaceholders(labels.input, { group: groupLabel, year }));
                suffix.className = 'input-group-text';
                suffix.textContent = '%';
                inputGroup.append(input, suffix);
                cell.appendChild(inputGroup);

                return cell;
            };

            const excludedEntries = (field, group, duration) => {
                const entries = [];
                const prefix = `${field}|${group}|`;

                for (const [key, value] of scheduleState.entries()) {
                    if (!key.startsWith(prefix)) {
                        continue;
                    }

                    const year = Number(key.slice(prefix.length));
                    const percentage = parsePercentage(value);

                    if (year > duration && percentage !== null && percentage > 0) {
                        entries.push({ year, value });
                    } else if (year > duration && percentage === 0) {
                        scheduleState.delete(key);
                    }
                }

                return entries.sort((left, right) => left.year - right.year);
            };

            const renderExcludedGroup = (field, group, groupLabel, entries) => {
                const groupElement = document.createElement('div');
                const heading = document.createElement('div');
                const label = document.createElement('strong');
                const button = document.createElement('button');
                const values = document.createElement('div');
                const hint = document.createElement('div');

                groupElement.className = 'excluded-years-group';
                groupElement.dataset.excludedGroup = '';
                groupElement.dataset.scheduleField = field;
                groupElement.dataset.scheduleGroup = group;
                groupElement.dataset.groupLabel = groupLabel;
                heading.className = 'd-flex flex-wrap align-items-center justify-content-between gap-2 mb-2';
                label.textContent = groupLabel;
                button.className = 'btn btn-sm btn-outline-success';
                button.type = 'button';
                button.dataset.acceptRedistribution = '';
                button.textContent = labels.acceptRedistribution;
                values.className = 'd-flex flex-wrap gap-2';
                values.dataset.excludedValues = '';

                for (const entry of entries) {
                    const valueElement = document.createElement('span');
                    const percentageElement = document.createElement('strong');
                    const input = document.createElement('input');

                    valueElement.className = 'excluded-year-value';
                    valueElement.dataset.excludedYear = String(entry.year);
                    valueElement.append(`${replacePlaceholders(labels.year, { year: entry.year })}: `);
                    percentageElement.textContent = `${entry.value}%`;
                    input.type = 'hidden';
                    input.name = `${field}[${group}][${entry.year}]`;
                    input.value = entry.value;
                    input.dataset.excludedInput = '';
                    input.dataset.year = String(entry.year);
                    valueElement.append(percentageElement, input);
                    values.appendChild(valueElement);
                }

                hint.className = 'small mt-2';
                hint.dataset.redistributionHint = '';
                heading.append(label, button);
                groupElement.append(heading, values, hint);
                excludedGroups.appendChild(groupElement);
            };

            const renderSchedule = (duration, captureCurrentInputs = true) => {
                if (!scheduleEditor || !excludedGroups || !Number.isInteger(duration) || duration < 1) {
                    return;
                }

                if (captureCurrentInputs) {
                    captureScheduleInputs();
                }

                for (const table of scheduleEditor.querySelectorAll('[data-schedule-table]')) {
                    const header = table.querySelector('[data-schedule-header]');

                    if (!header) {
                        continue;
                    }

                    header.replaceChildren(createHeaderCell(labels.group, 'text-start'));

                    for (let year = 1; year <= duration; year += 1) {
                        header.appendChild(createHeaderCell(replacePlaceholders(labels.year, { year })));
                    }

                    header.appendChild(createHeaderCell(labels.total));
                }

                excludedGroups.replaceChildren();

                for (const row of scheduleEditor.querySelectorAll('[data-schedule-row]')) {
                    const field = row.dataset.scheduleField;
                    const group = row.dataset.scheduleGroup;
                    const groupLabel = row.dataset.groupLabel;
                    const groupHeading = document.createElement('th');
                    const totalCell = document.createElement('td');
                    const totalBadge = document.createElement('span');

                    groupHeading.scope = 'row';
                    groupHeading.className = 'text-start text-nowrap';
                    groupHeading.textContent = groupLabel;
                    row.replaceChildren(groupHeading);

                    for (let year = 1; year <= duration; year += 1) {
                        const key = stateKey(field, group, year);
                        scheduleState.set(key, scheduleState.get(key) ?? '0');
                        row.appendChild(createPercentageCell(field, group, groupLabel, year));
                    }

                    totalCell.className = 'text-nowrap';
                    totalBadge.className = 'badge text-bg-warning';
                    totalBadge.dataset.groupTotal = '';
                    totalCell.appendChild(totalBadge);
                    row.appendChild(totalCell);

                    const entries = excludedEntries(field, group, duration);

                    if (entries.length > 0) {
                        renderExcludedGroup(field, group, groupLabel, entries);
                    }
                }

                if (durationBadge) {
                    durationBadge.textContent = replacePlaceholders(labels.duration, { count: duration });
                }

                updateEditorStatus();
            };

            if (scheduleEditor && durationInput) {
                captureScheduleInputs();

                const initialDuration = parseDuration();

                if (initialDuration !== null) {
                    renderSchedule(initialDuration);
                    captureCashFlowInputs();
                }

                const handleDurationChange = () => {
                    const duration = parseDuration();

                    if (duration === null) {
                        scheduleStatus.textContent = labels.invalidDuration;
                        scheduleStatus.classList.remove('alert-success');
                        scheduleStatus.classList.add('alert-warning');

                        return;
                    }

                    renderSchedule(duration);
                    renderCashFlowInputs(duration);
                };

                durationInput.addEventListener('input', handleDurationChange);
                durationInput.addEventListener('change', handleDurationChange);

                scheduleEditor.addEventListener('input', event => {
                    if (!event.target.matches('[data-schedule-input]')) {
                        return;
                    }

                    const row = event.target.closest('[data-schedule-row]');

                    if (!row) {
                        return;
                    }

                    scheduleState.set(
                        stateKey(row.dataset.scheduleField, row.dataset.scheduleGroup, Number(event.target.dataset.year)),
                        event.target.value,
                    );
                    updateEditorStatus();
                });

                scheduleEditor.addEventListener('click', event => {
                    const button = event.target.closest('[data-accept-redistribution]');

                    if (!button || button.disabled) {
                        return;
                    }

                    const groupElement = button.closest('[data-excluded-group]');
                    const duration = parseDuration();

                    if (!groupElement || duration === null) {
                        return;
                    }

                    const prefix = `${groupElement.dataset.scheduleField}|${groupElement.dataset.scheduleGroup}|`;

                    for (const key of scheduleState.keys()) {
                        if (key.startsWith(prefix) && Number(key.slice(prefix.length)) > duration) {
                            scheduleState.delete(key);
                        }
                    }

                    renderSchedule(duration, false);
                });
            }

            cashFlowInputBody?.addEventListener('input', event => {
                if (!event.target.matches('[data-cash-flow-input]')) {
                    return;
                }

                cashFlowState.set(`${event.target.dataset.year}|${event.target.dataset.field}`, event.target.value);
            });

            if (!form || !previewButton || !previewPanel || !previewError || !previewResults) {
                return;
            }

            const formatValue = (value, suffix = '') => {
                if (value === null || value === undefined || value === '') {
                    return unavailable;
                }

                const parts = String(value).split('.');
                const sign = parts[0].startsWith('-') ? '-' : '';
                const integer = parts[0].replace('-', '').replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                const fraction = parts[1] ? `.${parts[1]}` : '';

                return `${sign}${integer}${fraction}${suffix}`;
            };

            const setPreviewValue = (id, value, suffix = '') => {
                const element = document.getElementById(id);

                if (element) {
                    element.textContent = formatValue(value, suffix);
                }
            };

            const renderAnnualCollection = (metrics, currency) => {
                if (!previewScheduleWarning || !previewAnnualCollection || !previewAnnualCollectionBody || !previewAnnualCollectionTotal) {
                    return;
                }

                previewAnnualCollectionBody.replaceChildren();

                if (metrics.annual_collection_total === null) {
                    previewScheduleWarning.classList.remove('d-none');
                    previewAnnualCollection.classList.add('d-none');

                    return;
                }

                for (const collection of metrics.annual_collections) {
                    const row = document.createElement('tr');
                    const year = document.createElement('th');
                    const total = document.createElement('td');
                    const cumulative = document.createElement('td');

                    year.scope = 'row';
                    year.textContent = replacePlaceholders(labels.year, { year: collection.year });
                    total.className = 'text-end';
                    total.textContent = formatValue(collection.total, currency);
                    cumulative.className = 'text-end';
                    cumulative.textContent = formatValue(collection.cumulative_total, currency);
                    row.append(year, total, cumulative);
                    previewAnnualCollectionBody.appendChild(row);
                }

                previewAnnualCollectionTotal.textContent = formatValue(metrics.annual_collection_total, currency);
                previewScheduleWarning.classList.add('d-none');
                previewAnnualCollection.classList.remove('d-none');
            };

            previewButton.addEventListener('click', async () => {
                previewPanel.classList.remove('d-none');
                previewError.classList.add('d-none');
                previewError.replaceChildren();
                previewScheduleWarning?.classList.add('d-none');
                previewAnnualCollection?.classList.add('d-none');
                previewResults.classList.add('opacity-50');
                previewButton.disabled = true;
                previewButton.textContent = @json(__('app.projects.preview.loading'));

                try {
                    const response = await fetch(form.dataset.previewUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: new FormData(form),
                    });
                    const payload = await response.json();

                    if (!response.ok) {
                        const messages = Object.values(payload.errors ?? {}).flat();
                        const list = document.createElement('ul');
                        list.className = 'mb-0';

                        for (const message of messages.length > 0 ? messages : [@json(__('app.projects.preview.error'))]) {
                            const item = document.createElement('li');
                            item.textContent = message;
                            list.appendChild(item);
                        }

                        previewError.appendChild(list);
                        previewError.classList.remove('d-none');

                        return;
                    }

                    const metrics = payload.metrics;
                    const currency = payload.currency_code ? ` ${payload.currency_code}` : '';
                    setPreviewValue('preview-revenue', metrics.revenues.total_with_hotel, currency);
                    setPreviewValue('preview-cost', metrics.totals.total_cost, currency);
                    setPreviewValue('preview-profit', metrics.totals.estimated_profit, currency);
                    setPreviewValue('preview-margin', metrics.indicators.profit_margin_percent, '%');
                    renderAnnualCollection(metrics, currency);
                } catch (error) {
                    previewError.textContent = @json(__('app.projects.preview.error'));
                    previewError.classList.remove('d-none');
                } finally {
                    previewResults.classList.remove('opacity-50');
                    previewButton.disabled = false;
                    previewButton.textContent = @json(__('app.projects.preview.button'));
                }
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('project-form');

            if (!form) {
                return;
            }

            const stepButtons = [...form.querySelectorAll('[data-form-step-target]')];
            const stepPanels = [...form.querySelectorAll('[data-form-step]')];
            const previousButton = document.getElementById('form-step-previous');
            const nextButton = document.getElementById('form-step-next');
            const previewButton = document.getElementById('preview-button');
            const hotelSelect = document.getElementById('has_hotel');
            const mobileTitle = document.getElementById('mobile-step-title');
            const mobileCount = document.getElementById('mobile-step-count');
            const mobileProgress = document.getElementById('mobile-step-progress');
            let currentStep = 'basic';

            const visibleButtons = () => stepButtons.filter(button => !button.hidden);

            const refreshStepStates = () => {
                for (const button of stepButtons) {
                    const panels = stepPanels.filter(panel => panel.dataset.formStep === button.dataset.formStepTarget);
                    const controls = panels.flatMap(panel => [...panel.querySelectorAll('input:not([type="hidden"]), select, textarea')]);
                    const hasError = panels.some(panel => panel.querySelector('.is-invalid, .text-danger'));
                    const requiredControls = controls.filter(control => control.required);
                    const completionControls = requiredControls.length > 0 ? requiredControls : controls;
                    let hasValues = completionControls.length > 0 && completionControls.every(control => String(control.value).trim() !== '');

                    if (button.dataset.formStepTarget === 'schedule') {
                        hasValues = document.getElementById('collection-schedule-status')?.classList.contains('alert-success') ?? false;
                    }

                    if (button.dataset.formStepTarget === 'review') {
                        hasValues = !document.getElementById('preview-panel')?.classList.contains('d-none');
                    }

                    button.classList.toggle('has-error', hasError);
                    button.classList.toggle('is-complete', hasValues && !hasError);
                    button.querySelector('.form-step-check')?.classList.toggle('opacity-0', !hasValues || hasError);
                }
            };

            const updateHotelStep = () => {
                const hotelButton = stepButtons.find(button => button.dataset.formStepTarget === 'hotel');
                const hasHotel = hotelSelect?.value === '1';

                if (hotelButton) {
                    hotelButton.hidden = !hasHotel;
                }

                if (!hasHotel && currentStep === 'hotel') {
                    showStep('advanced');
                }

                visibleButtons().forEach((button, index) => {
                    const number = button.querySelector('.form-step-number');

                    if (number) {
                        number.textContent = String(index + 1);
                    }
                });
            };

            const showStep = (step, focusHeading = false) => {
                const availableButtons = visibleButtons();
                const requestedButton = availableButtons.find(button => button.dataset.formStepTarget === step) ?? availableButtons[0];

                if (!requestedButton) {
                    return;
                }

                currentStep = requestedButton.dataset.formStepTarget;

                for (const panel of stepPanels) {
                    panel.hidden = panel.dataset.formStep !== currentStep;
                }

                for (const button of stepButtons) {
                    const isActive = button === requestedButton;
                    button.classList.toggle('active', isActive);
                    isActive ? button.setAttribute('aria-current', 'step') : button.removeAttribute('aria-current');
                }

                const index = availableButtons.indexOf(requestedButton);
                previousButton.disabled = index <= 0;
                nextButton.disabled = index >= availableButtons.length - 1;
                mobileTitle.textContent = requestedButton.querySelector('span:nth-child(2)')?.textContent ?? '';
                mobileCount.textContent = @json(__('app.projects.workflow.step', ['current' => ':current', 'total' => ':total']))
                    .replace(':current', String(index + 1))
                    .replace(':total', String(availableButtons.length));
                mobileProgress.style.width = `${((index + 1) / availableButtons.length) * 100}%`;

                if (focusHeading) {
                    const heading = stepPanels.find(panel => panel.dataset.formStep === currentStep)?.querySelector('h2, h3');
                    heading?.setAttribute('tabindex', '-1');
                    heading?.focus({ preventScroll: true });
                    heading?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            };

            stepButtons.forEach(button => {
                button.addEventListener('click', () => showStep(button.dataset.formStepTarget, true));
            });

            previousButton?.addEventListener('click', () => {
                const buttons = visibleButtons();
                const index = buttons.findIndex(button => button.dataset.formStepTarget === currentStep);
                showStep(buttons[Math.max(0, index - 1)]?.dataset.formStepTarget, true);
            });

            nextButton?.addEventListener('click', () => {
                const buttons = visibleButtons();
                const index = buttons.findIndex(button => button.dataset.formStepTarget === currentStep);
                showStep(buttons[Math.min(buttons.length - 1, index + 1)]?.dataset.formStepTarget, true);
            });

            previewButton?.addEventListener('click', () => showStep('review', true));
            hotelSelect?.addEventListener('change', () => {
                updateHotelStep();
                refreshStepStates();
            });

            form.addEventListener('input', refreshStepStates);
            form.addEventListener('invalid', event => {
                const panel = event.target.closest('[data-form-step]');

                if (panel) {
                    showStep(panel.dataset.formStep);
                }
            }, true);

            form.querySelectorAll('[data-error-step]').forEach(link => {
                link.addEventListener('click', event => {
                    event.preventDefault();
                    showStep(link.dataset.errorStep);
                    requestAnimationFrame(() => {
                        const target = document.querySelector(link.getAttribute('href'));
                        target?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        target?.focus?.({ preventScroll: true });
                    });
                });
            });

            updateHotelStep();
            refreshStepStates();

            const firstErrorStep = stepButtons.find(button => button.classList.contains('has-error'))?.dataset.formStepTarget;
            showStep(firstErrorStep ?? 'basic');

            if (firstErrorStep) {
                document.getElementById('project-validation-summary')?.focus();
            }
        });
    </script>
@endpush
