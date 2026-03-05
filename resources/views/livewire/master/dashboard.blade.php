<div class="page-wrapper">
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        Master Dashboard
                    </h2>
                    <div class="text-muted mt-1">Ringkasan data semua business</div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="row row-deck row-cards">

                {{-- Card: Total Business --}}
                <div class="col-sm-6 col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="subheader">Total Business</div>
                            </div>
                            <div class="h1 mb-3">{{ $totalBusiness }}</div>
                            <div class="d-flex mb-2">
                                <div class="text-muted">Business terdaftar dalam sistem</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card: Total Owner --}}
                <div class="col-sm-6 col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="subheader">Total Owner</div>
                            </div>
                            <div class="h1 mb-3">{{ $totalOwner }}</div>
                            <div class="d-flex mb-2">
                                <div class="text-muted">Owner terdaftar dalam sistem</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card: Shortcut --}}
                <div class="col-sm-6 col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="subheader">Kelola</div>
                            </div>
                            <div class="mt-3">
                                <a href="/master/business" class="btn btn-primary w-100">
                                    <span class="material-symbols-outlined me-2"
                                        style="font-size: 18px; vertical-align: middle;">business</span>
                                    Daftar Business & Owner
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
