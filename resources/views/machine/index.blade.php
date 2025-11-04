@extends('layout.app')

@section('title', 'Machine')

@section('content')

    <div class="card">
        <div class="card-header border-0">
            <div class="card-title">
                <div class="card-title m-0">
                    <h3 class="fw-bolder m-0">{{ 'List ' . ucwords(request()->segment(2)) }}
                    </h3>
                </div>
            </div>
            <div class="card-toolbar">
                <div class="d-flex justify-content-end" data-kt-customer-table-toolbar="base">
                    <a href="{{ route('machine.create') }}" class="btn btn-sm btn-primary">Add
                        {{ ucwords(request()->segment(2)) }}</a>
                </div>
                <div class="d-flex justify-content-end align-items-center d-none" data-kt-customer-table-toolbar="selected">
                    <div class="fw-bolder me-5">
                        <span class="me-2" data-kt-customer-table-select="selected_count"></span>Selected
                    </div>
                    <button type="button" class="btn btn-danger" data-kt-customer-table-select="delete_selected">Delete
                        Selected</button>
                </div>
            </div>
        </div>
        <div class="card-body pt-0">
            <table class="table align-middle table-row-dashed fs-6 gy-5" id="{{ request()->segment(2) }}table">
                <thead>
                    <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                        {{-- <th class="w-10px pe-2">
                            <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                <input class="form-check-input" type="checkbox" data-kt-check="true"
                                    data-kt-check-target="#kt_customers_table .form-check-input" value="1" />
                            </div>
                        </th> --}}
                        <th>#</th>
                        <th>Cloud Id</th>
                        <th>Machine Type</th>
                        <th>Machine Name</th>
                        <th>Option</th>
                    </tr>
                </thead>
                <tbody class="fw-bold text-gray-600">
                </tbody>
            </table>
        </div>
    </div>

@endsection
