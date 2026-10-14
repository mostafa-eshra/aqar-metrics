@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const detailTab = document.getElementById('project-calculation-tab');
            const searchInput = document.getElementById('calculation-search');
            const typeFilter = document.getElementById('calculation-type-filter');
            const detailRows = [...document.querySelectorAll('[data-calculation-row]')];
            const emptyState = document.getElementById('calculation-empty-state');

            document.querySelectorAll('[data-metric-target]').forEach(trigger => {
                trigger.addEventListener('click', () => {
                    if (!detailTab || !window.bootstrap?.Tab) {
                        return;
                    }

                    window.bootstrap.Tab.getOrCreateInstance(detailTab).show();

                    window.requestAnimationFrame(() => {
                        const target = document.getElementById(trigger.dataset.metricTarget);

                        if (!target) {
                            return;
                        }

                        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        target.classList.add('result-row-highlight');
                        window.setTimeout(() => target.classList.remove('result-row-highlight'), 1800);
                    });
                });
            });

            const filterRows = () => {
                const search = String(searchInput?.value ?? '').trim().toLocaleLowerCase('ar');
                const type = typeFilter?.value ?? '';
                let visibleCount = 0;

                detailRows.forEach(row => {
                    const matchesSearch = search === '' || row.dataset.calculationSearch.includes(search);
                    const matchesType = type === '' || row.dataset.calculationType === type;
                    const isVisible = matchesSearch && matchesType;

                    row.classList.toggle('d-none', !isVisible);
                    visibleCount += isVisible ? 1 : 0;
                });

                emptyState?.classList.toggle('d-none', visibleCount !== 0);
            };

            searchInput?.addEventListener('input', filterRows);
            typeFilter?.addEventListener('change', filterRows);

            document.querySelectorAll('.js-confirm-archive').forEach(form => {
                form.addEventListener('submit', event => {
                    if (!window.confirm(form.dataset.confirm)) {
                        event.preventDefault();
                    }
                });
            });
        });
    </script>
@endpush
