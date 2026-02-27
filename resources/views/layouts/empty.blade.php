<!doctype html>

<html lang="en" data-bs-theme-primary="teal">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>{{ $title ?? '' }} &mdash; {{ env('APP_NAME') }} | {{ env('APP_TITLE') }}</title>
    <link rel="icon" href="{{ asset('assets/img/logo/logo.png') }}">

    <!-- BEGIN PAGE LEVEL STYLES -->
    <link href="{{ asset('assets/libs/jsvectormap/dist/jsvectormap.css') }}" rel="stylesheet" />
    <!-- END PAGE LEVEL STYLES -->

    <!-- BEGIN GLOBAL MANDATORY STYLES -->
    <link href="{{ asset('assets/css/tabler.css') }}" rel="stylesheet" />
    <!-- END GLOBAL MANDATORY STYLES -->

    <!-- BEGIN PLUGINS STYLES -->
    <link href="{{ asset('assets/css/tabler-vendors.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/tabler-themes.css') }}" rel="stylesheet" />

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.23.0/sweetalert2.min.css"
        integrity="sha512-Ivy7sPrd6LPp20adiK3al16GBelPtqswhJnyXuha3kGtmQ1G2qWpjuipfVDaZUwH26b3RDe8x707asEpvxl7iA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="{{ asset('assets/libs/tom-select/dist/css/tom-select.bootstrap5.min.css') }}" rel="stylesheet" />

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />

    <!-- END PLUGINS STYLES -->

    <!-- BEGIN CUSTOM FONT -->
    <style>
        @import url("https://rsms.me/inter/inter.css");

        .material-symbols-outlined {
            font-size: 20px;
        }

        .badge .material-symbols-outlined {
            font-size: unset;
        }

        .nav-icon {
            margin-inline-end: 0.5rem;
            color: inherit;
        }

        .ts-control {
            padding: 0.5625rem 3rem 0.5625rem 1rem !important;
            display: flex;
            flex-wrap: nowrap;
        }

        .ts-control>div {
            white-space: nowrap;
        }

        .ts-control>input {
            min-width: unset !important;
        }

        .ts-dropdown,
        .ts-dropdown.form-control,
        .ts-dropdown.form-select {
            z-index: 9999999999;
            background: #fff;
        }

        .table tr td {
            vertical-align: middle;
        }
    </style>
    <!-- END CUSTOM FONT -->

    @yield('link')
</head>

<body>
    <div class="page">
        <div class="page-wrapper">
            <!-- BEGIN PAGE BODY -->
            <main id="content" class="page-body">
                <div class="container-xl">
                    {{ $slot }}
                </div>
            </main>
            <!-- END PAGE BODY -->
        </div>
    </div>

    <!-- BEGIN PAGE LIBRARIES -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"
        integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.23.0/sweetalert2.all.min.js"
        integrity="sha512-J+4Nt/+nieSNJjQGCPb8jKf5/wv31QiQM10bOotEHUKc9tB1Pn0gXQS6XXPtDoQhHHao5poTnSByMInzafUqzA=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="{{ asset('assets/libs/tom-select/dist/js/tom-select.complete.min.js') }}"></script>
    <script src="{{ asset('assets/libs/litepicker/dist/litepicker.js') }}"></script>
    <!-- END PAGE LIBRARIES -->

    <!-- BEGIN GLOBAL MANDATORY SCRIPTS -->
    <script src="{{ asset('assets/js/tabler.min.js') }}" defer></script>
    <!-- END GLOBAL MANDATORY SCRIPTS -->

    <script>
        const Select = {};
        const dateLitePicker = {};
        const Toast = Swal.mixin({
            toast: true,
            position: "top-end",
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            }
        });

        window.addEventListener('switch-tab', (event) => {
            var tabId = event.detail.tabId;

            $('.nav-tabs a[href="#' + tabId + '"]').tab('show');
        });

        window.addEventListener('show-modal', (event) => {
            var modalId = event.detail.modalId;
            $('#' + modalId).modal('show');
        });

        $(document).on('shown.bs.modal', '.modal', function() {
            setTimeout(() => {
                initTomSelect();
            }, 200);
        });

        $(document).on('hidden.bs.modal', '.modal', function() {
            $(this).find('.tom-select').each(function() {
                if (this.tomselect) {
                    this.tomselect.destroy();
                }
            });
        });

        window.addEventListener('hide-modal', (event) => {
            var modalId = event.detail.modalId;

            $('#' + modalId).find('.tom-select').each(function() {
                const selectId = $(this).attr('id');
                if (Select[selectId]) {
                    Select[selectId].destroy();
                    delete Select[selectId];
                }
            });

            $('#' + modalId).modal('hide');
        });

        window.addEventListener('alert', (event) => {
            Toast.fire({
                icon: event.detail.type,
                title: event.detail.message,
            });
        });

        window.addEventListener('redirect', (event) => {
            setTimeout(() => {
                window.location.href = event.detail.url;
            }, event.detail.timeout || 0);
        });

        document.addEventListener('livewire:initialized', (e) => {
            Livewire.on('confirm-delete', (event) => {
                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Data yang dihapus tidak dapat dikembalikan!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Livewire.dispatch('delete-confirmed', {
                            id: event.id
                        });
                    }
                });
            });

            Livewire.hook('morph.updated', () => {
                setTimeout(() => {
                    initTomSelect();
                }, 100);
            });

            initTomSelect();
        });

        window.addEventListener('open-receipt', (event) => {
            window.open(event.detail.url, '_blank');
        });

        document.addEventListener('DOMContentLoaded', function(e) {
            $('input').on('focus', function() {
                $(this).select();
            });

            initTomSelect();
            initLitepicker();
        });

        function initSingleTomSelect(el) {
            const selectId = el.getAttribute('id');
            if (!selectId) {
                return;
            }

            let initialValue = el.value;
            if (Select[selectId]) {
                try {
                    initialValue = Select[selectId].getValue() || el.value;
                    Select[selectId].destroy();
                } catch (e) {}
                delete Select[selectId];
            }

            try {
                Select[selectId] = new TomSelect(el, {
                    copyClassesToDropdown: false,
                    dropdownParent: "body",
                    controlInput: "<input>",
                    render: {
                        item: function(data, escape) {
                            if (data.customProperties) {
                                return '<div><span class="dropdown-item-indicator">' + data
                                    .customProperties + "</span>" + escape(data.text) + "</div>";
                            }

                            if (el.classList.contains('select-icon')) {
                                return '<div class="d-flex align-items-center gap-2">' +
                                    '<span class="material-symbols-outlined">' + escape(data.value) +
                                    '</span> ' +
                                    '<span>' + escape(data.text) + '</span>' +
                                    '</div>';
                            }

                            return "<div>" + escape(data.text) + "</div>";
                        },
                        option: function(data, escape) {
                            if (data.customProperties) {
                                return '<div><span class="dropdown-item-indicator">' + data
                                    .customProperties + "</span>" + escape(data.text) + "</div>";
                            }

                            if (el.classList.contains('select-icon')) {
                                return '<div class="d-flex align-items-center gap-2">' +
                                    '<span class="material-symbols-outlined">' + escape(data.value) +
                                    '</span> ' +
                                    '<span>' + escape(data.text) + '</span>' +
                                    '</div>';
                            }

                            return "<div>" + escape(data.text) + "</div>";
                        },
                    },
                    onChange: function(value) {
                        el.value = value;
                        el.dispatchEvent(new Event('change', {
                            bubbles: true
                        }));
                    }
                });

                if (initialValue) {
                    Select[selectId].setValue(initialValue);
                }
            } catch (e) {}
        }

        function initLitepicker() {
            document.querySelectorAll('.litepicker').forEach((el) => {
                const selectId = el.getAttribute('id');
                if (!selectId) {
                    return;
                }

                dateLitePicker[selectId] = new Litepicker({
                    element: el,
                    buttonText: {
                        previousMonth: `<!-- Download SVG icon from http://tabler.io/icons/icon/chevron-left -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" class="icon icon-1"><path d="M15 6l-6 6l6 6" /></svg>`,
                        nextMonth: `<!-- Download SVG icon from http://tabler.io/icons/icon/chevron-right -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" class="icon icon-1"><path d="M9 6l6 6l-6 6" /></svg>`,
                    },
                });
            });
        }

        function initTomSelect() {
            document.querySelectorAll('.tom-select').forEach(initSingleTomSelect);
        }
    </script>

    @yield('script')

</body>

</html>
