@extends('layout.app')

@section('title', 'Machine')

@section('content')
    @php
        $segmentsfn = function () {
            $seg = request()->segments();
            if (end($seg) == 'edit') {
                $seg = collect($seg)->reverse()->values()->forget(1);
                $seg = $seg->reverse()->values()->toArray();
            }
            return $seg;
        };

        $segments = $segmentsfn();
    @endphp

    <div class="card">
        {{-- <div class="card-header border-0 cursor-pointer" role="button" data-bs-toggle="collapse" data-bs-target="#kt_account_profile_details" aria-expanded="true" aria-controls="kt_account_profile_details"> --}}
        <div class="card-header border-0">
            <div class="card-title">
                <div class="card-title m-0">
                    <h3 class="fw-bolder m-0">{{ ucwords(end($segments)) . ' ' . ucwords($segments[1]) }}
                    </h3>
                </div>
            </div>
            <div class="card-toolbar">
                <div class="d-flex justify-content-end" data-kt-customer-table-toolbar="base">
                    <a href="{{ route('machine.index') }}" class="btn btn-sm btn-primary">List
                        {{ ucwords($segments[1]) }}</a>
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
        <div id="kt_account_profile_details" class="collapse show">
            <form id="kt_account_profile_details_form" class="form" method="POST" action="{{ route('machine.update', $data->msn_id) }}">
                @csrf
                @method('PUT')
                <div class="card-body border-top p-9">
                    {{-- <div class="row mb-6">
                        <label class="col-lg-4 col-form-label fw-bold fs-6">Avatar</label>
                        <div class="col-lg-8">
                            <div class="image-input image-input-outline" data-kt-image-input="true"
                                style="background-image: url(assets/media/avatars/blank.png)">
                                <div class="image-input-wrapper w-125px h-125px"
                                    style="background-image: url(assets/media/avatars/150-26.jpg)"></div>
                                <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                    data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change avatar">
                                    <i class="bi bi-pencil-fill fs-7"></i>
                                    <input type="file" name="avatar" accept=".png, .jpg, .jpeg" />
                                    <input type="hidden" name="avatar_remove" />
                                </label>
                                <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                    data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="Cancel avatar">
                                    <i class="bi bi-x fs-2"></i>
                                </span>
                                <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                    data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="Remove avatar">
                                    <i class="bi bi-x fs-2"></i>
                                </span>
                            </div>
                            <div class="form-text">Allowed file types: png, jpg, jpeg.</div>
                        </div>
                    </div> --}}
                    <div class="row mb-6">
                        <label class="col-lg-4 col-form-label required fw-bold fs-7">Company</label>
                        <div class="col-lg-8 fv-row">
                            <input type="text" name="company" class="form-control form-control-sm form-control"
                                placeholder="PT KAHAPTEX" value="{{ old('msn_type') ? old('company') : $data->company }}" />
                            @error('company')
                                <small class="text-danger">{{ '* ' . $message }}</small>
                            @enderror
                        </div>
                    </div>
                    <div class="row mb-6">
                        <label class="col-lg-4 col-form-label required fw-bold fs-7">Machine Type</label>
                        <div class="col-lg-8 fv-row">
                            <input type="text" name="msn_type" class="form-control form-control-sm form-control"
                                placeholder="Revo W-202BNC" value="{{ old('msn_type') ? old('msn_type') : $data->msn_type }}" />
                            @error('msn_type')
                                <small class="text-danger">{{ '* ' . $message }}</small>
                            @enderror
                        </div>
                    </div>
                    <div class="row mb-6">
                        <label class="col-lg-4 col-form-label required fw-bold fs-7">Cloud Id</label>
                        <div class="col-lg-8 fv-row">
                            <input type="text" name="cloud_id" class="form-control form-control-sm form-control"
                                placeholder="C263388123302938" value="{{ old('cloud_id') ? old('cloud_id') : $data->cloud_id }}" />
                            @error('cloud_id')
                                <small class="text-danger">{{ '* ' . $message }}</small>
                            @enderror
                        </div>
                    </div>
                    <div class="row mb-6">
                        <label class="col-lg-4 col-form-label required fw-bold fs-7">Machine Name</label>
                        <div class="col-lg-8 fv-row">
                            <input type="text" name="msn_name" class="form-control form-control-sm form-control"
                                placeholder="HRD" value="{{ old('msn_name') ? old('msn_name') : $data->msn_name }}" />
                            @error('msn_name')
                                <small class="text-danger">{{ '* ' . $message }}</small>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end py-6 px-9">
                    <button type="reset" class="btn btn-sm btn-light btn-active-light-primary me-2">Discard</button>
                    <button type="button" onclick="alerts2(this)" class="btn btn-sm btn-primary" id="kt_account_profile_details_submit">Save
                        Changes</button>
                </div>
            </form>
        </div>
    </div>

@endsection
