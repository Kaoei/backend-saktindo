@php
    $themeBase = 'DashboardKit-main';
@endphp

<script src="{{ asset($themeBase.'/js/vendor-all.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="{{ asset($themeBase.'/js/bootstrap.min.js') }}"></script>
<script src="{{ asset($themeBase.'/js/feather.min.js') }}"></script>
<script src="{{ asset($themeBase.'/js/pcoded.min.js') }}"></script>

<script>
    $(document).ready(function() {
        function initSelect2(elements) {
            $(elements || 'select:not(.no-select2)').each(function() {
                // If it is already initialized, skip
                if ($(this).hasClass("select2-hidden-accessible")) return;
                
                $(this).select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    dropdownParent: $(this).closest('.modal').length ? $(this).closest('.modal') : $(document).find('body')
                });
            });
        }

        // Initialize on load
        initSelect2();

        // Listen for bootstrap modals / dynamic elements
        $(document).on('shown.bs.modal', '.modal', function() {
            initSelect2($(this).find('select:not(.no-select2)'));
        });

        // Expose initSelect2 globally
        window.initSelect2 = initSelect2;
    });
</script>
