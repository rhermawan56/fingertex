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
                <div class="d-flex justify-content-end mx-1" data-kt-customer-table-toolbar="base">
                    <button type="button" onclick="resetTime(this)" class="btn btn-sm btn-primary text-center">
                        <span title="Reset Time" class="btn btn-sm svg-icon svg-icon-primary svg-icon-1hx p-0 m-0">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none">
                                <path
                                    d="M14.5 20.7259C14.6 21.2259 14.2 21.826 13.7 21.926C13.2 22.026 12.6 22.0259 12.1 22.0259C9.5 22.0259 6.9 21.0259 5 19.1259C1.4 15.5259 1.09998 9.72592 4.29998 5.82592L5.70001 7.22595C3.30001 10.3259 3.59999 14.8259 6.39999 17.7259C8.19999 19.5259 10.8 20.426 13.4 19.926C13.9 19.826 14.4 20.2259 14.5 20.7259ZM18.4 16.8259L19.8 18.2259C22.9 14.3259 22.7 8.52593 19 4.92593C16.7 2.62593 13.5 1.62594 10.3 2.12594C9.79998 2.22594 9.4 2.72595 9.5 3.22595C9.6 3.72595 10.1 4.12594 10.6 4.02594C13.1 3.62594 15.7 4.42595 17.6 6.22595C20.5 9.22595 20.7 13.7259 18.4 16.8259Z"
                                    fill="black" />
                                <path opacity="0.3"
                                    d="M2 3.62592H7C7.6 3.62592 8 4.02592 8 4.62592V9.62589L2 3.62592ZM16 14.4259V19.4259C16 20.0259 16.4 20.4259 17 20.4259H22L16 14.4259Z"
                                    fill="black" />
                            </svg>
                        </span>
                    </button>
                </div>
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
        <div class="card-body table-block pt-0">
            <table class="table align-middle table-row-dashed fs-6 gy-5"
                id="{{ request()->segment(2) }}table">
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
