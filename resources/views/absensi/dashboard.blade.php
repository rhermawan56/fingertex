@extends('layout.app') {{-- ganti sesuai layout lo --}}

@section('content')
<div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-title">
            <h3 class="fw-bold mb-0">History Absensi</h3>
        </div>
    </div>

    <div class="card-body pt-0">
        <div class="table-responsive">
            <table id="logtable" class="table align-middle table-row-dashed fs-6 gy-5">
                <thead>
                    <tr class="text-start text-muted fw-bolder fs-7 text-uppercase gs-0">
                        <th class="min-w-50px">#</th>
                        <th class="min-w-100px">Tanggal</th>
                        <th class="min-w-100px">Jam</th>
                        <th class="min-w-100px">Status</th>
                        <th class="min-w-150px">Karyawan</th>
                        <th class="min-w-100px">Lokasi</th>
                        <th class="min-w-100px">Company</th>
                        <th class="min-w-100px">Validasi</th>
                        <th class="min-w-100px">Action</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 fw-semibold">
                   
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection


@push('scripts')