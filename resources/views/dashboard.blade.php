@extends('layout.app')

@section('title', 'Dashboard')

@section('content')
<div class="mb-6">
    <div class="alert alert-primary d-flex align-items-center p-4">
        <i class="bi bi-person-circle fs-2x me-3"></i>
        <div class="d-flex flex-column">
            <h4 class="mb-1">Selamat datang, {{ Auth::user()->name }}!</h4>
            <span>Semoga harimu menyenangkan 😊</span>
        </div>
    </div>
</div>
<div class="row g-6 g-xl-9 mb-6">
    <div class="col-md-4">
        <div class="card card-flush h-md-100">
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="d-flex flex-stack">
                    <span class="fs-2hx fw-bold text-primary">120</span>
                    <i class="bi bi-person-check fs-1 text-success"></i>
                </div>
                <div class="fw-semibold fs-6 text-gray-400">Absensi Hari Ini</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-flush h-md-100">
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="d-flex flex-stack">
                    <span class="fs-2hx fw-bold text-primary">45</span>
                    <i class="bi bi-people fs-1 text-info"></i>
                </div>
                <div class="fw-semibold fs-6 text-gray-400">Total Karyawan</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-flush h-md-100">
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="d-flex flex-stack">
                    <span class="fs-2hx fw-bold text-primary">5</span>
                    <i class="bi bi-person-dash fs-1 text-danger"></i>
                </div>
                <div class="fw-semibold fs-6 text-gray-400">Izin/Sakit Hari Ini</div>
            </div>
        </div>
    </div>
</div>
<div class="card mb-6">
    <div class="card-header">
        <h3 class="card-title">Grafik Absensi Bulanan</h3>
    </div>
    <div class="card-body">
        <div id="absensiChart" style="height: 300px;"></div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('absensiChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
            datasets: [{
                label: 'Absensi',
                data: [100, 110, 120, 90, 130, 125, 140, 135, 120, 115, 130, 140],
                backgroundColor: 'rgba(54, 162, 235, 0.7)'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            }
        }
    });
});


</script>
@endsection
