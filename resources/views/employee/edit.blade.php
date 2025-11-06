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
                    <a href="{{ route('employee.index') }}" class="btn btn-sm btn-primary">List
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
            <form id="kt_account_profile_details_form" class="form" method="POST"
                action="{{ route('employee.update', request()->segment(1)) }}">
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
                        <label class="col-lg-4 col-form-label required fw-bold fs-7">Employee Id</label>
                        <div class="col-lg-8 fv-row">
                            <input type="text" name="kar_id"
                                class="form-control form-control-sm form-control-solid pe-none" placeholder="123"
                                value="{{ $data->kar_id }}" readonly />
                            @error('company')
                                <small class="text-danger">{{ '* ' . $message }}</small>
                            @enderror
                        </div>
                    </div>
                    <div class="row mb-6">
                        <label class="col-lg-4 col-form-label required fw-bold fs-7">Employee Name</label>
                        <div class="col-lg-8 fv-row">
                            <input type="text" name="nama"
                                class="form-control form-control-sm form-control-solid pe-none" placeholder="Udin"
                                value="{{ $data->nama }}" readonly />
                            @error('msn_type')
                                <small class="text-danger">{{ '* ' . $message }}</small>
                            @enderror
                        </div>
                    </div>
                    <div class="row mb-6">
                        <label class="col-lg-4 col-form-label required fw-bold fs-7">Machine Active</label>
                        <div class="col-lg-8 fv-row">
                            <select name="machine[]" class="form-select form-select-sm form-select-solid pe-none"
                                data-control="select2" data-placeholder="Select an option" data-allow-clear="true"
                                multiple="multiple" readonly>
                                <option></option>
                                @foreach ($machine as $m)
                                    <option value="{{ $m->cloud_id }}"
                                        {{ $data->cloud_id->contains($m->cloud_id) ? 'selected' : '' }}>{{ $m->msn_name }}
                                    </option>
                                @endforeach
                            </select>

                            {{-- <input type="text" name="cloud_id" class="form-control form-control-sm form-control"
                                placeholder="" value="" /> --}}
                            @error('machine')
                                <small class="text-danger">{{ '* ' . $message }}</small>
                            @enderror
                        </div>
                    </div>
                    <div class="row mb-6">
                        <label class="col-lg-4 col-form-label fw-bold fs-7">Add to Machine</label>
                        <div class="col-lg-8 fv-row">
                            <select name="addmachine[]" class="form-select form-select-sm" data-control="select2"
                                data-placeholder="Select an option" data-allow-clear="true" multiple="multiple">
                                <option></option>
                                @foreach ($machine as $m)
                                    @if (!$data->cloud_id->contains($m->cloud_id))
                                        <option value="{{ $m->cloud_id }}">{{ $m->msn_name }}</option>
                                    @endif
                                @endforeach
                            </select>

                            {{-- <input type="text" name="cloud_id" class="form-control form-control-sm form-control"
                                placeholder="" value="" /> --}}
                            @error('addmachine')
                                <small class="text-danger">{{ '* ' . $message }}</small><br>
                            @enderror
                            @error('addmachine.*')
                                <small class="text-danger">{{ '* ' . $message }}</small>
                            @enderror
                        </div>
                    </div>
                    <div class="row mb-6">
                        <label class="col-lg-4 col-form-label fw-bold fs-7">Remove From Machine</label>
                        <div class="col-lg-8 fv-row">
                            <select name="removemachine[]" class="form-select form-select-sm" data-control="select2"
                                data-placeholder="Select an option" data-allow-clear="true" multiple="multiple">
                                <option></option>
                                @foreach ($machine as $m)
                                    @if ($data->cloud_id->contains($m->cloud_id))
                                        <option value="{{ $m->cloud_id }}">{{ $m->msn_name }}</option>
                                    @endif
                                @endforeach
                                <option value="123">tes</option>
                            </select>

                            {{-- <input type="text" name="cloud_id" class="form-control form-control-sm form-control"
                                placeholder="" value="" /> --}}
                            @error('removemachine')
                                <small class="text-danger">{{ '* ' . $message }}</small><br>
                            @enderror
                            @error('removemachine.*')
                                <small class="text-danger">{{ '* ' . $message }}</small>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end py-6 px-9">
                    <button type="reset" class="btn btn-sm btn-light btn-active-light-primary me-2">Discard</button>
                    <button type="button" onclick="alerts2(this)" class="btn btn-sm btn-primary"
                        id="kt_account_profile_details_submit">Save
                        Changes</button>
                </div>
            </form>
        </div>
    </div>

@endsection
