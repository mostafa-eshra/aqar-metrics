@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('project-form');
            const previewButton = document.getElementById('preview-button');
            const previewPanel = document.getElementById('preview-panel');
            const previewError = document.getElementById('preview-error');
            const previewResults = document.getElementById('preview-results');
            const unavailable = @json(__('app.projects.not_available'));

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

            previewButton.addEventListener('click', async () => {
                previewPanel.classList.remove('d-none');
                previewError.classList.add('d-none');
                previewError.replaceChildren();
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
@endpush
